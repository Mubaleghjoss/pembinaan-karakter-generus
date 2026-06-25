<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaMateriTargetProgress extends Model
{
    use HasFactory;

    protected $table = 'siswa_materi_target_progress';

    protected $fillable = [
        'siswa_id',
        'materi_target_id',
        'is_completed',
        'completed_at',
        'actor_type',
        'actor_id',
        'note',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(MateriTarget::class, 'materi_target_id');
    }
}
