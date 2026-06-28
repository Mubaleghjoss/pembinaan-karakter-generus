<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'calendar_date',
        'pdf_path',
        'video_url',
        'video_links',
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
        'calendar_date' => 'date',
        'is_active' => 'boolean',
        'pdf_path' => 'array', // Now stores array of PDF files
        'video_links' => 'array',
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

    public static function pdfFileNameForTitle(string $title, int $index = 0, int $total = 1): string
    {
        $baseName = Str::of($title)
            ->replaceMatches('/[\\\\\/:*?"<>|]+/', ' ')
            ->squish()
            ->limit(150, '')
            ->toString();
        $baseName = $baseName !== '' ? $baseName : 'Materi';
        $number = $total > 1 ? ' - '.($index + 1) : '';

        return "{$baseName}{$number}.pdf";
    }

    public function pdfFileName(int $index): string
    {
        return self::pdfFileNameForTitle($this->judul, $index, $this->pdf_count);
    }

    public static function normalizeVideoLinksInput(mixed $links): array
    {
        if (is_string($links)) {
            $links = [$links];
        }

        if (! is_array($links)) {
            return [];
        }

        $normalized = [];

        foreach ($links as $link) {
            if (is_array($link)) {
                $normalized = array_merge($normalized, self::normalizeVideoLinksInput($link));
                continue;
            }

            $url = trim((string) $link);

            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $normalized[] = $url;
        }

        return array_values(array_unique($normalized));
    }

    public static function youtubeIdFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        preg_match(
            '/(?:(?:youtube|youtube-nocookie)\.com\/(?:shorts\/|[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
            $url,
            $matches
        );

        return $matches[1] ?? null;
    }

    public static function googleDriveFileIdFromUrl(?string $url): ?string
    {
        $host = parse_url((string) $url, PHP_URL_HOST) ?? '';

        if (! $url || (! str_contains($host, 'drive.google.com') && ! str_contains($host, 'docs.google.com'))) {
            return null;
        }

        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        return isset($query['id']) && is_string($query['id']) ? $query['id'] : null;
    }

    public static function embedVideoUrl(?string $url): ?string
    {
        $youtubeId = self::youtubeIdFromUrl($url);

        if ($youtubeId) {
            return "https://www.youtube.com/embed/{$youtubeId}";
        }

        $driveId = self::googleDriveFileIdFromUrl($url);

        if ($driveId) {
            return "https://drive.google.com/file/d/{$driveId}/preview";
        }

        return null;
    }

    public static function videoSourceLabel(?string $url): string
    {
        $host = parse_url((string) $url, PHP_URL_HOST) ?: '';

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtube-nocookie.com') || str_contains($host, 'youtu.be')) {
            return 'YouTube';
        }

        if (str_contains($host, 'drive.google.com') || str_contains($host, 'docs.google.com')) {
            return 'Google Drive';
        }

        return 'Link Video';
    }

    public function getVideoLinkUrlsAttribute(): array
    {
        $links = [];
        $rawLinks = $this->attributes['video_links'] ?? null;

        if (is_string($rawLinks) && trim($rawLinks) !== '') {
            $decoded = json_decode($rawLinks, true);
            $links = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawLinks)) {
            $links = $rawLinks;
        }

        if ($this->video_url) {
            array_unshift($links, $this->video_url);
        }

        return self::normalizeVideoLinksInput($links);
    }

    public function getVideoItemsAttribute(): array
    {
        return collect($this->video_link_urls)
            ->map(function (string $url, int $index) {
                $source = self::videoSourceLabel($url);

                return [
                    'url' => $url,
                    'embed_url' => self::embedVideoUrl($url),
                    'source' => $source,
                    'title' => count($this->video_link_urls) > 1
                        ? 'Video '.($index + 1).' - '.$source
                        : $source,
                ];
            })
            ->values()
            ->all();
    }

    public function getHasVideoLinksAttribute(): bool
    {
        return ! empty($this->video_link_urls);
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
        foreach ($this->video_link_urls as $url) {
            $id = self::youtubeIdFromUrl($url);

            if ($id) {
                return $id;
            }
        }

        return null;
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
