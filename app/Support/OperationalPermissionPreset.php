<?php

namespace App\Support;

class OperationalPermissionPreset
{
    public static function all(): array
    {
        return [
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
}
