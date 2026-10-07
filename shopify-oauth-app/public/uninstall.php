<?php

declare(strict_types=1);

use App\Database\Connection;
use App\OAuth\AppUninstaller;
use App\Security\Csrf;
use App\Security\TokenCipher;
use App\Shopify\ShopDomainValidator;
use App\Stores\StoreRepository;
use App\Webhooks\WebhookRepository;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo 'Invalid or missing request verification token.';
    exit;
}

$shopDomain = isset($_POST['shop']) ? strtolower(trim((string) $_POST['shop'])) : '';

if ($shopDomain === '' || !ShopDomainValidator::isValid($shopDomain)) {
    http_response_code(400);
    echo 'Invalid shop domain.';
    exit;
}

$pdo = Connection::fromConfig($config);
$cipher = new TokenCipher($config['security']['encryption_key']);
$storeRepository = new StoreRepository($pdo, $cipher);
$webhookRepository = new WebhookRepository($pdo);

$accessToken = $storeRepository->findActiveAccessToken($shopDomain);
if ($accessToken === null) {
    header('Location: ' . $config['app']['url'] . '/dashboard.php?shop=' . urlencode($shopDomain));
    exit;
}

$storeId = $webhookRepository->findStoreId($shopDomain);

try {
    (new AppUninstaller())->revoke($shopDomain, $accessToken, $config['shopify']['api_version']);
} catch (\Throwable $exception) {
    error_log('[uninstall] revoke failed for ' . $shopDomain . ': ' . $exception->getMessage());
    http_response_code(502);
    echo 'Failed to uninstall the app. Please try again.';
    exit;
}

// Update local state immediately; Shopify's app/uninstalled webhook will also arrive
// later and safely no-op, since the store will no longer be found as active.
if ($storeId !== null) {
    $webhookRepository->deleteAllForStore($storeId);
}
$storeRepository->markUninstalled($shopDomain);

header('Location: ' . $config['app']['url'] . '/dashboard.php?shop=' . urlencode($shopDomain));
exit;
