<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            if (! Schema::hasColumn('materi', 'rpp_teacher_pool')) {
                $table->json('rpp_teacher_pool')->nullable()->after('rpp_catch_up_ranges');
            }

            if (! Schema::hasColumn('materi', 'rpp_teacher_overrides')) {
                $table->json('rpp_teacher_overrides')->nullable()->after('rpp_teacher_pool');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('materi')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            foreach (['rpp_teacher_overrides', 'rpp_teacher_pool'] as $column) {
                if (Schema::hasColumn('materi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
