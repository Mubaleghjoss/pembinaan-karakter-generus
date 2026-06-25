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
            $table->string('kategori', 20)->default('harian')->after('deskripsi'); // harian, mingguan, bulanan
            $table->date('tanggal_mulai')->nullable()->after('kategori');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->integer('poin')->default(10)->after('tanggal_selesai'); // configurable points per task
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karakter', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'tanggal_mulai', 'tanggal_selesai', 'poin']);
        });
    }
};
