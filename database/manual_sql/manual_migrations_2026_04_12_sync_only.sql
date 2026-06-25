-- Manual SQL for cPanel/phpMyAdmin
-- Follow-up sync after manual_migrations_2026_04_09_to_2026_04_11.sql
-- Audit date: 2026-04-12
--
-- Important:
-- 1. After checking database/migrations in this repo, there are NO new schema migrations
--    after 2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks.php.
-- 2. This file does NOT add new tables/columns.
-- 3. Use this only if:
--    - struktur tabel/kolom sudah masuk manual SQL sebelumnya, tetapi
--    - tabel `migrations` di server belum mencatat migration 2026-04-12.
--
-- Aman dijalankan berulang karena semua INSERT memakai NOT EXISTS.

START TRANSACTION;

SET @next_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_12_001000_add_photo_proof_support_to_karakter_tasks', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_12_001000_add_photo_proof_support_to_karakter_tasks'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_12_030000_add_voice_note_proof_support_to_karakter_tasks', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_12_030000_add_voice_note_proof_support_to_karakter_tasks'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks', @next_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks'
);

COMMIT;

-- Optional verification:
-- SELECT `migration`, `batch`
-- FROM `migrations`
-- WHERE `migration` IN (
--   '2026_04_12_001000_add_photo_proof_support_to_karakter_tasks',
--   '2026_04_12_030000_add_voice_note_proof_support_to_karakter_tasks',
--   '2026_04_12_040000_add_proof_requirements_and_voice_limit_to_karakter_tasks'
-- )
-- ORDER BY `migration`;
