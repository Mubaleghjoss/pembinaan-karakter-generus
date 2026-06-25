-- Manual SQL for cPanel/phpMyAdmin
-- Period: 2026-04-12
-- Use this file if server still misses proof-related columns for Tugas PKG.
-- This file adds schema for:
-- - 2026_04_12_001000_add_photo_proof_support_to_karakter_tasks
-- - 2026_04_12_030000_add_voice_note_proof_support_to_karakter_tasks
-- - 2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks
--
-- Safe enough for repeated use on MySQL/MariaDB that support IF NOT EXISTS.
-- If your server rejects one ALTER because the column already exists, skip that line and continue.

START TRANSACTION;

ALTER TABLE `karakter`
    ADD COLUMN IF NOT EXISTS `allows_photo_proof` TINYINT(1) NOT NULL DEFAULT 0 AFTER `target_klik`,
    ADD COLUMN IF NOT EXISTS `photo_proof_bonus_points` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `allows_photo_proof`,
    ADD COLUMN IF NOT EXISTS `photo_proof_instruction` TEXT NULL AFTER `photo_proof_bonus_points`,
    ADD COLUMN IF NOT EXISTS `allows_voice_note_proof` TINYINT(1) NOT NULL DEFAULT 0 AFTER `photo_proof_instruction`,
    ADD COLUMN IF NOT EXISTS `voice_note_bonus_points` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `allows_voice_note_proof`,
    ADD COLUMN IF NOT EXISTS `voice_note_instruction` TEXT NULL AFTER `voice_note_bonus_points`,
    ADD COLUMN IF NOT EXISTS `proof_requirement` VARCHAR(30) NOT NULL DEFAULT 'optional' AFTER `voice_note_instruction`,
    ADD COLUMN IF NOT EXISTS `voice_note_max_seconds` INT UNSIGNED NULL AFTER `proof_requirement`;

UPDATE `karakter`
SET `proof_requirement` = 'optional'
WHERE `proof_requirement` IS NULL OR `proof_requirement` = '';

ALTER TABLE `siswa_karakter_checklist`
    ADD COLUMN IF NOT EXISTS `proof_path` VARCHAR(255) NULL AFTER `click_history`,
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
