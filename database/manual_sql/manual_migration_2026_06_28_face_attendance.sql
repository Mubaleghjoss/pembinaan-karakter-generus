-- Jalankan hanya jika deployment tidak dapat memakai `php artisan migrate --force`.

CREATE TABLE IF NOT EXISTS `face_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_type` VARCHAR(20) NOT NULL,
  `subject_id` BIGINT UNSIGNED NOT NULL,
  `descriptor_payload` TEXT NOT NULL,
  `photo_path` VARCHAR(255) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `enrolled_by_user_id` BIGINT UNSIGNED NULL,
  `last_used_at` TIMESTAMP NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `face_profiles_subject_status_index` (`subject_type`, `subject_id`, `status`),
  KEY `face_profiles_status_subject_type_index` (`status`, `subject_type`),
  KEY `face_profiles_enrolled_by_user_id_index` (`enrolled_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`, `group`, `type`, `created_at`, `updated_at`)
VALUES
  ('face_attendance_enabled_siswa', '1', 'face_attendance', 'boolean', NOW(), NOW()),
  ('face_attendance_enabled_pamong', '1', 'face_attendance', 'boolean', NOW(), NOW()),
  ('face_attendance_center_lat', '-6.219501040781815', 'face_attendance', 'string', NOW(), NOW()),
  ('face_attendance_center_lng', '106.64336089878178', 'face_attendance', 'string', NOW(), NOW()),
  ('face_attendance_radius_value', '100', 'face_attendance', 'string', NOW(), NOW()),
  ('face_attendance_radius_unit', 'meter', 'face_attendance', 'string', NOW(), NOW()),
  ('face_attendance_match_threshold', '35.00', 'face_attendance', 'string', NOW(), NOW()),
  ('face_attendance_max_accuracy_meters', '150', 'face_attendance', 'integer', NOW(), NOW()),
  ('popup_face_enrollment_prompt_enabled', '1', 'popup', 'boolean', NOW(), NOW()),
  ('popup_face_enrollment_prompt_required', '1', 'popup', 'boolean', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `value` = VALUES(`value`),
  `group` = VALUES(`group`),
  `type` = VALUES(`type`),
  `updated_at` = NOW();

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_28_090000_create_face_profiles_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_28_090000_create_face_profiles_table'
);
