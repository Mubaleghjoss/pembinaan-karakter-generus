CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscribable_type` VARCHAR(80) NOT NULL,
    `subscribable_id` BIGINT UNSIGNED NOT NULL,
    `endpoint` VARCHAR(500) NOT NULL,
    `public_key` VARCHAR(255) NULL,
    `auth_token` VARCHAR(255) NULL,
    `content_encoding` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
    KEY `push_subscriptions_subscribable_morph_idx` (`subscribable_type`, `subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pwa_notification_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `notifiable_type` VARCHAR(80) NOT NULL,
    `notifiable_id` BIGINT UNSIGNED NOT NULL,
    `notification_key` VARCHAR(120) NOT NULL,
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pwa_delivery_notifiable_key_unique` (`notifiable_type`, `notifiable_id`, `notification_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
