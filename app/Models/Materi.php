<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materi extends Model
{
    use HasFactory;

    protected $table = 'materi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'judul',
        'materi_folder_id',
        'deskripsi',
        'bulan',
        'pdf_path',
        'video_url',
        'is_active',
        'rpp_is_enabled',
        'rpp_status',
        'rpp_total_pages',
        'rpp_start_page',
        'rpp_pages_per_session',
        'rpp_start_date',
        'rpp_start_time',
        'rpp_end_time',
        'rpp_end_date',
        'rpp_extra_sessions',
        'rpp_catch_up_ranges',
        'rpp_teacher_pool',
        'rpp_teacher_overrides',
        'rpp_published_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bulan' => 'date',
        'is_active' => 'boolean',
        'pdf_path' => 'array', // Now stores array of PDF files
        'rpp_is_enabled' => 'boolean',
        'rpp_total_pages' => 'integer',
        'rpp_start_page' => 'integer',
        'rpp_pages_per_session' => 'integer',
        'rpp_start_date' => 'date',
        'rpp_end_date' => 'date',
        'rpp_extra_sessions' => 'array',
        'rpp_catch_up_ranges' => 'array',
        'rpp_teacher_pool' => 'array',
        'rpp_teacher_overrides' => 'array',
        'rpp_published_at' => 'datetime',
    ];

    /**
     * Get PDF files as collection.
     */
    public function getPdfFilesAttribute(): array
    {
        return $this->pdf_path ?? [];
    }

    /**
     * Check if materi has PDF files.
     */
    public function hasPdfFiles(): bool
    {
        return !empty($this->pdf_path);
    }

    public function hasRpp(): bool
    {
        return $this->rpp_is_enabled
            && $this->rpp_total_pages
            && $this->rpp_pages_per_session
            && $this->rpp_start_date;
    }

    public function isRppPublished(): bool
    {
        return $this->rpp_status === 'published';
    }

    /**
     * Get count of PDF files.
     */
    public function getPdfCountAttribute(): int
    {
        return count($this->pdf_path ?? []);
    }

    /**
     * Get the user who created this materi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MateriFolder::class, 'materi_folder_id');
    }

    public function rppJournals(): HasMany
    {
        return $this->hasMany(MateriRppJournal::class);
    }

    /**
     * Scope for active materi only.
     */
    public function scopeActive($query)
    {
        return $query->where($this->getTable() . '.is_active', true);
    }

    /**
     * Scope for current month materi.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('bulan', now()->month)
                     ->whereYear('bulan', now()->year);
    }

    /**
     * Get YouTube video ID from URL.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        if (!$this->video_url) {
            return null;
        }

        // Match various YouTube URL formats
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->video_url, $matches);
        
        return $matches[1] ?? null;
    }

    /**
     * Get embedded YouTube URL.
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        $id = $this->youtube_id;
        return $id ? "https://www.youtube.com/embed/{$id}" : null;
    }
}
