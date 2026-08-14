<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranProgressSubmission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'siswa_id', 'cycle_id', 'sheet_id', 'scan_id', 'marked_on', 'completed_surahs',
        'ambiguous_surahs', 'active_surah', 'active_ayah', 'status', 'submitted_by_type',
        'submitted_by_id', 'reviewed_by', 'reviewed_at', 'review_notes', 'metadata',
    ];

    protected $casts = [
        'marked_on' => 'date', 'completed_surahs' => 'array', 'ambiguous_surahs' => 'array',
        'active_surah' => 'integer', 'active_ayah' => 'integer', 'reviewed_at' => 'datetime', 'metadata' => 'array',
    ];

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class); }
    public function cycle(): BelongsTo { return $this->belongsTo(QuranReadingCycle::class, 'cycle_id'); }
    public function sheet(): BelongsTo { return $this->belongsTo(QuranReadingSheet::class, 'sheet_id'); }
    public function scan(): BelongsTo { return $this->belongsTo(QuranReadingScan::class, 'scan_id'); }
}
