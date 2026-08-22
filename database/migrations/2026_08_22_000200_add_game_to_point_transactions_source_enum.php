<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('point_transactions')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            // SQLite/lainnya: kolom string bebas, tidak perlu ubah enum.
            return;
        }

        $col = DB::selectOne("SHOW COLUMNS FROM point_transactions WHERE Field = 'source'");
        if (! $col) {
            return;
        }

        // Hanya ubah bila 'game' belum ada di definisi enum.
        if (str_contains((string) $col->Type, "'game'")) {
            return;
        }

        DB::statement("ALTER TABLE `point_transactions` MODIFY `source` ENUM('attendance','character','badge','manual','streak','perfect_month','game') NOT NULL");
    }

    public function down(): void
    {
        // Tidak dikembalikan otomatis (menghindari kehilangan data source='game').
    }
};
