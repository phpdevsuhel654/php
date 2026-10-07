<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

$appName = 'Shopify Multi-Store Learning App';
$apiVersion = htmlspecialchars($config['shopify']['api_version'], ENT_QUOTES, 'UTF-8');
$environment = htmlspecialchars($config['app']['env'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $appName ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <div class="container">
        <h1><?= $appName ?></h1>
        <p class="status status-active">Application is running.</p>
        <p>This first step only verifies the PHP application foundation.</p>
        <dl>
            <dt>Environment</dt>
            <dd><code><?= $environment ?></code></dd>
            <dt>Shopify API version</dt>
            <dd><code><?= $apiVersion ?></code></dd>
        </dl>
    </div>
</body>
</html>
