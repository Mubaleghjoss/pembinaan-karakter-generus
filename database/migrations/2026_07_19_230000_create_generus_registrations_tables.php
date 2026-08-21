<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('siswa', 'tempat_lahir')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('tempat_lahir', 120)->nullable()->after('jenis_kelamin');
            });
        }

        Schema::table('siswa', function (Blueprint $table) {});

        if (! Schema::hasTable('generus_registration_invites')) {
            Schema::create('generus_registration_invites', function (Blueprint $table) {
                $table->id();
                $table->string('label', 120);
                $table->string('token_hash', 64)->unique();
                $table->unsignedInteger('max_uses')->default(1);
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('expires_at')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('generus_registrations')) {
            Schema::create('generus_registrations', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('invite_id')->nullable()->constrained('generus_registration_invites')->nullOnDelete();
                $table->foreignId('siswa_id')->nullable()->unique()->constrained('siswa')->nullOnDelete();
                $table->string('download_token_hash', 64);
                $table->string('parent_name', 120);
                $table->string('parent_phone', 30);
                $table->string('student_name', 120);
                $table->string('student_phone', 30);
                $table->string('kelompok', 60);
                $table->string('birth_place', 120);
                $table->date('birth_date');
                $table->string('school_grade', 20);
                $table->string('parent_signature_path');
                $table->string('student_signature_path');
                $table->string('statement_version', 20)->default('v1');
                $table->timestamp('statement_accepted_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->string('source_ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['parent_phone', 'student_phone']);
                $table->index('submitted_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generus_registrations');
        Schema::dropIfExists('generus_registration_invites');

        if (Schema::hasColumn('siswa', 'tempat_lahir')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('tempat_lahir');
            });
        }
    }
};
