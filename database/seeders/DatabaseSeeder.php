<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'System Administrator',
            ]
        );

        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            [
                'display_name' => 'Pamong',
                'description' => 'Pamong',
            ]
        );

        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            [
                'display_name' => 'Siswa',
                'description' => 'Siswa',
            ]
        );

        // Create admin user
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@grattendance.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]
        );

        // Create sample teacher
        $teacher = User::firstOrCreate(
            ['username' => 'guru'],
            [
                'email' => 'guru@grattendance.com',
                'password' => Hash::make('guru123'),
                'email_verified_at' => now(),
                'role_id' => $teacherRole->id,
                'status' => 'active',
            ]
        );

        // Create sample classes
        $kelas1 = Kelas::firstOrCreate(
            ['kode_kelas' => 'K1A'],
            [
                'nama' => 'Kelas 1A',
                'tingkat' => '1',
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 1A untuk siswa tingkat 1',
                'is_active' => true,
            ]
        );

        $kelas2 = Kelas::firstOrCreate(
            ['kode_kelas' => 'K2A'],
            [
                'nama' => 'Kelas 2A',
                'tingkat' => '2',
                'kapasitas' => 30,
                'deskripsi' => 'Kelas 2A untuk siswa tingkat 2',
                'is_active' => true,
            ]
        );

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin credentials: admin@grattendance.com / admin123');
        $this->command->info('Pamong credentials: guru@grattendance.com / guru123');
    }
}
