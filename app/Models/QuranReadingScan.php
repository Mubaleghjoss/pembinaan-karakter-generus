<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranReadingScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id', 'sheet_id', 'uploaded_by_type', 'uploaded_by_id', 'original_path',
        'processed_path', 'status', 'extracted_rows', 'metadata', 'confirmed_at', 'files_purged_at',
    ];

    protected $casts = [
        'extracted_rows' => 'array',
        'metadata' => 'array',
        'confirmed_at' => 'datetime',
        'files_purged_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(QuranReadingSheet::class, 'sheet_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(QuranReadingEntry::class, 'scan_id');
    }

    public function progressSubmission()
    {
        return $this->hasOne(QuranProgressSubmission::class, 'scan_id');
    }
}
