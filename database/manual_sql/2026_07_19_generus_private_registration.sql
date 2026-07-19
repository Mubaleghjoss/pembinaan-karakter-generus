-- Jalankan hanya jika deploy tidak dapat menjalankan `php artisan migrate --force`.
-- Skrip ini ditujukan untuk MySQL/MariaDB dan hanya dijalankan satu kali.

ALTER TABLE `siswa`
    ADD COLUMN `tempat_lahir` VARCHAR(120) NULL AFTER `jenis_kelamin`;

CREATE TABLE `generus_registration_invites` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(120) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `max_uses` INT UNSIGNED NOT NULL DEFAULT 1,
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` TIMESTAMP NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `generus_registration_invites_token_hash_unique` (`token_hash`),
    KEY `generus_registration_invites_expires_at_index` (`expires_at`),
    KEY `generus_registration_invites_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `generus_registrations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_id` CHAR(36) NOT NULL,
    `invite_id` BIGINT UNSIGNED NULL,
    `siswa_id` BIGINT UNSIGNED NULL,
    `download_token_hash` VARCHAR(64) NOT NULL,
    `parent_name` VARCHAR(120) NOT NULL,
    `parent_phone` VARCHAR(30) NOT NULL,
    `student_name` VARCHAR(120) NOT NULL,
    `student_phone` VARCHAR(30) NOT NULL,
    `kelompok` VARCHAR(60) NOT NULL,
    `birth_place` VARCHAR(120) NOT NULL,
    `birth_date` DATE NOT NULL,
    `school_grade` VARCHAR(20) NOT NULL,
    `parent_signature_path` VARCHAR(255) NOT NULL,
    `student_signature_path` VARCHAR(255) NOT NULL,
    `statement_version` VARCHAR(20) NOT NULL DEFAULT 'v1',
    `statement_accepted_at` TIMESTAMP NOT NULL,
    `submitted_at` TIMESTAMP NOT NULL,
    `source_ip` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `generus_registrations_public_id_unique` (`public_id`),
    UNIQUE KEY `generus_registrations_siswa_id_unique` (`siswa_id`),
    KEY `generus_registrations_invite_id_foreign` (`invite_id`),
    KEY `generus_registrations_phones_index` (`parent_phone`, `student_phone`),
    KEY `generus_registrations_submitted_at_index` (`submitted_at`),
    CONSTRAINT `generus_registrations_invite_id_foreign` FOREIGN KEY (`invite_id`) REFERENCES `generus_registration_invites` (`id`) ON DELETE SET NULL,
    CONSTRAINT `generus_registrations_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
