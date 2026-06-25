<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_siswa_id')->nullable()->constrained('siswa')->onDelete('cascade');
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_siswa_id')->nullable()->constrained('siswa')->onDelete('cascade');
            $table->foreignId('receiver_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->enum('message_type', ['text', 'image', 'link'])->default('text');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index(['sender_siswa_id', 'receiver_user_id']);
            $table->index(['sender_user_id', 'receiver_siswa_id']);
            $table->index(['sender_siswa_id', 'receiver_siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
