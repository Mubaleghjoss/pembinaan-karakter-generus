<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('karakter_luhurs')) {
            Schema::create('karakter_luhurs', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('nomor')->index();      // 1..29
                $table->string('slug', 120)->unique();
                $table->string('nama', 150);                          // nama karakter (jawaban rangkai kata)
                $table->string('nama_arab', 150)->nullable();
                $table->string('kategori', 120)->nullable();
                $table->string('ringkas', 200)->nullable();           // label singkat
                $table->text('deskripsi')->nullable();
                $table->text('definisi')->nullable();
                $table->json('dalil_quran')->nullable();              // [{arab,terjemahan,sumber}]
                $table->json('dalil_hadits')->nullable();
                $table->json('hikmah')->nullable();                   // [string]
                $table->json('studi_kasus')->nullable();              // [string] skenario kehidupan
                $table->json('tips_amal')->nullable();                // [string]
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('karakter_luhurs');
    }
};
