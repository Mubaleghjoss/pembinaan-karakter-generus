-- Manual SQL: tanggal tampil kalender untuk materi biasa.
-- Jalankan di phpMyAdmin/cPanel jika migrasi Laravel belum bisa dijalankan.
-- Aman diulang pada MariaDB/MySQL yang mendukung IF NOT EXISTS.

START TRANSACTION;

ALTER TABLE `materi`
  ADD COLUMN IF NOT EXISTS `calendar_date` DATE NULL AFTER `bulan`,
  ADD INDEX IF NOT EXISTS `materi_calendar_date_index` (`calendar_date`);

COMMIT;
