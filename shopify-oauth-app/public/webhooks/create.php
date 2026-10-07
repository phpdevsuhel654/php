<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Security\Csrf;
use App\Security\TokenCipher;
use App\Shopify\ShopDomainValidator;
use App\Shopify\ShopifyClientFactory;
use App\Stores\StoreRepository;
use App\Webhooks\WebhookRepository;
use PHPShopify\Exception\ApiException;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = require dirname(__DIR__, 2) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo 'Invalid or missing request verification token.';
    exit;
}

$shopDomain = isset($_POST['shop']) ? strtolower(trim((string) $_POST['shop'])) : '';
$topic = 'orders/create';

if ($shopDomain === '' || !ShopDomainValidator::isValid($shopDomain)) {
    http_response_code(400);
    echo 'Invalid shop domain.';
    exit;
}

$pdo = Connection::fromConfig($config);
$cipher = new TokenCipher($config['security']['encryption_key']);
$storeRepository = new StoreRepository($pdo, $cipher);
$webhookRepository = new WebhookRepository($pdo);
$clientFactory = new ShopifyClientFactory($storeRepository, $config['shopify']['api_version']);

$storeId = $webhookRepository->findStoreId($shopDomain);
if ($storeId === null) {
    http_response_code(404);
    echo 'No active installation found for this shop.';
    exit;
}

$callbackUrl = $config['app']['url'] . '/webhooks/orders-create.php';

try {
    $shopify = $clientFactory->forShop($shopDomain);

    $webhook = $shopify->Webhook->post([
        'topic' => $topic,
        'address' => $callbackUrl,
        'format' => 'json',
    ]);

    $webhookRepository->upsert($storeId, $topic, (string) $webhook['id'], $callbackUrl, $config['shopify']['api_version']);

    header('Location: ' . $config['app']['url'] . '/dashboard.php?shop=' . urlencode($shopDomain));
    exit;
} catch (ApiException $exception) {
    http_response_code(502);
    echo 'Failed to create webhook: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    error_log('[webhooks/create] ' . $shopDomain . ': ' . $exception->getMessage());
    exit;
} catch (\Throwable $exception) {
    http_response_code(500);
    echo 'Unable to create webhook due to a server error.';
    error_log('[webhooks/create] unexpected error for ' . $shopDomain . ': ' . $exception->getMessage());
    exit;
}
