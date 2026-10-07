<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__);

if (is_file($projectRoot . '/.env')) {
    Dotenv::createImmutable($projectRoot)->safeLoad();
}

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'local',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
        'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/'),
    ],
    'shopify' => [
        'client_id' => $_ENV['SHOPIFY_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['SHOPIFY_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['SHOPIFY_REDIRECT_URI'] ?? '',
        'api_version' => $_ENV['SHOPIFY_API_VERSION'] ?? '2026-07',
        'scopes' => array_values(array_filter(array_map('trim', explode(',', $_ENV['SHOPIFY_SCOPES'] ?? '')))),
    ],
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? '',
        'username' => $_ENV['DB_USERNAME'] ?? '',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],
    'security' => [
        'encryption_key' => $_ENV['APP_ENCRYPTION_KEY'] ?? '',
    ],
];
