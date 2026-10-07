<?php

declare(strict_types=1);

use App\Database\Connection;
use App\OAuth\AccessScopesClient;
use App\OAuth\HmacVerifier;
use App\OAuth\StateRepository;
use App\OAuth\TokenExchangeClient;
use App\Security\TokenCipher;
use App\Shopify\ShopDomainValidator;
use App\Shopify\ShopifyClientFactory;
use App\Stores\StoreRepository;
use App\Webhooks\WebhookRepository;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = require dirname(__DIR__, 2) . '/config/config.php';

$shopDomain = isset($_GET['shop']) ? strtolower(trim((string) $_GET['shop'])) : '';
$state = isset($_GET['state']) ? (string) $_GET['state'] : '';
$code = isset($_GET['code']) ? (string) $_GET['code'] : '';

if ($shopDomain === '' || !ShopDomainValidator::isValid($shopDomain)) {
    http_response_code(400);
    echo 'Invalid shop domain.';
    exit;
}

if ($code === '' || $state === '') {
    http_response_code(400);
    echo 'Missing required OAuth parameters.';
    exit;
}

if (!HmacVerifier::verify($_GET, $config['shopify']['client_secret'])) {
    http_response_code(400);
    echo 'HMAC validation failed.';
    exit;
}

$pdo = Connection::fromConfig($config);

$stateRepository = new StateRepository($pdo);
if (!$stateRepository->consume($state, $shopDomain)) {
    http_response_code(400);
    echo 'Invalid or expired OAuth state.';
    exit;
}

try {
    $tokenClient = new TokenExchangeClient();
    $tokenResponse = $tokenClient->exchange(
        $shopDomain,
        $config['shopify']['client_id'],
        $config['shopify']['client_secret'],
        $code
    );
} catch (\Throwable $exception) {
    error_log('[oauth-callback] token exchange failed: ' . $exception->getMessage());
    http_response_code(502);
    echo 'Failed to complete installation. Please try again.';
    exit;
}

// The token exchange response's 'scope' field is not authoritative for apps using Shopify
// Managed Installation, so the true granted scopes are read from access_scopes.json instead.
try {
    $accessScopesClient = new AccessScopesClient();
    $grantedScopes = $accessScopesClient->fetch($shopDomain, $tokenResponse['access_token']);
} catch (\Throwable $exception) {
    error_log('[oauth-callback] failed to fetch access scopes: ' . $exception->getMessage());
    $grantedScopes = isset($tokenResponse['scope'])
        ? array_values(array_filter(array_map('trim', explode(',', (string) $tokenResponse['scope']))))
        : [];
}

try {
    $cipher = new TokenCipher($config['security']['encryption_key']);
    $storeRepository = new StoreRepository($pdo, $cipher);
    $storeRepository->upsertInstalledStore($shopDomain, $tokenResponse['access_token'], $grantedScopes);
} catch (\Throwable $exception) {
    error_log('[oauth-callback] failed to persist store: ' . $exception->getMessage());
    http_response_code(500);
    echo 'Installation could not be completed due to a server error.';
    exit;
}

// Best-effort: register the mandatory uninstall webhook so token/data cleanup happens
// automatically later. Installation must not fail if this particular call fails.
try {
    $webhookRepository = new WebhookRepository($pdo);
    $storeId = $webhookRepository->findStoreId($shopDomain);
    if ($storeId !== null && $webhookRepository->findByStoreAndTopic($storeId, 'app/uninstalled') === null) {
        $shopify = (new ShopifyClientFactory($storeRepository, $config['shopify']['api_version']))->forShop($shopDomain);
        $webhook = $shopify->Webhook->post([
            'topic' => 'app/uninstalled',
            'address' => $config['app']['url'] . '/webhooks/app-uninstalled.php',
            'format' => 'json',
        ]);
        $webhookRepository->upsert($storeId, 'app/uninstalled', (string) $webhook['id'], $config['app']['url'] . '/webhooks/app-uninstalled.php', $config['shopify']['api_version']);
    }
} catch (\Throwable $exception) {
    error_log('[oauth-callback] failed to register app/uninstalled webhook: ' . $exception->getMessage());
}

header('Location: ' . $config['app']['url'] . '/dashboard.php?shop=' . urlencode($shopDomain));
exit;
