-- Manual fix: bersihkan referensi yatim sebelum menambahkan foreign key Jurnal RPP.
-- Pakai saat import phpMyAdmin berhenti pada error:
-- #1452 Cannot add or update a child row ... materi_rpp_journals_schedule_reminder_id_foreign
--
-- Penyebab: ada baris di materi_rpp_journals / assignees yang menunjuk ID parent
-- yang tidak ada lagi di schedule_reminders, materi, users, atau siswa.

START TRANSACTION;

-- Jurnal tetap dipertahankan, hanya link parent yang tidak valid dibuat NULL.
UPDATE `materi_rpp_journals` AS `j`
LEFT JOIN `schedule_reminders` AS `s` ON `s`.`id` = `j`.`schedule_reminder_id`
SET `j`.`schedule_reminder_id` = NULL
WHERE `j`.`schedule_reminder_id` IS NOT NULL
  AND `s`.`id` IS NULL;

UPDATE `materi_rpp_journals` AS `j`
LEFT JOIN `materi` AS `m` ON `m`.`id` = `j`.`materi_id`
SET `j`.`materi_id` = NULL
WHERE `j`.`materi_id` IS NOT NULL
  AND `m`.`id` IS NULL;

UPDATE `materi_rpp_journals` AS `j`
LEFT JOIN `users` AS `u` ON `u`.`id` = `j`.`created_by`
SET `j`.`created_by` = NULL
WHERE `j`.`created_by` IS NOT NULL
  AND `u`.`id` IS NULL;

UPDATE `materi_rpp_journals` AS `j`
LEFT JOIN `users` AS `u` ON `u`.`id` = `j`.`updated_by`
SET `j`.`updated_by` = NULL
WHERE `j`.`updated_by` IS NOT NULL
  AND `u`.`id` IS NULL;

UPDATE `materi_rpp_journals` AS `j`
LEFT JOIN `users` AS `u` ON `u`.`id` = `j`.`reviewed_by`
SET `j`.`reviewed_by` = NULL
WHERE `j`.`reviewed_by` IS NOT NULL
  AND `u`.`id` IS NULL;

UPDATE `materi_rpp_journals` AS `j`
LEFT JOIN `siswa` AS `s` ON `s`.`id` = `j`.`submitted_by_siswa_id`
SET `j`.`submitted_by_siswa_id` = NULL
WHERE `j`.`submitted_by_siswa_id` IS NOT NULL
  AND `s`.`id` IS NULL;

-- Penugasan lama di schedule_reminders: jika target sudah tidak ada, kosongkan.
UPDATE `schedule_reminders` AS `sr`
LEFT JOIN `users` AS `u` ON `u`.`id` = `sr`.`journal_assignee_user_id`
SET
  `sr`.`journal_assignee_user_id` = NULL,
  `sr`.`journal_assignee_type` = CASE
    WHEN `sr`.`journal_assignee_type` = 'user' THEN NULL
    ELSE `sr`.`journal_assignee_type`
  END
WHERE `sr`.`journal_assignee_user_id` IS NOT NULL
  AND `u`.`id` IS NULL;

UPDATE `schedule_reminders` AS `sr`
LEFT JOIN `siswa` AS `s` ON `s`.`id` = `sr`.`journal_assignee_siswa_id`
SET
  `sr`.`journal_assignee_siswa_id` = NULL,
  `sr`.`journal_assignee_type` = CASE
    WHEN `sr`.`journal_assignee_type` = 'siswa' THEN NULL
    ELSE `sr`.`journal_assignee_type`
  END
WHERE `sr`.`journal_assignee_siswa_id` IS NOT NULL
  AND `s`.`id` IS NULL;

-- Penugasan banyak petugas: baris tanpa event/target valid tidak berguna, jadi hapus.
DELETE `a`
FROM `materi_rpp_journal_assignees` AS `a`
LEFT JOIN `schedule_reminders` AS `sr` ON `sr`.`id` = `a`.`schedule_reminder_id`
WHERE `sr`.`id` IS NULL;

DELETE `a`
FROM `materi_rpp_journal_assignees` AS `a`
LEFT JOIN `users` AS `u` ON `u`.`id` = `a`.`user_id`
WHERE `a`.`user_id` IS NOT NULL
  AND `u`.`id` IS NULL;

DELETE `a`
FROM `materi_rpp_journal_assignees` AS `a`
LEFT JOIN `siswa` AS `s` ON `s`.`id` = `a`.`siswa_id`
WHERE `a`.`siswa_id` IS NOT NULL
  AND `s`.`id` IS NULL;

UPDATE `materi_rpp_journal_assignees` AS `a`
LEFT JOIN `users` AS `u` ON `u`.`id` = `a`.`assigned_by`
SET `a`.`assigned_by` = NULL
WHERE `a`.`assigned_by` IS NOT NULL
  AND `u`.`id` IS NULL;

COMMIT;

-- Pastikan index child tersedia sebelum foreign key ditambahkan.
ALTER TABLE `materi_rpp_journals`
  ADD INDEX IF NOT EXISTS `materi_rpp_journals_created_by_index` (`created_by`),
  ADD INDEX IF NOT EXISTS `materi_rpp_journals_updated_by_index` (`updated_by`),
  ADD INDEX IF NOT EXISTS `materi_rpp_journals_reviewed_by_index` (`reviewed_by`),
  ADD INDEX IF NOT EXISTS `materi_rpp_journals_submitted_by_siswa_id_index` (`submitted_by_siswa_id`);

ALTER TABLE `schedule_reminders`
  ADD INDEX IF NOT EXISTS `schedule_reminders_journal_assignee_user_id_index` (`journal_assignee_user_id`),
  ADD INDEX IF NOT EXISTS `schedule_reminders_journal_assignee_siswa_id_index` (`journal_assignee_siswa_id`);

ALTER TABLE `materi_rpp_journal_assignees`
  ADD INDEX IF NOT EXISTS `rpp_journal_assignee_user_id_index` (`user_id`),
  ADD INDEX IF NOT EXISTS `rpp_journal_assignee_siswa_id_index` (`siswa_id`),
  ADD INDEX IF NOT EXISTS `rpp_journal_assignee_assigned_by_index` (`assigned_by`);

-- Tambahkan foreign key jika belum ada. Satu constraint per ALTER agar mudah dilacak.
DROP PROCEDURE IF EXISTS `pkg_add_fk_if_missing`;

DELIMITER //
CREATE PROCEDURE `pkg_add_fk_if_missing`(
  IN p_table_name VARCHAR(64),
  IN p_constraint_name VARCHAR(64),
  IN p_ddl TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = p_table_name
      AND `CONSTRAINT_NAME` = p_constraint_name
      AND `CONSTRAINT_TYPE` = 'FOREIGN KEY'
  ) THEN
    SET @pkg_fk_ddl = p_ddl;
    PREPARE pkg_fk_stmt FROM @pkg_fk_ddl;
    EXECUTE pkg_fk_stmt;
    DEALLOCATE PREPARE pkg_fk_stmt;
  END IF;
END//
DELIMITER ;

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journals',
  'materi_rpp_journals_schedule_reminder_id_foreign',
  'ALTER TABLE `materi_rpp_journals` ADD CONSTRAINT `materi_rpp_journals_schedule_reminder_id_foreign` FOREIGN KEY (`schedule_reminder_id`) REFERENCES `schedule_reminders` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journals',
  'materi_rpp_journals_materi_id_foreign',
  'ALTER TABLE `materi_rpp_journals` ADD CONSTRAINT `materi_rpp_journals_materi_id_foreign` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journals',
  'materi_rpp_journals_created_by_foreign',
  'ALTER TABLE `materi_rpp_journals` ADD CONSTRAINT `materi_rpp_journals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journals',
  'materi_rpp_journals_updated_by_foreign',
  'ALTER TABLE `materi_rpp_journals` ADD CONSTRAINT `materi_rpp_journals_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journals',
  'materi_rpp_journals_reviewed_by_foreign',
  'ALTER TABLE `materi_rpp_journals` ADD CONSTRAINT `materi_rpp_journals_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journals',
  'materi_rpp_journals_submitted_by_siswa_id_foreign',
  'ALTER TABLE `materi_rpp_journals` ADD CONSTRAINT `materi_rpp_journals_submitted_by_siswa_id_foreign` FOREIGN KEY (`submitted_by_siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'schedule_reminders',
  'schedule_reminders_journal_assignee_user_id_foreign',
  'ALTER TABLE `schedule_reminders` ADD CONSTRAINT `schedule_reminders_journal_assignee_user_id_foreign` FOREIGN KEY (`journal_assignee_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'schedule_reminders',
  'schedule_reminders_journal_assignee_siswa_id_foreign',
  'ALTER TABLE `schedule_reminders` ADD CONSTRAINT `schedule_reminders_journal_assignee_siswa_id_foreign` FOREIGN KEY (`journal_assignee_siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journal_assignees',
  'materi_rpp_journal_assignees_schedule_reminder_id_foreign',
  'ALTER TABLE `materi_rpp_journal_assignees` ADD CONSTRAINT `materi_rpp_journal_assignees_schedule_reminder_id_foreign` FOREIGN KEY (`schedule_reminder_id`) REFERENCES `schedule_reminders` (`id`) ON DELETE CASCADE'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journal_assignees',
  'materi_rpp_journal_assignees_user_id_foreign',
  'ALTER TABLE `materi_rpp_journal_assignees` ADD CONSTRAINT `materi_rpp_journal_assignees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journal_assignees',
  'materi_rpp_journal_assignees_siswa_id_foreign',
  'ALTER TABLE `materi_rpp_journal_assignees` ADD CONSTRAINT `materi_rpp_journal_assignees_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE'
);

CALL `pkg_add_fk_if_missing`(
  'materi_rpp_journal_assignees',
  'materi_rpp_journal_assignees_assigned_by_foreign',
  'ALTER TABLE `materi_rpp_journal_assignees` ADD CONSTRAINT `materi_rpp_journal_assignees_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL'
);

DROP PROCEDURE IF EXISTS `pkg_add_fk_if_missing`;
