-- Manual SQL: banyak link video untuk materi.
-- Jalankan di phpMyAdmin/cPanel jika migrasi Laravel belum bisa dijalankan.
-- Aman diulang pada MariaDB/MySQL yang mendukung IF NOT EXISTS.

START TRANSACTION;

ALTER TABLE `materi`
  ADD COLUMN IF NOT EXISTS `video_links` LONGTEXT NULL AFTER `video_url`;

UPDATE `materi`
SET `video_links` = JSON_ARRAY(`video_url`)
WHERE `video_url` IS NOT NULL
  AND TRIM(`video_url`) <> ''
  AND (`video_links` IS NULL OR TRIM(`video_links`) = '' OR TRIM(`video_links`) = '[]');

COMMIT;
