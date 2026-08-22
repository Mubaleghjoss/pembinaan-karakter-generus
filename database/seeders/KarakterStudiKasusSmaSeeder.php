<?php

namespace Database\Seeders;

use App\Models\KarakterLuhur;
use Illuminate\Database\Seeder;

/**
 * Menambah studi kasus khas anak SMA ke tiap karakter.
 * Idempoten: hanya menambah skenario yang belum ada (dedupe), tidak menghapus data admin.
 */
class KarakterStudiKasusSmaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/karakter29_studikasus_sma.json');
        if (! is_file($path)) {
            $this->command?->warn("File studi kasus tidak ditemukan: {$path}");
            return;
        }

        $map = json_decode(file_get_contents($path), true) ?: [];
        $added = 0;

        foreach ($map as $slug => $cases) {
            $karakter = KarakterLuhur::where('slug', $slug)->first();
            if (! $karakter) {
                continue;
            }

            $existing = array_values(array_filter((array) ($karakter->studi_kasus ?? []), fn ($s) => filled($s)));
            $existingNorm = array_map(fn ($s) => mb_strtolower(trim($s)), $existing);

            foreach ($cases as $case) {
                $norm = mb_strtolower(trim($case));
                if ($norm !== '' && ! in_array($norm, $existingNorm, true)) {
                    $existing[] = $case;
                    $existingNorm[] = $norm;
                    $added++;
                }
            }

            $karakter->update(['studi_kasus' => array_values($existing)]);
        }

        $this->command?->info("Menambahkan {$added} studi kasus SMA ke bank karakter.");
    }
}
