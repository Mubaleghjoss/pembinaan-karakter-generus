<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persiapan_acara', function (Blueprint $table) {
            $table->id();
            $table->string('judul_acara');
            $table->text('deskripsi_acara')->nullable();
            $table->datetime('waktu_acara')->nullable();
            $table->string('tempat')->nullable();
            $table->json('materi_pemateri')->nullable(); // [{materi, pemateri}]
            $table->json('perlengkapan')->nullable();    // ["item1", "item2"]
            $table->foreignId('pj_acara_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('tim_dokumentasi')->nullable();  // [user_id1, user_id2]
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persiapan_acara');
    }
};
