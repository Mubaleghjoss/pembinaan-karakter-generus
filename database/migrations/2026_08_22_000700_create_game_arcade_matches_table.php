<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Head-to-head arcade "Pecah Karakter" via kode (lintas peran: pamong vs siswa).
        // Kedua pemain memakai seed kata yang sama, main offline, submit skor sekali.
        // Ringan: 1 baris per match, polling hanya SELECT 1 row.
        if (! Schema::hasTable('game_arcade_matches')) {
            Schema::create('game_arcade_matches', function (Blueprint $table) {
                $table->id();
                $table->string('code', 8)->unique();
                $table->string('seed', 40);            // seed acak untuk urutan kata identik
                $table->string('status', 20)->default('waiting'); // waiting|playing|finished

                $table->string('p1_type', 20);         // siswa|staff
                $table->unsignedBigInteger('p1_id')->nullable();
                $table->string('p1_name', 120);
                $table->integer('p1_score')->nullable();

                $table->string('p2_type', 20)->nullable();
                $table->unsignedBigInteger('p2_id')->nullable();
                $table->string('p2_name', 120)->nullable();
                $table->integer('p2_score')->nullable();

                $table->string('winner', 8)->nullable(); // p1|p2|draw
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'last_activity_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_arcade_matches');
    }
};
