<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Duel mendukung pemain staff (admin/pamong) + lintas peran (pamong vs siswa).
        // p1/p2 sudah ada untuk siswa; tambah kolom tipe & referensi staff supaya bisa lintas peran.
        Schema::table('game_duels', function (Blueprint $table) {
            if (! Schema::hasColumn('game_duels', 'p1_type')) {
                $table->string('p1_type', 20)->default('siswa')->after('opponent_type');
            }
            if (! Schema::hasColumn('game_duels', 'p2_type')) {
                $table->string('p2_type', 20)->nullable()->after('p1_type');
            }
            if (! Schema::hasColumn('game_duels', 'p1_name')) {
                $table->string('p1_name', 120)->nullable()->after('p2_type');
            }
            if (! Schema::hasColumn('game_duels', 'p2_name')) {
                $table->string('p2_name', 120)->nullable()->after('p1_name');
            }
            // p1_siswa_id / p2_siswa_id dipakai umum (id pemain sesuai p*_type).
            // Nullable-kan agar staff (users.id) juga bisa mengisi kolom ini.
        });

        // FK p2_siswa_id ke siswa bisa menghalangi id staff. Longgarkan bila ada.
        // (SQLite lokal tanpa FK ketat; MySQL prod: cek & drop FK bila ada)
        try {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                $fks = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_duels' AND REFERENCED_TABLE_NAME IS NOT NULL");
                foreach ($fks as $fk) {
                    \DB::statement("ALTER TABLE game_duels DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                }
            }
        } catch (\Throwable $e) {
            // abaikan; kolom tetap bisa dipakai tanpa FK
        }
    }

    public function down(): void
    {
        Schema::table('game_duels', function (Blueprint $table) {
            foreach (['p1_type', 'p2_type', 'p1_name', 'p2_name'] as $col) {
                if (Schema::hasColumn('game_duels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
