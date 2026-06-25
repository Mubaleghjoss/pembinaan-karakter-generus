-- Manual SQL for cPanel/phpMyAdmin
-- Migrations:
-- 2026_05_09_000001_add_laporan_penyaksian_to_default_pamong_permissions
-- 2026_05_09_000002_add_laporan_penyaksian_to_pkg_pamong_permission_rows
--
-- Purpose:
-- - Add laporan_penyaksian to default pamong permissions.
-- - Add laporan_penyaksian to existing PKG/tracer/tugas pamong permission rows.
-- - Optionally mark both Laravel migrations as executed.
--
-- Safe to re-run on MySQL/MariaDB. Backup database before running.

START TRANSACTION;

SET @db_name := DATABASE();

SET @fallback_menu_json := '["dashboard","materi","calendar","manual_attendance","laporan_penyaksian"]';
SET @fallback_crud_json := '{"materi":["view"],"calendar":["view"],"manual_attendance":["view","create"],"laporan_penyaksian":["view","tindak_lanjut"]}';

-- Update default_pamong_menu_permissions in settings.
SET @menu_raw := (
    SELECT `value`
    FROM `settings`
    WHERE `key` = 'default_pamong_menu_permissions'
    LIMIT 1
);

SET @menu_json := IF(
    JSON_VALID(COALESCE(@menu_raw, '')),
    @menu_raw,
    @fallback_menu_json
);

SET @menu_json := IF(JSON_TYPE(@menu_json) = 'ARRAY', @menu_json, @fallback_menu_json);

SET @menu_json := IF(
    JSON_CONTAINS(@menu_json, JSON_QUOTE('laporan_penyaksian')) = 1,
    @menu_json,
    JSON_ARRAY_APPEND(@menu_json, '$', 'laporan_penyaksian')
);

SET @has_settings_type := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'settings'
      AND COLUMN_NAME = 'type'
);

SET @has_settings_group := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'settings'
      AND COLUMN_NAME = 'group'
);

SET @sql := CASE
    WHEN @has_settings_type > 0 AND @has_settings_group > 0 THEN
        'INSERT INTO `settings` (`key`, `value`, `type`, `group`, `created_at`, `updated_at`)
         VALUES (''default_pamong_menu_permissions'', @menu_json, ''json'', ''permissions'', NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @menu_json, `type` = ''json'', `group` = ''permissions'', `updated_at` = NOW()'
    WHEN @has_settings_type > 0 THEN
        'INSERT INTO `settings` (`key`, `value`, `type`, `created_at`, `updated_at`)
         VALUES (''default_pamong_menu_permissions'', @menu_json, ''json'', NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @menu_json, `type` = ''json'', `updated_at` = NOW()'
    WHEN @has_settings_group > 0 THEN
        'INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
         VALUES (''default_pamong_menu_permissions'', @menu_json, ''permissions'', NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @menu_json, `group` = ''permissions'', `updated_at` = NOW()'
    ELSE
        'INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`)
         VALUES (''default_pamong_menu_permissions'', @menu_json, NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @menu_json, `updated_at` = NOW()'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update default_pamong_crud_permissions in settings.
SET @crud_raw := (
    SELECT `value`
    FROM `settings`
    WHERE `key` = 'default_pamong_crud_permissions'
    LIMIT 1
);

SET @crud_json := IF(
    JSON_VALID(COALESCE(@crud_raw, '')),
    @crud_raw,
    @fallback_crud_json
);

SET @crud_json := IF(JSON_TYPE(@crud_json) = 'OBJECT', @crud_json, @fallback_crud_json);
SET @crud_json := JSON_SET(@crud_json, '$.laporan_penyaksian', JSON_ARRAY('view', 'tindak_lanjut'));

SET @sql := CASE
    WHEN @has_settings_type > 0 AND @has_settings_group > 0 THEN
        'INSERT INTO `settings` (`key`, `value`, `type`, `group`, `created_at`, `updated_at`)
         VALUES (''default_pamong_crud_permissions'', @crud_json, ''json'', ''permissions'', NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @crud_json, `type` = ''json'', `group` = ''permissions'', `updated_at` = NOW()'
    WHEN @has_settings_type > 0 THEN
        'INSERT INTO `settings` (`key`, `value`, `type`, `created_at`, `updated_at`)
         VALUES (''default_pamong_crud_permissions'', @crud_json, ''json'', NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @crud_json, `type` = ''json'', `updated_at` = NOW()'
    WHEN @has_settings_group > 0 THEN
        'INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`)
         VALUES (''default_pamong_crud_permissions'', @crud_json, ''permissions'', NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @crud_json, `group` = ''permissions'', `updated_at` = NOW()'
    ELSE
        'INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`)
         VALUES (''default_pamong_crud_permissions'', @crud_json, NOW(), NOW())
         ON DUPLICATE KEY UPDATE `value` = @crud_json, `updated_at` = NOW()'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add laporan_penyaksian to existing restricted pamong permission rows when the row
-- already has a PKG-related menu or an old default menu set.
UPDATE `pamong_permissions`
SET
    `menu_permissions` = JSON_ARRAY_APPEND(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), '$', 'laporan_penyaksian'),
    `crud_permissions` = JSON_SET(
        IF(
            JSON_VALID(COALESCE(NULLIF(`crud_permissions`, ''), '{}'))
                AND JSON_TYPE(COALESCE(NULLIF(`crud_permissions`, ''), '{}')) = 'OBJECT',
            COALESCE(NULLIF(`crud_permissions`, ''), '{}'),
            '{}'
        ),
        '$.laporan_penyaksian',
        JSON_ARRAY('view', 'tindak_lanjut')
    ),
    `updated_at` = NOW()
WHERE `is_excluded` = 0
  AND JSON_VALID(COALESCE(NULLIF(`menu_permissions`, ''), '[]'))
  AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('laporan_penyaksian')) = 0
  AND (
      JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('pr')) = 1
      OR JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('tracer_karakter')) = 1
      OR JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('tugas_pkg')) = 1
      OR (
          JSON_LENGTH(COALESCE(NULLIF(`menu_permissions`, ''), '[]')) = 3
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('dashboard')) = 1
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('materi')) = 1
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('calendar')) = 1
      )
      OR (
          JSON_LENGTH(COALESCE(NULLIF(`menu_permissions`, ''), '[]')) = 4
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('dashboard')) = 1
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('materi')) = 1
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('calendar')) = 1
          AND JSON_CONTAINS(COALESCE(NULLIF(`menu_permissions`, ''), '[]'), JSON_QUOTE('manual_attendance')) = 1
      )
  );

COMMIT;

-- Mark both Laravel migrations as executed.
-- Run this section only if the migrations table exists.

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
     SELECT ''2026_05_09_000001_add_laporan_penyaksian_to_default_pamong_permissions'', @next_batch
     WHERE NOT EXISTS (
         SELECT 1
         FROM `migrations`
         WHERE `migration` = ''2026_05_09_000001_add_laporan_penyaksian_to_default_pamong_permissions''
     )',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_migrations_table > 0,
    'INSERT INTO `migrations` (`migration`, `batch`)
     SELECT ''2026_05_09_000002_add_laporan_penyaksian_to_pkg_pamong_permission_rows'', @next_batch
     WHERE NOT EXISTS (
         SELECT 1
         FROM `migrations`
         WHERE `migration` = ''2026_05_09_000002_add_laporan_penyaksian_to_pkg_pamong_permission_rows''
     )',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
