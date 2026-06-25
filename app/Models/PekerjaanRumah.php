<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PekerjaanRumah extends Model
{
    use HasFactory;

    protected $table = 'pekerjaan_rumah';

    protected $fillable = [
        'judul',
        'deskripsi',
        'karakter_id',
        'deadline',
        'proof_type',
        'target_type',
        'target_kelas_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function karakter(): BelongsTo
    {
        return $this->belongsTo(Karakter::class);
    }

    public function targetKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'target_kelas_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(PRSubmission::class, 'pr_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForKelas($query, int $kelasId)
    {
        return $query->where(function ($query) use ($kelasId) {
            $query->where('target_type', 'all')
                ->orWhere(function ($query) use ($kelasId) {
                    $query->where('target_type', 'kelas')
                        ->where('target_kelas_id', $kelasId);
                });
        });
    }

    public function isOverdue(): bool
    {
        return $this->deadline?->isPast() ?? false;
    }
}
