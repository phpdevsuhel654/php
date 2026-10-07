<?php

declare(strict_types=1);

namespace App\Shopify;

use App\Stores\StoreRepository;
use PHPShopify\ShopifySDK;
use RuntimeException;

final class ShopifyClientFactory
{
    public function __construct(private StoreRepository $storeRepository, private string $apiVersion)
    {
    }

    public function forShop(string $shopDomain): ShopifySDK
    {
        $accessToken = $this->storeRepository->findActiveAccessToken($shopDomain);

        if ($accessToken === null) {
            throw new RuntimeException("No active installation found for shop: {$shopDomain}");
        }

        // Config is always rebuilt from the resolved store's own token, never from caller input,
        // so one store's credentials can never be applied to another store's requests.
        return new ShopifySDK([
            'ShopUrl' => $shopDomain,
            'AccessToken' => $accessToken,
            'ApiVersion' => $this->apiVersion,
        ]);
    }
}
