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
        'hikmah', 'studi_kasus', 'penerapan', 'tips_amal', 'is_active',
    ];

    protected $casts = [
        'dalil_quran' => 'array',
        'dalil_hadits' => 'array',
        'hikmah' => 'array',
        'studi_kasus' => 'array',
        'penerapan' => 'array',
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

    /**
     * Penerapan terstruktur: benar / salah / dampak positif / dampak negatif.
     */
    public function penerapanList(): array
    {
        $p = (array) ($this->penerapan ?? []);
        $clean = fn ($k) => array_values(array_filter((array) ($p[$k] ?? []), fn ($s) => filled($s)));

        return [
            'benar' => $clean('benar'),
            'salah' => $clean('salah'),
            'dampak_positif' => $clean('dampak_positif'),
            'dampak_negatif' => $clean('dampak_negatif'),
        ];
    }

    public function hasPenerapan(): bool
    {
        $p = $this->penerapanList();
        return count($p['benar']) || count($p['salah']) || count($p['dampak_positif']) || count($p['dampak_negatif']);
    }
}
