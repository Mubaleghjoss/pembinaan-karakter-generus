<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Berita extends Model
{
    use HasFactory;

    public const SOCIAL_PLATFORMS = [
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
        'x' => 'X / Twitter',
        'other' => 'Tautan lainnya',
    ];

    protected $table = 'berita';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'judul',
        'slug',
        'isi',
        'cover_path',
        'pdf_path',
        'images',
        'tags',
        'status',
        'published_at',
        'author_id',
        'view_count',
        'download_count',
        'is_featured',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'tags' => 'array',
        'images' => 'array',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($berita) {
            $berita->slug = self::uniqueSlug($berita->judul);
        });

        static::updating(function ($berita) {
            if ($berita->isDirty('judul')) {
                $berita->slug = self::uniqueSlug($berita->judul, $berita->getKey());
            }
        });
    }

    /**
     * Get the author that owns the berita.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Get excerpt from content (Accessor)
     */
    public function getExcerptAttribute(): string
    {
        return $this->excerpt(150);
    }

    /**
     * Get excerpt with custom length
     */
    public function excerpt(int $length = 150): string
    {
        return Str::limit(strip_tags($this->isi), $length);
    }

    /**
     * Available optional link labels for the news form.
     *
     * @return array<string, string>
     */
    public static function socialPlatforms(): array
    {
        return self::SOCIAL_PLATFORMS;
    }

    /**
     * Get only supported, non-empty social links from metadata.
     *
     * @return array<string, string>
     */
    public function getSocialLinksAttribute(): array
    {
        $links = data_get($this->metadata, 'social_links', []);

        if (! is_array($links)) {
            return [];
        }

        return collect(self::SOCIAL_PLATFORMS)
            ->keys()
            ->mapWithKeys(function (string $platform) use ($links) {
                $url = trim((string) ($links[$platform] ?? ''));

                return $url === '' ? [] : [$platform => $url];
            })
            ->all();
    }

    /**
     * Get reading time estimate
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->isi));

        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    /**
     * Scope for published articles
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for featured articles
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for articles by tag
     */
    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('judul', 'like', "%{$search}%")
                ->orWhere('isi', 'like', "%{$search}%");
        });
    }

    private static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $counter = 2;

        while (self::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' &&
               $this->published_at &&
               $this->published_at->isPast();
    }
}
