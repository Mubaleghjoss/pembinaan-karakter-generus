-- Manual SQL for cPanel/phpMyAdmin
-- Period: 2026-04-09 to 2026-04-11
-- Safe enough for repeated use on MySQL/MariaDB that support IF NOT EXISTS.
-- If your server rejects one ALTER because the column already exists, skip that line and continue.

START TRANSACTION;

-- 1. RPG maps: missing combat columns
ALTER TABLE `rpg_maps`
    ADD COLUMN IF NOT EXISTS `enemies` JSON NULL AFTER `obstacles`,
    ADD COLUMN IF NOT EXISTS `difficulty` VARCHAR(255) NOT NULL DEFAULT 'easy' AFTER `enemies`,
    ADD COLUMN IF NOT EXISTS `shield_duration_seconds` INT UNSIGNED NOT NULL DEFAULT 8 AFTER `difficulty`,
    ADD COLUMN IF NOT EXISTS `ammo_per_pickup` INT UNSIGNED NOT NULL DEFAULT 3 AFTER `shield_duration_seconds`,
    ADD COLUMN IF NOT EXISTS `shield_pickups_count` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `ammo_per_pickup`,
    ADD COLUMN IF NOT EXISTS `ammo_pickups_count` INT UNSIGNED NOT NULL DEFAULT 2 AFTER `shield_pickups_count`;

-- 2. Theme settings: shell colors
ALTER TABLE `theme_settings`
    ADD COLUMN IF NOT EXISTS `sidebar_color` VARCHAR(255) NOT NULL DEFAULT '#ffffff' AFTER `light_color`,
    ADD COLUMN IF NOT EXISTS `topbar_color` VARCHAR(255) NOT NULL DEFAULT '#ffffff' AFTER `sidebar_color`;

UPDATE `theme_settings`
SET `sidebar_color` = '#ffffff'
WHERE `sidebar_color` IS NULL OR `sidebar_color` = '';

UPDATE `theme_settings`
SET `topbar_color` = '#ffffff'
WHERE `topbar_color` IS NULL OR `topbar_color` = '';

-- 3. Settings: card footer text
INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'card_footer_text', 'Kartu ini adalah identitas resmi peserta PKG Panunggangan', 'id_card', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `key` = 'card_footer_text'
);

-- 4. Point periods table
CREATE TABLE IF NOT EXISTS `point_periods` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `point_settings` JSON NULL,
    `notes` TEXT NULL,
    `activated_at` TIMESTAMP NULL DEFAULT NULL,
    `closed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `point_periods_slug_unique` (`slug`),
    KEY `point_periods_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Default gamification settings
INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_hadir', '10', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_hadir');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_terlambat', '5', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_terlambat');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_izin', '2', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_izin');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_sakit', '2', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_sakit');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_alpha', '0', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_alpha');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_karakter', '5', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_karakter');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_streak_7', '20', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_streak_7');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_streak_30', '50', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_streak_30');

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
SELECT 'points_perfect_month', '100', 'gamification', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'points_perfect_month');

-- 6. Pamong presensi metadata
ALTER TABLE `pamong_presensi`
    ADD COLUMN IF NOT EXISTS `metadata` JSON NULL AFTER `verified_at`;

-- 7. Kelompok siswa
ALTER TABLE `siswa`
    ADD COLUMN IF NOT EXISTS `kelompok` VARCHAR(60) NULL AFTER `alamat`,
    ADD INDEX IF NOT EXISTS `siswa_kelompok_index` (`kelompok`);

UPDATE `siswa`
SET `kelompok` = LOWER(TRIM(`alamat`))
WHERE `kelompok` IS NULL
  AND `alamat` IS NOT NULL
  AND LOWER(TRIM(`alamat`)) IN ('panunggangan utara', 'sawah dalam', 'pakulonan');

-- 8. Tugas PKG: bukti foto + bonus poin
ALTER TABLE `karakter`
    ADD COLUMN IF NOT EXISTS `allows_photo_proof` TINYINT(1) NOT NULL DEFAULT 0 AFTER `target_klik`,
    ADD COLUMN IF NOT EXISTS `photo_proof_bonus_points` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `allows_photo_proof`,
    ADD COLUMN IF NOT EXISTS `photo_proof_instruction` TEXT NULL AFTER `photo_proof_bonus_points`,
    ADD COLUMN IF NOT EXISTS `allows_voice_note_proof` TINYINT(1) NOT NULL DEFAULT 0 AFTER `photo_proof_instruction`,
    ADD COLUMN IF NOT EXISTS `voice_note_bonus_points` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `allows_voice_note_proof`,
    ADD COLUMN IF NOT EXISTS `voice_note_instruction` TEXT NULL AFTER `voice_note_bonus_points`,
    ADD COLUMN IF NOT EXISTS `proof_requirement` VARCHAR(30) NOT NULL DEFAULT 'optional' AFTER `voice_note_instruction`,
    ADD COLUMN IF NOT EXISTS `voice_note_max_seconds` INT UNSIGNED NULL AFTER `proof_requirement`;

ALTER TABLE `siswa_karakter_checklist`
    ADD COLUMN IF NOT EXISTS `proof_path` VARCHAR(255) NULL AFTER `hasil_teks`,
    ADD COLUMN IF NOT EXISTS `proof_original_size_kb` INT UNSIGNED NULL AFTER `proof_path`,
    ADD COLUMN IF NOT EXISTS `proof_compressed_size_kb` INT UNSIGNED NULL AFTER `proof_original_size_kb`,
    ADD COLUMN IF NOT EXISTS `voice_note_path` VARCHAR(255) NULL AFTER `proof_compressed_size_kb`,
    ADD COLUMN IF NOT EXISTS `voice_note_size_kb` INT UNSIGNED NULL AFTER `voice_note_path`,
    ADD COLUMN IF NOT EXISTS `voice_note_duration_seconds` INT UNSIGNED NULL AFTER `voice_note_size_kb`;

COMMIT;

-- Optional: mark these migrations as executed in Laravel migrations table.
-- Run this block only if table `migrations` exists and you want Laravel records kept in sync.

SET @next_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_09_210000_add_missing_columns_to_rpg_maps_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_09_210000_add_missing_columns_to_rpg_maps_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_150000_add_card_footer_text_setting', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_150000_add_card_footer_text_setting'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_160000_add_shell_colors_to_theme_settings_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_160000_add_shell_colors_to_theme_settings_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_170000_add_pickup_settings_to_rpg_maps_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_170000_add_pickup_settings_to_rpg_maps_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_173000_add_pickup_counts_to_rpg_maps_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_173000_add_pickup_counts_to_rpg_maps_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_180000_create_point_periods_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_180000_create_point_periods_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_181000_add_metadata_to_pamong_presensi_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_181000_add_metadata_to_pamong_presensi_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_190000_add_kelompok_to_siswa_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_190000_add_kelompok_to_siswa_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_12_001000_add_photo_proof_support_to_karakter_tasks', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_12_001000_add_photo_proof_support_to_karakter_tasks'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_12_030000_add_voice_note_proof_support_to_karakter_tasks', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_12_030000_add_voice_note_proof_support_to_karakter_tasks'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks'
);
