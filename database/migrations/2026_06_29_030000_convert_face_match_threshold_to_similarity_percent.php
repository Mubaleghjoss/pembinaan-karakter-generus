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
            ->whereIn('value', ['0.60', '0.6', '0.85', '4.00', '4'])
            ->update([
                'value' => '35.00',
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
            ->where('value', '35.00')
            ->update([
                'value' => '4.00',
                'updated_at' => now(),
            ]);
    }
};
