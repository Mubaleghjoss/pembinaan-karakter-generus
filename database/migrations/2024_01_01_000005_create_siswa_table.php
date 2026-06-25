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
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 30)->unique();
            $table->string('nama', 120);
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas');
            $table->string('foto_path')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');

            // QR Code Security Fields
            $table->string('qr_secret_salt', 64)->unique();
            $table->string('qr_token', 128)->nullable();
            $table->timestamp('qr_token_expires_at')->nullable();
            $table->integer('qr_scan_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();

            // Parent/Guardian Information
            $table->string('nama_wali', 120)->nullable();
            $table->string('phone_wali', 30)->nullable();
            $table->string('email_wali', 160)->nullable();

            // Additional Security
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nis', 'is_active']);
            $table->index(['nama', 'kelas_id']);
            $table->index('qr_secret_salt');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
