<?php

declare(strict_types=1);

namespace App\OAuth;

use JsonException;
use RuntimeException;

final class TokenExchangeClient
{
    /**
     * @return array{access_token: string, scope?: string}
     */
    public function exchange(string $shopDomain, string $clientId, string $clientSecret, string $code): array
    {
        $url = sprintf('https://%s/admin/oauth/access_token', $shopDomain);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
            ], JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Failed to reach Shopify token endpoint: ' . $curlError);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('Shopify rejected the token exchange request (HTTP ' . $httpCode . ').');
        }

        try {
            $data = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Shopify token response was not valid JSON.', 0, $exception);
        }

        if (!is_array($data) || !isset($data['access_token']) || !is_string($data['access_token'])) {
            throw new RuntimeException('Shopify token response did not include an access token.');
        }

        return $data;
    }
}
