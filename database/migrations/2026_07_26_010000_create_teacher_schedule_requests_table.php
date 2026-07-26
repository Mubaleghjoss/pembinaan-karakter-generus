<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_schedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')
                ->constrained('teacher_schedule_assignments')
                ->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->cascadeOnDelete();
            $table->string('request_type', 20);
            $table->text('reason');
            $table->string('status', 20)->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_profile_id', 'status'], 'teacher_schedule_request_teacher_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_schedule_requests');
    }
};
