<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->nullable()->constrained('materi')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title', 160);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('background_color', 7)->default('#0f172a');
            $table->string('path_mode', 30)->default('overview_between');
            $table->json('canvas_data');
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['materi_id', 'is_published']);
        });

        Schema::create('presentation_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')->constrained('presentations')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('mime_type', 80);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentation_assets');
        Schema::dropIfExists('presentations');
    }
};
