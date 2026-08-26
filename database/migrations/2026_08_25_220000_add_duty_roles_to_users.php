<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peran tugas (duty roles) untuk akun operasional: satu akun bisa memegang
 * beberapa peran sekaligus, mis. Pengisi Presensi + Verifikator Tugas PKG.
 * Disimpan sebagai array slug JSON agar tidak perlu tabel pivot baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'duty_roles')) {
                $table->json('duty_roles')->nullable()->after('organizational_sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'duty_roles')) {
                $table->dropColumn('duty_roles');
            }
        });
    }
};
