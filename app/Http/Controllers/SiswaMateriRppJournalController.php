<?php

namespace App\Http\Controllers;

use App\Models\MateriRppJournal;
use App\Models\ScheduleReminder;
use App\Models\Siswa;
use App\Services\MateriRppJournalWorkflowService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiswaMateriRppJournalController extends Controller
{
    public function __construct(
        protected MateriRppJournalWorkflowService $workflow
    ) {}

    public function index(Request $request)
    {
        $siswa = $this->siswa();
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

        $schedules = $this->workflow->visibleStudentSchedules($siswa)
            ->with(['rppJournal', 'sourceMateri'])
            ->whereBetween('start_date', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->when($workflowStatus === 'pending', fn (Builder $query) => $query->whereDoesntHave('rppJournal'))
            ->when($workflowStatus !== '' && $workflowStatus !== 'pending', fn (Builder $query) =>
                $query->whereHas('rppJournal', fn (Builder $journal) => $journal->where('workflow_status', $workflowStatus))
            )
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('siswa.materi-rpp-journals.index', [
            'schedules' => $schedules,
            'selectedMonth' => $month,
            'selectedWorkflowStatus' => $workflowStatus,
            'workflowOptions' => ['pending' => 'Belum Diisi'] + MateriRppJournal::workflowOptions(),
            'workflowService' => $this->workflow,
        ]);
    }

    public function show(ScheduleReminder $scheduleReminder)
    {
        $siswa = $this->siswa();
        abort_unless($this->workflow->canViewAsStudent($siswa, $scheduleReminder), 403);
        abort_unless($scheduleReminder->isJournalAvailable(), 403);

        $scheduleReminder->loadMissing(['rppJournal', 'sourceMateri']);
        $journal = $scheduleReminder->rppJournal
            ?: new MateriRppJournal($this->workflow->snapshotFromSchedule($scheduleReminder));

        return view('siswa.materi-rpp-journals.form', [
            'scheduleReminder' => $scheduleReminder,
            'journal' => $journal,
            'statusOptions' => MateriRppJournal::statusOptions(),
            'canSubmit' => $this->workflow->canSubmitAsStudent($siswa, $scheduleReminder, $scheduleReminder->rppJournal),
        ]);
    }

    public function store(Request $request, ScheduleReminder $scheduleReminder)
    {
        $siswa = $this->siswa();
        $existing = $scheduleReminder->rppJournal;
        abort_unless($this->workflow->canSubmitAsStudent($siswa, $scheduleReminder, $existing), 403);

        $validated = $this->validatedPayload($request);

        DB::transaction(function () use ($scheduleReminder, $siswa, $validated) {
            $schedule = ScheduleReminder::query()->lockForUpdate()->findOrFail($scheduleReminder->id);
            $journal = MateriRppJournal::query()
                ->where('schedule_reminder_id', $schedule->id)
                ->lockForUpdate()
                ->first();

            abort_if($journal && $journal->workflow_status !== MateriRppJournal::WORKFLOW_NEEDS_REVISION, 403);

            MateriRppJournal::updateOrCreate(
                ['schedule_reminder_id' => $schedule->id],
                array_merge($this->workflow->snapshotFromSchedule($schedule), $validated, [
                    'workflow_status' => MateriRppJournal::WORKFLOW_PENDING_REVIEW,
                    'created_by' => null,
                    'updated_by' => null,
                    'submitted_by_siswa_id' => $siswa->id,
                    'submitted_at' => now(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_note' => null,
                ])
            );
        });

        $this->workflow->touchCache();

        return redirect()
            ->route('siswa.materi-rpp-journals.index')
            ->with('success', 'Jurnal berhasil dikirim dan menunggu konfirmasi pamong.');
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'realization_status' => ['required', Rule::in(array_keys(MateriRppJournal::statusOptions()))],
            'actual_page_start' => 'nullable|integer|min:1',
            'actual_page_end' => 'nullable|integer|min:1',
            'notes' => 'required|string|max:5000',
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

    private function siswa(): Siswa
    {
        $siswa = Auth::guard('siswa')->user();
        abort_unless($siswa instanceof Siswa, 403);

        return $siswa;
    }
}
