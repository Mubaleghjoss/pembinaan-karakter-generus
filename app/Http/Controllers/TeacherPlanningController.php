<?php

namespace App\Http\Controllers;

use App\Models\TeacherAvailabilityInvite;
use App\Models\TeacherProfile;
use App\Models\TeacherScheduleAssignment;
use App\Models\TeacherSchedulePeriod;
use App\Models\TeacherScheduleSession;
use App\Models\TeacherScheduleTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Services\TeacherSchedulePlanner;
use App\Services\TeacherStatementDocumentService;
use App\Support\ParticipantProfileOptions;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherPlanningController extends Controller
{
    public function __construct(private readonly TeacherSchedulePlanner $planner)
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorizeModule('view');
        $selectedMonth = Carbon::createFromFormat('Y-m', $request->get('month', now()->format('Y-m')))->startOfMonth();
        $period = TeacherSchedulePeriod::query()
            ->whereDate('month', $selectedMonth)
            ->with(['sessions.assignments.teacher.user'])
            ->first();
        $profilesQuery = TeacherProfile::query()
            ->with('user:id,name,username');
        if ($period) {
            $profilesQuery
                ->withCount(['assignments as current_assignments_count' => fn ($query) => $query->whereHas(
                    'session',
                    fn ($session) => $session->where('period_id', $period->id)
                )])
                ->withCount(['assignments as current_main_count' => fn ($query) => $query
                    ->where('role', 'main')
                    ->whereHas('session', fn ($session) => $session->where('period_id', $period->id))])
                ->withCount(['assignments as current_backup_count' => fn ($query) => $query
                    ->where('role', 'backup')
                    ->whereHas('session', fn ($session) => $session->where('period_id', $period->id))]);
        }
        $profiles = $profilesQuery
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $eligibleProfiles = TeacherProfile::query()->eligible()->orderBy('name')->get();
        $templates = TeacherScheduleTemplate::query()
            ->orderBy('sort_order')
            ->orderBy('weekday')
            ->orderBy('rombel')
            ->get();
        $hasActiveTemplates = $templates->contains('is_active', true);
        $invite = TeacherAvailabilityInvite::query()->latest('id')->first();
        $successMessageSettings = [
            'title' => Setting::get(
                Setting::TEACHER_SUCCESS_TITLE_KEY,
                Setting::TEACHER_SUCCESS_TITLE_DEFAULT
            ),
            'message' => Setting::get(
                Setting::TEACHER_SUCCESS_MESSAGE_KEY,
                Setting::TEACHER_SUCCESS_MESSAGE_DEFAULT
            ),
        ];
        $warnings = $period ? $this->planner->warnings($period) : [];
        $stats = [
            'total' => TeacherProfile::count(),
            'eligible' => TeacherProfile::eligible()->count(),
            'monday' => TeacherProfile::eligible()->whereJsonContains('available_nights', 'monday')->count(),
            'tuesday' => TeacherProfile::eligible()->whereJsonContains('available_nights', 'tuesday')->count(),
            'friday' => TeacherProfile::eligible()->whereJsonContains('available_nights', 'friday')->count(),
        ];
        $roleStats = collect([
            TeacherProfile::ROLE_BOTH => 'Utama & cadangan',
            TeacherProfile::ROLE_MAIN => 'Utama',
            TeacherProfile::ROLE_BACKUP => 'Cadangan',
            TeacherProfile::ROLE_AS_NEEDED => 'Sesuai kebutuhan',
            TeacherProfile::ROLE_UNAVAILABLE => 'Belum memungkinkan',
        ])->map(fn ($label, $role) => [
            'label' => $label,
            'count' => TeacherProfile::query()->where('participation_role', $role)->count(),
        ])->values();
        $rombelStats = collect(TeacherProfile::ROMBELS)->map(fn ($label, $rombel) => [
            'label' => $label,
            'count' => TeacherProfile::eligible()->whereJsonContains('rombels', $rombel)->count(),
        ])->values();
        $confirmationDue = TeacherScheduleAssignment::query()
            ->where('confirmation_status', 'pending')
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', now()->addDays(3)))
            ->count();
        $reminderDue = TeacherScheduleAssignment::query()
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', now()->addDay()))
            ->count();
        $linkableUsers = User::query()
            ->whereIn('status', ['active'])
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
            ->orderBy('name')
            ->orderBy('username')
            ->get(['id', 'name', 'username', 'phone']);

        return view('teacher-planning.index', compact(
            'selectedMonth', 'period', 'profiles', 'eligibleProfiles', 'templates',
            'invite', 'warnings', 'stats', 'linkableUsers', 'roleStats', 'rombelStats',
            'confirmationDue', 'reminderDue', 'successMessageSettings', 'hasActiveTemplates'
        ) + [
            'groups' => ParticipantProfileOptions::groups(),
            'rombels' => TeacherProfile::ROMBELS,
            'nights' => TeacherProfile::NIGHTS,
        ]);
    }

    public function updateInvite(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $invite = TeacherAvailabilityInvite::query()->latest('id')->first();
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'access_code' => [$invite ? 'nullable' : 'required', 'string', 'min:6', 'max:32', 'regex:/^[A-Za-z0-9]+$/'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'max_uses' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($invite && (int) $validated['max_uses'] < $invite->used_count) {
            throw ValidationException::withMessages([
                'max_uses' => "Kuota tidak boleh kurang dari penggunaan saat ini ({$invite->used_count}).",
            ]);
        }

        $plainCode = filled($validated['access_code'] ?? null)
            ? Str::upper($validated['access_code'])
            : null;
        $invite ??= new TeacherAvailabilityInvite(['used_count' => 0]);
        $invite->fill([
            'label' => trim($validated['label']),
            'max_uses' => (int) $validated['max_uses'],
            'expires_at' => now()->addDays((int) $validated['valid_days']),
            'is_active' => $request->boolean('is_active'),
        ]);
        if ($plainCode) {
            $invite->token_hash = hash('sha256', $plainCode);
        }
        $invite->save();

        $redirect = back()->with('success', 'Kode akses pendataan guru berhasil disimpan.');

        return $plainCode
            ? $redirect->with('teacher_access_code', $plainCode)
            : $redirect;
    }

    public function updateSuccessMessage(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'success_title' => ['required', 'string', 'max:120'],
            'success_message' => ['required', 'string', 'max:500'],
        ]);

        Setting::setMany([
            Setting::TEACHER_SUCCESS_TITLE_KEY => trim($validated['success_title']),
            Setting::TEACHER_SUCCESS_MESSAGE_KEY => trim($validated['success_message']),
        ], 'teacher_planning');

        return back()->with('success', 'Pesan setelah formulir terkirim berhasil disimpan.');
    }

    public function updateProfile(Request $request, TeacherProfile $teacherProfile): RedirectResponse
    {
        $this->authorizeModule('edit');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'public_name' => ['nullable', 'string', 'max:80'],
            'kelompok' => ['required', Rule::in(array_keys(ParticipantProfileOptions::groups()))],
            'whatsapp' => ['required', 'string', 'max:24'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('teacher_profiles', 'user_id')->ignore($teacherProfile->id)],
            'participation_role' => ['required', Rule::in([
                TeacherProfile::ROLE_BOTH, TeacherProfile::ROLE_MAIN, TeacherProfile::ROLE_BACKUP,
                TeacherProfile::ROLE_AS_NEEDED, TeacherProfile::ROLE_UNAVAILABLE,
            ])],
            'rombels' => ['nullable', 'array'],
            'rombels.*' => [Rule::in(array_keys(TeacherProfile::ROMBELS))],
            'available_nights' => ['nullable', 'array'],
            'available_nights.*' => [Rule::in(array_keys(TeacherProfile::NIGHTS))],
            'monthly_limit' => ['nullable', 'integer', 'min:1', 'max:3'],
            'constraints' => ['nullable', 'string', 'max:1000'],
            'competencies' => ['nullable', 'array'],
            'competencies.*' => [Rule::in(['quran', 'hadith', 'memorization', 'practice', 'class_support', 'all_materials'])],
            'material_readiness' => ['nullable', Rule::in(['ready', 'needs_support'])],
            'backup_contact_preference' => ['nullable', Rule::in(['ready', 'one_day_notice', 'unavailable'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $normalizedPhone = $this->normalizeWhatsapp($validated['whatsapp']);

        if (TeacherProfile::query()
            ->where('whatsapp_normalized', $normalizedPhone)
            ->whereKeyNot($teacherProfile->id)
            ->exists()) {
            throw ValidationException::withMessages(['whatsapp' => 'Nomor WhatsApp sudah dipakai profil lain.']);
        }

        $availableNights = array_values(array_unique($validated['available_nights'] ?? []));
        $orderedNights = collect($availableNights)
            ->sortBy(fn ($night) => ($teacherProfile->night_priorities ?? [])[$night] ?? 99)
            ->values();
        $nightPriorities = $orderedNights
            ->mapWithKeys(fn ($night, $index) => [$night => $index + 1])
            ->all();

        $teacherProfile->update([
            ...$validated,
            'name' => preg_replace('/\s+/', ' ', trim($validated['name'])),
            'public_name' => trim($validated['public_name'] ?? '') ?: null,
            'whatsapp_normalized' => $normalizedPhone,
            'rombels' => array_values(array_unique($validated['rombels'] ?? [])),
            'available_nights' => $availableNights,
            'night_priorities' => $nightPriorities,
            'competencies' => array_values(array_unique($validated['competencies'] ?? [])),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Profil kesediaan guru berhasil diperbarui.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->authorizeModule('create');
        $validated = $request->validate([
            'weekday' => ['required', Rule::in(array_keys(TeacherProfile::NIGHTS))],
            'rombel' => ['required', Rule::in(array_keys(TeacherProfile::ROMBELS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        TeacherScheduleTemplate::query()->updateOrCreate([
            'weekday' => $validated['weekday'],
            'rombel' => $validated['rombel'],
        ], [
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Template slot jadwal berhasil disimpan.');
    }

    public function toggleTemplate(TeacherScheduleTemplate $teacherScheduleTemplate): RedirectResponse
    {
        $this->authorizeModule('edit');
        $teacherScheduleTemplate->update(['is_active' => ! $teacherScheduleTemplate->is_active]);

        return back()->with('success', 'Status template berhasil diperbarui.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->authorizeModule('create');
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m']]);

        if (! TeacherScheduleTemplate::query()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'templates' => 'Jadwal belum dapat dibuat. Tambahkan dan aktifkan minimal satu Template Slot Mingguan terlebih dahulu.',
            ]);
        }

        $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $period = TeacherSchedulePeriod::query()->firstOrCreate([
            'month' => $month->toDateString(),
        ], [
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);
        $this->planner->generate($period, $request->user()->id);

        return redirect()->route('teacher-planning.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Draft jadwal bulanan berhasil dibuat.');
    }

    public function destroyProfile(
        Request $request,
        TeacherProfile $teacherProfile,
        TeacherStatementDocumentService $documentService
    ): RedirectResponse {
        abort_unless($request->user()->isAdmin(), 403);
        $signaturePath = $teacherProfile->signature_path;

        DB::transaction(function () use ($teacherProfile): void {
            $profile = TeacherProfile::query()->lockForUpdate()->findOrFail($teacherProfile->id);

            if ($profile->assignments()->exists()) {
                throw ValidationException::withMessages([
                    'teacher_profile' => 'Data guru tidak dapat dihapus karena sudah digunakan dalam jadwal. Lepaskan seluruh penugasannya terlebih dahulu.',
                ]);
            }

            $inviteId = $profile->invite_id;
            $profile->delete();

            if ($inviteId) {
                $invite = TeacherAvailabilityInvite::query()->lockForUpdate()->find($inviteId);
                if ($invite && $invite->used_count > 0) {
                    $invite->decrement('used_count');
                }
            }
        });

        $documentService->deleteSignature($signaturePath);

        return back()->with('success', 'Data kesediaan guru berhasil dihapus.');
    }

    public function assign(
        Request $request,
        TeacherScheduleSession $teacherScheduleSession,
        string $role
    ): RedirectResponse {
        $this->authorizeModule('edit');
        abort_unless(in_array($role, ['main', 'backup'], true), 404);
        $validated = $request->validate([
            'teacher_profile_id' => ['nullable', 'integer', 'exists:teacher_profiles,id'],
            'overload_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $period = $teacherScheduleSession->period;

        DB::transaction(function () use ($validated, $teacherScheduleSession, $role, $request, $period): void {
            $current = TeacherScheduleAssignment::query()
                ->where('session_id', $teacherScheduleSession->id)
                ->where('role', $role)
                ->first();

            if (! filled($validated['teacher_profile_id'] ?? null)) {
                $current?->delete();

                return;
            }

            $teacher = TeacherProfile::query()->findOrFail($validated['teacher_profile_id']);
            $duplicateRole = TeacherScheduleAssignment::query()
                ->where('session_id', $teacherScheduleSession->id)
                ->where('teacher_profile_id', $teacher->id)
                ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                ->exists();
            if ($duplicateRole) {
                throw ValidationException::withMessages([
                    'teacher_profile_id' => 'Orang yang sama tidak dapat menjadi utama sekaligus cadangan.',
                ]);
            }

            $load = $this->planner->monthlyLoad($teacher, $period);
            $replacingSameTeacher = $current?->teacher_profile_id === $teacher->id;
            $wouldExceed = ! $replacingSameTeacher && $teacher->monthly_limit && $load >= $teacher->monthly_limit;
            if ($wouldExceed && blank($validated['overload_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    'overload_reason' => "Penugasan melebihi batas {$teacher->monthly_limit} tugas. Tuliskan alasan override.",
                ]);
            }

            $current?->delete();
            $this->planner->createAssignment(
                $teacherScheduleSession,
                $teacher,
                $role,
                'manual',
                true,
                $request->user()->id,
                $validated['overload_reason'] ?? null
            );
        });

        return back()->with('success', 'Penugasan guru berhasil diperbarui.');
    }

    public function swap(TeacherScheduleSession $teacherScheduleSession): RedirectResponse
    {
        $this->authorizeModule('edit');
        $assignments = $teacherScheduleSession->assignments()->get()->keyBy('role');
        abort_unless($assignments->has('main') && $assignments->has('backup'), 422, 'Pengajar utama dan cadangan harus terisi.');

        DB::transaction(function () use ($assignments): void {
            $main = $assignments->get('main');
            $backup = $assignments->get('backup');
            $main->update(['role' => 'switching']);
            $backup->update(['role' => 'main', 'source' => 'manual', 'is_locked' => true]);
            $main->update(['role' => 'backup', 'source' => 'manual', 'is_locked' => true]);
        });

        return back()->with('success', 'Pengajar utama dan cadangan berhasil ditukar.');
    }

    public function publish(Request $request, TeacherSchedulePeriod $teacherSchedulePeriod): RedirectResponse
    {
        $this->authorizeModule('publish');
        $warnings = $this->planner->warnings($teacherSchedulePeriod);
        $validated = $request->validate([
            'warning_acknowledgement' => [$warnings ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);
        $teacherSchedulePeriod->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
            'publish_warning_acknowledgement' => $validated['warning_acknowledgement'] ?? null,
        ]);

        return back()->with('success', 'Jadwal berhasil diterbitkan ke kalender.');
    }

    public function destroyPeriod(
        Request $request,
        TeacherSchedulePeriod $teacherSchedulePeriod
    ): RedirectResponse {
        abort_unless($request->user()->isAdmin(), 403);
        $month = $teacherSchedulePeriod->month->format('Y-m');

        $teacherSchedulePeriod->delete();

        return redirect()->route('teacher-planning.index', ['month' => $month])
            ->with('success', 'Jadwal bulanan beserta seluruh sesi dan penugasannya berhasil dihapus.');
    }

    public function whatsapp(Request $request, TeacherScheduleAssignment $assignment, string $stage): RedirectResponse
    {
        $this->authorizeModule('edit');
        abort_unless(in_array($stage, ['h3', 'h1'], true), 404);
        $assignment->loadMissing(['teacher', 'session.period']);
        $token = Crypt::decryptString($assignment->confirmation_token_encrypted);
        $url = route('public.teacher-confirmation.show', $token);
        $role = $assignment->role === 'main' ? 'pengajar utama' : 'pengajar cadangan';
        $date = $assignment->session->session_date->translatedFormat('l, d F Y');
        $time = substr($assignment->session->start_time, 0, 5).'-'.substr($assignment->session->end_time, 0, 5);
        $message = $stage === 'h3'
            ? "Assalamu'alaikum {$assignment->teacher->name}. Mohon konfirmasi kesediaan sebagai {$role} rombel ".strtoupper($assignment->session->rombel)." pada {$date}, pukul {$time} WIB. Konfirmasi: {$url}"
            : "Assalamu'alaikum {$assignment->teacher->name}. Pengingat jadwal besok sebagai {$role} rombel ".strtoupper($assignment->session->rombel)." pukul {$time} WIB. Detail/konfirmasi: {$url}";
        $assignment->forceFill([
            "{$stage}_whatsapp_opened_at" => now(),
            'confirmation_requested_at' => $stage === 'h3'
                ? ($assignment->confirmation_requested_at ?? now())
                : $assignment->confirmation_requested_at,
        ])->save();

        return redirect()->away('https://wa.me/'.$assignment->teacher->whatsapp_normalized.'?text='.rawurlencode($message));
    }

    public function markWhatsappSent(TeacherScheduleAssignment $assignment, string $stage): RedirectResponse
    {
        $this->authorizeModule('edit');
        abort_unless(in_array($stage, ['h3', 'h1'], true), 404);
        $assignment->update(["{$stage}_whatsapp_sent_at" => now()]);

        return back()->with('success', 'Pesan ditandai sudah dikirim.');
    }

    public function exportExcel(TeacherSchedulePeriod $teacherSchedulePeriod): StreamedResponse
    {
        $this->authorizeModule('export');
        $teacherSchedulePeriod->load(['sessions.assignments.teacher']);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal');
        $sheet->fromArray(['Tanggal', 'Hari', 'Rombel', 'Jam', 'Utama', 'Cadangan', 'Status'], null, 'A1');
        $row = 2;

        foreach ($teacherSchedulePeriod->sessions->sortBy('session_date') as $session) {
            $main = $session->assignments->firstWhere('role', 'main');
            $backup = $session->assignments->firstWhere('role', 'backup');
            $sheet->fromArray([
                $session->session_date->format('d/m/Y'),
                $session->session_date->translatedFormat('l'),
                strtoupper($session->rombel),
                substr($session->start_time, 0, 5).'-'.substr($session->end_time, 0, 5),
                $main?->teacher?->name ?? 'Belum diisi',
                $backup?->teacher?->name ?? 'Belum diisi',
                $session->status,
            ], null, "A{$row}");
            $row++;
        }
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $loads = $teacherSchedulePeriod->sessions->flatMap->assignments->groupBy('teacher_profile_id');
        $loadSheet = $spreadsheet->createSheet();
        $loadSheet->setTitle('Beban Pengajar');
        $loadSheet->fromArray(['Nama', 'Utama', 'Cadangan', 'Total', 'Batas'], null, 'A1');
        $row = 2;
        foreach ($loads as $assignments) {
            $teacher = $assignments->first()->teacher;
            $loadSheet->fromArray([
                $teacher->name,
                $assignments->where('role', 'main')->count(),
                $assignments->where('role', 'backup')->count(),
                $assignments->count(),
                $teacher->monthly_limit ?: '4+',
            ], null, "A{$row}");
            $row++;
        }

        $filename = 'jadwal-mt-ms-'.$teacherSchedulePeriod->month->format('Y-m').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportPdf(TeacherSchedulePeriod $teacherSchedulePeriod)
    {
        $this->authorizeModule('export');
        $teacherSchedulePeriod->load(['sessions.assignments.teacher']);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('teacher-planning.export-pdf', ['period' => $teacherSchedulePeriod])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="jadwal-mt-ms-'.$teacherSchedulePeriod->month->format('Y-m').'.pdf"',
        ]);
    }

    public function exportImage(TeacherSchedulePeriod $teacherSchedulePeriod): View
    {
        $this->authorizeModule('export');
        $teacherSchedulePeriod->load(['sessions.assignments.teacher']);
        $rows = $teacherSchedulePeriod->sessions
            ->sortBy('session_date')
            ->map(function ($session) {
                return [
                    'date' => $session->session_date->translatedFormat('D, d M Y'),
                    'rombel' => strtoupper($session->rombel),
                    'time' => substr($session->start_time, 0, 5).'-'.substr($session->end_time, 0, 5),
                    'main' => $session->assignments->firstWhere('role', 'main')?->teacher?->name ?? 'Belum diisi',
                    'backup' => $session->assignments->firstWhere('role', 'backup')?->teacher?->name ?? 'Belum diisi',
                ];
            })
            ->values();

        return view('teacher-planning.export-image', [
            'period' => $teacherSchedulePeriod,
            'rows' => $rows,
        ]);
    }

    public function statementPreview(
        TeacherProfile $teacherProfile,
        TeacherStatementDocumentService $documentService
    )
    {
        $this->authorizeModule('view');

        return $documentService->response($teacherProfile, false);
    }

    public function statementDownload(
        TeacherProfile $teacherProfile,
        TeacherStatementDocumentService $documentService
    )
    {
        $this->authorizeModule('export');

        return $documentService->response($teacherProfile);
    }

    private function authorizeModule(string $operation): void
    {
        $user = request()->user();
        abort_unless(
            $user && ($user->isAdmin()
                || ($user->hasPamongMenuAccess('teacher_scheduling')
                    && $user->hasPamongCrudPermission('teacher_scheduling', $operation))),
            403
        );
    }

    private function normalizeWhatsapp(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        if (! preg_match('/^62[0-9]{8,13}$/', $digits)) {
            throw ValidationException::withMessages(['whatsapp' => 'Nomor WhatsApp tidak valid.']);
        }

        return $digits;
    }
}
