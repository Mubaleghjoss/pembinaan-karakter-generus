<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access',
                'permissions' => [
                    'view_students',
                    'manage_students',
                    'view_classes',
                    'manage_classes',
                    'view_attendance',
                    'manage_attendance',
                    'view_reports',
                    'manage_reports',
                    'view_qr',
                    'manage_qr',
                    'view_users',
                    'manage_users',
                    'view_roles',
                    'manage_roles',
                ],
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Pamong',
                'description' => 'Pamong with student and class management access',
                'permissions' => [
                    'view_students',
                    'manage_students',
                    'view_classes',
                    'manage_classes',
                    'view_attendance',
                    'manage_attendance',
                    'view_reports',
                    'view_qr',
                    'manage_qr',
                ],
            ],
            [
                'name' => 'pkg_manager',
                'display_name' => 'Pengurus PKG',
                'description' => 'Pengurus operasional PKG dengan akses terbatas per bidang',
                'permissions' => [
                    'view_students',
                    'manage_students',
                    'view_classes',
                    'manage_classes',
                    'view_attendance',
                    'manage_attendance',
                    'view_reports',
                    'manage_reports',
                    'view_qr',
                    'manage_qr',
                ],
            ],
            [
                'name' => 'student',
                'display_name' => 'Siswa',
                'description' => 'Student with limited access',
                'permissions' => [
                    'view_attendance',
                    'view_qr',
                ],
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Staff with attendance management',
                'permissions' => [
                    'view_students',
                    'view_classes',
                    'view_attendance',
                    'manage_attendance',
                    'view_qr',
                    'manage_qr',
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role + ['is_active' => true]
            );
        }
    }
}
