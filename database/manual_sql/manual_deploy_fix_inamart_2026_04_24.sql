-- Manual deploy fix for inamart.my.id via cPanel/phpMyAdmin
-- Run on production database when SSH/artisan migrate is unavailable.
--
-- Covers missing DB objects seen after deploy:
-- - organizational_teams table
-- - users organizational columns
-- - attendance_schedules.target_audience
--
-- Safe to re-run on MySQL/MariaDB.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `organizational_teams` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `short_name` VARCHAR(40) NULL,
    `description` TEXT NULL,
    `color_hex` VARCHAR(7) NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `organizational_teams_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `users` ADD COLUMN `organizational_team_id` BIGINT UNSIGNED NULL AFTER `role_id`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'organizational_team_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `users` ADD COLUMN `organizational_title` VARCHAR(120) NULL AFTER `organizational_team_id`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'organizational_title'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `users` ADD COLUMN `organizational_sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `organizational_title`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'organizational_sort_order'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `users` ADD INDEX `users_organizational_team_id_index` (`organizational_team_id`)',
        'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND INDEX_NAME = 'users_organizational_team_id_index'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `users` ADD CONSTRAINT `users_organizational_team_id_foreign` FOREIGN KEY (`organizational_team_id`) REFERENCES `organizational_teams` (`id`) ON DELETE SET NULL',
        'SELECT 1')
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'users_organizational_team_id_foreign'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `attendance_schedules` ADD COLUMN `target_audience` VARCHAR(20) NOT NULL DEFAULT ''all'' AFTER `days`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'attendance_schedules' AND COLUMN_NAME = 'target_audience'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `attendance_schedules`
SET `target_audience` = 'all'
WHERE `target_audience` IS NULL OR `target_audience` = '';

COMMIT;

SET @next_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_15_090000_create_organizational_teams_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_15_090000_create_organizational_teams_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_15_091000_add_organizational_fields_to_users_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_15_091000_add_organizational_fields_to_users_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_23_181000_add_target_audience_to_attendance_schedules_table', @next_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_23_181000_add_target_audience_to_attendance_schedules_table');
