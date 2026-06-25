<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_rapat', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('tanggal_rapat')->nullable();
            $table->foreignId('column_id')->constrained('kanban_columns')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('due_date')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Settings table for who can create
        Schema::create('catatan_rapat_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default setting: both admin and pamong can create
        DB::table('catatan_rapat_settings')->insert([
            ['key' => 'can_create_roles', 'value' => json_encode(['admin', 'teacher']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_rapat_settings');
        Schema::dropIfExists('catatan_rapat');
    }
};
