<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance_schedules', 'target_audience')) {
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->string('target_audience', 20)->default('all')->after('days');
            });
        }

        DB::table('attendance_schedules')
            ->whereNull('target_audience')
            ->orWhere('target_audience', '')
            ->update(['target_audience' => 'all']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_schedules', 'target_audience')) {
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->dropColumn('target_audience');
            });
        }
    }
};
