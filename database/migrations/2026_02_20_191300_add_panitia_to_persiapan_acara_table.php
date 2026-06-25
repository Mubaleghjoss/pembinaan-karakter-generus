<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persiapan_acara', function (Blueprint $table) {
            // Single JSON column for all PJ assignments: {"pj_acara": [1,2], "pj_konsumsi": [3], ...}
            $table->json('panitia')->nullable()->after('tim_dokumentasi');
        });
    }

    public function down(): void
    {
        Schema::table('persiapan_acara', function (Blueprint $table) {
            $table->dropColumn('panitia');
        });
    }
};
