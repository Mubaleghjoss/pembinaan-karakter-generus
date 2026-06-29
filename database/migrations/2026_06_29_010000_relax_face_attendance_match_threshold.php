<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'face_attendance_match_threshold')
            ->whereIn('value', ['0.60', '0.6'])
            ->update([
                'value' => '0.85',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'face_attendance_match_threshold')
            ->where('value', '0.85')
            ->update([
                'value' => '0.60',
                'updated_at' => now(),
            ]);
    }
};
