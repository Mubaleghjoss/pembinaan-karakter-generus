<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update Berita Table
        Schema::table('berita', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('cover_path');
            $table->integer('download_count')->default(0)->after('view_count');
            $table->json('images')->nullable()->after('cover_path'); // For slider images
        });

        // Create Settings Table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('settings')->insert([
            [
                'key' => 'attendance_success_message',
                'value' => 'KAMU TELAH TERDAFTAR HADIR PADA HARI INI. LANCAR BAROKAH',
                'type' => 'string',
                'description' => 'Pesan yang muncul setelah scan QR berhasil',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance_open_time',
                'value' => '07:00',
                'type' => 'time',
                'description' => 'Waktu mulai presensi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance_close_time',
                'value' => '17:00',
                'type' => 'time',
                'description' => 'Waktu selesai presensi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berita', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'download_count', 'images']);
        });

        Schema::dropIfExists('settings');
    }
};
