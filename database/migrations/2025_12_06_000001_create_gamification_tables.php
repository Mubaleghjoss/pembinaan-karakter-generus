<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Levels table
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('min_points');
            $table->integer('max_points')->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('warna', 7)->default('#6B7280');
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['min_points', 'max_points']);
        });

        // Badges table
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi');
            $table->string('icon')->nullable();
            $table->json('kriteria');
            $table->integer('poin_reward')->default(0);
            $table->string('kategori')->default('general');
            $table->string('warna', 7)->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'kategori']);
        });

        // User badges table
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignId('badge_id')->constrained('badges')->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'badge_id']);
            $table->index(['siswa_id', 'earned_at']);
        });

        // Siswa points table
        Schema::create('siswa_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->integer('total_points')->default(0);
            $table->integer('level')->default(1);
            $table->integer('attendance_points')->default(0);
            $table->integer('character_points')->default(0);
            $table->integer('bonus_points')->default(0);
            $table->integer('spent_points')->default(0);
            $table->date('last_attendance_date')->nullable();
            $table->integer('attendance_streak')->default(0);
            $table->date('last_character_date')->nullable();
            $table->integer('character_streak')->default(0);
            $table->timestamps();
            $table->unique('siswa_id');
            $table->index(['total_points', 'level']);
        });

        // Point transactions table
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->enum('type', ['earned', 'spent', 'bonus', 'penalty']);
            $table->enum('source', ['attendance', 'character', 'badge', 'manual', 'streak', 'perfect_month']);
            $table->integer('points');
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['siswa_id', 'created_at']);
            $table->index(['type', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('siswa_points');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('levels');
    }
};
