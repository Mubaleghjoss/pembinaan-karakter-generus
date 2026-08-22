<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('boss_battles')) {
            Schema::create('boss_battles', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 120);                 // mis. "Sifat Malas"
                $table->string('deskripsi', 300)->nullable();
                $table->string('mode', 30)->default('tebak'); // tebak | rangkai
                $table->unsignedInteger('max_hp')->default(500);
                $table->integer('current_hp')->default(500);
                $table->string('status', 20)->default('active'); // active | defeated | ended
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['status']);
            });
        }

        if (! Schema::hasTable('boss_hits')) {
            Schema::create('boss_hits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('boss_battle_id')->constrained('boss_battles')->cascadeOnDelete();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->unsignedInteger('damage')->default(0);   // akumulasi damage siswa ini
                $table->unsignedInteger('correct_count')->default(0);
                $table->boolean('points_awarded')->default(false);
                $table->timestamps();

                $table->unique(['boss_battle_id', 'siswa_id']);
                $table->index(['boss_battle_id', 'damage']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('boss_hits');
        Schema::dropIfExists('boss_battles');
    }
};
