<?php

declare(strict_types=1);

namespace App\OAuth;

use RuntimeException;

final class AppUninstaller
{
    /**
     * Revokes the current access token via Shopify's Access API, which uninstalls
     * the app for this store. Shopify will also send an app/uninstalled webhook afterward.
     */
    public function revoke(string $shopDomain, string $accessToken, string $apiVersion): void
    {
        $url = sprintf('https://%s/admin/api/%s/api_permissions/current.json', $shopDomain, $apiVersion);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Shopify-Access-Token: ' . $accessToken],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Failed to reach Shopify Access API: ' . $curlError);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('Shopify rejected the uninstall request (HTTP ' . $httpCode . '): ' . $response);
        }
    }
}
