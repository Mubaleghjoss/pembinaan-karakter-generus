<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PamongActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user/pamong that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity for a pamong user.
     */
    public static function log(
        int $userId,
        string $action,
        string $description,
        ?string $module = null,
        ?array $metadata = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Human-readable action labels.
     */
    public static function getActionLabels(): array
    {
        return [
            'login' => 'Login',
            'logout' => 'Logout',
            'view' => 'Lihat',
            'create' => 'Buat',
            'edit' => 'Edit',
            'delete' => 'Hapus',
            'verify' => 'Verifikasi',
            'export' => 'Ekspor',
            'import' => 'Impor',
            'send' => 'Kirim',
            'broadcast' => 'Siaran',
        ];
    }

    /**
     * Get action label.
     */
    public function getActionLabelAttribute(): string
    {
        $labels = self::getActionLabels();
        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Module labels.
     */
    public static function getModuleLabels(): array
    {
        return [
            'auth' => 'Autentikasi',
            'siswa' => 'Data Siswa',
            'presensi' => 'Presensi',
            'tracer_karakter' => 'Tracer Karakter',
            'materi' => 'Materi',
            'pr' => 'Tugas PKG',
            'tugas_pkg' => 'Tugas PKG',
            'berita' => 'Berita',
            'chat' => 'Chat',
            'group_chat' => 'Group Chat',
            'calendar' => 'Kalender',
            'gamification' => 'Gamifikasi',
            'laporan_penyaksian' => 'Laporan Penyaksian',
        ];
    }

    /**
     * Get module label.
     */
    public function getModuleLabelAttribute(): string
    {
        $labels = self::getModuleLabels();
        return $labels[$this->module] ?? ($this->module ?? '-');
    }
}
