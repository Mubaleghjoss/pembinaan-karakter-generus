<?php

namespace Database\Seeders;

use App\Models\KarakterLuhur;
use Illuminate\Database\Seeder;

/**
 * Isi field penerapan (benar/salah/dampak) tiap karakter dari PDF SIGAP 29.
 * Idempoten: hanya mengisi bila kolom masih kosong (tidak menimpa editan admin).
 */
class KarakterPenerapanSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/karakter29_penerapan.json');
        if (! is_file($path)) {
            $this->command?->warn("File penerapan tidak ditemukan: {$path}");
            return;
        }

        $map = json_decode(file_get_contents($path), true) ?: [];
        $filled = 0;

        foreach ($map as $slug => $data) {
            $karakter = KarakterLuhur::where('slug', $slug)->first();
            if (! $karakter) {
                continue;
            }

            // Jangan timpa jika admin sudah mengisi penerapan.
            if ($karakter->hasPenerapan()) {
                continue;
            }

            $karakter->update([
                'penerapan' => [
                    'benar' => array_values($data['benar'] ?? []),
                    'salah' => array_values($data['salah'] ?? []),
                    'dampak_positif' => array_values($data['dampak_positif'] ?? []),
                    'dampak_negatif' => array_values($data['dampak_negatif'] ?? []),
                ],
            ]);
            $filled++;
        }

        $this->command?->info("Mengisi penerapan (benar/salah/dampak) untuk {$filled} karakter.");
    }
}
