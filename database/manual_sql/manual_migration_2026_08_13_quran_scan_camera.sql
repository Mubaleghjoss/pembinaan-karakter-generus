ALTER TABLE `quran_reading_sheets`
  ADD COLUMN `template_version` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `row_count`;

ALTER TABLE `quran_reading_scans`
  MODIFY `uploaded_by_id` BIGINT UNSIGNED NULL;

ALTER TABLE `quran_reading_entries`
  MODIFY `submitted_by_id` BIGINT UNSIGNED NULL;
