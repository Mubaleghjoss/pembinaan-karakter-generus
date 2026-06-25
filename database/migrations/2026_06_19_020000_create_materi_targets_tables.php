<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswa') && ! Schema::hasColumn('siswa', 'target_grade_override')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('target_grade_override', 20)->nullable()->after('kelas_id')->index();
            });
        }

        if (! Schema::hasTable('materi_targets')) {
            Schema::create('materi_targets', function (Blueprint $table) {
                $table->id();
                $table->string('category', 50);
                $table->string('target_grade', 20);
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['target_grade', 'category', 'is_active'], 'materi_targets_grade_category_active_index');
                $table->index(['sort_order', 'title'], 'materi_targets_order_title_index');
            });
        }

        if (! Schema::hasTable('siswa_materi_target_progress')) {
            Schema::create('siswa_materi_target_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('materi_target_id')->constrained('materi_targets')->cascadeOnDelete();
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->string('actor_type', 20)->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['siswa_id', 'materi_target_id'], 'siswa_materi_target_unique');
                $table->index(['materi_target_id', 'is_completed'], 'siswa_materi_target_progress_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_materi_target_progress');
        Schema::dropIfExists('materi_targets');

        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'target_grade_override')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('target_grade_override');
            });
        }
    }
};
