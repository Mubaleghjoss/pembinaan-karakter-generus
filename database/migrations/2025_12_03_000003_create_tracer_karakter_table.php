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
        Schema::create('tracer_karakter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignId('karakter_id')->constrained('karakter')->onDelete('cascade');
            $table->foreignId('pamong_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('checked_at');
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Index for faster queries
            $table->index(['siswa_id', 'karakter_id']);
            $table->index(['pamong_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracer_karakter');
    }
};
