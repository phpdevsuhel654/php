<?php

declare(strict_types=1);

namespace App\OAuth;

final class AuthorizationUrlBuilder
{
    /**
     * @param string[] $scopes
     */
    public static function build(string $shopDomain, string $clientId, array $scopes, string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => $clientId,
            'scope' => implode(',', $scopes),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return sprintf('https://%s/admin/oauth/authorize?%s', $shopDomain, $query);
    }
}
