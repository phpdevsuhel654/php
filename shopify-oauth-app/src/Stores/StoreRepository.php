<?php

declare(strict_types=1);

namespace App\Stores;

use App\Security\TokenCipher;
use JsonException;
use PDO;

final class StoreRepository
{
    public function __construct(private PDO $pdo, private TokenCipher $cipher)
    {
    }

    /**
     * @param string[] $scopes
     */
    public function upsertInstalledStore(string $shopDomain, string $accessToken, array $scopes): void
    {
        $encryptedToken = $this->cipher->encrypt($accessToken);

        try {
            $scopesJson = json_encode(array_values($scopes), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Failed to encode granted scopes.', 0, $exception);
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO shopify_stores (shop_domain, access_token_encrypted, scopes, status, installed_at)
             VALUES (:shop_domain, :access_token_encrypted, :scopes, "active", NOW())
             ON DUPLICATE KEY UPDATE
                access_token_encrypted = VALUES(access_token_encrypted),
                scopes = VALUES(scopes),
                status = "active",
                installed_at = NOW(),
                uninstalled_at = NULL'
        );

        $statement->execute([
            'shop_domain' => $shopDomain,
            'access_token_encrypted' => $encryptedToken,
            'scopes' => $scopesJson,
        ]);
    }

    public function findActiveAccessToken(string $shopDomain): ?string
    {
        $statement = $this->pdo->prepare(
            'SELECT access_token_encrypted FROM shopify_stores WHERE shop_domain = :shop_domain AND status = "active" LIMIT 1'
        );
        $statement->execute(['shop_domain' => $shopDomain]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->cipher->decrypt($row['access_token_encrypted']);
    }

    /**
     * Marks a store uninstalled and blanks the now-dead token ciphertext so no working
     * secret is retained at rest; the row itself is kept for audit/reinstall history.
     */
    public function markUninstalled(string $shopDomain): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE shopify_stores
             SET status = "uninstalled", uninstalled_at = NOW(), access_token_encrypted = :blank_token
             WHERE shop_domain = :shop_domain'
        );
        $statement->execute([
            'blank_token' => $this->cipher->encrypt(''),
            'shop_domain' => $shopDomain,
        ]);
    }
}
