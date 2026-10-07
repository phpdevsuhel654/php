<?php

declare(strict_types=1);

namespace App\Webhooks;

final class WebhookHmacVerifier
{
    public static function verify(string $rawBody, string $clientSecret, ?string $headerHmac): bool
    {
        if ($headerHmac === null || $headerHmac === '') {
            return false;
        }

        $computedHmac = base64_encode(hash_hmac('sha256', $rawBody, $clientSecret, true));

        return hash_equals($computedHmac, $headerHmac);
    }
}
