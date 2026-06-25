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
        Schema::table('siswa', function (Blueprint $table) {
            // Kolom untuk menyimpan password plain text (untuk referensi admin)
            $table->string('password_plain', 100)->nullable()->after('password');
        });

        // Update existing records: set password_plain = nis (default password)
        \DB::table('siswa')->whereNull('password_plain')->update([
            'password_plain' => \DB::raw('nis')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('password_plain');
        });
    }
};
