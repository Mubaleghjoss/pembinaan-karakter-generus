<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add group column if not exists
        if (!Schema::hasColumn('settings', 'group')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('group')->default('general')->after('value');
                $table->index('group');
            });
        }

        // Insert default settings if not exists
        $defaults = [
            // General settings
            ['key' => 'site_title', 'value' => 'PKG Presensi', 'group' => 'general'],
            ['key' => 'site_name', 'value' => 'Pembinaan Karakter Generus', 'group' => 'general'],
            ['key' => 'site_logo', 'value' => null, 'group' => 'general'],
            ['key' => 'primary_color', 'value' => '#667EEA', 'group' => 'general'],
            
            // ID Card settings
            ['key' => 'card_title', 'value' => 'KARTU PESERTA', 'group' => 'id_card'],
            ['key' => 'card_subtitle', 'value' => 'Pembinaan Karakter Generus', 'group' => 'id_card'],
            ['key' => 'card_logo', 'value' => null, 'group' => 'id_card'],
            ['key' => 'card_color', 'value' => '#667EEA', 'group' => 'id_card'],
        ];

        foreach ($defaults as $setting) {
            // Only insert if key doesn't exist
            if (!DB::table('settings')->where('key', $setting['key'])->exists()) {
                DB::table('settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'group')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropIndex(['group']);
                $table->dropColumn('group');
            });
        }

        // Remove inserted settings
        DB::table('settings')->whereIn('key', [
            'site_title', 'site_name', 'site_logo', 'primary_color',
            'card_title', 'card_subtitle', 'card_logo', 'card_color'
        ])->delete();
    }
};
