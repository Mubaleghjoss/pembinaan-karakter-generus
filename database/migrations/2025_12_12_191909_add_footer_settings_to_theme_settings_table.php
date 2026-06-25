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
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->string('footer_text')->nullable()->after('app_description');
            $table->string('footer_organization')->nullable()->after('footer_text');
            $table->string('footer_address')->nullable()->after('footer_organization');
            $table->string('footer_phone')->nullable()->after('footer_address');
            $table->string('footer_email')->nullable()->after('footer_phone');
            $table->text('footer_social_links')->nullable()->after('footer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_text',
                'footer_organization',
                'footer_address',
                'footer_phone',
                'footer_email',
                'footer_social_links',
            ]);
        });
    }
};
