<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('karakter', function (Blueprint $table) {
            $table->enum('jenis_penyelesaian', ['checklist', 'teks', 'klik'])->default('checklist')->after('deskripsi');
            $table->text('target_teks')->nullable()->after('jenis_penyelesaian');
            $table->integer('target_klik')->nullable()->after('target_teks');
        });

        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->text('hasil_teks')->nullable()->after('verified_at');
            $table->json('click_history')->nullable()->after('hasil_teks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->dropColumn(['hasil_teks', 'click_history']);
        });

        Schema::table('karakter', function (Blueprint $table) {
            $table->dropColumn(['jenis_penyelesaian', 'target_teks', 'target_klik']);
        });
    }
};
