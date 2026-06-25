<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('primary_color')->default('#3B82F6'); // Blue
            $table->string('secondary_color')->default('#10B981'); // Green
            $table->string('accent_color')->default('#F59E0B'); // Amber
            $table->string('success_color')->default('#10B981'); // Green
            $table->string('warning_color')->default('#F59E0B'); // Amber
            $table->string('danger_color')->default('#EF4444'); // Red
            $table->string('dark_color')->default('#1F2937'); // Gray-800
            $table->string('light_color')->default('#F9FAFB'); // Gray-50
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('app_name')->default('PKG Presensi');
            $table->text('app_description')->nullable();
            $table->timestamps();
        });

        // Insert default theme
        DB::table('theme_settings')->insert([
            'primary_color' => '#667EEA',
            'secondary_color' => '#764BA2',
            'accent_color' => '#F59E0B',
            'success_color' => '#10B981',
            'warning_color' => '#F59E0B',
            'danger_color' => '#EF4444',
            'dark_color' => '#1F2937',
            'light_color' => '#F9FAFB',
            'app_name' => 'PKG Presensi',
            'app_description' => 'Sistem Presensi QR Code - Pembinaan Karakter Generus',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
