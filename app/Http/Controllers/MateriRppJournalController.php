<?php

namespace App\Http\Controllers;

use App\Exports\MateriRppJournalExport;
use App\Models\Materi;
use App\Models\MateriRppJournal;
use App\Models\MateriRppJournalAssignee;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Models\User;
use App\Services\MateriRppJournalWorkflowService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MateriRppJournalController extends Controller
{
    public function __construct(
        protected MateriRppJournalWorkflowService $workflow
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $this->staffUser();
        abort_unless($this->workflow->canUseStaffJournal($user), 403);

        $filters = $this->journalFilters($request);

        $schedules = $this->filteredSchedules($user, $filters)
            ->with([
                'rppJournal.creator',
                'rppJournal.submittedBySiswa',
                'sourceMateri',
                'journalAssigneeUser',
                'journalAssigneeSiswa',
                'journalAssignees.user',
                'journalAssignees.siswa',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('end_time')
            ->paginate(20)
            ->withQueryString();

        $materiOptions = Materi::query()
            ->where('rpp_is_enabled', true)
            ->orderBy('judul')
            ->get(['id', 'judul']);

        return view('materi-rpp-journals.index', [
            'schedules' => $schedules,
            'materiOptions' => $materiOptions,
            'workflowOptions' => ['pending' => 'Belum Diisi'] + MateriRppJournal::workflowOptions(),
            'selectedMonth' => $filters['month'],
            'selectedWorkflowStatus' => $filters['workflow_status'],
            'selectedMateriId' => $filters['materi_id'],
            'workflowService' => $this->workflow,
        ]);
    }

    public function export(Request $request)
    {
        $user = $this->staffUser();
        abort_unless($this->workflow->canUseStaffJournal($user), 403);

        $filters = $this->journalFilters($request);
        $schedules = $this->filteredSchedules($user, $filters)
            ->with([
                'rppJournal.creator',
                'rppJournal.submittedBySiswa',
                'rppJournal.reviewer',
                'sourceMateri',
                'journalAssigneeUser',
                'journalAssigneeSiswa',
                'journalAssignees.user',
                'journalAssignees.siswa',
            ])
            ->orderBy('start_date')
            ->orderBy('end_time')
            ->get();

        $materiLabel = $filters['materi_id'] > 0
            ? Materi::query()->whereKey($filters['materi_id'])->value('judul')
            : null;
        $workflowLabels = ['pending' => 'Belum Diisi'] + MateriRppJournal::workflowOptions();

        return (new MateriRppJournalExport($schedules, [
            'period' => Carbon::createFromFormat('Y-m', $filters['month'])->locale('id')->translatedFormat('F Y'),
            'materi' => $materiLabel ?: 'Semua Materi',
            'workflow_status' => $workflowLabels[$filters['workflow_status']] ?? 'Semua Status',
        ]))->download('jurnal-rpp-' . $filters['month'] . '.xlsx');
    }

    public function forSchedule(Request $request, ScheduleReminder $scheduleReminder)
    {
        $user = $this->staffUser();
        abort_unless($this->workflow->canViewStaffSchedule($user, $scheduleReminder), 403);

        return $this->renderForm($request, $user, $scheduleReminder);
    }

    public function storeForSchedule(Request $request, ScheduleReminder $scheduleReminder)
    {
        $user = $this->staffUser();
        abort_unless($this->workflow->canSubmitAsStaff($user, $scheduleReminder), 403);

        $validated = $this->validatedPayload($request);

        $journal = DB::transaction(function () use ($scheduleReminder, $validated, $user) {
            $scheduleReminder = ScheduleReminder::query()->lockForUpdate()->findOrFail($scheduleReminder->id);
            $existing = MateriRppJournal::query()
                ->where('schedule_reminder_id', $scheduleReminder->id)
                ->lockForUpdate()
                ->first();

            return MateriRppJournal::updateOrCreate(
                ['schedule_reminder_id' => $scheduleReminder->id],
                array_merge($this->workflow->snapshotFromSchedule($scheduleReminder), $validated, [
                    'workflow_status' => MateriRppJournal::WORKFLOW_APPROVED,
                    'created_by' => $existing?->created_by ?: $user->id,
                    'updated_by' => $user->id,
                    'submitted_at' => $existing?->submitted_at ?: now(),
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'review_note' => null,
                ])
            );
        });

        $this->workflow->touchCache();

        return redirect()
            ->route('materi-rpp-journals.edit', $journal)
            ->with('success', 'Jurnal RPP berhasil disimpan dan disahkan.');
    }

    public function edit(Request $request, MateriRppJournal $journal)
    {
        $user = $this->staffUser();
        $schedule = $journal->scheduleReminder;
        abort_unless($schedule && $this->workflow->canViewStaffSchedule($user, $schedule), 403);

        return $this->renderForm($request, $user, $schedule, $journal);
    }

    public function update(Request $request, MateriRppJournal $journal)
    {
        $user = $this->staffUser();
        $schedule = $journal->scheduleReminder;
        abort_unless($schedule && $this->workflow->canSubmitAsStaff($user, $schedule), 403);

        $journal->update(array_merge($this->validatedPayload($request), [
            'workflow_status' => MateriRppJournal::WORKFLOW_APPROVED,
            'updated_by' => $user->id,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => null,
        ]));

        $this->workflow->touchCache();

        return redirect()
            ->route('materi-rpp-journals.edit', $journal)
            ->with('success', 'Jurnal RPP berhasil diperbarui.');
    }

    public function addAssignee(Request $request, ScheduleReminder $scheduleReminder)
    {
        $user = $this->staffUser();
        abort_unless($this->workflow->canManageAll($user), 403);
        abort_unless($scheduleReminder->source_type === ScheduleReminder::SOURCE_MATERI_RPP, 404);

        $validated = $request->validate([
            'assignee_type' => ['required', Rule::in([
                MateriRppJournalWorkflowService::ASSIGNEE_USER,
                MateriRppJournalWorkflowService::ASSIGNEE_SISWA,
            ])],
            'assignee_id' => 'required|integer|min:1',
        ]);

        $this->workflow->addAssignee(
            $scheduleReminder,
            $validated['assignee_type'],
            (int) $validated['assignee_id'],
            $user
        );

        return redirect()
            ->route('materi-rpp-journals.schedule', $scheduleReminder)
            ->with('success', 'Petugas jurnal berhasil ditambahkan.');
    }

    public function removeAssignee(ScheduleReminder $scheduleReminder, MateriRppJournalAssignee $assignee)
    {
        $user = $this->staffUser();
        abort_unless($this->workflow->canManageAll($user), 403);
        abort_unless($scheduleReminder->source_type === ScheduleReminder::SOURCE_MATERI_RPP, 404);

        $this->workflow->removeAssignee($scheduleReminder, $assignee);

        return redirect()
            ->route('materi-rpp-journals.schedule', $scheduleReminder)
            ->with('success', 'Petugas jurnal berhasil dihapus.');
    }

    public function review(Request $request, MateriRppJournal $journal)
    {
        $user = $this->staffUser();
        $journal->loadMissing('scheduleReminder');
        abort_unless($this->workflow->canReview($user, $journal), 403);
        abort_unless($journal->workflow_status === MateriRppJournal::WORKFLOW_PENDING_REVIEW, 422);

        $validated = $request->validate([
            'review_action' => ['required', Rule::in(['approve', 'revise'])],
            'review_note' => [
                Rule::requiredIf($request->input('review_action') === 'revise'),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $approved = $validated['review_action'] === 'approve';
        $journal->update([
            'workflow_status' => $approved
                ? MateriRppJournal::WORKFLOW_APPROVED
                : MateriRppJournal::WORKFLOW_NEEDS_REVISION,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
            'updated_by' => $user->id,
        ]);

        $this->workflow->touchCache();

        return redirect()
            ->route('materi-rpp-journals.edit', $journal)
            ->with('success', $approved ? 'Jurnal siswa berhasil disahkan.' : 'Jurnal dikembalikan untuk diperbaiki.');
    }

    private function renderForm(
        Request $request,
        User $user,
        ScheduleReminder $schedule,
        ?MateriRppJournal $journal = null
    ) {
        $schedule->loadMissing([
            'rppJournal',
            'sourceMateri',
            'journalAssigneeUser',
            'journalAssigneeSiswa',
            'journalAssignees.user',
            'journalAssignees.siswa',
        ]);

        $journal ??= $schedule->rppJournal;
        $journal ??= new MateriRppJournal($this->workflow->snapshotFromSchedule($schedule));
        $journal->setRelation('scheduleReminder', $schedule);
        $journal->loadMissing(['creator', 'submittedBySiswa', 'reviewer', 'materi']);

        $assigneeType = in_array($request->input('assignee_type'), ['user', 'siswa'], true)
            ? $request->input('assignee_type')
            : ($schedule->journal_assignee_type ?: 'user');
        $assigneeQuery = trim((string) $request->input('assignee_q'));

        return view('materi-rpp-journals.form', [
            'journal' => $journal,
            'scheduleReminder' => $schedule,
            'statusOptions' => MateriRppJournal::statusOptions(),
            'isNew' => ! $journal->exists,
            'canSubmit' => $this->workflow->canSubmitAsStaff($user, $schedule),
            'canReview' => $journal->exists && $this->workflow->canReview($user, $journal),
            'canAssign' => $this->workflow->canManageAll($user) && ! $journal->exists,
            'assigneeType' => $assigneeType,
            'assigneeQuery' => $assigneeQuery,
            'assigneeOptions' => $this->assigneeOptions($assigneeType, $assigneeQuery),
        ]);
    }

    private function assigneeOptions(string $type, string $search)
    {
        if ($type === MateriRppJournalWorkflowService::ASSIGNEE_SISWA) {
            return Siswa::query()
                ->where('is_active', true)
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $match) use ($search) {
                    $match->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                }))
                ->orderBy('nama')
                ->limit(20)
                ->get(['id', 'nama', 'nis'])
                ->map(fn (Siswa $siswa) => [
                    'id' => $siswa->id,
                    'label' => $siswa->nama . ($siswa->nis ? ' - ' . $siswa->nis : ''),
                ]);
        }

        return User::query()
            ->where('status', 'active')
            ->whereHas('role', fn (Builder $role) => $role->whereIn('name', [
                User::ROLE_ADMIN,
                User::ROLE_TEACHER,
                User::ROLE_PKG_MANAGER,
            ]))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $match) use ($search) {
                $match->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'username'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $user->display_name . ($user->username ? ' - ' . $user->username : ''),
            ]);
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'realization_status' => ['required', Rule::in(array_keys(MateriRppJournal::statusOptions()))],
            'actual_page_start' => 'nullable|integer|min:1',
            'actual_page_end' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:5000',
            'obstacles' => 'nullable|string|max:5000',
            'follow_up' => 'nullable|string|max:5000',
        ]);

        if (
            ! empty($validated['actual_page_start'])
            && ! empty($validated['actual_page_end'])
            && (int) $validated['actual_page_end'] < (int) $validated['actual_page_start']
        ) {
            throw ValidationException::withMessages([
                'actual_page_end' => 'Halaman akhir realisasi harus lebih besar atau sama dengan halaman awal.',
            ]);
        }

        return $validated;
    }

    private function journalFilters(Request $request): array
    {
        $month = (string) $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $date = Carbon::createFromFormat('Y-m', $month);
        $workflowStatus = (string) $request->input('workflow_status');
        $allowedStatuses = array_merge(['pending'], array_keys(MateriRppJournal::workflowOptions()));

        if (! in_array($workflowStatus, $allowedStatuses, true)) {
            $workflowStatus = '';
        }

        return [
            'month' => $month,
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'workflow_status' => $workflowStatus,
            'materi_id' => $request->integer('materi_id'),
        ];
    }

    private function filteredSchedules(User $user, array $filters): Builder
    {
        return $this->workflow->visibleStaffSchedules($user)
            ->whereBetween('start_date', [$filters['start_date'], $filters['end_date']])
            ->when($filters['materi_id'] > 0, fn (Builder $query) => $query->where('source_id', $filters['materi_id']))
            ->when($filters['workflow_status'] === 'pending', fn (Builder $query) => $query->whereDoesntHave('rppJournal'))
            ->when(
                $filters['workflow_status'] !== '' && $filters['workflow_status'] !== 'pending',
                fn (Builder $query) => $query->whereHas(
                    'rppJournal',
                    fn (Builder $journal) => $journal->where('workflow_status', $filters['workflow_status'])
                )
            );
    }

    private function staffUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
