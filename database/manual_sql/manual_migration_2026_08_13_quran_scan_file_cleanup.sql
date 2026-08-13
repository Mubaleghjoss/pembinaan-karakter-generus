ALTER TABLE `quran_reading_scans`
  MODIFY `original_path` varchar(255) NULL,
  MODIFY `processed_path` varchar(255) NULL,
  ADD COLUMN `files_purged_at` timestamp NULL AFTER `confirmed_at`,
  ADD INDEX `quran_reading_scans_files_purged_at_index` (`files_purged_at`);
