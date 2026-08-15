<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranReadingEntry extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'siswa_id', 'reading_date', 'page_start', 'page_end', 'surah_start', 'ayah_start',
        'surah_end', 'ayah_end', 'mushaf_label', 'notes', 'source', 'submitted_by_type',
        'submitted_by_id', 'status', 'verified_by', 'verified_at', 'verification_notes',
        'sheet_id', 'sheet_row_number', 'scan_id',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(QuranReadingSheet::class, 'sheet_id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(QuranReadingScan::class, 'scan_id');
    }

    public function getPageCountAttribute(): int
    {
        if ($this->page_start === null || $this->page_end === null) {
            return 0;
        }

        return max(0, (int) $this->page_end - (int) $this->page_start + 1);
    }

    public function getPageRangeLabelAttribute(): string
    {
        return $this->page_start === null || $this->page_end === null
            ? 'Halaman tidak dicatat'
            : 'Halaman '.$this->page_start.'–'.$this->page_end;
    }
}
