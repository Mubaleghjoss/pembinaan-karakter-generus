<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi_rpp_journal_assignees')) {
            Schema::create('materi_rpp_journal_assignees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('schedule_reminder_id')->constrained('schedule_reminders')->cascadeOnDelete();
                $table->string('assignee_type', 20);
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('siswa_id')->nullable()->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['schedule_reminder_id', 'user_id'], 'rpp_journal_assignee_schedule_user_unique');
                $table->unique(['schedule_reminder_id', 'siswa_id'], 'rpp_journal_assignee_schedule_siswa_unique');
                $table->index(['assignee_type', 'user_id'], 'rpp_journal_assignee_type_user_index');
                $table->index(['assignee_type', 'siswa_id'], 'rpp_journal_assignee_type_siswa_index');
            });
        }

        DB::table('schedule_reminders')
            ->where('source_type', 'materi_rpp')
            ->orderBy('id')
            ->chunkById(200, function ($schedules) {
                foreach ($schedules as $schedule) {
                    $now = now();
                    $payload = json_decode((string) ($schedule->source_payload ?? ''), true);
                    $teacherUserId = (int) ($payload['teacher_user_id'] ?? 0);

                    if ($teacherUserId > 0 && DB::table('users')->where('id', $teacherUserId)->exists()) {
                        DB::table('materi_rpp_journal_assignees')->insertOrIgnore([
                            'schedule_reminder_id' => $schedule->id,
                            'assignee_type' => 'user',
                            'user_id' => $teacherUserId,
                            'siswa_id' => null,
                            'assigned_by' => $schedule->created_by,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    if ($schedule->journal_assignee_type === 'user' && $schedule->journal_assignee_user_id) {
                        DB::table('materi_rpp_journal_assignees')->insertOrIgnore([
                            'schedule_reminder_id' => $schedule->id,
                            'assignee_type' => 'user',
                            'user_id' => $schedule->journal_assignee_user_id,
                            'siswa_id' => null,
                            'assigned_by' => $schedule->created_by,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    if ($schedule->journal_assignee_type === 'siswa' && $schedule->journal_assignee_siswa_id) {
                        DB::table('materi_rpp_journal_assignees')->insertOrIgnore([
                            'schedule_reminder_id' => $schedule->id,
                            'assignee_type' => 'siswa',
                            'user_id' => null,
                            'siswa_id' => $schedule->journal_assignee_siswa_id,
                            'assigned_by' => $schedule->created_by,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });

        Cache::forget('materi-rpp-journal-schema-ready-v1');
    }

    public function down(): void
    {
        Cache::forget('materi-rpp-journal-schema-ready-v1');
        Schema::dropIfExists('materi_rpp_journal_assignees');
    }
};
