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
            if (!Schema::hasColumn('rpg_maps', 'enemies')) {
                $table->json('enemies')->nullable()->after('obstacles');
            }

            if (!Schema::hasColumn('rpg_maps', 'difficulty')) {
                $table->string('difficulty')->default('easy')->after('enemies');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rpg_maps')) {
            return;
        }

        Schema::table('rpg_maps', function (Blueprint $table) {
            if (Schema::hasColumn('rpg_maps', 'difficulty')) {
                $table->dropColumn('difficulty');
            }

            if (Schema::hasColumn('rpg_maps', 'enemies')) {
                $table->dropColumn('enemies');
            }
        });
    }
};
