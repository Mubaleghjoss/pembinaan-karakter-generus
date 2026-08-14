<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_reading_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->unsignedSmallInteger('cycle_number');
            $table->string('status', 20)->default('active')->index();
            $table->date('started_at');
            $table->date('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['siswa_id', 'cycle_number'], 'quran_cycle_student_number_unique');
            $table->index(['siswa_id', 'status']);
        });

        Schema::table('quran_reading_sheets', function (Blueprint $table) {
            $table->string('sheet_type', 24)->default('weekly')->after('template_version')->index();
            $table->foreignId('cycle_id')->nullable()->after('sheet_type')->constrained('quran_reading_cycles')->nullOnDelete();
            $table->json('metadata')->nullable()->after('last_position');
        });

        Schema::create('quran_surah_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('quran_reading_cycles')->cascadeOnDelete();
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('last_ayah')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->string('source', 24)->default('manual');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['cycle_id', 'surah_number'], 'quran_cycle_surah_unique');
        });

        Schema::create('quran_progress_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('quran_reading_cycles')->cascadeOnDelete();
            $table->foreignId('sheet_id')->nullable()->constrained('quran_reading_sheets')->nullOnDelete();
            $table->foreignId('scan_id')->nullable()->unique()->constrained('quran_reading_scans')->nullOnDelete();
            $table->date('marked_on')->nullable();
            $table->json('completed_surahs')->nullable();
            $table->json('ambiguous_surahs')->nullable();
            $table->unsignedTinyInteger('active_surah')->nullable();
            $table->unsignedSmallInteger('active_ayah')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('submitted_by_type', 20);
            $table->unsignedBigInteger('submitted_by_id')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['siswa_id', 'status']);
            $table->index(['cycle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_progress_submissions');
        Schema::dropIfExists('quran_surah_progress');
        Schema::table('quran_reading_sheets', function (Blueprint $table) {
            $table->dropForeign(['cycle_id']);
            $table->dropColumn(['sheet_type', 'cycle_id', 'metadata']);
        });
        Schema::dropIfExists('quran_reading_cycles');
    }
};
