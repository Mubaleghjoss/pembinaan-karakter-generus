-- =============================================
-- FIX: Tambahkan kolom yang hilang di rpg_maps
-- Jalankan di phpMyAdmin cPanel
-- Aman dijalankan berkali-kali
-- =============================================

-- Cek dan tambahkan kolom 'obstacles' jika belum ada
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpg_maps' AND COLUMN_NAME = 'obstacles');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `rpg_maps` ADD COLUMN `obstacles` JSON NULL AFTER `background_theme`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambahkan kolom 'enemies' jika belum ada
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpg_maps' AND COLUMN_NAME = 'enemies');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `rpg_maps` ADD COLUMN `enemies` JSON NULL AFTER `obstacles`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cek dan tambahkan kolom 'difficulty' jika belum ada
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpg_maps' AND COLUMN_NAME = 'difficulty');
SET @sql = IF(@col_exists = 0, 
    "ALTER TABLE `rpg_maps` ADD COLUMN `difficulty` VARCHAR(20) NOT NULL DEFAULT 'easy' AFTER `enemies`", 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifikasi: tampilkan struktur tabel setelah perubahan
DESCRIBE `rpg_maps`;
