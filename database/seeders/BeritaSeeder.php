<?php

namespace Database\Seeders;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BeritaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();

        if (! $admin) {
            $this->command->error('Admin user not found. Please run DatabaseSeeder first.');

            return;
        }

        $beritaData = [
            [
                'judul' => 'Selamat Datang di PKG Presensi',
                'isi' => 'Sistem Presensi QR Code untuk Pembinaan Karakter Generus. Dengan sistem ini, presensi menjadi lebih mudah, cepat, dan akurat. Cukup scan QR Code pada kartu peserta Anda dan presensi akan tercatat secara otomatis.',
            ],
            [
                'judul' => 'Kegiatan Perkemahan Sabtu Minggu',
                'isi' => 'Kegiatan Perkemahan Sabtu Minggu akan dilaksanakan pada tanggal 25-26 November 2025. Semua peserta PKG diharapkan untuk mengikuti kegiatan ini. Jangan lupa membawa perlengkapan camping dan semangat!',
            ],
            [
                'judul' => 'Workshop Kepemimpinan',
                'isi' => 'Workshop Kepemimpinan akan diadakan pada hari Sabtu, 2 Desember 2025. Kegiatan ini bertujuan untuk mengembangkan jiwa kepemimpinan dan karakter generus pada setiap peserta.',
            ],
        ];

        foreach ($beritaData as $data) {
            Berita::create([
                'judul' => $data['judul'],
                'slug' => Str::slug($data['judul']),
                'isi' => $data['isi'],
                'author_id' => $admin->id,
                'published_at' => now(),
                'view_count' => 0,
            ]);
        }

        $this->command->info('Sample berita created successfully!');
    }
}
