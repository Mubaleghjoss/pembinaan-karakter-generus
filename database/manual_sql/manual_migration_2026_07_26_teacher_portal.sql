-- Pendamping manual untuk migration 2026_07_26_010000_create_teacher_portal_tables.
-- Jalankan hanya bila artisan migrate tidak dapat dipakai pada hosting.

INSERT INTO roles (name, display_name, description, permissions, is_active, created_at, updated_at)
VALUES ('guru', 'Guru', 'Guru PKG dengan akses Portal Guru', '[]', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    description = VALUES(description),
    permissions = VALUES(permissions),
    is_active = 1,
    updated_at = NOW();

SET @has_must_change_password = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'must_change_password'
);
SET @sql = IF(
    @has_must_change_password = 0,
    'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_password_changed_at = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_changed_at'
);
SET @sql = IF(
    @has_password_changed_at = 0,
    'ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL AFTER must_change_password',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS teacher_materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    google_drive_url VARCHAR(1000) NOT NULL,
    rombels JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT teacher_materials_created_by_fk
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX teacher_materials_is_active_index (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_material_session (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_material_id BIGINT UNSIGNED NOT NULL,
    teacher_schedule_session_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT teacher_material_session_material_fk
        FOREIGN KEY (teacher_material_id) REFERENCES teacher_materials(id) ON DELETE CASCADE,
    CONSTRAINT teacher_material_session_session_fk
        FOREIGN KEY (teacher_schedule_session_id) REFERENCES teacher_schedule_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY teacher_material_session_unique (teacher_material_id, teacher_schedule_session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (migration, batch)
SELECT
    '2026_07_26_010000_create_teacher_portal_tables',
    COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_07_26_010000_create_teacher_portal_tables'
);
