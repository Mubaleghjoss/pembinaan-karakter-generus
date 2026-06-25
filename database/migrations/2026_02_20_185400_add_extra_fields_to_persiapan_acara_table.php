<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persiapan_acara', function (Blueprint $table) {
            $table->integer('nomor_ke')->nullable()->after('judul_acara');
            $table->string('peserta')->nullable()->after('tempat');
            $table->string('pakaian')->nullable()->after('peserta');
            $table->string('waktu_selesai')->nullable()->after('waktu_acara');
            $table->json('catatan_tambahan')->nullable()->after('perlengkapan');
            $table->json('rundown')->nullable()->after('catatan_tambahan');
        });
    }

    public function down(): void
    {
        Schema::table('persiapan_acara', function (Blueprint $table) {
            $table->dropColumn(['nomor_ke', 'peserta', 'pakaian', 'waktu_selesai', 'catatan_tambahan', 'rundown']);
        });
    }
};
