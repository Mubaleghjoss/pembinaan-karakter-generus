<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('theme_settings', 'sidebar_color')) {
                $table->string('sidebar_color')->default('#ffffff')->after('light_color');
            }

            if (!Schema::hasColumn('theme_settings', 'topbar_color')) {
                $table->string('topbar_color')->default('#ffffff')->after('sidebar_color');
            }
        });

        DB::table('theme_settings')
            ->whereNull('sidebar_color')
            ->update(['sidebar_color' => '#ffffff']);

        DB::table('theme_settings')
            ->whereNull('topbar_color')
            ->update(['topbar_color' => '#ffffff']);
    }

    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('theme_settings', 'sidebar_color')) {
                $dropColumns[] = 'sidebar_color';
            }

            if (Schema::hasColumn('theme_settings', 'topbar_color')) {
                $dropColumns[] = 'topbar_color';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
