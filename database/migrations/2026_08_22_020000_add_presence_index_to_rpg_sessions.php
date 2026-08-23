<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rpg_game_sessions')) {
            return;
        }

        Schema::table('rpg_game_sessions', function (Blueprint $table) {
            // Query presence & online players menyaring rpg_map_id + updated_at.
            // Index gabungan ini mempercepatnya (hindari full scan saat banyak sesi).
            $table->index(['rpg_map_id', 'updated_at'], 'rpg_sessions_map_updated_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rpg_game_sessions')) {
            return;
        }

        Schema::table('rpg_game_sessions', function (Blueprint $table) {
            $table->dropIndex('rpg_sessions_map_updated_idx');
        });
    }
};
