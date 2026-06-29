-- Jalankan bila deployment tidak memakai `php artisan migrate --force`.
-- Setting ini sekarang berarti minimal kemiripan wajah dalam persen.

UPDATE `settings`
SET `value` = '35.00',
    `updated_at` = NOW()
WHERE `key` = 'face_attendance_match_threshold'
  AND `value` IN ('0.60', '0.6', '0.85', '4.00', '4');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_29_030000_convert_face_match_threshold_to_similarity_percent', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_29_030000_convert_face_match_threshold_to_similarity_percent'
);
