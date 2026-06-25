<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'plain_password')) {
            DB::table('users')->whereNotNull('plain_password')->update(['plain_password' => null]);
        }

        if (Schema::hasTable('siswa')) {
            if (Schema::hasColumn('siswa', 'password_plain')) {
                DB::table('siswa')->whereNotNull('password_plain')->update(['password_plain' => null]);
            }

            if (Schema::hasColumn('siswa', 'ortu_password_plain')) {
                DB::table('siswa')->whereNotNull('ortu_password_plain')->update(['ortu_password_plain' => null]);
            }
        }
    }

    public function down(): void
    {
        // Plain text password cleanup is intentionally irreversible.
    }
};
