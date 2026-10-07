<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Security\Csrf;
use App\Shopify\ShopDomainValidator;
use App\Webhooks\WebhookRepository;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

$shopDomain = isset($_GET['shop']) ? strtolower(trim((string) $_GET['shop'])) : '';

if ($shopDomain === '' || !ShopDomainValidator::isValid($shopDomain)) {
    http_response_code(400);
    echo 'Invalid shop domain.';
    exit;
}

$pdo = Connection::fromConfig($config);
$statement = $pdo->prepare(
    'SELECT shop_domain, status, installed_at FROM shopify_stores WHERE shop_domain = :shop_domain LIMIT 1'
);
$statement->execute(['shop_domain' => $shopDomain]);
$store = $statement->fetch();

$webhookTopic = 'orders/create';
$webhook = null;
if ($store) {
    $webhookRepository = new WebhookRepository($pdo);
    $storeId = $webhookRepository->findStoreId($shopDomain);
    if ($storeId !== null) {
        $webhook = $webhookRepository->findByStoreAndTopic($storeId, $webhookTopic);
    }
}

$csrfToken = Csrf::token();

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Store Dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>
<div class="container">
<a class="back-link" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/stores.php">&larr; Back to Stores</a>
<?php if ($store && $store['status'] === 'active'): ?>
    <h1>Store connected</h1>
    <p><?= htmlspecialchars($store['shop_domain'], ENT_QUOTES, 'UTF-8') ?> is
        <span class="status status-active"><?= htmlspecialchars($store['status'], ENT_QUOTES, 'UTF-8') ?></span>.</p>
    <p>Installed at <?= htmlspecialchars($store['installed_at'], ENT_QUOTES, 'UTF-8') ?>.</p>
    <p>
        <a href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/products.php?shop=<?= urlencode($shopDomain) ?>">View Products</a>
        &nbsp;·&nbsp;
        <a href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/orders.php?shop=<?= urlencode($shopDomain) ?>">View Orders</a>
    </p>

    <h2>Orders Create Webhook</h2>
    <div class="card">
        <?php if ($webhook): ?>
            <p>Status: <span class="status status-active"><?= htmlspecialchars($webhook['status'], ENT_QUOTES, 'UTF-8') ?></span></p>
            <form class="inline" method="post" action="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/webhooks/delete.php">
                <input type="hidden" name="shop" value="<?= htmlspecialchars($shopDomain, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-danger">Delete Webhook</button>
            </form>
        <?php else: ?>
            <p>Status: Not created</p>
            <form class="inline" method="post" action="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/webhooks/create.php">
                <input type="hidden" name="shop" value="<?= htmlspecialchars($shopDomain, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn">Create Webhook</button>
            </form>
        <?php endif; ?>
    </div>

    <h2>Danger Zone</h2>
    <div class="card danger-zone">
        <p>Uninstalling revokes this store's access token via Shopify's API and cannot be undone from here — you would need to reinstall.</p>
        <form method="post" action="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/uninstall.php" onsubmit="return confirm('Uninstall the app from ' + <?= json_encode($shopDomain) ?> + '? This revokes its access token.');">
            <input type="hidden" name="shop" value="<?= htmlspecialchars($shopDomain, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-danger">Uninstall App</button>
        </form>
    </div>
<?php elseif ($store): ?>
    <h1>Store uninstalled</h1>
    <p><?= htmlspecialchars($store['shop_domain'], ENT_QUOTES, 'UTF-8') ?> uninstalled this app.
        Its access token has been invalidated and local webhook records were removed.</p>
    <p><a href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/install.php?shop=<?= urlencode($shopDomain) ?>">Reinstall App</a></p>
<?php else: ?>
    <h1>Store not found</h1>
    <p>No installation record exists for this shop yet.</p>
<?php endif; ?>
</div>
</body>
</html>
