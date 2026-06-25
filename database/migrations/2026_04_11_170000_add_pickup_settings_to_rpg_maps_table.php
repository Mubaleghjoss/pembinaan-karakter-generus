<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rpg_maps')) {
            return;
        }

        Schema::table('rpg_maps', function (Blueprint $table) {
            if (!Schema::hasColumn('rpg_maps', 'shield_duration_seconds')) {
                $table->unsignedInteger('shield_duration_seconds')->default(8)->after('difficulty');
            }

            if (!Schema::hasColumn('rpg_maps', 'ammo_per_pickup')) {
                $table->unsignedInteger('ammo_per_pickup')->default(3)->after('shield_duration_seconds');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rpg_maps')) {
            return;
        }

        Schema::table('rpg_maps', function (Blueprint $table) {
            if (Schema::hasColumn('rpg_maps', 'ammo_per_pickup')) {
                $table->dropColumn('ammo_per_pickup');
            }

            if (Schema::hasColumn('rpg_maps', 'shield_duration_seconds')) {
                $table->dropColumn('shield_duration_seconds');
            }
        });
    }
};
