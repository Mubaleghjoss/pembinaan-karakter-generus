<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_reminders')) {
            $addAssigneeType = ! Schema::hasColumn('schedule_reminders', 'journal_assignee_type');
            $addAssigneeUser = ! Schema::hasColumn('schedule_reminders', 'journal_assignee_user_id');
            $addAssigneeSiswa = ! Schema::hasColumn('schedule_reminders', 'journal_assignee_siswa_id');

            Schema::table('schedule_reminders', function (Blueprint $table) use ($addAssigneeType, $addAssigneeUser, $addAssigneeSiswa) {
                if ($addAssigneeType) {
                    $table->string('journal_assignee_type', 20)->nullable()->after('source_payload');
                }

                if ($addAssigneeUser) {
                    $table->foreignId('journal_assignee_user_id')->nullable()->after('journal_assignee_type')->constrained('users')->nullOnDelete();
                }

                if ($addAssigneeSiswa) {
                    $table->foreignId('journal_assignee_siswa_id')->nullable()->after('journal_assignee_user_id')->constrained('siswa')->nullOnDelete();
                }
            });

            Schema::table('schedule_reminders', function (Blueprint $table) {
                $table->index(
                    ['source_type', 'journal_assignee_user_id', 'start_date'],
                    'schedule_rpp_journal_user_date_index'
                );
                $table->index(
                    ['source_type', 'journal_assignee_siswa_id', 'start_date'],
                    'schedule_rpp_journal_siswa_date_index'
                );
            });
        }

        if (Schema::hasTable('materi_rpp_journals')) {
            $addWorkflowStatus = ! Schema::hasColumn('materi_rpp_journals', 'workflow_status');
            $addSubmittedBySiswa = ! Schema::hasColumn('materi_rpp_journals', 'submitted_by_siswa_id');
            $addSubmittedAt = ! Schema::hasColumn('materi_rpp_journals', 'submitted_at');
            $addReviewedBy = ! Schema::hasColumn('materi_rpp_journals', 'reviewed_by');
            $addReviewedAt = ! Schema::hasColumn('materi_rpp_journals', 'reviewed_at');
            $addReviewNote = ! Schema::hasColumn('materi_rpp_journals', 'review_note');

            Schema::table('materi_rpp_journals', function (Blueprint $table) use (
                $addWorkflowStatus,
                $addSubmittedBySiswa,
                $addSubmittedAt,
                $addReviewedBy,
                $addReviewedAt,
                $addReviewNote
            ) {
                if ($addWorkflowStatus) {
                    $table->string('workflow_status', 30)->default('approved')->after('realization_status');
                }

                if ($addSubmittedBySiswa) {
                    $table->foreignId('submitted_by_siswa_id')->nullable()->after('updated_by')->constrained('siswa')->nullOnDelete();
                }

                if ($addSubmittedAt) {
                    $table->timestamp('submitted_at')->nullable()->after('submitted_by_siswa_id');
                }

                if ($addReviewedBy) {
                    $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
                }

                if ($addReviewedAt) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }

                if ($addReviewNote) {
                    $table->text('review_note')->nullable()->after('reviewed_at');
                }
            });

            Schema::table('materi_rpp_journals', function (Blueprint $table) {
                $table->index(
                    ['workflow_status', 'journal_date'],
                    'materi_rpp_journals_workflow_date_index'
                );
            });
        }

        $this->backfillExistingAssignments();
        Cache::forget('materi-rpp-journal-schema-ready-v1');
    }

    public function down(): void
    {
        Cache::forget('materi-rpp-journal-schema-ready-v1');
        if (Schema::hasTable('materi_rpp_journals')) {
            Schema::table('materi_rpp_journals', function (Blueprint $table) {
                $table->dropIndex('materi_rpp_journals_workflow_date_index');
                $table->dropConstrainedForeignId('reviewed_by');
                $table->dropConstrainedForeignId('submitted_by_siswa_id');
                $table->dropColumn(['workflow_status', 'submitted_at', 'reviewed_at', 'review_note']);
            });
        }

        if (Schema::hasTable('schedule_reminders')) {
            Schema::table('schedule_reminders', function (Blueprint $table) {
                $table->dropIndex('schedule_rpp_journal_user_date_index');
                $table->dropIndex('schedule_rpp_journal_siswa_date_index');
                $table->dropConstrainedForeignId('journal_assignee_user_id');
                $table->dropConstrainedForeignId('journal_assignee_siswa_id');
                $table->dropColumn('journal_assignee_type');
            });
        }
    }

    private function backfillExistingAssignments(): void
    {
        if (! Schema::hasColumns('schedule_reminders', [
            'source_type',
            'source_payload',
            'journal_assignee_type',
            'journal_assignee_user_id',
        ])) {
            return;
        }

        DB::table('schedule_reminders')
            ->where('source_type', 'materi_rpp')
            ->whereNull('journal_assignee_user_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $payload = json_decode((string) ($row->source_payload ?? ''), true);
                    $teacherUserId = (int) ($payload['teacher_user_id'] ?? 0);

                    if ($teacherUserId < 1 || ! DB::table('users')->where('id', $teacherUserId)->exists()) {
                        continue;
                    }

                    DB::table('schedule_reminders')
                        ->where('id', $row->id)
                        ->update([
                            'journal_assignee_type' => 'user',
                            'journal_assignee_user_id' => $teacherUserId,
                            'journal_assignee_siswa_id' => null,
                        ]);
                }
            });
    }
};
