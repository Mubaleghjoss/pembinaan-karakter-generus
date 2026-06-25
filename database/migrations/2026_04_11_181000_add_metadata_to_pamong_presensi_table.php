<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pamong_presensi', 'metadata')) {
            Schema::table('pamong_presensi', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('verified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pamong_presensi', 'metadata')) {
            Schema::table('pamong_presensi', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
