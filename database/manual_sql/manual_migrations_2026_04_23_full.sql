-- Manual SQL for cPanel/phpMyAdmin
-- Period: 2026-04-23
-- This file covers migrations created on 2026-04-23:
-- - 2026_04_23_093500_clear_plain_password_columns
-- - 2026_04_23_181000_add_target_audience_to_attendance_schedules_table
--
-- Safe section below adds target_audience for attendance schedules.
-- The plain-password cleanup migration is intentionally placed as an optional block at the bottom
-- because it deletes saved admin-visible passwords and cannot be undone.
--
-- Safe enough for repeated use on MySQL/MariaDB that support IF NOT EXISTS.
-- If your server rejects one ALTER because the column already exists, skip that line and continue.

START TRANSACTION;

-- 1. Attendance schedule target audience
ALTER TABLE `attendance_schedules`
    ADD COLUMN IF NOT EXISTS `target_audience` VARCHAR(20) NOT NULL DEFAULT 'all' AFTER `days`;

UPDATE `attendance_schedules`
SET `target_audience` = 'all'
WHERE `target_audience` IS NULL OR `target_audience` = '';

COMMIT;

-- Optional: mark the safe migration as executed in Laravel migrations table.
-- Run this block only if table `migrations` exists and you want Laravel records kept in sync.

SET @next_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_23_181000_add_target_audience_to_attendance_schedules_table', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_23_181000_add_target_audience_to_attendance_schedules_table'
);

-- ---------------------------------------------------------------------------
-- OPTIONAL / DESTRUCTIVE CLEANUP
-- ---------------------------------------------------------------------------
-- Only run this block if you intentionally want to clear all saved plaintext
-- passwords from users, siswa, and ortu columns. This is irreversible.
--
-- Important for current app behavior:
-- The admin "Kelola Akun" password display depends on users.plain_password
-- after an admin creates/resets/changes a password. Running this cleanup will
-- make existing displayed passwords become "Tidak tersimpan" until reset/changed again.
--
-- To run it, remove the leading "-- " from the SQL statements below.

-- START TRANSACTION;

-- UPDATE `users`
-- SET `plain_password` = NULL
-- WHERE `plain_password` IS NOT NULL;

-- UPDATE `siswa`
-- SET `password_plain` = NULL
-- WHERE `password_plain` IS NOT NULL;

-- UPDATE `siswa`
-- SET `ortu_password_plain` = NULL
-- WHERE `ortu_password_plain` IS NOT NULL;

-- COMMIT;

-- SET @next_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1;

-- INSERT INTO `migrations` (`migration`, `batch`)
-- SELECT '2026_04_23_093500_clear_plain_password_columns', @next_batch
-- WHERE NOT EXISTS (
--     SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_23_093500_clear_plain_password_columns'
-- );
