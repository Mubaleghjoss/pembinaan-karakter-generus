CREATE TABLE IF NOT EXISTS `teacher_schedule_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `teacher_profile_id` BIGINT UNSIGNED NOT NULL,
  `request_type` VARCHAR(20) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `admin_note` TEXT NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_schedule_requests_status_index` (`status`),
  KEY `teacher_schedule_request_teacher_status` (`teacher_profile_id`, `status`),
  CONSTRAINT `teacher_schedule_requests_assignment_id_foreign`
    FOREIGN KEY (`assignment_id`) REFERENCES `teacher_schedule_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_schedule_requests_teacher_profile_id_foreign`
    FOREIGN KEY (`teacher_profile_id`) REFERENCES `teacher_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_schedule_requests_resolved_by_foreign`
    FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
