-- =============================================
-- RPG Quest Tables - Migration SQL
-- Jalankan di phpMyAdmin pada cPanel
-- =============================================

-- 1. Tabel rpg_maps
CREATE TABLE IF NOT EXISTS `rpg_maps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `grid_size` INT NOT NULL DEFAULT 10,
    `background_theme` VARCHAR(255) NOT NULL DEFAULT 'grass',
    `obstacles` JSON NULL COMMENT '[{x:1,y:2}, ...]',
    `enemies` JSON NULL COMMENT '[{x:0,y:0,speed:1,avatar:"👻"}, ...]',
    `difficulty` VARCHAR(20) NOT NULL DEFAULT 'easy' COMMENT 'easy, medium, hard',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel rpg_npcs
CREATE TABLE IF NOT EXISTS `rpg_npcs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `rpg_map_id` BIGINT UNSIGNED NOT NULL,
    `nama` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) NOT NULL DEFAULT '🧙',
    `pos_x` INT NOT NULL,
    `pos_y` INT NOT NULL,
    `pertanyaan` TEXT NOT NULL,
    `pilihan_jawaban` JSON NOT NULL COMMENT '["Jawaban A","Jawaban B","Jawaban C","Jawaban D"]',
    `jawaban_benar` INT NOT NULL DEFAULT 0 COMMENT 'index 0-3',
    `poin` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `rpg_npcs_rpg_map_id_is_active_index` (`rpg_map_id`, `is_active`),
    INDEX `rpg_npcs_pos_x_pos_y_index` (`pos_x`, `pos_y`),
    CONSTRAINT `rpg_npcs_rpg_map_id_foreign` FOREIGN KEY (`rpg_map_id`) REFERENCES `rpg_maps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel rpg_game_sessions
CREATE TABLE IF NOT EXISTS `rpg_game_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `siswa_id` BIGINT UNSIGNED NOT NULL,
    `rpg_map_id` BIGINT UNSIGNED NOT NULL,
    `pos_x` INT NOT NULL DEFAULT 0,
    `pos_y` INT NOT NULL DEFAULT 0,
    `answered_npcs` JSON NULL COMMENT '[1, 3, 5] NPC IDs yang sudah dijawab',
    `total_score` INT NOT NULL DEFAULT 0,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `rpg_game_sessions_siswa_id_rpg_map_id_unique` (`siswa_id`, `rpg_map_id`),
    INDEX `rpg_game_sessions_updated_at_index` (`updated_at`),
    CONSTRAINT `rpg_game_sessions_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `rpg_game_sessions_rpg_map_id_foreign` FOREIGN KEY (`rpg_map_id`) REFERENCES `rpg_maps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel rpg_characters
CREATE TABLE IF NOT EXISTS `rpg_characters` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `siswa_id` BIGINT UNSIGNED NOT NULL,
    `avatar` VARCHAR(255) NOT NULL DEFAULT '🧑‍🎓',
    `nama_karakter` VARCHAR(255) NULL,
    `warna` VARCHAR(255) NOT NULL DEFAULT '#3B82F6',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `rpg_characters_siswa_id_unique` (`siswa_id`),
    CONSTRAINT `rpg_characters_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tambahkan ke tabel migrations (agar Laravel tahu sudah dijalankan)
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2026_03_11_120000_create_rpg_tables', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` AS m));
