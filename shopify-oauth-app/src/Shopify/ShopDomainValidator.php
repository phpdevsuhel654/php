<?php

declare(strict_types=1);

namespace App\Shopify;

final class ShopDomainValidator
{
    // Shopify shop domains are lowercase alphanumeric/hyphen labels under myshopify.com.
    private const PATTERN = '/^[a-z0-9][a-z0-9\-]*\.myshopify\.com$/';

    public static function isValid(string $shopDomain): bool
    {
        return (bool) preg_match(self::PATTERN, strtolower($shopDomain));
    }
}
