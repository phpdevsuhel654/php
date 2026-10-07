<?php

declare(strict_types=1);

namespace App\Webhooks;

use PDO;

final class WebhookRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findStoreId(string $shopDomain): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM shopify_stores WHERE shop_domain = :shop_domain AND status = "active" LIMIT 1'
        );
        $statement->execute(['shop_domain' => $shopDomain]);
        $row = $statement->fetch();

        return $row ? (int) $row['id'] : null;
    }

    public function upsert(int $storeId, string $topic, string $webhookId, string $callbackUrl, string $apiVersion): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO shopify_webhooks (shop_id, topic, webhook_id, callback_url, api_version, status)
             VALUES (:shop_id, :topic, :webhook_id, :callback_url, :api_version, "active")
             ON DUPLICATE KEY UPDATE
                webhook_id = VALUES(webhook_id),
                callback_url = VALUES(callback_url),
                api_version = VALUES(api_version),
                status = "active"'
        );
        $statement->execute([
            'shop_id' => $storeId,
            'topic' => $topic,
            'webhook_id' => $webhookId,
            'callback_url' => $callbackUrl,
            'api_version' => $apiVersion,
        ]);
    }

    /**
     * @return array{webhook_id: string}|null
     */
    public function findByStoreAndTopic(int $storeId, string $topic): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT webhook_id, status FROM shopify_webhooks WHERE shop_id = :shop_id AND topic = :topic LIMIT 1'
        );
        $statement->execute(['shop_id' => $storeId, 'topic' => $topic]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function markDeleted(int $storeId, string $topic): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM shopify_webhooks WHERE shop_id = :shop_id AND topic = :topic'
        );
        $statement->execute(['shop_id' => $storeId, 'topic' => $topic]);
    }

    /**
     * Removes all locally tracked webhook subscriptions for a store; Shopify deletes
     * its own webhook subscriptions automatically when an app is uninstalled.
     */
    public function deleteAllForStore(int $storeId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM shopify_webhooks WHERE shop_id = :shop_id');
        $statement->execute(['shop_id' => $storeId]);
    }

    /**
     * Records a webhook delivery for idempotency; returns false if this event_id was already processed.
     */
    public function recordDeliveryOnce(int $storeId, string $topic, string $eventId): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT IGNORE INTO webhook_delivery_log (shop_id, topic, event_id) VALUES (:shop_id, :topic, :event_id)'
        );
        $statement->execute([
            'shop_id' => $storeId,
            'topic' => $topic,
            'event_id' => $eventId,
        ]);

        return $statement->rowCount() > 0;
    }
}
