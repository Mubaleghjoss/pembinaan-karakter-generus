<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi_targets')) {
            return;
        }

        if (! Schema::hasColumn('materi_targets', 'semester')) {
            Schema::table('materi_targets', function (Blueprint $table) {
                $table->unsignedTinyInteger('semester')->nullable()->after('target_grade');
                $table->index('semester', 'materi_targets_semester_index');
            });
        }

        if (! Schema::hasColumn('materi_targets', 'source_key')) {
            Schema::table('materi_targets', function (Blueprint $table) {
                $table->string('source_key', 120)->nullable()->after('is_active');
                $table->unique('source_key', 'materi_targets_source_key_unique');
            });
        }

        Schema::table('materi_targets', function (Blueprint $table) {
            $table->index(
                ['target_grade', 'semester', 'category', 'is_active'],
                'materi_targets_grade_semester_category_active_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('materi_targets')) {
            return;
        }

        Schema::table('materi_targets', function (Blueprint $table) {
            $table->dropIndex('materi_targets_grade_semester_category_active_index');
        });

        if (Schema::hasColumn('materi_targets', 'source_key')) {
            Schema::table('materi_targets', function (Blueprint $table) {
                $table->dropUnique('materi_targets_source_key_unique');
                $table->dropColumn('source_key');
            });
        }

        if (Schema::hasColumn('materi_targets', 'semester')) {
            Schema::table('materi_targets', function (Blueprint $table) {
                $table->dropIndex('materi_targets_semester_index');
                $table->dropColumn('semester');
            });
        }
    }
};
