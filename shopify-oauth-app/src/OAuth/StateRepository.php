<?php

declare(strict_types=1);

namespace App\OAuth;

use DateTimeImmutable;
use PDO;

final class StateRepository
{
    private const TTL_SECONDS = 600;

    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $shopDomain): string
    {
        $state = bin2hex(random_bytes(32));
        $hash = hash('sha256', $state);
        $expiresAt = (new DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds'))->format('Y-m-d H:i:s');

        $statement = $this->pdo->prepare(
            'INSERT INTO oauth_states (state_hash, shop_domain, expires_at) VALUES (:state_hash, :shop_domain, :expires_at)'
        );
        $statement->execute([
            'state_hash' => $hash,
            'shop_domain' => $shopDomain,
            'expires_at' => $expiresAt,
        ]);

        return $state;
    }

    public function consume(string $state, string $shopDomain): bool
    {
        $hash = hash('sha256', $state);

        $statement = $this->pdo->prepare(
            'SELECT id, expires_at FROM oauth_states
             WHERE state_hash = :state_hash AND shop_domain = :shop_domain AND consumed_at IS NULL
             LIMIT 1'
        );
        $statement->execute([
            'state_hash' => $hash,
            'shop_domain' => $shopDomain,
        ]);
        $row = $statement->fetch();

        if (!$row) {
            return false;
        }

        if (new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) {
            return false;
        }

        $update = $this->pdo->prepare('UPDATE oauth_states SET consumed_at = NOW() WHERE id = :id');
        $update->execute(['id' => $row['id']]);

        return true;
    }
}
