<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->timestamp('graduated_at')->nullable()->after('status')->index();
            $table->boolean('alumni_can_submit')->default(true)->after('graduated_at');
            $table->foreignId('alumni_reviewer_id')->nullable()->after('alumni_can_submit')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('pamong_siswa', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable()->after('created_at')->index();
            $table->foreignId('ended_by')->nullable()->after('ended_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pamong_siswa', function (Blueprint $table) {
            $table->dropForeign(['ended_by']);
            $table->dropColumn(['ended_at', 'ended_by']);
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign(['alumni_reviewer_id']);
            $table->dropColumn(['graduated_at', 'alumni_can_submit', 'alumni_reviewer_id']);
        });
    }
};
