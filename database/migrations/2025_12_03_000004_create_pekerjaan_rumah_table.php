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
        Schema::create('pekerjaan_rumah', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi');
            $table->foreignId('karakter_id')->constrained('karakter')->onDelete('cascade');
            $table->datetime('deadline');
            $table->enum('proof_type', ['photo', 'video', 'link'])->default('photo');
            $table->enum('target_type', ['all', 'kelas'])->default('all');
            $table->foreignId('target_kelas_id')->nullable()->constrained('kelas')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Index for faster queries
            $table->index(['deadline', 'is_active']);
            $table->index('target_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pekerjaan_rumah');
    }
};
