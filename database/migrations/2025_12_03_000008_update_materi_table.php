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
        // Skip if table doesn't exist (will be created by create_materi_table migration)
        if (!Schema::hasTable('materi')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('materi', 'bulan')) {
                $table->date('bulan')->nullable()->after('deskripsi');
            }
            if (!Schema::hasColumn('materi', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('bulan');
            }
            if (!Schema::hasColumn('materi', 'video_url')) {
                $table->string('video_url')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('materi', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('video_url');
            }
            if (!Schema::hasColumn('materi', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('materi', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
            if (!Schema::hasColumn('materi', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materi', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['bulan', 'pdf_path', 'video_url', 'is_active', 'created_by']);
        });
    }
};
