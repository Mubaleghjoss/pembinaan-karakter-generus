<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ortu_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siswa_karakter_checklist_id');
            $table->unsignedBigInteger('siswa_id');
            $table->text('comment');
            $table->timestamps();

            $table->index('siswa_karakter_checklist_id');
            $table->index('siswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ortu_comments');
    }
};
