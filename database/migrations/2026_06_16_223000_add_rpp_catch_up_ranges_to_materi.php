<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi') || Schema::hasColumn('materi', 'rpp_catch_up_ranges')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            $table->json('rpp_catch_up_ranges')->nullable()->after('rpp_extra_sessions');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('materi') || ! Schema::hasColumn('materi', 'rpp_catch_up_ranges')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            $table->dropColumn('rpp_catch_up_ranges');
        });
    }
};
