<?php

namespace App\Providers;

use App\Models\Chat;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use App\Models\Karakter;
use App\Models\LaporanPenyaksian;
use App\Models\Setting;
use App\Models\SiswaKarakterChecklist;
use App\Models\ThemeSetting;
use App\Services\MateriRppJournalWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share settings with all views
        View::composer('*', function ($view) {
            try {
                $currentTheme = ThemeSetting::current();
                $siteSettings = [
                    'site_title' => Setting::get('site_title', 'PKG Presensi'),
                    'site_name' => Setting::get('site_name', 'Pembinaan Karakter Generus'),
                    'site_logo' => Setting::get('site_logo') ?: $currentTheme->logo_path,
                    'primary_color' => Setting::get('primary_color', '#667EEA'),
                ];
                
                $cardTitle = Setting::get('card_title', 'KARTU IDENTITAS');
                if (strcasecmp(trim((string) $cardTitle), 'KARTU PESERTA') === 0) {
                    $cardTitle = 'KARTU IDENTITAS';
                }

                $cardSettings = [
                    'card_title' => $cardTitle,
                    'card_subtitle' => Setting::get('card_subtitle', 'Pembinaan Karakter Generus'),
                    'card_logo' => Setting::get('card_logo'),
                    'card_color' => Setting::get('card_color', '#667EEA'),
                    'card_footer_text' => Setting::get('card_footer_text', 'Kartu ini adalah identitas resmi peserta PKG Panunggangan'),
                ];

                $pendingPkgVerificationCount = 0;
                $pendingLaporanPenyaksianCount = 0;
                $appSidebarUnreadChatCount = 0;
                $appSidebarPendingJournalCount = 0;
                $siswaSidebarPendingTaskCount = 0;
                $siswaSidebarUnreadChatCount = 0;
                $siswaSidebarPendingJournalCount = 0;
                $ortuSidebarPendingTaskCount = 0;
                $ortuSidebarUnreadChatCount = 0;
                $user = Auth::user();
                if ($user && $user->hasPamongMenuAccess('tracer_karakter')) {
                    $pendingQuery = SiswaKarakterChecklist::query()->whereNull('verified_at');

                    if ($user->isTeacher()) {
                        $assignedIds = $user->getAssignedSiswaIds();
                        $pendingQuery->whereIn('siswa_id', $assignedIds ?: [0]);
                    }

                    $pendingPkgVerificationCount = (int) $pendingQuery->count();
                }

                if ($user && $user->hasPamongMenuAccess('laporan_penyaksian')) {
                    $laporanQuery = LaporanPenyaksian::query()->pending();

                    if ($user->isTeacher()) {
                        $laporanQuery->forPamong($user->id);
                    }

                    $pendingLaporanPenyaksianCount = (int) $laporanQuery->count();
                }

                if ($user && $user->hasPamongMenuAccess('chat')) {
                    $appSidebarUnreadChatCount = (int) Chat::query()
                        ->where('receiver_user_id', $user->id)
                        ->where('is_read', false)
                        ->count();

                    $groupIds = $user->hasRole('admin')
                        ? \App\Models\ChatGroup::query()->where('is_active', true)->pluck('id')
                        : ChatGroupMember::query()
                            ->where('user_id', $user->id)
                            ->pluck('chat_group_id');

                    $appSidebarUnreadChatCount += (int) ChatGroupMessage::query()
                        ->whereIn('chat_group_id', $groupIds)
                        ->where(function ($query) use ($user) {
                            $query->whereNull('sender_user_id')
                                ->orWhere('sender_user_id', '!=', $user->id);
                        })
                        ->whereRaw("NOT JSON_CONTAINS(COALESCE(is_read_by, '[]'), ?)", [json_encode("user_{$user->id}")])
                        ->count();
                }

                if ($user) {
                    $appSidebarPendingJournalCount = app(MateriRppJournalWorkflowService::class)
                        ->pendingStaffCount($user);
                }

                $today = today();

                if (Auth::guard('siswa')->check()) {
                    $siswa = Auth::guard('siswa')->user();

                    $siswaSidebarPendingTaskCount = (int) Karakter::query()
                        ->where('is_active', true)
                        ->where(function ($query) use ($today) {
                            $query->whereNull('tanggal_mulai')
                                ->orWhereDate('tanggal_mulai', '<=', $today);
                        })
                        ->where(function ($query) use ($today) {
                            $query->whereNull('tanggal_selesai')
                                ->orWhereDate('tanggal_selesai', '>=', $today);
                        })
                        ->whereDoesntHave('checklists', function ($query) use ($siswa, $today) {
                            $query->where('siswa_id', $siswa->id)
                                ->whereDate('checked_at', $today);
                        })
                        ->count();

                    $siswaSidebarUnreadChatCount = (int) Chat::query()
                        ->where('receiver_siswa_id', $siswa->id)
                        ->where('is_read', false)
                        ->count();

                    $groupIds = ChatGroupMember::query()
                        ->where('siswa_id', $siswa->id)
                        ->pluck('chat_group_id');

                    $siswaSidebarUnreadChatCount += (int) ChatGroupMessage::query()
                        ->whereIn('chat_group_id', $groupIds)
                        ->where(function ($query) use ($siswa) {
                            $query->whereNull('sender_siswa_id')
                                ->orWhere('sender_siswa_id', '!=', $siswa->id);
                        })
                        ->whereRaw("NOT JSON_CONTAINS(COALESCE(is_read_by, '[]'), ?)", [json_encode("siswa_{$siswa->id}")])
                        ->count();

                    $siswaSidebarPendingJournalCount = app(MateriRppJournalWorkflowService::class)
                        ->pendingStudentCount($siswa);
                }

                if (Auth::guard('ortu')->check()) {
                    $siswa = Auth::guard('ortu')->user();

                    $ortuSidebarPendingTaskCount = (int) Karakter::query()
                        ->where('is_active', true)
                        ->where(function ($query) use ($today) {
                            $query->whereNull('tanggal_mulai')
                                ->orWhereDate('tanggal_mulai', '<=', $today);
                        })
                        ->where(function ($query) use ($today) {
                            $query->whereNull('tanggal_selesai')
                                ->orWhereDate('tanggal_selesai', '>=', $today);
                        })
                        ->whereDoesntHave('checklists', function ($query) use ($siswa, $today) {
                            $query->where('siswa_id', $siswa->id)
                                ->whereDate('checked_at', $today);
                        })
                        ->count();

                    $ortuSidebarUnreadChatCount = (int) Chat::query()
                        ->where('receiver_siswa_id', $siswa->id)
                        ->where('is_read', false)
                        ->count();
                }
                
                $view->with('siteSettings', $siteSettings);
                $view->with('cardSettings', $cardSettings);
                $view->with('currentTheme', $currentTheme);
                $view->with('pendingPkgVerificationCount', $pendingPkgVerificationCount);
                $view->with('pendingLaporanPenyaksianCount', $pendingLaporanPenyaksianCount);
                $view->with('appSidebarUnreadChatCount', $appSidebarUnreadChatCount);
                $view->with('appSidebarPendingJournalCount', $appSidebarPendingJournalCount);
                $view->with('siswaSidebarPendingTaskCount', $siswaSidebarPendingTaskCount);
                $view->with('siswaSidebarUnreadChatCount', $siswaSidebarUnreadChatCount);
                $view->with('siswaSidebarPendingJournalCount', $siswaSidebarPendingJournalCount);
                $view->with('ortuSidebarPendingTaskCount', $ortuSidebarPendingTaskCount);
                $view->with('ortuSidebarUnreadChatCount', $ortuSidebarUnreadChatCount);
            } catch (\Exception $e) {
                // If settings table doesn't exist yet, use defaults
                $view->with('siteSettings', [
                    'site_title' => 'PKG Presensi',
                    'site_name' => 'Pembinaan Karakter Generus',
                    'site_logo' => null,
                    'primary_color' => '#667EEA',
                ]);
                $view->with('cardSettings', [
                    'card_title' => 'KARTU IDENTITAS',
                    'card_subtitle' => 'Pembinaan Karakter Generus',
                    'card_logo' => null,
                    'card_color' => '#667EEA',
                    'card_footer_text' => 'Kartu ini adalah identitas resmi peserta PKG Panunggangan',
                ]);
                $view->with('currentTheme', new ThemeSetting(ThemeSetting::defaults()));
                $view->with('pendingPkgVerificationCount', 0);
                $view->with('pendingLaporanPenyaksianCount', 0);
                $view->with('appSidebarUnreadChatCount', 0);
                $view->with('appSidebarPendingJournalCount', 0);
                $view->with('siswaSidebarPendingTaskCount', 0);
                $view->with('siswaSidebarUnreadChatCount', 0);
                $view->with('siswaSidebarPendingJournalCount', 0);
                $view->with('ortuSidebarPendingTaskCount', 0);
                $view->with('ortuSidebarUnreadChatCount', 0);
            }
        });
    }
}
