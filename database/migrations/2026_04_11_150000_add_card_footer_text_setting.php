<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('settings')->where('key', 'card_footer_text')->exists()) {
            DB::table('settings')->insert([
                'key' => 'card_footer_text',
                'value' => 'Kartu ini adalah identitas resmi peserta PKG Panunggangan',
                'group' => 'id_card',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'card_footer_text')->delete();
    }
};
