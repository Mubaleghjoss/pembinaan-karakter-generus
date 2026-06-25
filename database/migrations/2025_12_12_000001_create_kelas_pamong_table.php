<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel pivot untuk relasi many-to-many antara Kelas dan Pamong.
     * 1 Kelas bisa memiliki banyak Pamong (2-3 pamong per kelas).
     * 1 Pamong bisa mengajar di banyak Kelas.
     */
    public function up(): void
    {
        Schema::create('kelas_pamong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->foreignId('pamong_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('pengajar'); // pengajar, wali_kelas, pendamping
            $table->timestamps();

            // Unique constraint: 1 pamong hanya bisa 1x di 1 kelas
            $table->unique(['kelas_id', 'pamong_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_pamong');
    }
};
