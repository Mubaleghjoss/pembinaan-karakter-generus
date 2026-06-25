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
        Schema::create('pamong_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('menu_permissions')->nullable()->comment('Array of allowed menu keys');
            $table->json('crud_permissions')->nullable()->comment('Array of allowed CRUD operations per module');
            $table->boolean('is_excluded')->default(false)->comment('If true, pamong has full access (excluded from restrictions)');
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pamong_permissions');
    }
};
