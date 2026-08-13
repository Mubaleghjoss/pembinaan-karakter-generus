<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_reading_sheets', function (Blueprint $table) {
            $table->unsignedTinyInteger('template_version')->default(1)->after('row_count');
        });

        Schema::table('quran_reading_scans', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by_id')->nullable()->change();
        });
        Schema::table('quran_reading_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('quran_reading_entries')->whereNull('submitted_by_id')->delete();
        DB::table('quran_reading_scans')->whereNull('uploaded_by_id')->delete();

        Schema::table('quran_reading_scans', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by_id')->nullable(false)->change();
        });
        Schema::table('quran_reading_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by_id')->nullable(false)->change();
        });

        Schema::table('quran_reading_sheets', function (Blueprint $table) {
            $table->dropColumn('template_version');
        });
    }
};
