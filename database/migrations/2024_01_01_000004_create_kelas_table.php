<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 80);
            $table->string('tingkat', 20)->nullable();
            $table->string('kode_kelas', 20)->unique();
            $table->foreignId('pamong_id')->nullable()->constrained('pamong');
            $table->integer('kapasitas')->default(30);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nama', 'tingkat', 'is_active']);
            $table->index('kode_kelas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
