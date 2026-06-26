-- Manual SQL: struktur folder utama Materi PKG.
-- Jalankan di phpMyAdmin/cPanel jika migrasi Laravel belum bisa dijalankan.
-- Aman diulang pada MariaDB/MySQL yang mendukung IF NOT EXISTS.

START TRANSACTION;

ALTER TABLE `materi_folders`
  ADD COLUMN IF NOT EXISTS `parent_id` BIGINT UNSIGNED NULL AFTER `id`,
  ADD INDEX IF NOT EXISTS `materi_folders_parent_order_index` (`parent_id`, `sort_order`, `name`);

CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_materi_main_folders` (
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL
);

TRUNCATE TABLE `tmp_materi_main_folders`;

INSERT INTO `tmp_materi_main_folders` (`name`, `description`, `sort_order`) VALUES
('PKG', 'Materi 29 karakter luhur.', 1),
('PPG', 'Folder materi PPG yang diisi manual oleh admin.', 2),
('RPP Target Generus SMP SMA', 'Folder RPP target generus SMP dan SMA yang diisi manual oleh admin.', 3);

INSERT INTO `materi_folders` (`name`, `parent_id`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT t.`name`, NULL, t.`description`, t.`sort_order`, 1, NOW(), NOW()
FROM `tmp_materi_main_folders` t
WHERE NOT EXISTS (
  SELECT 1 FROM `materi_folders` mf WHERE mf.`name` = t.`name`
);

UPDATE `materi_folders` mf
JOIN `tmp_materi_main_folders` t ON t.`name` = mf.`name`
SET mf.`parent_id` = NULL,
    mf.`description` = COALESCE(NULLIF(mf.`description`, ''), t.`description`),
    mf.`sort_order` = t.`sort_order`,
    mf.`is_active` = 1,
    mf.`updated_at` = NOW();

SET @pkg_folder_id := (SELECT `id` FROM `materi_folders` WHERE `name` = 'PKG' ORDER BY `id` LIMIT 1);

CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_pkg_character_folders` (
  `sort_order` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL
);

TRUNCATE TABLE `tmp_pkg_character_folders`;

INSERT INTO `tmp_pkg_character_folders` (`sort_order`, `name`) VALUES
(1, 'Akhlaqul Karimah'),
(2, 'Alim Faqih'),
(3, 'Mandiri'),
(4, 'Rukun'),
(5, 'Kompak'),
(6, 'Kerjasama yang baik'),
(7, 'Jujur'),
(8, 'Amanah'),
(9, 'Mujhid Muzhid'),
(10, 'Bersyukur'),
(11, 'Mempersungguh'),
(12, 'Mengagungkan'),
(13, 'Berdoa'),
(14, 'Benar'),
(15, 'Kurup'),
(16, 'Janji'),
(17, 'Syukur atas nikmat'),
(18, 'Istirja'' saat musibah'),
(19, 'Sabar dalam cobaab'),
(20, 'Bertaubat atas kesalahan'),
(21, 'Yang kuat membantu yang lemah'),
(22, 'Yang bisa membantu yang belum bisa'),
(23, 'Yang ingat mengingatkan yang lupa'),
(24, 'Yang salah dinasehati agar bertaubat'),
(25, 'Bicara yang baik dan benar'),
(26, 'Jujur dan saling percaya'),
(27, 'sabar dan keporo ngalah'),
(28, 'Tidak menyakiti / merusak sesama'),
(29, 'Saling memperhatikan & menjaga perasaan');

INSERT INTO `materi_folders` (`name`, `parent_id`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT t.`name`, @pkg_folder_id, NULL, t.`sort_order`, 1, NOW(), NOW()
FROM `tmp_pkg_character_folders` t
WHERE @pkg_folder_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `materi_folders` mf WHERE mf.`name` = t.`name` AND mf.`id` <> @pkg_folder_id
  );

UPDATE `materi_folders` mf
JOIN `tmp_pkg_character_folders` t ON t.`name` = mf.`name`
SET mf.`parent_id` = @pkg_folder_id,
    mf.`sort_order` = t.`sort_order`,
    mf.`is_active` = 1,
    mf.`updated_at` = NOW()
WHERE @pkg_folder_id IS NOT NULL
  AND mf.`id` <> @pkg_folder_id;

DROP TEMPORARY TABLE IF EXISTS `tmp_pkg_character_folders`;
DROP TEMPORARY TABLE IF EXISTS `tmp_materi_main_folders`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_26_120000_add_parent_to_materi_folders_and_seed_main_groups',
       COALESCE((SELECT MAX(`batch`) + 1 FROM (SELECT `batch` FROM `migrations`) AS batches), 1)
WHERE EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'migrations'
)
AND NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_06_26_120000_add_parent_to_materi_folders_and_seed_main_groups'
);

COMMIT;
