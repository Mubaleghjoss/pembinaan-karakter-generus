<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE share_infos MODIFY COLUMN target ENUM('all', 'siswa', 'ortu', 'pamong') DEFAULT 'all'");
    }

    public function down(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE share_infos MODIFY COLUMN target ENUM('all', 'siswa', 'ortu') DEFAULT 'all'");
    }
};
