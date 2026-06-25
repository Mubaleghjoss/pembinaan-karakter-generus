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
        Schema::create('pr_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_id')->constrained('pekerjaan_rumah')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->enum('proof_type', ['photo', 'video', 'link']);
            $table->string('proof_path');
            $table->timestamp('submitted_at');
            $table->enum('status', ['pending', 'verified', 'revision'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->boolean('is_late')->default(false);
            $table->timestamps();

            // Ensure one submission per student per PR
            $table->unique(['pr_id', 'siswa_id']);
            
            // Index for faster queries
            $table->index(['status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_submissions');
    }
};
