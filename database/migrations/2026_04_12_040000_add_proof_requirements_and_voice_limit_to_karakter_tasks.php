<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karakter', function (Blueprint $table) {
            $table->string('proof_requirement', 30)->default('optional')->after('voice_note_instruction');
            $table->unsignedInteger('voice_note_max_seconds')->nullable()->after('proof_requirement');
        });

        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->unsignedInteger('voice_note_duration_seconds')->nullable()->after('voice_note_size_kb');
        });
    }

    public function down(): void
    {
        Schema::table('siswa_karakter_checklist', function (Blueprint $table) {
            $table->dropColumn([
                'voice_note_duration_seconds',
            ]);
        });

        Schema::table('karakter', function (Blueprint $table) {
            $table->dropColumn([
                'proof_requirement',
                'voice_note_max_seconds',
            ]);
        });
    }
};
