<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('constraints');
            $table->string('document_token_hash', 64)->nullable()->unique()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropUnique('teacher_profiles_document_token_hash_unique');
            $table->dropColumn(['signature_path', 'document_token_hash']);
        });
    }
};
