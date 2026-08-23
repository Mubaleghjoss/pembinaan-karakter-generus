<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rpg_maps', function (Blueprint $table) {
            if (! Schema::hasColumn('rpg_maps', 'boss_enabled')) {
                $table->boolean('boss_enabled')->default(false)->after('enemies');
            }
            if (! Schema::hasColumn('rpg_maps', 'boss_config')) {
                $table->json('boss_config')->nullable()->after('boss_enabled');
            }
        });

        if (Schema::hasTable('rpg_game_sessions')) {
            Schema::table('rpg_game_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('rpg_game_sessions', 'boss_defeated_at')) {
                    $table->timestamp('boss_defeated_at')->nullable()->after('completed_at');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('rpg_maps', function (Blueprint $table) {
            if (Schema::hasColumn('rpg_maps', 'boss_config')) {
                $table->dropColumn('boss_config');
            }
            if (Schema::hasColumn('rpg_maps', 'boss_enabled')) {
                $table->dropColumn('boss_enabled');
            }
        });

        if (Schema::hasTable('rpg_game_sessions')) {
            Schema::table('rpg_game_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('rpg_game_sessions', 'boss_defeated_at')) {
                    $table->dropColumn('boss_defeated_at');
                }
            });
        }
    }
};
