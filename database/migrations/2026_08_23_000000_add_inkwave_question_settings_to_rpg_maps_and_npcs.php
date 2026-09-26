<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rpg_maps')) {
            Schema::table('rpg_maps', function (Blueprint $table) {
                if (! Schema::hasColumn('rpg_maps', 'inkwave_randomize_questions')) {
                    $table->boolean('inkwave_randomize_questions')->default(true)->after('ammo_pickups_count');
                }
                if (! Schema::hasColumn('rpg_maps', 'inkwave_question_difficulty')) {
                    $table->string('inkwave_question_difficulty')->default('all')->after('inkwave_randomize_questions');
                }
                if (! Schema::hasColumn('rpg_maps', 'inkwave_repeat_policy')) {
                    $table->string('inkwave_repeat_policy')->default('allow_repeat')->after('inkwave_question_difficulty');
                }
                if (! Schema::hasColumn('rpg_maps', 'inkwave_max_questions')) {
                    $table->unsignedInteger('inkwave_max_questions')->default(10)->after('inkwave_repeat_policy');
                }
                if (! Schema::hasColumn('rpg_maps', 'inkwave_question_time_limit_seconds')) {
                    $table->unsignedInteger('inkwave_question_time_limit_seconds')->default(30)->after('inkwave_max_questions');
                }
            });
        }

        if (Schema::hasTable('rpg_npcs') && ! Schema::hasColumn('rpg_npcs', 'difficulty')) {
            Schema::table('rpg_npcs', function (Blueprint $table) {
                $table->string('difficulty')->default('medium')->after('poin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rpg_npcs') && Schema::hasColumn('rpg_npcs', 'difficulty')) {
            Schema::table('rpg_npcs', function (Blueprint $table) {
                $table->dropColumn('difficulty');
            });
        }

        if (Schema::hasTable('rpg_maps')) {
            Schema::table('rpg_maps', function (Blueprint $table) {
                foreach ([
                    'inkwave_question_time_limit_seconds',
                    'inkwave_max_questions',
                    'inkwave_repeat_policy',
                    'inkwave_question_difficulty',
                    'inkwave_randomize_questions',
                ] as $column) {
                    if (Schema::hasColumn('rpg_maps', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
