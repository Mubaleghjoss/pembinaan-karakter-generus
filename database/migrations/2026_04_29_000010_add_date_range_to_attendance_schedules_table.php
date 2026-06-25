<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance_schedules', 'start_date')) {
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->date('start_date')->nullable()->after('target_audience');
            });
        }

        if (! Schema::hasColumn('attendance_schedules', 'end_date')) {
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date');
            });
        }

        DB::table('attendance_schedules')
            ->whereNull('start_date')
            ->whereNull('end_date')
            ->update([
                'start_date' => '2026-04-23',
                'end_date' => '2026-04-23',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_schedules', 'end_date')) {
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->dropColumn('end_date');
            });
        }

        if (Schema::hasColumn('attendance_schedules', 'start_date')) {
            Schema::table('attendance_schedules', function (Blueprint $table) {
                $table->dropColumn('start_date');
            });
        }
    }
};
