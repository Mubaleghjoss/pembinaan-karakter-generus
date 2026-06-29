-- Jalankan setelah manual_migration_2026_06_28_face_attendance.sql bila tidak memakai `php artisan migrate --force`.

UPDATE `settings`
SET `value` = '0.85',
    `updated_at` = NOW()
WHERE `key` = 'face_attendance_match_threshold'
  AND `value` IN ('0.60', '0.6');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_29_010000_relax_face_attendance_match_threshold', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_29_010000_relax_face_attendance_match_threshold'
);
