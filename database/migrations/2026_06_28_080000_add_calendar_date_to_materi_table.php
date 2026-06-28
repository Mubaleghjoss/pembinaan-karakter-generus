<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi') || Schema::hasColumn('materi', 'calendar_date')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            $table->date('calendar_date')->nullable()->after('bulan')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('materi') || ! Schema::hasColumn('materi', 'calendar_date')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            $table->dropColumn('calendar_date');
        });
    }
};
