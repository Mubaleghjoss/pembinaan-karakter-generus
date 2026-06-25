<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained('chat_groups')->onDelete('cascade');
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('sender_siswa_id')->nullable()->constrained('siswa')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->string('attachment_path')->nullable();
            $table->json('is_read_by')->nullable(); // Array of user/siswa IDs who have read
            $table->timestamps();
            
            $table->index('chat_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group_messages');
    }
};
