<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_columns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#3B82F6');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Insert default columns
        DB::table('kanban_columns')->insert([
            ['name' => 'Backlog', 'color' => '#6B7280', 'order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'To Do', 'color' => '#3B82F6', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'In Progress', 'color' => '#F59E0B', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Done', 'color' => '#10B981', 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_columns');
    }
};
