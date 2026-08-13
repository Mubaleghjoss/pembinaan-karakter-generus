<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_reading_scans', function (Blueprint $table) {
            $table->string('original_path')->nullable()->change();
            $table->string('processed_path')->nullable()->change();
            $table->timestamp('files_purged_at')->nullable()->after('confirmed_at')->index();
        });
    }

    public function down(): void
    {
        DB::table('quran_reading_scans')
            ->whereNull('original_path')
            ->update(['original_path' => 'quran-reading-scans/file-sudah-dibersihkan.jpg']);

        Schema::table('quran_reading_scans', function (Blueprint $table) {
            $table->dropIndex(['files_purged_at']);
            $table->dropColumn('files_purged_at');
            $table->string('original_path')->nullable(false)->change();
            $table->string('processed_path')->nullable()->change();
        });
    }
};
