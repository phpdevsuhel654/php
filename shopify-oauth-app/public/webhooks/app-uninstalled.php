<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Security\TokenCipher;
use App\Stores\StoreRepository;
use App\Webhooks\WebhookHmacVerifier;
use App\Webhooks\WebhookRepository;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = require dirname(__DIR__, 2) . '/config/config.php';

$rawBody = file_get_contents('php://input');

$hmacHeader = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? null;
$topicHeader = $_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? 'app/uninstalled';
$shopHeader = strtolower(trim($_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] ?? ''));
$webhookEventId = $_SERVER['HTTP_X_SHOPIFY_WEBHOOK_ID'] ?? null;

if (!WebhookHmacVerifier::verify($rawBody, $config['shopify']['client_secret'], $hmacHeader)) {
    http_response_code(401);
    error_log('[webhooks/app-uninstalled] HMAC verification failed for shop header: ' . $shopHeader);
    exit;
}

if ($shopHeader === '' || $webhookEventId === null) {
    http_response_code(400);
    exit;
}

$pdo = Connection::fromConfig($config);
$webhookRepository = new WebhookRepository($pdo);

$storeId = $webhookRepository->findStoreId($shopHeader);
if ($storeId === null) {
    // Already inactive or unknown; acknowledge to stop retries.
    http_response_code(200);
    exit;
}

if (!$webhookRepository->recordDeliveryOnce($storeId, $topicHeader, (string) $webhookEventId)) {
    http_response_code(200);
    exit;
}

$cipher = new TokenCipher($config['security']['encryption_key']);
$storeRepository = new StoreRepository($pdo, $cipher);

$webhookRepository->deleteAllForStore($storeId);
$storeRepository->markUninstalled($shopHeader);

error_log('[webhooks/app-uninstalled] processed uninstall for shop=' . $shopHeader);

http_response_code(200);
