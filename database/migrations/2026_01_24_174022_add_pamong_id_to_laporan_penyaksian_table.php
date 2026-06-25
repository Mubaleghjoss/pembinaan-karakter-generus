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
        Schema::table('laporan_penyaksian', function (Blueprint $table) {
            $table->unsignedBigInteger('pamong_id')->nullable()->after('siswa_id');
            $table->foreign('pamong_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_penyaksian', function (Blueprint $table) {
            $table->dropForeign(['pamong_id']);
            $table->dropColumn('pamong_id');
        });
    }
};
