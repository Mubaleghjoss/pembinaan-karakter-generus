-- Manual migration: Jurnal RPP Materi Kalender
-- Jalankan di database server jika tidak memakai `php artisan migrate`.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `materi_rpp_journals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_reminder_id` BIGINT UNSIGNED NULL,
  `materi_id` BIGINT UNSIGNED NULL,
  `journal_date` DATE NOT NULL,
  `session_number` INT UNSIGNED NULL,
  `session_type` VARCHAR(30) NULL,
  `materi_title` VARCHAR(255) NULL,
  `target_page_range` VARCHAR(60) NULL,
  `target_page_start` INT UNSIGNED NULL,
  `target_page_end` INT UNSIGNED NULL,
  `actual_page_start` INT UNSIGNED NULL,
  `actual_page_end` INT UNSIGNED NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `teacher_name` VARCHAR(255) NULL,
  `teacher_user_id` BIGINT UNSIGNED NULL,
  `realization_status` VARCHAR(30) NOT NULL DEFAULT 'terlaksana',
  `notes` TEXT NULL,
  `obstacles` TEXT NULL,
  `follow_up` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materi_rpp_journals_schedule_reminder_id_unique` (`schedule_reminder_id`),
  KEY `materi_rpp_journals_materi_date_index` (`materi_id`, `journal_date`),
  KEY `materi_rpp_journals_status_date_index` (`realization_status`, `journal_date`),
  KEY `materi_rpp_journals_teacher_user_index` (`teacher_user_id`),
  KEY `materi_rpp_journals_created_by_index` (`created_by`),
  KEY `materi_rpp_journals_updated_by_index` (`updated_by`),
  CONSTRAINT `materi_rpp_journals_schedule_reminder_id_foreign`
    FOREIGN KEY (`schedule_reminder_id`) REFERENCES `schedule_reminders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `materi_rpp_journals_materi_id_foreign`
    FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `materi_rpp_journals_created_by_foreign`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `materi_rpp_journals_updated_by_foreign`
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
