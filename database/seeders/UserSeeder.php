<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $staffRole = Role::where('name', 'staff')->first();

        // Create Admin User
        User::create([
            'username' => 'admin',
            'email' => 'admin@pkg.com',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Teacher Users
        User::create([
            'username' => 'guru_matematika',
            'email' => 'guru1@pkg.com',
            'password' => Hash::make('guru123'),
            'role_id' => $teacherRole->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::create([
            'username' => 'guru_bahasa',
            'email' => 'guru2@pkg.com',
            'password' => Hash::make('guru123'),
            'role_id' => $teacherRole->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Staff User
        User::create([
            'username' => 'staff_tu',
            'email' => 'staff@pkg.com',
            'password' => Hash::make('staff123'),
            'role_id' => $staffRole->id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
