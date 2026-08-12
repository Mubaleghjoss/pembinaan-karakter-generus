<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_reading_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('token_hash', 64);
            $table->string('status', 20)->default('active')->index();
            $table->unsignedTinyInteger('row_count')->default(12);
            $table->json('last_position')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_reading_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sheet_id')->nullable()->constrained('quran_reading_sheets')->nullOnDelete();
            $table->string('uploaded_by_type', 20);
            $table->unsignedBigInteger('uploaded_by_id');
            $table->string('original_path');
            $table->string('processed_path')->nullable();
            $table->string('status', 24)->default('awaiting_confirmation')->index();
            $table->json('extracted_rows')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['uploaded_by_type', 'uploaded_by_id']);
        });

        Schema::create('quran_reading_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('reading_date')->index();
            $table->unsignedSmallInteger('page_start');
            $table->unsignedSmallInteger('page_end');
            $table->unsignedTinyInteger('surah_start');
            $table->unsignedSmallInteger('ayah_start');
            $table->unsignedTinyInteger('surah_end');
            $table->unsignedSmallInteger('ayah_end');
            $table->string('mushaf_label', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 20)->default('manual');
            $table->string('submitted_by_type', 20);
            $table->unsignedBigInteger('submitted_by_id');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->foreignId('sheet_id')->nullable()->constrained('quran_reading_sheets')->nullOnDelete();
            $table->unsignedTinyInteger('sheet_row_number')->nullable();
            $table->foreignId('scan_id')->nullable()->constrained('quran_reading_scans')->nullOnDelete();
            $table->timestamps();
            $table->index(['siswa_id', 'status', 'reading_date']);
            $table->index(['submitted_by_type', 'submitted_by_id']);
            $table->unique(['sheet_id', 'sheet_row_number'], 'quran_sheet_row_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_reading_entries');
        Schema::dropIfExists('quran_reading_scans');
        Schema::dropIfExists('quran_reading_sheets');
    }
};
