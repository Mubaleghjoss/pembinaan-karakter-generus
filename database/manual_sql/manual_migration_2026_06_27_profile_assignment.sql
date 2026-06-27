-- Jalankan hanya jika deployment tidak dapat memakai `php artisan migrate --force`.

ALTER TABLE `siswa`
  ADD COLUMN IF NOT EXISTS `profile_assignment_confirmed_at` TIMESTAMP NULL
  AFTER `target_grade_override`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `kelompok` VARCHAR(60) NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `profile_assignment_confirmed_at` TIMESTAMP NULL AFTER `kelompok`;

ALTER TABLE `users`
  ADD INDEX IF NOT EXISTS `users_kelompok_index` (`kelompok`);

INSERT INTO `settings` (`key`, `value`, `group`, `type`, `created_at`, `updated_at`)
VALUES
  ('popup_profile_assignment_prompt_enabled', '1', 'popup', 'boolean', NOW(), NOW()),
  ('popup_profile_assignment_prompt_required', '1', 'popup', 'boolean', NOW(), NOW()),
  ('popup_biometric_prompt_enabled', '0', 'popup', 'boolean', NOW(), NOW()),
  ('popup_biometric_prompt_required', '0', 'popup', 'boolean', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `value` = VALUES(`value`),
  `group` = VALUES(`group`),
  `type` = VALUES(`type`),
  `updated_at` = NOW();

INSERT INTO `materi_targets` (
  `target_grade`,
  `semester`,
  `category`,
  `title`,
  `description`,
  `sort_order`,
  `is_active`,
  `source_key`,
  `created_at`,
  `updated_at`
)
SELECT
  'pranikah',
  `semester`,
  `category`,
  `title`,
  CONCAT(`description`, '\n\nTarget lanjutan untuk generus Pranikah setelah menyelesaikan SMA/K.'),
  `sort_order`,
  `is_active`,
  CONCAT('kmgt_pranikah_', `source_key`),
  NOW(),
  NOW()
FROM `materi_targets`
WHERE `target_grade` = 'sma_12'
  AND `source_key` IS NOT NULL
ON DUPLICATE KEY UPDATE
  `target_grade` = VALUES(`target_grade`),
  `semester` = VALUES(`semester`),
  `category` = VALUES(`category`),
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `sort_order` = VALUES(`sort_order`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = NOW();
