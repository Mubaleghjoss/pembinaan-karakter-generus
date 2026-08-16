<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class OperationalMobileNavigation
{
    public const MAX_FAVORITES = 6;

    public const DEFAULT_ADMIN_FAVORITES = [
        'dashboard',
        'students',
        'attendance',
        'task_verification',
        'materials',
        'teacher_planning',
    ];

    /**
     * Build a mobile navigation payload without running database queries.
     *
     * @param  array{verification?: int, reports?: int, chat?: int, journals?: int}  $badges
     */
    public function build(
        User $user,
        Request $request,
        array $badges = [],
        bool $teacherPortalAvailable = false
    ): ?array {
        if ($user->isGuru()) {
            return null;
        }

        $verificationBadge = (int) ($badges['verification'] ?? 0);
        $reportBadge = (int) ($badges['reports'] ?? 0);
        $chatBadge = (int) ($badges['chat'] ?? 0);
        $journalBadge = (int) ($badges['journals'] ?? 0);
        $items = [];

        $add = function (
            string $section,
            string $key,
            string $label,
            string $icon,
            string $url,
            bool $active,
            bool $allowed,
            int $badge = 0,
            ?string $target = null
        ) use (&$items): void {
            if (! $allowed) {
                return;
            }

            $items[$key] = array_filter([
                'key' => $key,
                'section' => $section,
                'label' => $label,
                'icon' => $icon,
                'url' => $url,
                'active' => $active,
                'badge' => $badge,
                'target' => $target,
            ], static fn ($value) => $value !== null);
        };

        $isAdmin = $user->isAdmin();
        $can = static fn (string $permission): bool => $user->hasPamongMenuAccess($permission);
        $canGeneralAttendance = $isAdmin || $user->isPengurusPkg() || $can('presensi');
        $canCalendar = $isAdmin || $can('calendar');
        $canManualAttendance = $isAdmin || $can('manual_attendance');
        $canSchedule = $isAdmin || $can('jadwal');
        $canJournal = $isAdmin || $user->isPengurusPkg() || $user->isTeacher();

        $add('Data Utama', 'dashboard', 'Dashboard', 'home', route('dashboard'), $request->routeIs('dashboard'), $can('dashboard'));
        $add('Data Utama', 'students', 'Siswa', 'users', route('siswa.index'), $request->routeIs('siswa.*'), $can('siswa'));
        $add('Data Utama', 'parents', 'Orang Tua', 'users', route('ortu-management.index'), $request->routeIs('ortu-management.*'), $can('siswa'));
        $add(
            'Data Utama',
            'pamong',
            'Pamong',
            'users',
            route('pamong.index'),
            $request->routeIs('pamong.*') && ! $request->routeIs('pamong-presensi.*') && ! $request->routeIs('pamong.chat.*'),
            $isAdmin
        );
        $add('Data Utama', 'classes', 'Binaan Pamong', 'database', route('kelas.index'), $request->routeIs('kelas.*'), $can('siswa'));
        $add('Data Utama', 'export', 'Ekspor Data', 'export', route('export.index'), $request->routeIs('export.*'), $can('export'));

        $add('Presensi', 'attendance', 'Presensi Siswa', 'attendance', route('presensi.index'), $request->routeIs('presensi.index') && $request->query('tab') !== 'input', $canGeneralAttendance);
        $add(
            'Presensi',
            'manual_attendance',
            'Input Manual',
            'check',
            route('presensi.index', ['tab' => 'input']).'#input',
            $request->routeIs('manual-attendance.*') || ($request->routeIs('presensi.index') && $request->query('tab') === 'input'),
            $canManualAttendance
        );
        $add('Presensi', 'attendance_points', 'Poin Kehadiran', 'check', route('cek-kehadiran.index'), $request->routeIs('cek-kehadiran.*'), $can('cek_kehadiran'));
        $add('Presensi', 'pamong_attendance', 'Presensi Pamong', 'attendance', route('pamong-presensi.index'), $request->routeIs('pamong-presensi.*'), $can('pamong_presensi'));
        $add('Presensi', 'attendance_schedule', 'Jadwal Presensi', 'calendar', route('attendance-schedule.index'), $request->routeIs('attendance-schedule.*'), $canSchedule);
        $add('Presensi', 'qr', 'QR Code', 'qr', route('qr.generate'), $request->routeIs('qr.*'), $can('qr_generate'));
        $add('Presensi', 'calendar', 'Kalender', 'calendar', route('calendar.index'), $request->routeIs('calendar.*'), $canCalendar);

        $add('Konten & Komunikasi', 'news', 'Berita', 'news', route('berita.index'), $request->routeIs('berita.*'), $can('berita'));
        $add('Konten & Komunikasi', 'materials', 'Materi', 'book', route('materi.index'), $request->routeIs('materi.*'), $can('materi'));
        $add('Konten & Komunikasi', 'presentations', 'Presentasi', 'presentation', route('presentations.index'), $request->routeIs('presentations.*'), $can('materi'));
        $add(
            'Konten & Komunikasi',
            'rpp_journals',
            'Jurnal RPP',
            'journal',
            route('materi-rpp-journals.index'),
            $request->routeIs('materi-rpp-journals.*'),
            $canJournal,
            $journalBadge
        );
        $add('Konten & Komunikasi', 'chat', 'Chat', 'chat', route('pamong.chat.index'), $request->routeIs('pamong.chat.*'), $can('chat'), $chatBadge);
        $add('Konten & Komunikasi', 'group_chat', 'Grup Chat', 'chat', route('group-chat.index'), $request->routeIs('group-chat.*'), $can('group_chat'), $chatBadge);
        $add('Konten & Komunikasi', 'meeting_notes', 'Catatan Rapat', 'journal', route('catatan-rapat.index'), $request->routeIs('catatan-rapat.*'), $can('catatan_rapat'));

        $add('Tugas PKG', 'active_tasks', 'Daftar Tugas Aktif', 'check', route('tugas-pkg.index'), $request->routeIs('tugas-pkg.index') || $request->routeIs('pr.*'), $can('pr'));
        $add(
            'Tugas PKG',
            'task_verification',
            'Verifikasi Tugas PKG',
            'check',
            route('tugas-pkg.verification'),
            $request->routeIs('tugas-pkg.verification') || $request->routeIs('tracer-karakter.*'),
            $can('tracer_karakter'),
            $verificationBadge
        );
        $add(
            'Tugas PKG',
            'quran_reading',
            "Tracer Bacaan Al-Qur'an",
            'book',
            route('quran.index'),
            $request->routeIs('quran.*'),
            $can('tracer_bacaan_quran')
        );
        $add('Tugas PKG', 'task_master', 'Buat Tugas PKG', 'journal', route('tugas-pkg.master'), $request->routeIs('tugas-pkg.master') || $request->routeIs('karakter.*'), $can('tugas_pkg'));
        $add(
            'Tugas PKG',
            'witness_reports',
            'Info Lapor PKG',
            'report',
            route('laporan-penyaksian.index'),
            $request->routeIs('laporan-penyaksian.*'),
            $can('laporan_penyaksian'),
            $reportBadge
        );

        $add('Gamifikasi & Game', 'gamification', 'Gamifikasi', 'game', route('admin.gamification.badges'), $request->routeIs('admin.gamification.*') && ! $request->routeIs('admin.gamification.transactions'), $can('gamification'));
        $add('Gamifikasi & Game', 'point_transactions', 'Riwayat Transaksi', 'report', route('admin.gamification.transactions'), $request->routeIs('admin.gamification.transactions'), $can('gamification'));
        $add('Gamifikasi & Game', 'rpg', 'Game 29 Karakter', 'rpg', route('admin.rpg.index'), $request->routeIs('admin.rpg.*'), $can('game'));

        $add('Administrasi', 'teacher_planning', 'Pendataan & Jadwal Guru', 'calendar', route('teacher-planning.index'), $request->routeIs('teacher-planning.*'), $can('teacher_scheduling'));
        $add('Administrasi', 'settings', 'Pengaturan', 'settings', route('settings.index'), $request->routeIs('settings.*') || $request->routeIs('users.*'), $isAdmin);
        $add('Administrasi', 'data_pull', 'Tarik Data', 'database', route('admin.data-pull.index'), $request->routeIs('admin.data-pull.*'), $isAdmin);
        $add('Administrasi', 'certificate', 'Sertifikat Level', 'card', route('admin.certificate.settings', 1), $request->routeIs('admin.certificate.*'), $isAdmin);

        $add('Halaman Publik', 'public_page', 'Halaman Publik', 'globe', route('public.index'), false, true, 0, '_blank');

        $sections = collect($items)
            ->groupBy('section')
            ->map(fn ($sectionItems, string $label): array => [
                'label' => $label,
                'items' => $sectionItems->map(fn (array $item): array => $this->withoutInternalFields($item))->values()->all(),
            ])
            ->values()
            ->all();

        $bottomItems = $isAdmin
            ? $this->adminBottomItems($items, $request)
            : $this->pamongBottomItems($items);

        $accountItems = [
            [
                'label' => 'Profil',
                'icon' => 'user',
                'url' => route('profile.show'),
                'active' => $request->routeIs('profile.show') || $request->routeIs('profile.update*'),
            ],
        ];

        if ($user->hasAnyRole(User::attendanceRoleNames())) {
            $accountItems[] = [
                'label' => 'ID Card',
                'icon' => 'card',
                'url' => route('profile.id-card'),
                'active' => $request->routeIs('profile.id-card*'),
            ];
        }

        if ($teacherPortalAvailable) {
            $accountItems[] = [
                'label' => 'Portal Guru',
                'icon' => 'book',
                'url' => route('guru.dashboard'),
                'active' => $request->routeIs('guru.*'),
            ];
        }

        $sections[] = [
            'label' => 'Akun',
            'items' => $accountItems,
        ];

        $bottomKeys = collect($bottomItems)->pluck('key');

        return [
            'tone' => $isAdmin ? 'blue' : 'teal',
            'portal_label' => $isAdmin ? 'Portal Admin' : 'Portal Pamong',
            'home_url' => route('dashboard'),
            'profile_url' => route('profile.show'),
            'profile_label' => 'profil',
            'user_name' => $user->display_name,
            'user_meta' => $user->operationalRoleLabel(),
            'photo_url' => $user->avatar_url,
            'bottom_items' => collect($bottomItems)->map(fn (array $item): array => $this->withoutInternalFields($item))->values()->all(),
            'more_active' => collect($items)->contains(fn (array $item, string $key): bool => ! $bottomKeys->contains($key) && (bool) $item['active'])
                || collect($accountItems)->contains(fn (array $item): bool => (bool) $item['active']),
            'sheet_sections' => $sections,
            'push' => [
                'subscribe_url' => route('pwa.push-subscriptions.store'),
                'unsubscribe_url' => route('pwa.push-subscriptions.destroy'),
                'badge_count' => $verificationBadge,
            ],
            'logout_url' => route('logout'),
            'favorites' => $isAdmin ? $this->adminFavorites($user, $items) : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function adminFavoriteKeys(): array
    {
        return [
            'dashboard',
            'students',
            'parents',
            'pamong',
            'classes',
            'export',
            'attendance',
            'attendance_recap',
            'generus_recap',
            'manual_attendance',
            'attendance_points',
            'pamong_attendance',
            'attendance_schedule',
            'qr',
            'calendar',
            'news',
            'materials',
            'presentations',
            'rpp_journals',
            'chat',
            'group_chat',
            'meeting_notes',
            'active_tasks',
            'task_verification',
            'task_master',
            'witness_reports',
            'gamification',
            'point_transactions',
            'rpg',
            'teacher_planning',
            'settings',
            'data_pull',
            'certificate',
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function adminBottomItems(array $items, Request $request): array
    {
        $definitions = [
            ['key' => 'dashboard', 'label' => 'Beranda', 'icon' => 'home', 'active' => $request->routeIs('dashboard')],
            ['key' => 'students', 'label' => 'Data', 'icon' => 'users', 'active' => $request->routeIs('siswa.*') || $request->routeIs('ortu-management.*') || $request->routeIs('pamong.*') || $request->routeIs('kelas.*') || $request->routeIs('export.*')],
            ['key' => 'attendance', 'label' => 'Presensi', 'icon' => 'attendance', 'active' => $request->routeIs('presensi.*') || $request->routeIs('manual-attendance.*') || $request->routeIs('cek-kehadiran.*') || $request->routeIs('pamong-presensi.*') || $request->routeIs('attendance-schedule.*') || $request->routeIs('qr.*') || $request->routeIs('calendar.*')],
            ['key' => 'task_verification', 'label' => 'Tugas', 'icon' => 'check', 'active' => $request->routeIs('tugas-pkg.*') || $request->routeIs('tracer-karakter.*') || $request->routeIs('karakter.*') || $request->routeIs('laporan-penyaksian.*') || $request->routeIs('pr.*')],
            ['key' => 'materials', 'label' => 'Konten', 'icon' => 'book', 'active' => $request->routeIs('berita.*') || $request->routeIs('materi.*') || $request->routeIs('presentations.*') || $request->routeIs('materi-rpp-journals.*') || $request->routeIs('pamong.chat.*') || $request->routeIs('group-chat.*') || $request->routeIs('catatan-rapat.*')],
        ];

        return collect($definitions)
            ->filter(fn (array $definition): bool => isset($items[$definition['key']]))
            ->map(function (array $definition) use ($items): array {
                return array_replace($items[$definition['key']], $definition);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function pamongBottomItems(array $items): array
    {
        $slots = [
            ['dashboard'],
            ['calendar'],
            ['pamong_attendance', 'attendance', 'manual_attendance', 'attendance_schedule', 'attendance_points'],
            ['task_verification', 'active_tasks', 'task_master', 'witness_reports'],
            ['chat', 'group_chat', 'materials'],
        ];

        $selected = [];
        foreach ($slots as $candidates) {
            foreach ($candidates as $key) {
                if (isset($items[$key]) && ! isset($selected[$key])) {
                    $selected[$key] = $items[$key];
                    if ($key === 'dashboard') {
                        $selected[$key]['label'] = 'Beranda';
                    }
                    break;
                }
            }
        }

        $fallback = [
            'materials',
            'manual_attendance',
            'witness_reports',
            'teacher_planning',
            'rpp_journals',
            'students',
            'news',
            'meeting_notes',
            'gamification',
            'rpg',
        ];

        foreach ($fallback as $key) {
            if (count($selected) >= 5) {
                break;
            }
            if (isset($items[$key]) && ! isset($selected[$key])) {
                $selected[$key] = $items[$key];
            }
        }

        return array_values(array_slice($selected, 0, 5, true));
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function adminFavorites(User $user, array $items): array
    {
        $stored = $user->mobile_menu_favorites;
        $selectedKeys = $stored === null ? self::DEFAULT_ADMIN_FAVORITES : (array) $stored;
        $selectedKeys = collect($selectedKeys)
            ->filter(fn ($key): bool => is_string($key) && isset($items[$key]) && in_array($key, $this->adminFavoriteKeys(), true))
            ->unique()
            ->take(self::MAX_FAVORITES)
            ->values();

        return [
            'selected_keys' => $selectedKeys->all(),
            'selected_items' => $selectedKeys
                ->map(fn (string $key): array => $this->withoutInternalFields($items[$key]))
                ->values()
                ->all(),
            'available_items' => collect($this->adminFavoriteKeys())
                ->filter(fn (string $key): bool => isset($items[$key]))
                ->map(fn (string $key): array => [
                    'key' => $key,
                    'label' => $items[$key]['label'],
                    'icon' => $items[$key]['icon'],
                ])
                ->values()
                ->all(),
            'default_keys' => self::DEFAULT_ADMIN_FAVORITES,
            'max' => self::MAX_FAVORITES,
            'update_url' => route('profile.mobile-menu-favorites.update'),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function withoutInternalFields(array $item): array
    {
        unset($item['key'], $item['section']);

        return $item;
    }
}
