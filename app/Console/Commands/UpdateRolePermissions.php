<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class UpdateRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:update-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update role permissions for all roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating role permissions...');

        // Update admin role
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->update([
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
            ]);
            $this->info('Admin permissions updated');
        }

        // Update teacher role
        $teacher = Role::where('name', 'teacher')->first();
        if ($teacher) {
            $teacher->update([
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
            ]);
            $this->info('Pamong permissions updated');
        }

        $pkgManager = Role::where('name', 'pkg_manager')->first();
        if ($pkgManager) {
            $pkgManager->update([
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
            ]);
            $this->info('Pengurus PKG permissions updated');
        }

        // Update student role
        $student = Role::where('name', 'student')->first();
        if ($student) {
            $student->update([
                'permissions' => [
                    'view_attendance',
                    'view_qr',
                ],
            ]);
            $this->info('Student permissions updated');
        }

        $this->info('All role permissions updated successfully!');
    }
}
