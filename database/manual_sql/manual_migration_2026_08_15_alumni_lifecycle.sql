-- Pendamping migration 2026_08_15_000000_add_alumni_lifecycle_to_students.php
-- Jalankan hanya bila deployment tidak dapat menjalankan `php artisan migrate --force`.

ALTER TABLE `siswa`
  ADD COLUMN `graduated_at` TIMESTAMP NULL AFTER `status`,
  ADD COLUMN `alumni_can_submit` TINYINT(1) NOT NULL DEFAULT 1 AFTER `graduated_at`,
  ADD COLUMN `alumni_reviewer_id` BIGINT UNSIGNED NULL AFTER `alumni_can_submit`,
  ADD INDEX `siswa_graduated_at_index` (`graduated_at`),
  ADD INDEX `siswa_alumni_reviewer_id_index` (`alumni_reviewer_id`),
  ADD CONSTRAINT `siswa_alumni_reviewer_id_foreign`
    FOREIGN KEY (`alumni_reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `pamong_siswa`
  ADD COLUMN `ended_at` TIMESTAMP NULL AFTER `created_at`,
  ADD COLUMN `ended_by` BIGINT UNSIGNED NULL AFTER `ended_at`,
  ADD INDEX `pamong_siswa_ended_at_index` (`ended_at`),
  ADD INDEX `pamong_siswa_ended_by_index` (`ended_by`),
  ADD CONSTRAINT `pamong_siswa_ended_by_foreign`
    FOREIGN KEY (`ended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
