<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karakter', function (Blueprint $table) {
            $table->text('photo_proof_instruction')->nullable()->after('photo_proof_bonus_points');
            $table->boolean('allows_voice_note_proof')->default(false)->after('photo_proof_instruction');
            $table->unsignedInteger('voice_note_bonus_points')->default(0)->after('allows_voice_note_proof');
            $table->text('voice_note_instruction')->nullable()->after('voice_note_bonus_points');
        });

        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->string('voice_note_path')->nullable()->after('proof_compressed_size_kb');
            $table->unsignedInteger('voice_note_size_kb')->nullable()->after('voice_note_path');
        });
    }

    public function down(): void
    {
        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->dropColumn([
                'voice_note_path',
                'voice_note_size_kb',
            ]);
        });

        Schema::table('karakter', function (Blueprint $table) {
            $table->dropColumn([
                'photo_proof_instruction',
                'allows_voice_note_proof',
                'voice_note_bonus_points',
                'voice_note_instruction',
            ]);
        });
    }
};
