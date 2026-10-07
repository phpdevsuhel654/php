<?php

declare(strict_types=1);

use App\Database\Connection;
use App\OAuth\AuthorizationUrlBuilder;
use App\OAuth\StateRepository;
use App\Shopify\ShopDomainValidator;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

$shopProvided = array_key_exists('shop', $_GET);
$shopDomain = $shopProvided ? strtolower(trim((string) $_GET['shop'])) : '';

if (!$shopProvided) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Install Store</title>
        <link rel="stylesheet" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
    </head>
    <body>
        <?php require __DIR__ . '/partials/nav.php'; ?>
        <div class="container">
            <h1>Install Store</h1>
            <div class="card">
                <form method="get" action="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/install.php">
                    <label for="shop">Shop domain</label>
                    <input type="text" id="shop" name="shop" placeholder="your-store.myshopify.com" required>
                    <button type="submit" class="btn">Install</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($shopDomain === '' || !ShopDomainValidator::isValid($shopDomain)) {
    http_response_code(400);
    echo 'Invalid shop domain. Expected format: your-store.myshopify.com';
    exit;
}

$pdo = Connection::fromConfig($config);
$stateRepository = new StateRepository($pdo);
$state = $stateRepository->create($shopDomain);

$authorizationUrl = AuthorizationUrlBuilder::build(
    $shopDomain,
    $config['shopify']['client_id'],
    $config['shopify']['scopes'],
    $config['shopify']['redirect_uri'],
    $state
);

header('Location: ' . $authorizationUrl);
exit;
