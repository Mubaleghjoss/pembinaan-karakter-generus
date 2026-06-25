<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default Schedule'); // Nama jadwal
            $table->time('open_time')->default('06:00:00'); // Jam buka presensi
            $table->time('late_threshold')->default('07:00:00'); // Batas waktu terlambat
            $table->time('close_time')->default('23:59:00'); // Jam tutup presensi
            $table->json('days')->nullable(); // Hari aktif: ["monday","tuesday",...]
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default schedule
        DB::table('attendance_schedules')->insert([
            'name' => 'Jadwal Default',
            'open_time' => '06:00:00',
            'late_threshold' => '07:00:00',
            'close_time' => '23:59:00',
            'days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
            'is_active' => true,
            'description' => 'Jadwal presensi default untuk semua hari kerja',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_schedules');
    }
};
