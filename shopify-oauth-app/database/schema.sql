CREATE DATABASE IF NOT EXISTS shopify_learning
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE shopify_learning;

CREATE TABLE shopify_stores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_domain VARCHAR(255) NOT NULL,
    access_token_encrypted TEXT NOT NULL,
    scopes JSON NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    installed_at DATETIME NOT NULL,
    uninstalled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shopify_stores_shop_domain (shop_domain),
    KEY idx_shopify_stores_status (status)
) ENGINE=InnoDB;

CREATE TABLE oauth_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    state_hash CHAR(64) NOT NULL,
    shop_domain VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    consumed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_oauth_states_state_hash (state_hash),
    KEY idx_oauth_states_expiry (expires_at),
    KEY idx_oauth_states_shop_domain (shop_domain)
) ENGINE=InnoDB;

CREATE TABLE shopify_webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    topic VARCHAR(128) NOT NULL,
    webhook_id VARCHAR(255) NOT NULL,
    callback_url VARCHAR(2048) NOT NULL,
    api_version VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shopify_webhooks_store_topic (shop_id, topic),
    UNIQUE KEY uq_shopify_webhooks_webhook_id (webhook_id),
    KEY idx_shopify_webhooks_status (status),
    CONSTRAINT fk_shopify_webhooks_store
        FOREIGN KEY (shop_id) REFERENCES shopify_stores (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE webhook_delivery_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    topic VARCHAR(128) NOT NULL,
    event_id VARCHAR(255) NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_webhook_delivery (shop_id, event_id),
    CONSTRAINT fk_webhook_delivery_store
        FOREIGN KEY (shop_id) REFERENCES shopify_stores (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

