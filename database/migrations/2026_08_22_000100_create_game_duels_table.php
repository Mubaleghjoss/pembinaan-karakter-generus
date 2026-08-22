<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_duels')) {
            Schema::create('game_duels', function (Blueprint $table) {
                $table->id();
                $table->string('mode', 30);                 // 'rangkai' | 'tebak'
                $table->string('opponent_type', 10);        // 'ai' | 'pvp'
                $table->string('status', 20)->default('waiting'); // waiting|active|finished
                $table->unsignedTinyInteger('total_rounds')->default(5);

                // pemain 1 (penantang) & pemain 2 (lawan; null utk AI)
                $table->foreignId('p1_siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('p2_siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
                $table->string('ai_difficulty', 10)->nullable(); // easy|medium|hard (utk AI)

                $table->unsignedSmallInteger('p1_score')->default(0);
                $table->unsignedSmallInteger('p2_score')->default(0);

                // soal & progres disimpan JSON (ringan, 1 baris/duel; hindari tabel ronde terpisah)
                $table->json('questions')->nullable();       // [{karakter_id, prompt, answer, choices?}]
                $table->json('p1_answers')->nullable();       // [{round, correct, ms}]
                $table->json('p2_answers')->nullable();

                $table->string('winner', 10)->nullable();     // p1|p2|draw
                $table->string('join_code', 8)->nullable()->unique(); // utk PvP undang
                $table->timestamp('last_activity_at')->nullable()->index();
                $table->timestamps();

                $table->index(['mode', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_duels');
    }
};
