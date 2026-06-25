-- Manual SQL: skema RPP Materi, Target Materi, dan Jurnal RPP untuk deploy/sync.
-- Jalankan di phpMyAdmin/cPanel jika server belum menjalankan migrasi Laravel terbaru.
-- Aman diulang pada MariaDB/MySQL yang mendukung IF NOT EXISTS.

START TRANSACTION;

-- Folder materi dan kolom RPP pada materi.
CREATE TABLE IF NOT EXISTS `materi_folders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `materi_folders_order_index` (`sort_order`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `materi`
  ADD COLUMN IF NOT EXISTS `materi_folder_id` BIGINT UNSIGNED NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `rpp_is_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `rpp_status` VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER `rpp_is_enabled`,
  ADD COLUMN IF NOT EXISTS `rpp_total_pages` INT UNSIGNED NULL AFTER `rpp_status`,
  ADD COLUMN IF NOT EXISTS `rpp_start_page` INT UNSIGNED NULL AFTER `rpp_total_pages`,
  ADD COLUMN IF NOT EXISTS `rpp_pages_per_session` INT UNSIGNED NULL AFTER `rpp_start_page`,
  ADD COLUMN IF NOT EXISTS `rpp_start_date` DATE NULL AFTER `rpp_pages_per_session`,
  ADD COLUMN IF NOT EXISTS `rpp_start_time` TIME NULL AFTER `rpp_start_date`,
  ADD COLUMN IF NOT EXISTS `rpp_end_time` TIME NULL AFTER `rpp_start_time`,
  ADD COLUMN IF NOT EXISTS `rpp_end_date` DATE NULL AFTER `rpp_end_time`,
  ADD COLUMN IF NOT EXISTS `rpp_extra_sessions` JSON NULL AFTER `rpp_end_date`,
  ADD COLUMN IF NOT EXISTS `rpp_catch_up_ranges` JSON NULL AFTER `rpp_extra_sessions`,
  ADD COLUMN IF NOT EXISTS `rpp_teacher_pool` JSON NULL AFTER `rpp_catch_up_ranges`,
  ADD COLUMN IF NOT EXISTS `rpp_teacher_overrides` JSON NULL AFTER `rpp_teacher_pool`,
  ADD COLUMN IF NOT EXISTS `rpp_published_at` TIMESTAMP NULL DEFAULT NULL AFTER `rpp_teacher_overrides`,
  ADD INDEX IF NOT EXISTS `materi_folder_id_index` (`materi_folder_id`);

UPDATE `materi`
SET `rpp_is_enabled` = 0
WHERE `rpp_is_enabled` IS NULL;

UPDATE `materi`
SET `rpp_status` = 'draft'
WHERE `rpp_status` IS NULL OR `rpp_status` = '';

ALTER TABLE `materi`
  MODIFY `rpp_is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY `rpp_status` VARCHAR(20) NOT NULL DEFAULT 'draft';

-- Kolom sumber event dan penugasan jurnal pada kalender.
ALTER TABLE `schedule_reminders`
  ADD COLUMN IF NOT EXISTS `source_type` VARCHAR(50) NULL AFTER `created_by`,
  ADD COLUMN IF NOT EXISTS `source_id` BIGINT UNSIGNED NULL AFTER `source_type`,
  ADD COLUMN IF NOT EXISTS `source_payload` JSON NULL AFTER `source_id`,
  ADD COLUMN IF NOT EXISTS `journal_assignee_type` VARCHAR(20) NULL AFTER `source_payload`,
  ADD COLUMN IF NOT EXISTS `journal_assignee_user_id` BIGINT UNSIGNED NULL AFTER `journal_assignee_type`,
  ADD COLUMN IF NOT EXISTS `journal_assignee_siswa_id` BIGINT UNSIGNED NULL AFTER `journal_assignee_user_id`,
  ADD INDEX IF NOT EXISTS `schedule_reminders_source_index` (`source_type`, `source_id`),
  ADD INDEX IF NOT EXISTS `schedule_rpp_journal_user_date_index` (`source_type`, `journal_assignee_user_id`, `start_date`),
  ADD INDEX IF NOT EXISTS `schedule_rpp_journal_siswa_date_index` (`source_type`, `journal_assignee_siswa_id`, `start_date`);

-- Target materi dan ceklis selesai.
ALTER TABLE `siswa`
  ADD COLUMN IF NOT EXISTS `target_grade_override` VARCHAR(20) NULL AFTER `kelas_id`,
  ADD INDEX IF NOT EXISTS `siswa_target_grade_override_index` (`target_grade_override`);

CREATE TABLE IF NOT EXISTS `materi_targets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(50) NOT NULL,
  `target_grade` VARCHAR(20) NOT NULL,
  `semester` TINYINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `source_key` VARCHAR(120) NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materi_targets_source_key_unique` (`source_key`),
  KEY `materi_targets_grade_category_active_index` (`target_grade`, `category`, `is_active`),
  KEY `materi_targets_grade_semester_category_active_index` (`target_grade`, `semester`, `category`, `is_active`),
  KEY `materi_targets_semester_index` (`semester`),
  KEY `materi_targets_order_title_index` (`sort_order`, `title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `materi_targets`
  ADD COLUMN IF NOT EXISTS `semester` TINYINT UNSIGNED NULL AFTER `target_grade`,
  ADD COLUMN IF NOT EXISTS `source_key` VARCHAR(120) NULL AFTER `is_active`,
  ADD UNIQUE INDEX IF NOT EXISTS `materi_targets_source_key_unique` (`source_key`),
  ADD INDEX IF NOT EXISTS `materi_targets_semester_index` (`semester`),
  ADD INDEX IF NOT EXISTS `materi_targets_grade_semester_category_active_index` (`target_grade`, `semester`, `category`, `is_active`);

CREATE TABLE IF NOT EXISTS `siswa_materi_target_progress` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `siswa_id` BIGINT UNSIGNED NOT NULL,
  `materi_target_id` BIGINT UNSIGNED NOT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `actor_type` VARCHAR(20) NULL,
  `actor_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `siswa_materi_target_unique` (`siswa_id`, `materi_target_id`),
  KEY `siswa_materi_target_progress_status_index` (`materi_target_id`, `is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jurnal RPP per event kalender.
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
  `workflow_status` VARCHAR(30) NOT NULL DEFAULT 'approved',
  `notes` TEXT NULL,
  `obstacles` TEXT NULL,
  `follow_up` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `submitted_by_siswa_id` BIGINT UNSIGNED NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `review_note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materi_rpp_journals_schedule_reminder_id_unique` (`schedule_reminder_id`),
  KEY `materi_rpp_journals_materi_date_index` (`materi_id`, `journal_date`),
  KEY `materi_rpp_journals_status_date_index` (`realization_status`, `journal_date`),
  KEY `materi_rpp_journals_workflow_date_index` (`workflow_status`, `journal_date`),
  KEY `materi_rpp_journals_teacher_user_index` (`teacher_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `materi_rpp_journals`
  ADD COLUMN IF NOT EXISTS `workflow_status` VARCHAR(30) NOT NULL DEFAULT 'approved' AFTER `realization_status`,
  ADD COLUMN IF NOT EXISTS `submitted_by_siswa_id` BIGINT UNSIGNED NULL AFTER `updated_by`,
  ADD COLUMN IF NOT EXISTS `submitted_at` TIMESTAMP NULL DEFAULT NULL AFTER `submitted_by_siswa_id`,
  ADD COLUMN IF NOT EXISTS `reviewed_by` BIGINT UNSIGNED NULL AFTER `submitted_at`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `reviewed_by`,
  ADD COLUMN IF NOT EXISTS `review_note` TEXT NULL AFTER `reviewed_at`,
  ADD INDEX IF NOT EXISTS `materi_rpp_journals_workflow_date_index` (`workflow_status`, `journal_date`);

UPDATE `materi_rpp_journals`
SET `workflow_status` = 'approved'
WHERE `workflow_status` IS NULL OR `workflow_status` = '';

ALTER TABLE `materi_rpp_journals`
  MODIFY `realization_status` VARCHAR(30) NOT NULL DEFAULT 'terlaksana',
  MODIFY `workflow_status` VARCHAR(30) NOT NULL DEFAULT 'approved';

-- Petugas jurnal lebih dari satu per event.
CREATE TABLE IF NOT EXISTS `materi_rpp_journal_assignees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_reminder_id` BIGINT UNSIGNED NOT NULL,
  `assignee_type` VARCHAR(20) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `siswa_id` BIGINT UNSIGNED NULL,
  `assigned_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rpp_journal_assignee_schedule_user_unique` (`schedule_reminder_id`, `user_id`),
  UNIQUE KEY `rpp_journal_assignee_schedule_siswa_unique` (`schedule_reminder_id`, `siswa_id`),
  KEY `rpp_journal_assignee_type_user_index` (`assignee_type`, `user_id`),
  KEY `rpp_journal_assignee_type_siswa_index` (`assignee_type`, `siswa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `materi_rpp_journal_assignees`
  (`schedule_reminder_id`, `assignee_type`, `user_id`, `siswa_id`, `assigned_by`, `created_at`, `updated_at`)
SELECT `id`, 'user', `journal_assignee_user_id`, NULL, `created_by`, NOW(), NOW()
FROM `schedule_reminders`
WHERE `source_type` = 'materi_rpp'
  AND `journal_assignee_user_id` IS NOT NULL;

INSERT IGNORE INTO `materi_rpp_journal_assignees`
  (`schedule_reminder_id`, `assignee_type`, `user_id`, `siswa_id`, `assigned_by`, `created_at`, `updated_at`)
SELECT `id`, 'siswa', NULL, `journal_assignee_siswa_id`, `created_by`, NOW(), NOW()
FROM `schedule_reminders`
WHERE `source_type` = 'materi_rpp'
  AND `journal_assignee_siswa_id` IS NOT NULL;

COMMIT;
