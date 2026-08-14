<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranSurahProgress extends Model
{
    use HasFactory;

    protected $table = 'quran_surah_progress';
    protected $fillable = ['cycle_id', 'surah_number', 'last_ayah', 'completed_at', 'source', 'updated_by'];
    protected $casts = ['surah_number' => 'integer', 'last_ayah' => 'integer', 'completed_at' => 'datetime'];

    public function cycle(): BelongsTo { return $this->belongsTo(QuranReadingCycle::class, 'cycle_id'); }
}
