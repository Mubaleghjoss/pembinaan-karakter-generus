<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Level;
use App\Models\Badge;
use App\Models\Siswa;
use App\Models\SiswaPoint;

class GamificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedLevels();
        $this->seedBadges();
        $this->seedSiswaPoints();
    }

    private function seedLevels(): void
    {
        $levels = [
            [
                'level' => 1,
                'nama' => 'Pemula',
                'deskripsi' => 'Siswa baru yang baru memulai perjalanan di PKG',
                'min_points' => 0,
                'max_points' => 99,
                'warna' => '#CD7F32',
                'benefits' => ['Akses dasar ke semua fitur']
            ],
            [
                'level' => 2,
                'nama' => 'Berkembang',
                'deskripsi' => 'Siswa yang mulai menunjukkan konsistensi',
                'min_points' => 100,
                'max_points' => 299,
                'warna' => '#C0C0C0',
                'benefits' => ['Badge khusus', 'Prioritas dalam leaderboard']
            ],
            [
                'level' => 3,
                'nama' => 'Baik',
                'deskripsi' => 'Siswa dengan performa yang baik dan konsisten',
                'min_points' => 300,
                'max_points' => 599,
                'warna' => '#FFD700',
                'benefits' => ['Badge eksklusif', 'Akses fitur premium', 'Sertifikat digital']
            ],
            [
                'level' => 4,
                'nama' => 'Sangat Baik',
                'deskripsi' => 'Siswa teladan dengan dedikasi tinggi',
                'min_points' => 600,
                'max_points' => 999,
                'warna' => '#00BFFF',
                'benefits' => ['Badge diamond', 'Prioritas utama', 'Reward khusus']
            ],
            [
                'level' => 5,
                'nama' => 'Teladan',
                'deskripsi' => 'Siswa terbaik dengan pencapaian luar biasa',
                'min_points' => 1000,
                'max_points' => null,
                'warna' => '#9400D3',
                'benefits' => ['Badge crown', 'Hall of Fame', 'Reward eksklusif', 'Mentor junior']
            ],
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(
                ['level' => $level['level']],
                $level
            );
        }
    }

    private function seedBadges(): void
    {
        $badges = [
            // Attendance badges
            [
                'nama' => 'Rajin Hadir',
                'deskripsi' => 'Hadir 30 hari berturut-turut tanpa absen',
                'kategori' => 'attendance',
                'warna' => '#10B981',
                'poin_reward' => 50,
                'kriteria' => ['type' => 'attendance_streak', 'value' => 30]
            ],
            [
                'nama' => 'Tepat Waktu',
                'deskripsi' => 'Hadir tepat waktu 20 kali berturut-turut',
                'kategori' => 'attendance',
                'warna' => '#3B82F6',
                'poin_reward' => 30,
                'kriteria' => ['type' => 'attendance_count', 'value' => 20]
            ],
            [
                'nama' => 'Perfect Month',
                'deskripsi' => 'Hadir sempurna selama 1 bulan penuh',
                'kategori' => 'attendance',
                'warna' => '#F59E0B',
                'poin_reward' => 100,
                'kriteria' => ['type' => 'perfect_month', 'value' => 1]
            ],
            [
                'nama' => 'Konsisten',
                'deskripsi' => 'Hadir 100 hari dalam satu semester',
                'kategori' => 'attendance',
                'warna' => '#8B5CF6',
                'poin_reward' => 150,
                'kriteria' => ['type' => 'attendance_count', 'value' => 100]
            ],
            // Character badges
            [
                'nama' => 'Karakter Baik',
                'deskripsi' => 'Mendapat 100 ceklis karakter positif',
                'kategori' => 'character',
                'warna' => '#EC4899',
                'poin_reward' => 50,
                'kriteria' => ['type' => 'character_count', 'value' => 100]
            ],
            [
                'nama' => 'Teladan Karakter',
                'deskripsi' => 'Streak karakter 14 hari berturut-turut',
                'kategori' => 'character',
                'warna' => '#14B8A6',
                'poin_reward' => 40,
                'kriteria' => ['type' => 'character_streak', 'value' => 14]
            ],
            [
                'nama' => 'Bintang Karakter',
                'deskripsi' => 'Mendapat 500 ceklis karakter positif',
                'kategori' => 'character',
                'warna' => '#F97316',
                'poin_reward' => 200,
                'kriteria' => ['type' => 'character_count', 'value' => 500]
            ],
            // General badges
            [
                'nama' => 'Rising Star',
                'deskripsi' => 'Mencapai 100 poin total',
                'kategori' => 'general',
                'warna' => '#EF4444',
                'poin_reward' => 20,
                'kriteria' => ['type' => 'total_points', 'value' => 100]
            ],
            [
                'nama' => 'Super Star',
                'deskripsi' => 'Mencapai 500 poin total',
                'kategori' => 'general',
                'warna' => '#6366F1',
                'poin_reward' => 50,
                'kriteria' => ['type' => 'total_points', 'value' => 500]
            ],
            [
                'nama' => 'Legend',
                'deskripsi' => 'Mencapai 1000 poin total',
                'kategori' => 'general',
                'warna' => '#A855F7',
                'poin_reward' => 100,
                'kriteria' => ['type' => 'total_points', 'value' => 1000]
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['nama' => $badge['nama']],
                $badge
            );
        }
    }

    private function seedSiswaPoints(): void
    {
        // Create SiswaPoint record for all existing siswa
        $siswaIds = Siswa::pluck('id');
        
        foreach ($siswaIds as $siswaId) {
            SiswaPoint::firstOrCreate(
                ['siswa_id' => $siswaId],
                [
                    'total_points' => 0,
                    'level' => 1,
                    'attendance_points' => 0,
                    'character_points' => 0,
                    'bonus_points' => 0,
                    'spent_points' => 0,
                    'attendance_streak' => 0,
                    'character_streak' => 0
                ]
            );
        }
    }
}
