<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('materi') || ! Schema::hasColumn('materi', 'pdf_path')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        Schema::table('materi', function (Blueprint $table) use ($driver) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $table->longText('pdf_path')->nullable()->change();
                return;
            }

            $table->text('pdf_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('materi') || ! Schema::hasColumn('materi', 'pdf_path')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            $table->string('pdf_path', 255)->nullable()->change();
        });
    }
};
