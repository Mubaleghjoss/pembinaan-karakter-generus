<?php

namespace App\Support;

use App\Models\PamongPermission;
use App\Models\Setting;
use Illuminate\Support\Str;

class OperationalPermissionPreset
{
    /**
     * Setting key + group where custom / overridden presets are stored (as JSON).
     */
    public const STORE_KEY = 'operational_permission_presets';
    public const STORE_GROUP = 'permissions';

    /**
     * Built-in presets shipped with the app. These always exist. Admins may
     * override them (an override is stored in the DB and wins by key) but the
     * baseline can never be lost — deleting an override reverts to this.
     */
    public static function builtin(): array
    {
        return [
            'pamong_pembimbing' => [
                'label' => 'Pamong Pembimbing',
                'description' => 'Paket standar pamong: verifikasi Tugas PKG & bacaan Qur\'an, presensi + bantu isi presensi manual semua generus, materi, kalender, chat.',
                'menu_permissions' => [
                    'dashboard', 'siswa', 'presensi', 'manual_attendance', 'cek_kehadiran',
                    'tracer_karakter', 'tracer_bacaan_quran', 'pr', 'materi', 'calendar',
                    'chat', 'group_chat', 'laporan_penyaksian', 'gamification', 'game',
                ],
                'crud_permissions' => [
                    'siswa' => ['view'],
                    'presensi' => ['view', 'create', 'edit', 'verify'],
                    'manual_attendance' => ['view', 'create', 'all_students'],
                    'cek_kehadiran' => ['view'],
                    'tracer_karakter' => ['view', 'create', 'edit'],
                    'tracer_bacaan_quran' => ['view', 'create', 'edit', 'verify'],
                    'pr' => ['view', 'create', 'edit', 'verify'],
                    'materi' => ['view'],
                    'calendar' => ['view'],
                    'chat' => ['view', 'send'],
                    'group_chat' => ['view', 'send'],
                    'laporan_penyaksian' => ['view', 'tindak_lanjut'],
                    'gamification' => ['view'],
                    'game' => ['view'],
                ],
            ],
            'pengurus_verifikator' => [
                'label' => 'Pengurus Verifikator',
                'description' => 'Pengurus PKG yang fokus memverifikasi: Tugas PKG, bacaan Qur\'an, presensi, dan laporan penyaksian. Tanpa input presensi manual.',
                'menu_permissions' => [
                    'dashboard', 'siswa', 'presensi', 'cek_kehadiran', 'tracer_karakter',
                    'tracer_bacaan_quran', 'pr', 'materi', 'calendar', 'chat',
                    'laporan_penyaksian', 'berita', 'gamification',
                ],
                'crud_permissions' => [
                    'siswa' => ['view', 'export'],
                    'presensi' => ['view', 'verify', 'export'],
                    'cek_kehadiran' => ['view'],
                    'tracer_karakter' => ['view', 'create', 'edit', 'export'],
                    'tracer_bacaan_quran' => ['view', 'create', 'edit', 'verify', 'export'],
                    'pr' => ['view', 'create', 'edit', 'verify'],
                    'materi' => ['view'],
                    'calendar' => ['view'],
                    'chat' => ['view', 'send'],
                    'laporan_penyaksian' => ['view', 'tindak_lanjut'],
                    'berita' => ['view'],
                    'gamification' => ['view'],
                ],
            ],
            'tim_presensi' => [
                'label' => 'Tim Presensi',
                'description' => 'Khusus kehadiran siswa: presensi, input manual, dan poin kehadiran. Menu lain disembunyikan.',
                'menu_permissions' => ['dashboard', 'presensi', 'manual_attendance', 'cek_kehadiran'],
                'crud_permissions' => [
                    'presensi' => ['view', 'create', 'edit', 'verify'],
                    'manual_attendance' => ['view', 'create', 'all_students'],
                    'cek_kehadiran' => ['view'],
                ],
            ],
            'presensi_operator' => [
                'label' => 'Operator Presensi',
                'description' => 'Fokus pada presensi siswa, poin kehadiran, kalender, dan presensi operasional.',
                'menu_permissions' => ['dashboard', 'presensi', 'manual_attendance', 'cek_kehadiran', 'calendar', 'pamong_presensi', 'export'],
                'crud_permissions' => [
                    'presensi' => ['view', 'create', 'edit', 'verify', 'export'],
                    'manual_attendance' => ['view', 'create', 'all_students'],
                    'cek_kehadiran' => ['view', 'delete'],
                    'export' => ['view', 'presensi'],
                ],
            ],
            'publikasi_berita' => [
                'label' => 'Publikasi Berita',
                'description' => 'Mengelola berita dan kalender publikasi kegiatan, dengan materi hanya untuk dilihat.',
                'menu_permissions' => ['dashboard', 'berita', 'materi', 'calendar'],
                'crud_permissions' => [
                    'berita' => ['view', 'create', 'edit', 'delete'],
                    'materi' => ['view'],
                ],
            ],
            'operator_data' => [
                'label' => 'Operator Data',
                'description' => 'Mengelola data siswa, tracer, tugas PKG, laporan penyaksian, dan ekspor operasional.',
                'menu_permissions' => ['dashboard', 'siswa', 'tracer_karakter', 'tracer_bacaan_quran', 'pr', 'laporan_penyaksian', 'calendar', 'export'],
                'crud_permissions' => [
                    'siswa' => ['view', 'create', 'edit', 'import', 'export'],
                    'tracer_karakter' => ['view', 'create', 'edit', 'export'],
                    'tracer_bacaan_quran' => ['view', 'create', 'edit', 'verify', 'export'],
                    'pr' => ['view', 'create', 'edit', 'verify'],
                    'laporan_penyaksian' => ['view', 'tindak_lanjut'],
                    'export' => ['view', 'siswa'],
                ],
            ],
            'game_gamifikasi' => [
                'label' => 'Game & Gamifikasi',
                'description' => 'Mengelola leaderboard, penyesuaian poin, dan konten game 29 karakter.',
                'menu_permissions' => ['dashboard', 'gamification', 'game', 'calendar'],
                'crud_permissions' => [
                    'gamification' => ['view', 'create', 'edit', 'delete', 'export', 'adjust', 'reset'],
                    'game' => ['view', 'create', 'edit', 'delete'],
                ],
            ],
            'komunikasi_monitoring' => [
                'label' => 'Komunikasi & Monitoring',
                'description' => 'Menangani chat, grup, laporan penyaksian, dan koordinasi lintas tim.',
                'menu_permissions' => ['dashboard', 'chat', 'group_chat', 'catatan_rapat', 'laporan_penyaksian', 'calendar', 'berita'],
                'crud_permissions' => [
                    'chat' => ['view', 'send', 'broadcast'],
                    'group_chat' => ['view', 'create', 'send'],
                    'catatan_rapat' => ['view', 'create', 'edit'],
                    'laporan_penyaksian' => ['view', 'tindak_lanjut'],
                    'berita' => ['view'],
                ],
            ],
        ];
    }

    /**
     * Custom / override presets stored in the DB (settings table), keyed by slug.
     */
    public static function custom(): array
    {
        $raw = Setting::get(self::STORE_KEY, null);

        if (! $raw) {
            return [];
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * All presets: built-in merged with custom. Custom entries override
     * built-ins that share the same key and add brand-new ones.
     * Each entry is enriched with meta flags for the UI.
     */
    public static function all(): array
    {
        $builtin = self::builtin();
        $custom = self::custom();

        $merged = [];

        // Built-ins first (preserve their order), applying any stored override.
        foreach ($builtin as $key => $preset) {
            $isOverridden = array_key_exists($key, $custom);
            $data = $isOverridden ? array_merge($preset, $custom[$key]) : $preset;

            $merged[$key] = self::decorate($key, $data, $isOverridden ? 'override' : 'builtin');
        }

        // Then purely custom presets (not shadowing a built-in), newest last.
        foreach ($custom as $key => $preset) {
            if (array_key_exists($key, $builtin)) {
                continue;
            }

            $merged[$key] = self::decorate($key, $preset, 'custom');
        }

        return $merged;
    }

    protected static function decorate(string $key, array $data, string $source): array
    {
        return [
            'label' => $data['label'] ?? $key,
            'description' => $data['description'] ?? '',
            'menu_permissions' => array_values($data['menu_permissions'] ?? []),
            'crud_permissions' => $data['crud_permissions'] ?? [],
            'source' => $source,            // builtin | override | custom
            'is_builtin' => $source !== 'custom',
            'editable' => true,
            // A built-in with no override cannot be "deleted"; overrides revert,
            // pure custom presets are removed entirely.
            'deletable' => $source !== 'builtin',
        ];
    }

    public static function find(?string $key): ?array
    {
        if (! $key) {
            return null;
        }

        return self::all()[$key] ?? null;
    }

    public static function permissionsFor(?string $key): ?array
    {
        $preset = self::find($key);

        if (! $preset) {
            return null;
        }

        return [
            'menu_permissions' => $preset['menu_permissions'],
            'crud_permissions' => $preset['crud_permissions'],
        ];
    }

    /**
     * Sanitize incoming menu + CRUD arrays against what the app actually offers.
     */
    public static function sanitize(array $menuPermissions, array $crudPermissions): array
    {
        $availableMenus = array_keys(PamongPermission::getAvailableMenus());
        $availableCrud = PamongPermission::getAvailableCrudOperations();

        $menus = array_values(array_intersect($availableMenus, array_values(array_unique($menuPermissions))));

        $crud = [];
        foreach ($crudPermissions as $module => $operations) {
            if (! isset($availableCrud[$module])) {
                continue;
            }
            $ops = array_values(array_intersect($availableCrud[$module], array_values(array_unique((array) $operations))));
            if (! empty($ops)) {
                $crud[$module] = $ops;
            }
        }

        return [
            'menu_permissions' => $menus,
            'crud_permissions' => $crud,
        ];
    }

    /**
     * Create or update a preset. When $key is null a new slug is generated
     * from the label (kept unique). Returns the stored key.
     */
    public static function save(?string $key, string $label, ?string $description, array $menuPermissions, array $crudPermissions): string
    {
        $custom = self::custom();
        $label = trim($label);

        if (! $key) {
            $key = self::uniqueKey($label, $custom);
        }

        $clean = self::sanitize($menuPermissions, $crudPermissions);

        $custom[$key] = [
            'label' => $label !== '' ? $label : $key,
            'description' => trim((string) $description),
            'menu_permissions' => $clean['menu_permissions'],
            'crud_permissions' => $clean['crud_permissions'],
        ];

        self::persist($custom);

        return $key;
    }

    /**
     * Delete a preset override / custom preset. Built-ins with no override
     * cannot be deleted (returns false).
     */
    public static function delete(string $key): bool
    {
        $custom = self::custom();

        if (! array_key_exists($key, $custom)) {
            return false;
        }

        unset($custom[$key]);
        self::persist($custom);

        return true;
    }

    protected static function uniqueKey(string $label, array $existing): string
    {
        $base = Str::slug($label, '_');
        if ($base === '') {
            $base = 'preset';
        }

        $builtinKeys = array_keys(self::builtin());
        $taken = array_merge(array_keys($existing), $builtinKeys);

        $key = $base;
        $i = 2;
        while (in_array($key, $taken, true)) {
            $key = $base . '_' . $i;
            $i++;
        }

        return $key;
    }

    protected static function persist(array $custom): void
    {
        Setting::set(self::STORE_KEY, json_encode($custom), self::STORE_GROUP);
    }
}
