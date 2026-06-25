<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->json('point_settings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        $defaults = [
            'points_hadir' => '10',
            'points_terlambat' => '5',
            'points_izin' => '2',
            'points_sakit' => '2',
            'points_alpha' => '0',
            'points_karakter' => '5',
            'points_streak_7' => '20',
            'points_streak_30' => '50',
            'points_perfect_month' => '100',
        ];

        foreach ($defaults as $key => $value) {
            if (!DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'group' => 'gamification',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('point_periods');

        DB::table('settings')->whereIn('key', [
            'points_hadir',
            'points_terlambat',
            'points_izin',
            'points_sakit',
            'points_alpha',
            'points_karakter',
            'points_streak_7',
            'points_streak_30',
            'points_perfect_month',
        ])->delete();
    }
};
