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
        Schema::table('karakter', function (Blueprint $table) {
            $table->boolean('allows_photo_proof')->default(false)->after('target_klik');
            $table->unsignedInteger('photo_proof_bonus_points')->default(0)->after('allows_photo_proof');
        });

        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('click_history');
            $table->unsignedInteger('proof_original_size_kb')->nullable()->after('proof_path');
            $table->unsignedInteger('proof_compressed_size_kb')->nullable()->after('proof_original_size_kb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->dropColumn([
                'proof_path',
                'proof_original_size_kb',
                'proof_compressed_size_kb',
            ]);
        });

        Schema::table('karakter', function (Blueprint $table) {
            $table->dropColumn([
                'allows_photo_proof',
                'photo_proof_bonus_points',
            ]);
        });
    }
};
