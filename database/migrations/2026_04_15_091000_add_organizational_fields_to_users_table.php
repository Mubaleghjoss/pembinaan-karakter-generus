<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizational_team_id')
                ->nullable()
                ->after('role_id')
                ->constrained('organizational_teams')
                ->nullOnDelete();
            $table->string('organizational_title', 120)->nullable()->after('organizational_team_id');
            $table->unsignedInteger('organizational_sort_order')->default(0)->after('organizational_title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizational_team_id');
            $table->dropColumn(['organizational_title', 'organizational_sort_order']);
        });
    }
};
