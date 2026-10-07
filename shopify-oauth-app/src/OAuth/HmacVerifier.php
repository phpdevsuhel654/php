<?php

declare(strict_types=1);

namespace App\OAuth;

final class HmacVerifier
{
    public static function verify(array $queryParams, string $clientSecret): bool
    {
        if (!isset($queryParams['hmac']) || !is_string($queryParams['hmac'])) {
            return false;
        }

        $hmac = $queryParams['hmac'];
        unset($queryParams['hmac'], $queryParams['signature']);

        ksort($queryParams);
        $message = urldecode(http_build_query($queryParams));

        $computedHmac = hash_hmac('sha256', $message, $clientSecret);

        // hash_equals prevents timing attacks when comparing the computed and provided HMAC.
        return hash_equals($computedHmac, $hmac);
    }
}
