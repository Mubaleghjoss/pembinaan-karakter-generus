<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPenyaksian extends Model
{
    use HasFactory;

    protected $table = 'laporan_penyaksian';

    protected $fillable = [
        'nama_pelapor',
        'email_pelapor',
        'phone_pelapor',
        'siswa_id',
        'pamong_id',
        'nama_generus',
        'karakter_belum_optimal',
        'tanggal_kejadian',
        'deskripsi_kejadian',
        'status',
        'ditindaklanjuti_oleh',
        'catatan_tindak_lanjut',
        'ditindaklanjuti_at',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'ditindaklanjuti_at' => 'datetime',
    ];

    /**
     * Get the siswa that is reported.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the pamong that is reported.
     */
    public function pamong(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pamong_id');
    }

    /**
     * Get the user who followed up.
     */
    public function penindak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditindaklanjuti_oleh');
    }

    /**
     * Get status label in Indonesian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'ditindaklanjuti' => 'Ditindaklanjuti',
            'selesai' => 'Selesai',
            default => $this->status,
        };
    }

    /**
     * Get status color for badge.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'ditindaklanjuti' => 'blue',
            'selesai' => 'green',
            default => 'gray',
        };
    }

    /**
     * Scope for pending reports.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for reports assigned to specific pamong's students.
     */
    public function scopeForPamong($query, $pamongId)
    {
        return $query->where(function ($q) use ($pamongId) {
            $q->where('pamong_id', $pamongId)
                ->orWhereHas('siswa.pamongAssignments', function ($assignmentQuery) use ($pamongId) {
                    $assignmentQuery->where('pamong_id', $pamongId);
                });
        });
    }
}
