<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_rapat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catatan_rapat_id')->nullable()->constrained('catatan_rapat')->onDelete('set null');
            $table->string('card_title'); // Store title for reference even if card deleted
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'deleted', 'moved']);
            $table->text('details')->nullable(); // JSON: what changed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_rapat_logs');
    }
};
