<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Webhooks\WebhookHmacVerifier;
use App\Webhooks\WebhookRepository;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = require dirname(__DIR__, 2) . '/config/config.php';

// Read the raw body before any parsing; HMAC must be verified against the exact bytes Shopify sent.
$rawBody = file_get_contents('php://input');

$hmacHeader = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? null;
$topicHeader = $_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? '';
// The shop domain must come from Shopify's own header, never from the JSON body, to prevent spoofing.
$shopHeader = strtolower(trim($_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] ?? ''));
$webhookEventId = $_SERVER['HTTP_X_SHOPIFY_WEBHOOK_ID'] ?? null;

if (!WebhookHmacVerifier::verify($rawBody, $config['shopify']['client_secret'], $hmacHeader)) {
    http_response_code(401);
    error_log('[webhooks/orders-create] HMAC verification failed for shop header: ' . $shopHeader);
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
    // Unknown or inactive store; acknowledge to stop retries but do not process.
    http_response_code(200);
    error_log('[webhooks/orders-create] received for unknown/inactive store: ' . $shopHeader);
    exit;
}

if (!$webhookRepository->recordDeliveryOnce($storeId, $topicHeader, (string) $webhookEventId)) {
    // Duplicate delivery (Shopify retry) - already processed, acknowledge without reprocessing.
    http_response_code(200);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    exit;
}

$orderId = $payload['id'] ?? null;
$orderNumber = $payload['order_number'] ?? null;
error_log(sprintf(
    '[webhooks/orders-create] shop=%s order_id=%s order_number=%s',
    $shopHeader,
    (string) $orderId,
    (string) $orderNumber
));

http_response_code(200);
