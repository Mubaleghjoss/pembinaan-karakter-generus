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
            if (!Schema::hasColumn('rpg_maps', 'shield_pickups_count')) {
                $table->unsignedInteger('shield_pickups_count')->default(1)->after('ammo_per_pickup');
            }

            if (!Schema::hasColumn('rpg_maps', 'ammo_pickups_count')) {
                $table->unsignedInteger('ammo_pickups_count')->default(2)->after('shield_pickups_count');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rpg_maps')) {
            return;
        }

        Schema::table('rpg_maps', function (Blueprint $table) {
            if (Schema::hasColumn('rpg_maps', 'ammo_pickups_count')) {
                $table->dropColumn('ammo_pickups_count');
            }

            if (Schema::hasColumn('rpg_maps', 'shield_pickups_count')) {
                $table->dropColumn('shield_pickups_count');
            }
        });
    }
};
