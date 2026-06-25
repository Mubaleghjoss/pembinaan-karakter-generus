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
        Schema::create('laporan_penyaksian', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pelapor');
            $table->string('email_pelapor')->nullable();
            $table->string('phone_pelapor')->nullable();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->onDelete('set null');
            $table->string('nama_generus')->comment('Nama siswa yang dilaporkan');
            $table->text('karakter_belum_optimal')->comment('Karakter yang belum ditunjukkan dengan baik');
            $table->date('tanggal_kejadian');
            $table->text('deskripsi_kejadian')->nullable();
            $table->enum('status', ['pending', 'ditindaklanjuti', 'selesai'])->default('pending');
            $table->foreignId('ditindaklanjuti_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->text('catatan_tindak_lanjut')->nullable();
            $table->timestamp('ditindaklanjuti_at')->nullable();
            $table->timestamps();
            
            $table->index('siswa_id');
            $table->index('status');
            $table->index('tanggal_kejadian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_penyaksian');
    }
};
