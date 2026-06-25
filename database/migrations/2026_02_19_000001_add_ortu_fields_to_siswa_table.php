<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('ortu_username')->nullable()->after('email_wali');
            $table->string('ortu_password')->nullable()->after('ortu_username');
            $table->string('ortu_password_plain')->nullable()->after('ortu_password');
        });

        // Set default ortu_username = NIS for all existing siswa
        DB::table('siswa')->whereNull('ortu_username')->update([
            'ortu_username' => DB::raw('nis'),
        ]);
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['ortu_username', 'ortu_password', 'ortu_password_plain']);
        });
    }
};
