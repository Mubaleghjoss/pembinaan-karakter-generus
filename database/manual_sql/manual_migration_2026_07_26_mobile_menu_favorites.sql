-- Pendamping manual untuk migration 2026_07_26_020000_add_mobile_menu_favorites_to_users_table.
-- Jalankan hanya bila artisan migrate tidak dapat dipakai pada hosting.

SET @has_mobile_menu_favorites = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'mobile_menu_favorites'
);
SET @sql = IF(
    @has_mobile_menu_favorites = 0,
    'ALTER TABLE users ADD COLUMN mobile_menu_favorites JSON NULL AFTER theme_preference',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT
    '2026_07_26_020000_add_mobile_menu_favorites_to_users_table',
    COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_07_26_020000_add_mobile_menu_favorites_to_users_table'
);
