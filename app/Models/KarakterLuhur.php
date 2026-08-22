<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KarakterLuhur extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor', 'slug', 'nama', 'nama_arab', 'kategori', 'ringkas',
        'deskripsi', 'definisi', 'dalil_quran', 'dalil_hadits',
        'hikmah', 'studi_kasus', 'tips_amal', 'is_active',
    ];

    protected $casts = [
        'dalil_quran' => 'array',
        'dalil_hadits' => 'array',
        'hikmah' => 'array',
        'studi_kasus' => 'array',
        'tips_amal' => 'array',
        'is_active' => 'boolean',
        'nomor' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (KarakterLuhur $model) {
            if (blank($model->slug) && filled($model->nama)) {
                $model->slug = Str::slug($model->nama);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Kandidat studi kasus (buang yang kosong).
     */
    public function studiKasusList(): array
    {
        return array_values(array_filter((array) ($this->studi_kasus ?? []), fn ($s) => filled($s)));
    }
}
