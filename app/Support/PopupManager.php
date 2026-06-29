<?php

namespace App\Support;

use App\Models\Setting;

class PopupManager
{
    public const GROUP = 'popup';

    public static function definitions(): array
    {
        return [
            'profile_assignment_prompt' => [
                'title' => 'Perbarui kelompok dan kelas sekolah',
                'description' => 'Popup wajib setelah login untuk memastikan kelompok siswa/pamong dan kelas sekolah siswa sesuai pembagian terbaru.',
                'targets' => ['Siswa', 'Pamong', 'Pengurus PKG'],
                'action_label' => 'Simpan pembaruan data',
                'default_enabled' => true,
                'default_required' => true,
            ],
            'biometric_prompt' => [
                'title' => 'Tautkan biometrik',
                'description' => 'Popup setelah login untuk pengguna yang belum punya biometrik valid atau masih memakai credential lama.',
                'targets' => ['Siswa', 'Ortu', 'Pamong/Admin'],
                'action_label' => 'Buka pengaturan biometrik',
                'default_enabled' => false,
                'default_required' => false,
            ],
            'face_enrollment_prompt' => [
                'title' => 'Daftarkan wajah presensi',
                'description' => 'Popup wajib untuk siswa dan pamong yang belum punya data wajah awal sebelum memakai scan wajah presensi.',
                'targets' => ['Siswa', 'Pamong', 'Pengurus PKG', 'Admin'],
                'action_label' => 'Buka pendaftaran wajah',
                'default_enabled' => true,
                'default_required' => true,
            ],
            'biodata_prompt' => [
                'title' => 'Lengkapi biodata',
                'description' => 'Popup setelah login untuk siswa dengan biodata yang belum lengkap.',
                'targets' => ['Siswa'],
                'action_label' => 'Lengkapi biodata',
                'default_enabled' => true,
                'default_required' => false,
            ],
        ];
    }

    public static function all(): array
    {
        $configs = [];

        foreach (self::definitions() as $key => $definition) {
            $configs[$key] = self::config($key);
        }

        return $configs;
    }

    public static function config(string $key): array
    {
        $definition = self::definitions()[$key] ?? null;

        if ($definition === null) {
            return [
                'key' => $key,
                'enabled' => false,
                'required' => false,
            ];
        }

        return array_merge($definition, [
            'key' => $key,
            'enabled' => self::getBoolean(
                self::enabledSettingKey($key),
                $definition['default_enabled'] ?? true
            ),
            'required' => self::getBoolean(
                self::requiredSettingKey($key),
                $definition['default_required'] ?? false
            ),
        ]);
    }

    public static function setConfig(string $key, bool $enabled, bool $required): void
    {
        if (!array_key_exists($key, self::definitions())) {
            return;
        }

        Setting::set(self::enabledSettingKey($key), $enabled ? '1' : '0', self::GROUP);
        Setting::set(
            self::requiredSettingKey($key),
            $enabled && $required ? '1' : '0',
            self::GROUP
        );
    }

    public static function enabled(string $key): bool
    {
        return self::config($key)['enabled'];
    }

    public static function required(string $key): bool
    {
        return self::config($key)['required'];
    }

    public static function enabledSettingKey(string $key): string
    {
        return "popup_{$key}_enabled";
    }

    public static function requiredSettingKey(string $key): string
    {
        return "popup_{$key}_required";
    }

    private static function getBoolean(string $key, bool $default): bool
    {
        $value = Setting::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
