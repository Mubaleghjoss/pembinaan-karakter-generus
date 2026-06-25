<?php

namespace App\Models;

use App\Support\TargetGrade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MateriTarget extends Model
{
    use HasFactory;

    public const CATEGORY_BACAAN_MAKNA_AL_QURAN = 'bacaan_makna_al_quran';
    public const CATEGORY_MAKNA_AL_QURAN = 'makna_al_quran';
    public const CATEGORY_MAKNA_AL_HADITS = 'makna_al_hadits';
    public const CATEGORY_HAFALAN = 'hafalan';
    public const CATEGORY_TULIS_ARAB = 'tulis_arab';
    public const CATEGORY_TAJWID = 'tajwid';
    public const CATEGORY_HAFALAN_DALIL = 'hafalan_dalil';
    public const CATEGORY_HAFALAN_SURAT = 'hafalan_surat';
    public const CATEGORY_DOA_HARIAN = 'doa_harian';
    public const CATEGORY_KEILMUAN_KEFAHAMAN = 'keilmuan_kefahaman';
    public const CATEGORY_MATERI_AKHLAQ = 'materi_akhlaq';
    public const CATEGORY_MATERI_KEMANDIRIAN = 'materi_kemandirian';
    public const CATEGORY_PRAKTEK_IBADAH = 'praktek_ibadah';

    protected $fillable = [
        'category',
        'target_grade',
        'semester',
        'title',
        'description',
        'sort_order',
        'is_active',
        'source_key',
        'created_by',
    ];

    protected $casts = [
        'semester' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_BACAAN_MAKNA_AL_QURAN => 'Bacaan & Makna Al Quran',
            self::CATEGORY_MAKNA_AL_QURAN => 'Makna Al Quran',
            self::CATEGORY_MAKNA_AL_HADITS => 'Makna Al Hadits',
            self::CATEGORY_HAFALAN => 'Hafalan',
            self::CATEGORY_TULIS_ARAB => 'Tulis Arab',
            self::CATEGORY_TAJWID => 'Tajwid',
            self::CATEGORY_HAFALAN_DALIL => 'Hafalan Dalil',
            self::CATEGORY_HAFALAN_SURAT => 'Hafalan Surat',
            self::CATEGORY_DOA_HARIAN => 'Doa Harian',
            self::CATEGORY_KEILMUAN_KEFAHAMAN => 'Keilmuan & Kefahaman Agama',
            self::CATEGORY_MATERI_AKHLAQ => 'Materi Akhlaq',
            self::CATEGORY_MATERI_KEMANDIRIAN => 'Materi Kemandirian',
            self::CATEGORY_PRAKTEK_IBADAH => 'Praktek Ibadah',
        ];
    }

    public static function defaultCategory(): string
    {
        return self::CATEGORY_BACAAN_MAKNA_AL_QURAN;
    }

    public static function semesterOptions(): array
    {
        return [
            1 => 'Semester 1',
            2 => 'Semester 2',
        ];
    }

    public static function defaultSemester(): int
    {
        $month = (int) now()->format('n');

        return $month >= 7 ? 1 : 2;
    }

    public static function categoryValues(): array
    {
        return array_keys(self::categoryOptions());
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryOptions()[$this->category] ?? $this->category;
    }

    public function getTargetGradeLabelAttribute(): string
    {
        return TargetGrade::label($this->target_grade) ?? $this->target_grade;
    }

    public function getSemesterLabelAttribute(): string
    {
        return self::semesterOptions()[$this->semester] ?? 'Semua semester';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(SiswaMateriTargetProgress::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForGrade(Builder $query, ?string $grade): Builder
    {
        return $query->when($grade, fn (Builder $builder) => $builder->where('target_grade', $grade));
    }

    public function scopeForCategory(Builder $query, ?string $category): Builder
    {
        return $query->when($category, fn (Builder $builder) => $builder->where('category', $category));
    }

    public function scopeForSemester(Builder $query, ?int $semester): Builder
    {
        return $query->when($semester, function (Builder $builder) use ($semester) {
            $builder->where(function (Builder $nested) use ($semester) {
                $nested->where('semester', $semester)
                    ->orWhereNull('semester');
            });
        });
    }
}
