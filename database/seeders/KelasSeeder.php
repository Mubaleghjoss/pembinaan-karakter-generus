<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            [
                'nama' => 'Kelas 1A',
                'tingkat' => '1',
                'kode_kelas' => '1A',
                'pamong_id' => null,
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 1A untuk siswa tingkat 1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kelas 1B',
                'tingkat' => '1',
                'kode_kelas' => '1B',
                'pamong_id' => null,
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 1B untuk siswa tingkat 1',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kelas 2A',
                'tingkat' => '2',
                'kode_kelas' => '2A',
                'pamong_id' => null,
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 2A untuk siswa tingkat 2',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kelas 2B',
                'tingkat' => '2',
                'kode_kelas' => '2B',
                'pamong_id' => null,
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 2B untuk siswa tingkat 2',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kelas 3A',
                'tingkat' => '3',
                'kode_kelas' => '3A',
                'pamong_id' => null,
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 3A untuk siswa tingkat 3',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('kelas')->insert($classes);
    }
}
