<?php

declare(strict_types=1);

namespace App\OAuth;

use RuntimeException;

final class AccessScopesClient
{
    /**
     * @return string[]
     */
    public function fetch(string $shopDomain, string $accessToken): array
    {
        $url = sprintf('https://%s/admin/oauth/access_scopes.json', $shopDomain);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Shopify-Access-Token: ' . $accessToken, 'Accept: application/json'],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Failed to reach Shopify access_scopes endpoint: ' . $curlError);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('Shopify rejected the access_scopes request (HTTP ' . $httpCode . ').');
        }

        $data = json_decode((string) $response, true);

        if (!is_array($data) || !isset($data['access_scopes']) || !is_array($data['access_scopes'])) {
            throw new RuntimeException('Unexpected access_scopes response from Shopify.');
        }

        return array_values(array_filter(array_map(
            static fn (array $scope): ?string => isset($scope['handle']) ? (string) $scope['handle'] : null,
            $data['access_scopes']
        )));
    }
}
