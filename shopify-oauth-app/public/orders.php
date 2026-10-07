<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Security\TokenCipher;
use App\Shopify\ShopDomainValidator;
use App\Shopify\ShopifyClientFactory;
use App\Stores\StoreRepository;
use PHPShopify\Exception\ApiException;
use PHPShopify\Exception\ResourceRateLimitException;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

$shopDomain = isset($_GET['shop']) ? strtolower(trim((string) $_GET['shop'])) : '';

if ($shopDomain === '' || !ShopDomainValidator::isValid($shopDomain)) {
    http_response_code(400);
    echo 'Invalid shop domain.';
    exit;
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$limit = max(1, min($limit, 250));

$pageInfo = isset($_GET['page_info']) ? (string) $_GET['page_info'] : null;

$pdo = Connection::fromConfig($config);
$cipher = new TokenCipher($config['security']['encryption_key']);
$storeRepository = new StoreRepository($pdo, $cipher);
$clientFactory = new ShopifyClientFactory($storeRepository, $config['shopify']['api_version']);

$orders = [];
$nextPageParams = [];
$prevPageParams = [];
$errorMessage = null;

try {
    $shopify = $clientFactory->forShop($shopDomain);

    $query = ['limit' => $limit, 'status' => 'any'];
    if ($pageInfo !== null) {
        $query['page_info'] = $pageInfo;
    }

    $orders = $shopify->Order->get($query);
    $nextPageParams = $shopify->Order->getNextPageParams();
    $prevPageParams = $shopify->Order->getPrevPageParams();
} catch (ResourceRateLimitException $exception) {
    $errorMessage = 'Shopify rate limit reached. Please wait a moment and try again.';
    error_log('[orders] rate limited for ' . $shopDomain . ': ' . $exception->getMessage());
} catch (ApiException $exception) {
    if ($exception->getCode() === 401) {
        $errorMessage = 'The stored access token is invalid or has been revoked. Please reinstall the app for this store.';
    } elseif ($exception->getCode() === 403) {
        $errorMessage = 'Access to orders was denied (HTTP 403). This is commonly caused by either a missing '
            . '"read_orders" scope, or by Shopify\'s "Protected customer data access" not being approved for this '
            . 'app yet (Partner/Dev Dashboard > API access > Protected customer data access). Orders contain '
            . 'protected customer data, so this approval is required in addition to OAuth scopes.';
    } else {
        $errorMessage = 'Shopify API error: ' . $exception->getMessage();
    }
    error_log('[orders] API error for ' . $shopDomain . ': ' . $exception->getMessage());
} catch (\Throwable $exception) {
    $errorMessage = 'Unable to load orders for this store.';
    error_log('[orders] unexpected error for ' . $shopDomain . ': ' . $exception->getMessage());
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Orders - <?= h($shopDomain) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>
<div class="container">
<a class="back-link" href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/dashboard.php?shop=<?= urlencode($shopDomain) ?>">&larr; Back to Store Dashboard</a>
<h1>Orders</h1>
<p>Store: <?= h($shopDomain) ?></p>

<?php if ($errorMessage !== null): ?>
    <p class="error"><?= h($errorMessage) ?></p>
<?php elseif (empty($orders)): ?>
    <p>No orders found for this store.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Order ID</th>
            <th>Order Number</th>
            <th>Financial Status</th>
            <th>Fulfillment Status</th>
            <th>Total Price</th>
            <th>Currency</th>
            <th>Created Date</th>
            <th>Updated Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= h((string) ($order['id'] ?? '')) ?></td>
                <td><?= h((string) ($order['order_number'] ?? '')) ?></td>
                <td><?= h((string) ($order['financial_status'] ?? '')) ?></td>
                <td><?= h((string) ($order['fulfillment_status'] ?? 'unfulfilled')) ?></td>
                <td><?= h((string) ($order['total_price'] ?? '')) ?></td>
                <td><?= h((string) ($order['currency'] ?? '')) ?></td>
                <td><?= h((string) ($order['created_at'] ?? '')) ?></td>
                <td><?= h((string) ($order['updated_at'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="pagination">
        <?php if (!empty($prevPageParams['page_info'])): ?>
            <a href="?shop=<?= urlencode($shopDomain) ?>&limit=<?= $limit ?>&page_info=<?= urlencode($prevPageParams['page_info']) ?>">&laquo; Previous</a>
        <?php endif; ?>
        <?php if (!empty($nextPageParams['page_info'])): ?>
            <a href="?shop=<?= urlencode($shopDomain) ?>&limit=<?= $limit ?>&page_info=<?= urlencode($nextPageParams['page_info']) ?>">Next &raquo;</a>
        <?php endif; ?>
    </p>
<?php endif; ?>

</div>
</body>
</html>
