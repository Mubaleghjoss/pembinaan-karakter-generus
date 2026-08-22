<?php

namespace Database\Seeders;

use App\Models\KarakterLuhur;
use Illuminate\Database\Seeder;

class KarakterLuhurSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/karakter29_seed.json');
        if (! is_file($path)) {
            $this->command?->warn("File seed karakter tidak ditemukan: {$path}");
            return;
        }

        $items = json_decode(file_get_contents($path), true) ?: [];

        foreach ($items as $item) {
            KarakterLuhur::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'nomor' => $item['nomor'] ?? 0,
                    'nama' => $item['nama'] ?? '',
                    'nama_arab' => $item['namaArab'] ?? null,
                    'kategori' => $item['kategori'] ?? null,
                    'ringkas' => $item['ringkas'] ?? null,
                    'deskripsi' => $item['deskripsi'] ?? null,
                    'definisi' => $item['definisi'] ?? null,
                    'dalil_quran' => $item['dalilQuran'] ?? [],
                    'dalil_hadits' => $item['dalilHadits'] ?? [],
                    'hikmah' => $item['hikmah'] ?? [],
                    'studi_kasus' => $item['studiKasus'] ?? [],
                    'tips_amal' => $item['tipsAmal'] ?? [],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Seeded '.count($items).' karakter luhur.');
    }
}
