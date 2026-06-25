-- Manual SQL for cPanel/phpMyAdmin
-- Migration:
-- 2026_04_29_000010_add_date_range_to_attendance_schedules_table
--
-- Purpose:
-- - Add attendance_schedules.start_date
-- - Add attendance_schedules.end_date
-- - Set existing attendance schedule rows to 2026-04-23
-- - Optionally mark the Laravel migration as executed
--
-- Safe to re-run on MySQL/MariaDB.

START TRANSACTION;

SET @db_name := DATABASE();

-- Add start_date if missing.
-- If target_audience already exists, place start_date after it.
-- If target_audience is not on the server yet, place start_date after days so this SQL still runs.
SET @has_target_audience := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'attendance_schedules'
      AND COLUMN_NAME = 'target_audience'
);

SET @has_start_date := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'attendance_schedules'
      AND COLUMN_NAME = 'start_date'
);

SET @sql := IF(
    @has_start_date = 0,
    IF(
        @has_target_audience > 0,
        'ALTER TABLE `attendance_schedules` ADD COLUMN `start_date` DATE NULL AFTER `target_audience`',
        'ALTER TABLE `attendance_schedules` ADD COLUMN `start_date` DATE NULL AFTER `days`'
    ),
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add end_date if missing.
SET @has_end_date := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'attendance_schedules'
      AND COLUMN_NAME = 'end_date'
);

SET @sql := IF(
    @has_end_date = 0,
    'ALTER TABLE `attendance_schedules` ADD COLUMN `end_date` DATE NULL AFTER `start_date`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Match the Laravel migration behavior:
-- existing rows without date range become active only on 23 April 2026.
UPDATE `attendance_schedules`
SET
    `start_date` = '2026-04-23',
    `end_date` = '2026-04-23',
    `updated_at` = NOW()
WHERE `start_date` IS NULL
  AND `end_date` IS NULL;

COMMIT;

-- Mark this Laravel migration as executed.
-- Run this section only if the `migrations` table exists.

SET @has_migrations_table := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'migrations'
);

SET @sql := IF(
    @has_migrations_table > 0,
    'SET @next_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_migrations_table > 0,
    'INSERT INTO `migrations` (`migration`, `batch`)
     SELECT ''2026_04_29_000010_add_date_range_to_attendance_schedules_table'', @next_batch
     WHERE NOT EXISTS (
         SELECT 1
         FROM `migrations`
         WHERE `migration` = ''2026_04_29_000010_add_date_range_to_attendance_schedules_table''
     )',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
