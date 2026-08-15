-- Pengalihan kelas operasional ke kelas sekolah.
-- Jalankan hanya jika php artisan migrate tidak tersedia.

ALTER TABLE `siswa`
    ADD COLUMN `school_grade` VARCHAR(20) NULL AFTER `kelas_id`,
    ADD INDEX `siswa_school_grade_index` (`school_grade`);

UPDATE `siswa`
SET `school_grade` = `target_grade_override`
WHERE `school_grade` IS NULL
  AND `target_grade_override` IN ('smp_7','smp_8','smp_9','sma_10','sma_11','sma_12','pranikah');

UPDATE `siswa` AS s
JOIN (
    SELECT gr.siswa_id, gr.school_grade
    FROM generus_registrations AS gr
    JOIN (
        SELECT siswa_id, MAX(submitted_at) AS latest_submitted_at
        FROM generus_registrations
        WHERE siswa_id IS NOT NULL
        GROUP BY siswa_id
    ) latest ON latest.siswa_id = gr.siswa_id AND latest.latest_submitted_at = gr.submitted_at
) AS registration_grade ON registration_grade.siswa_id = s.id
SET s.school_grade = registration_grade.school_grade
WHERE s.school_grade IS NULL
  AND registration_grade.school_grade IN ('smp_7','smp_8','smp_9','sma_10','sma_11','sma_12','pranikah');

UPDATE `siswa`
SET `target_grade_override` = NULL
WHERE `school_grade` IS NOT NULL
  AND `target_grade_override` = `school_grade`;
