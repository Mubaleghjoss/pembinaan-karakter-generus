<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_availability_invites', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('token_hash', 64)->unique();
            $table->unsignedInteger('max_uses')->default(100);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invite_id')->nullable()->constrained('teacher_availability_invites')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->string('public_name', 80)->nullable();
            $table->string('kelompok', 60)->index();
            $table->string('whatsapp', 24);
            $table->string('whatsapp_normalized', 20)->unique();
            $table->string('participation_role', 40)->index();
            $table->json('rombels')->nullable();
            $table->json('available_nights')->nullable();
            $table->json('night_priorities')->nullable();
            $table->unsignedTinyInteger('monthly_limit')->nullable();
            $table->json('competencies')->nullable();
            $table->string('material_readiness', 30)->nullable();
            $table->string('backup_contact_preference', 40)->nullable();
            $table->text('constraints')->nullable();
            $table->string('consent_version', 20)->default('v1');
            $table->timestamp('consented_at');
            $table->timestamp('submitted_at');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['kelompok', 'participation_role', 'is_active'], 'teacher_profiles_filter_index');
        });

        Schema::create('teacher_schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('weekday', 12);
            $table->string('rombel', 30);
            $table->time('start_time')->default('20:00:00');
            $table->time('end_time')->default('21:30:00');
            $table->string('location', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['weekday', 'rombel'], 'teacher_template_day_rombel_unique');
        });

        Schema::create('teacher_schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->text('publish_warning_acknowledgement')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_schedule_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('teacher_schedule_periods')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('teacher_schedule_templates')->nullOnDelete();
            $table->date('session_date')->index();
            $table->string('rombel', 30);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location', 120)->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'session_date', 'rombel', 'start_time'], 'teacher_session_slot_unique');
        });

        Schema::create('teacher_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('teacher_schedule_sessions')->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->restrictOnDelete();
            $table->string('role', 12);
            $table->string('source', 12)->default('auto');
            $table->boolean('is_locked')->default(false);
            $table->string('confirmation_status', 20)->default('pending')->index();
            $table->string('confirmation_token_hash', 64)->unique();
            $table->text('confirmation_token_encrypted');
            $table->timestamp('confirmation_requested_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('confirmation_note')->nullable();
            $table->timestamp('h3_whatsapp_opened_at')->nullable();
            $table->timestamp('h3_whatsapp_sent_at')->nullable();
            $table->timestamp('h1_whatsapp_opened_at')->nullable();
            $table->timestamp('h1_whatsapp_sent_at')->nullable();
            $table->text('overload_reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'role'], 'teacher_assignment_session_role_unique');
            $table->unique(['session_id', 'teacher_profile_id'], 'teacher_assignment_session_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_schedule_assignments');
        Schema::dropIfExists('teacher_schedule_sessions');
        Schema::dropIfExists('teacher_schedule_periods');
        Schema::dropIfExists('teacher_schedule_templates');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('teacher_availability_invites');
    }
};
