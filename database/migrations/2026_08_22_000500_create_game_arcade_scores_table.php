<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leaderboard TERPISAH untuk staff (admin/pamong) + skor game arcade (pecah karakter).
        // Tidak mencampuri poin gamifikasi siswa.
        if (! Schema::hasTable('game_arcade_scores')) {
            Schema::create('game_arcade_scores', function (Blueprint $table) {
                $table->id();
                $table->string('game', 40)->default('pecah-karakter'); // jenis game
                $table->string('player_type', 20);   // 'siswa' | 'staff'
                $table->unsignedBigInteger('player_id')->nullable(); // siswa.id atau users.id
                $table->string('player_name', 120);
                $table->unsignedInteger('score')->default(0);
                $table->unsignedInteger('best_combo')->default(0);
                $table->timestamps();

                $table->index(['game', 'player_type', 'score']);
                $table->index(['game', 'player_type', 'player_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_arcade_scores');
    }
};
