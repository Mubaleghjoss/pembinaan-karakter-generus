<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_reward_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('levels')->onDelete('cascade');
            $table->string('reward_type', 50); // sertifikat, pin, nominasi, piagam, apresiasi, piala
            $table->string('template_path')->nullable();
            $table->integer('name_y')->default(50); // Y position % from top
            $table->integer('font_size')->default(36);
            $table->string('font_color', 20)->default('#000000');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['level_id', 'reward_type']);
        });

        // Migrate existing certificate data from levels table
        $levels = DB::table('levels')
            ->whereNotNull('certificate_template')
            ->get();

        foreach ($levels as $level) {
            DB::table('level_reward_templates')->insert([
                'level_id' => $level->id,
                'reward_type' => 'sertifikat',
                'template_path' => $level->certificate_template,
                'name_y' => $level->certificate_name_y ?? 50,
                'font_size' => $level->certificate_font_size ?? 36,
                'font_color' => $level->certificate_font_color ?? '#000000',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('level_reward_templates');
    }
};
