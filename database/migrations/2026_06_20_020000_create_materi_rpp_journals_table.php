<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('materi_rpp_journals')) {
            return;
        }

        Schema::create('materi_rpp_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_reminder_id')->nullable()->unique()->constrained('schedule_reminders')->nullOnDelete();
            $table->foreignId('materi_id')->nullable()->constrained('materi')->cascadeOnDelete();
            $table->date('journal_date');
            $table->unsignedInteger('session_number')->nullable();
            $table->string('session_type', 30)->nullable();
            $table->string('materi_title')->nullable();
            $table->string('target_page_range', 60)->nullable();
            $table->unsignedInteger('target_page_start')->nullable();
            $table->unsignedInteger('target_page_end')->nullable();
            $table->unsignedInteger('actual_page_start')->nullable();
            $table->unsignedInteger('actual_page_end')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('teacher_name')->nullable();
            $table->unsignedBigInteger('teacher_user_id')->nullable();
            $table->string('realization_status', 30)->default('terlaksana');
            $table->text('notes')->nullable();
            $table->text('obstacles')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['materi_id', 'journal_date'], 'materi_rpp_journals_materi_date_index');
            $table->index(['realization_status', 'journal_date'], 'materi_rpp_journals_status_date_index');
            $table->index('teacher_user_id', 'materi_rpp_journals_teacher_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi_rpp_journals');
    }
};
