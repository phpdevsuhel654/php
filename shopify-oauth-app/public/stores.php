<?php

declare(strict_types=1);

use App\Database\Connection;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

$pdo = Connection::fromConfig($config);
$statement = $pdo->query(
    'SELECT shop_domain, status, installed_at, uninstalled_at FROM shopify_stores ORDER BY installed_at DESC'
);
$stores = $statement->fetchAll();

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Connected Stores</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>
<div class="container">
    <h1>Connected Stores</h1>

    <?php if (empty($stores)): ?>
        <p>No stores have installed this app yet.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Shop Domain</th>
                <th>Status</th>
                <th>Installed At</th>
                <th>Uninstalled At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($stores as $store): ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/dashboard.php?shop=<?= urlencode($store['shop_domain']) ?>">
                            <?= htmlspecialchars($store['shop_domain'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td class="status status-<?= htmlspecialchars($store['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($store['status'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td><?= htmlspecialchars((string) $store['installed_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($store['uninstalled_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
