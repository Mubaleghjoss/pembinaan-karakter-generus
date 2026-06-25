<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->string('certificate_template')->nullable()->after('benefits');
            $table->integer('certificate_name_y')->default(50)->after('certificate_template');
            $table->integer('certificate_font_size')->default(36)->after('certificate_name_y');
            $table->string('certificate_font_color', 20)->default('#000000')->after('certificate_font_size');
        });
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_template',
                'certificate_name_y',
                'certificate_font_size',
                'certificate_font_color',
            ]);
        });
    }
};
