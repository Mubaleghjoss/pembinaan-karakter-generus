CREATE TABLE IF NOT EXISTS `presentations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `materi_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `background_color` VARCHAR(7) NOT NULL DEFAULT '#0f172a',
  `path_mode` VARCHAR(30) NOT NULL DEFAULT 'overview_between',
  `canvas_data` JSON NOT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presentations_slug_unique` (`slug`),
  KEY `presentations_is_published_index` (`is_published`),
  KEY `presentations_materi_id_is_published_index` (`materi_id`, `is_published`),
  KEY `presentations_created_by_foreign` (`created_by`),
  CONSTRAINT `presentations_materi_id_foreign` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `presentations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presentation_assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `presentation_id` BIGINT UNSIGNED NOT NULL,
  `uploaded_by` BIGINT UNSIGNED NULL,
  `path` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(80) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `presentation_assets_presentation_id_foreign` (`presentation_id`),
  KEY `presentation_assets_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `presentation_assets_presentation_id_foreign` FOREIGN KEY (`presentation_id`) REFERENCES `presentations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presentation_assets_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
