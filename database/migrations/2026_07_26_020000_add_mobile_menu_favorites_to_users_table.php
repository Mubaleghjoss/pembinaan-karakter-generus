<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'mobile_menu_favorites')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('mobile_menu_favorites')->nullable()->after('theme_preference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'mobile_menu_favorites')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mobile_menu_favorites');
            });
        }
    }
};
