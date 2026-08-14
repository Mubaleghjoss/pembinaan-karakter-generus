<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranReadingCycle extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['siswa_id', 'cycle_number', 'status', 'started_at', 'completed_at', 'created_by'];

    protected $casts = ['started_at' => 'date', 'completed_at' => 'date'];

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class); }
    public function progress(): HasMany { return $this->hasMany(QuranSurahProgress::class, 'cycle_id'); }
    public function submissions(): HasMany { return $this->hasMany(QuranProgressSubmission::class, 'cycle_id'); }
    public function sheets(): HasMany { return $this->hasMany(QuranReadingSheet::class, 'cycle_id'); }
}
