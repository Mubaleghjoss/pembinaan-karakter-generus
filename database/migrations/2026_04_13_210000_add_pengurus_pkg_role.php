<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['name' => 'pkg_manager'],
            [
                'display_name' => 'Pengurus PKG',
                'description' => 'Pengurus operasional PKG dengan akses terbatas per bidang',
                'permissions' => json_encode([
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
                ], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasUsers = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', 'pkg_manager')
            ->exists();

        if (! $hasUsers) {
            DB::table('roles')->where('name', 'pkg_manager')->delete();
        }
    }
};
