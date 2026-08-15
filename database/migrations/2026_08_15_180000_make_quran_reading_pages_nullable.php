<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_reading_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('page_start')->nullable()->change();
            $table->unsignedSmallInteger('page_end')->nullable()->change();
        });
    }

    public function down(): void
    {
        \DB::table('quran_reading_entries')->whereNull('page_start')->update(['page_start' => 1]);
        \DB::table('quran_reading_entries')->whereNull('page_end')->update(['page_end' => 1]);
        Schema::table('quran_reading_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('page_start')->nullable(false)->change();
            $table->unsignedSmallInteger('page_end')->nullable(false)->change();
        });
    }
};
