<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('siswa_id')->nullable();
            $table->string('user_type', 20)->after('user_id')->default('siswa'); // siswa, admin, ortu
        });

        // Copy siswa_id to user_id
        DB::table('webauthn_credentials')->update([
            'user_id' => DB::raw('siswa_id'),
            'user_type' => 'siswa'
        ]);

        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->dropIndex(['siswa_id']);
            $table->dropColumn('siswa_id');
            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->cascadeOnDelete();
        });

        DB::table('webauthn_credentials')->where('user_type', 'siswa')->update([
            'siswa_id' => DB::raw('user_id')
        ]);

        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'user_id']);
            $table->dropColumn(['user_id', 'user_type']);
        });
    }
};
