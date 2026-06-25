<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // QR token for pamong attendance
            $table->string('qr_token')->nullable()->after('avatar_path');
            $table->timestamp('qr_token_generated_at')->nullable()->after('qr_token');
            
            // Theme preference
            $table->enum('theme_preference', ['light', 'dark', 'system'])->default('system')->after('qr_token_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_token_generated_at', 'theme_preference']);
        });
    }
};
