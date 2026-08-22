<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('karakter_luhurs') && ! Schema::hasColumn('karakter_luhurs', 'penerapan')) {
            Schema::table('karakter_luhurs', function (Blueprint $table) {
                // JSON: { benar:[], salah:[], dampak_positif:[], dampak_negatif:[] }
                $table->json('penerapan')->nullable()->after('studi_kasus');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('karakter_luhurs') && Schema::hasColumn('karakter_luhurs', 'penerapan')) {
            Schema::table('karakter_luhurs', function (Blueprint $table) {
                $table->dropColumn('penerapan');
            });
        }
    }
};
