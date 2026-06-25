<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RPG Maps
        Schema::create('rpg_maps', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('grid_size')->default(10);
            $table->string('background_theme')->default('grass');
            $table->json('obstacles')->nullable(); // [{x:1,y:2}, ...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // RPG NPCs (with quiz questions)
        Schema::create('rpg_npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rpg_map_id')->constrained('rpg_maps')->onDelete('cascade');
            $table->string('nama');
            $table->string('avatar')->default('🧙');
            $table->integer('pos_x');
            $table->integer('pos_y');
            $table->text('pertanyaan');
            $table->json('pilihan_jawaban'); // ["Jawaban A","Jawaban B","Jawaban C","Jawaban D"]
            $table->integer('jawaban_benar')->default(0); // index 0-3
            $table->integer('poin')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['rpg_map_id', 'is_active']);
            $table->index(['pos_x', 'pos_y']);
        });

        // RPG Game Sessions (player progress)
        Schema::create('rpg_game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignId('rpg_map_id')->constrained('rpg_maps')->onDelete('cascade');
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->json('answered_npcs')->nullable(); // [1, 3, 5] NPC IDs
            $table->integer('total_score')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'rpg_map_id']);
            $table->index('updated_at'); // for online presence queries
        });

        // RPG Characters (player customization)
        Schema::create('rpg_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->string('avatar')->default('🧑‍🎓');
            $table->string('nama_karakter')->nullable();
            $table->string('warna')->default('#3B82F6');
            $table->timestamps();
            $table->unique('siswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpg_characters');
        Schema::dropIfExists('rpg_game_sessions');
        Schema::dropIfExists('rpg_npcs');
        Schema::dropIfExists('rpg_maps');
    }
};
