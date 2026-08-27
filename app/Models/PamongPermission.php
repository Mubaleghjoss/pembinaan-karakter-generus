<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PamongPermission extends Model
{
    use HasFactory;

    public const FALLBACK_DEFAULT_MENU_PERMISSIONS = [
        'dashboard',
        'materi',
        'calendar',
        'manual_attendance',
        'laporan_penyaksian',
        'gamification',
        'game',
    ];

    public const FALLBACK_DEFAULT_CRUD_PERMISSIONS = [
        'materi' => ['view'],
        'calendar' => ['view'],
        'manual_attendance' => ['view', 'create'],
        'laporan_penyaksian' => ['view', 'tindak_lanjut'],
        'gamification' => ['view'],
        'game' => ['view'],
    ];

    protected $fillable = [
        'user_id',
        'menu_permissions',
        'crud_permissions',
        'is_excluded',
    ];

    protected $casts = [
        'menu_permissions' => 'array',
        'crud_permissions' => 'array',
        'is_excluded' => 'boolean',
    ];

    /**
     * Available menus for pamong
     */
    public static function getAvailableMenus(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'siswa' => 'Data Siswa',
            'presensi' => 'Presensi Siswa',
            'manual_attendance' => 'Input Manual',
            'tracer_karakter' => 'Tracer Karakter',
            'tracer_bacaan_quran' => "Tracer Bacaan Al-Qur'an",
            'tugas_pkg' => 'Tugas PKG',
            'cek_kehadiran' => 'Poin Kehadiran',
            'materi' => 'Materi',
            'rpp_journals' => 'Jurnal RPP',
            'pr' => 'Tugas PKG',
            'chat' => 'Chat Siswa',
            'group_chat' => 'Grup Chat',
            'catatan_rapat' => 'Catatan Rapat',
            'calendar' => 'Kalender',
            'jadwal' => 'Jadwal',
            'teacher_scheduling' => 'Pendataan & Jadwal Guru',
            'pamong_presensi' => 'Presensi Pamong',
            'berita' => 'Berita',
            'gamification' => 'Gamifikasi',
            'game' => 'Game 29 Karakter',
            'laporan_penyaksian' => 'Laporan Penyaksian',
            'export' => 'Ekspor Data',
            'qr_generate' => 'Buat QR Code',
        ];
    }

    /**
     * Get default menu permissions for new pamong
     */
    public static function getDefaultMenuPermissions(): array
    {
        return self::resolveDefaultPermissions()['menu_permissions'];
    }

    public static function getDefaultCrudPermissions(): array
    {
        return self::resolveDefaultPermissions()['crud_permissions'];
    }

    /**
     * Available CRUD operations per module
     */
    public static function getAvailableCrudOperations(): array
    {
        return [
            'siswa' => ['view', 'create', 'edit', 'delete', 'import', 'export'],
            'presensi' => ['view', 'create', 'edit', 'delete', 'verify', 'export'],
            'manual_attendance' => ['view', 'create', 'all_students'],
            'tracer_karakter' => ['view', 'create', 'edit', 'delete', 'export'],
            'tracer_bacaan_quran' => ['view', 'create', 'edit', 'verify', 'export'],
            'cek_kehadiran' => ['view', 'delete'],
            'materi' => ['view', 'create', 'edit', 'delete'],
            'rpp_journals' => ['view', 'manage'],
            'pr' => ['view', 'create', 'edit', 'delete', 'verify'],
            'berita' => ['view', 'create', 'edit', 'delete'],
            'chat' => ['view', 'send', 'broadcast'],
            'group_chat' => ['view', 'create', 'send'],
            'catatan_rapat' => ['view', 'create', 'edit', 'delete'],
            'calendar' => ['view'],
            'jadwal' => ['view', 'create', 'edit', 'delete'],
            'teacher_scheduling' => ['view', 'create', 'edit', 'publish', 'export'],
            'pamong_presensi' => ['view'],
            'gamification' => ['view', 'create', 'edit', 'delete', 'export', 'adjust', 'reset'],
            'game' => ['view', 'create', 'edit', 'delete'],
            'laporan_penyaksian' => ['view', 'tindak_lanjut', 'delete'],
            'export' => ['view', 'presensi', 'siswa', 'leaderboard'],
        ];
    }

    public static function getCrudOperationLabels(): array
    {
        return [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'edit' => 'Edit',
            'delete' => 'Hapus',
            'import' => 'Impor',
            'export' => 'Ekspor',
            'verify' => 'Verifikasi',
            'send' => 'Kirim',
            'broadcast' => 'Siaran',
            'adjust' => 'Sesuaikan',
            'reset' => 'Reset',
            'publish' => 'Terbitkan',
            'tindak_lanjut' => 'Tindak Lanjut',
            'presensi' => 'Presensi',
            'siswa' => 'Siswa',
            'leaderboard' => 'Peringkat',
            'all_students' => 'Semua Siswa',
            'manage' => 'Kelola Semua',
        ];
    }

    /**
     * Get the user that owns the permission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if pamong has access to a menu
     */
    public function hasMenuAccess(string $menu): bool
    {
        if ($this->is_excluded) {
            return true;
        }
        
        return $this->menu_permissions && in_array($menu, $this->menu_permissions);
    }

    /**
     * Check if pamong has CRUD permission
     */
    public function hasCrudPermission(string $module, string $operation): bool
    {
        if ($this->is_excluded) {
            return true;
        }
        
        if (!$this->crud_permissions || !isset($this->crud_permissions[$module])) {
            return false;
        }
        
        return in_array($operation, $this->crud_permissions[$module]);
    }

    /**
     * Set all menus as allowed
     */
    public function allowAllMenus(): void
    {
        $this->update([
            'menu_permissions' => array_keys(self::getAvailableMenus()),
        ]);
    }

    /**
     * Set all CRUD operations as allowed
     */
    public function allowAllCrud(): void
    {
        $this->update([
            'crud_permissions' => self::getAvailableCrudOperations(),
        ]);
    }

    protected static function resolveDefaultPermissions(): array
    {
        return Cache::remember('default_pamong_permissions_model', 3600, function () {
            $menuPermissions = Setting::get('default_pamong_menu_permissions', null, 'permissions');
            $crudPermissions = Setting::get('default_pamong_crud_permissions', null, 'permissions');

            $decodedMenus = is_string($menuPermissions) ? json_decode($menuPermissions, true) : $menuPermissions;
            $decodedCrud = is_string($crudPermissions) ? json_decode($crudPermissions, true) : $crudPermissions;

            return [
                'menu_permissions' => is_array($decodedMenus) ? $decodedMenus : self::FALLBACK_DEFAULT_MENU_PERMISSIONS,
                'crud_permissions' => is_array($decodedCrud)
                    ? self::normalizeCrudPermissions($decodedCrud)
                    : self::FALLBACK_DEFAULT_CRUD_PERMISSIONS,
            ];
        });
    }

    public static function normalizeCrudPermissions(array $crudPermissions): array
    {
        if (array_key_exists('materi', $crudPermissions)) {
            $crudPermissions['materi'] = array_values(array_intersect(
                (array) $crudPermissions['materi'],
                self::getAvailableCrudOperations()['materi']
            ));
        }

        return $crudPermissions;
    }
}
