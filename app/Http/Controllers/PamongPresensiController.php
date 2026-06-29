<?php

namespace App\Http\Controllers;

use App\Exports\PamongPresensiExport;
use App\Exports\PamongPresensiTemplateExport;
use App\Imports\PamongPresensiImport;
use App\Models\AttendanceSchedule;
use App\Models\PamongPresensi;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Contracts\PamongPresensiServiceInterface;
use App\Services\Contracts\PamongQrServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola Presensi Pamong
 */
class PamongPresensiController extends Controller
{
    public function __construct(
        protected PamongPresensiServiceInterface $pamongPresensiService,
        protected PamongQrServiceInterface $pamongQrService
    ) {}

    /**
     * Display a listing of pamong presensi.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();
        $userId = $currentUser->isAdmin()
            ? ($request->filled('user_id') ? $request->integer('user_id') : null)
            : $currentUser->id;
        $status = $request->status;
        $autoAlphaCount = $this->backfillClosedPamongAlpha($startDate, $endDate, $userId);

        $pamongUsers = User::query()
            ->select('id', 'name', 'username', 'kelompok')
            ->whereHas('role', function ($q) {
                $q->whereIn('name', User::attendanceRoleNames());
            })
            ->when(! $currentUser->isAdmin(), fn ($query) => $query->whereKey($currentUser->id))
            ->orderBy('name')
            ->get();

        $manualPamongUsers = $currentUser->isAdmin()
            ? $pamongUsers
            : collect([$currentUser])->map(fn (User $user) => $user->only(['id', 'name', 'username']));

        $manualDate = $request->input('date', now()->format('Y-m-d'));

        $presensi = $this->buildListingQuery($request, $startDate, $endDate)
            ->paginate(20)
            ->withQueryString();

        $stats = $this->pamongPresensiService->getStatistics(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $userId
        );
        $pamongGroupSummary = $this->buildPamongGroupPeriodSummary($startDate, $endDate, $currentUser);

        return view('pamong-presensi.index', compact(
            'presensi',
            'stats',
            'pamongUsers',
            'manualPamongUsers',
            'manualDate',
            'startDate',
            'endDate',
            'autoAlphaCount',
            'pamongGroupSummary'
        ));
    }

    /**
     * Show daily summary of pamong who have and have not filled attendance.
     */
    public function summary(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = isset($validated['date'])
            ? Carbon::parse($validated['date'])
            : now();

        $this->backfillClosedPamongAlpha($date, $date);

        $activeSchedules = AttendanceSchedule::query()
            ->where('is_active', true)
            ->orderBy('open_time')
            ->orderBy('id')
            ->get();

        $includeSiswa = $activeSchedules->contains(fn (AttendanceSchedule $schedule) => $schedule->targetsSiswa());
        $includePamong = $activeSchedules->contains(fn (AttendanceSchedule $schedule) => $schedule->targetsPamong());
        $targetLabel = match (true) {
            $includeSiswa && $includePamong => 'Siswa dan Pamong',
            $includeSiswa => 'Siswa saja',
            $includePamong => 'Pamong saja',
            default => 'Belum ada target aktif',
        };

        $pamongUsers = collect();
        $attendanceByUser = collect();
        if ($includePamong) {
            $pamongUsers = User::query()
                ->select([
                    'id',
                    'name',
                    'username',
                    'email',
                    'status',
                    'avatar_path',
                    'role_id',
                    'organizational_team_id',
                    'organizational_title',
                    'kelompok',
                ])
                ->with([
                    'role:id,name,display_name',
                    'organizationalTeam:id,name,short_name,color_hex',
                ])
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
                ->orderBy('name')
                ->orderBy('username')
                ->get();

            $attendanceByUser = PamongPresensi::query()
                ->with(['verifier:id,name'])
                ->whereDate('tanggal', $date->format('Y-m-d'))
                ->whereIn('user_id', $pamongUsers->pluck('id'))
                ->get()
                ->keyBy('user_id');
        }

        $siswaList = collect();
        $attendanceBySiswa = collect();
        $studentGroupSummary = collect();
        if ($includeSiswa) {
            $siswaColumns = ['id', 'nis', 'nama', 'kelas_id', 'status', 'is_active'];

            if (Siswa::hasKelompokColumn()) {
                $siswaColumns[] = 'kelompok';
            }

            $siswaList = Siswa::query()
                ->select($siswaColumns)
                ->with('kelas:id,nama')
                ->active()
                ->orderBy('nama')
                ->get();

            $attendanceBySiswa = Presensi::query()
                ->with(['verifier:id,name'])
                ->whereDate('tanggal', $date->format('Y-m-d'))
                ->whereIn('siswa_id', $siswaList->pluck('id'))
                ->get()
                ->keyBy('siswa_id');

            $studentGroupSummary = collect(Siswa::kelompokOptions())
                ->map(function (string $label, string $value) use ($siswaList, $attendanceBySiswa) {
                    $members = $siswaList
                        ->where('kelompok', $value)
                        ->sortBy('nama')
                        ->values();

                    $filled = $members->filter(fn (Siswa $siswa) => $attendanceBySiswa->has($siswa->id))->values();
                    $missing = $members->reject(fn (Siswa $siswa) => $attendanceBySiswa->has($siswa->id))->values();

                    return [
                        'key' => $value,
                        'label' => $label,
                        'total' => $members->count(),
                        'filled' => $filled->count(),
                        'missing' => $missing->count(),
                        'hadir' => $filled->filter(fn (Siswa $siswa) => $attendanceBySiswa->get($siswa->id)?->status === 'hadir')->count(),
                        'terlambat' => $filled->filter(fn (Siswa $siswa) => $attendanceBySiswa->get($siswa->id)?->status === 'terlambat')->count(),
                        'percent' => $members->count() > 0 ? round(($filled->count() / $members->count()) * 100, 1) : 0,
                    ];
                })
                ->values();
        }

        $pamongGroupSummary = collect(User::kelompokOptions())
            ->map(function (string $label, string $value) use ($pamongUsers, $attendanceByUser) {
                $members = $pamongUsers->where('kelompok', $value)->values();
                $filled = $members->filter(fn (User $user) => $attendanceByUser->has($user->id))->values();

                return [
                    'key' => $value,
                    'label' => $label,
                    'total' => $members->count(),
                    'filled' => $filled->count(),
                    'missing' => $members->count() - $filled->count(),
                    'hadir' => $filled->filter(fn (User $user) => $attendanceByUser->get($user->id)?->status === 'hadir')->count(),
                    'terlambat' => $filled->filter(fn (User $user) => $attendanceByUser->get($user->id)?->status === 'terlambat')->count(),
                    'percent' => $members->count() > 0 ? round(($filled->count() / $members->count()) * 100, 1) : 0,
                ];
            })
            ->values();

        $participants = collect()
            ->merge($pamongUsers->map(function (User $user) {
                return [
                    'key' => 'pamong-' . $user->id,
                    'type' => 'pamong',
                    'type_label' => 'Pamong',
                    'id' => $user->id,
                    'name' => $user->name ?: $user->username,
                    'identifier' => $user->username,
                    'unit' => $user->organizationalTeam?->short_name ?: $user->organizationalTeam?->name ?: 'Tanpa bidang',
                    'detail' => $user->organizational_title ?: $user->operationalRoleLabel(),
                ];
            }))
            ->merge($siswaList->map(function (Siswa $siswa) {
                return [
                    'key' => 'siswa-' . $siswa->id,
                    'type' => 'siswa',
                    'type_label' => 'Siswa',
                    'id' => $siswa->id,
                    'name' => $siswa->nama,
                    'identifier' => $siswa->nis,
                    'unit' => $siswa->kelas?->nama ?: 'Tanpa kelas',
                    'detail' => $siswa->kelompok_label,
                ];
            }))
            ->values();

        $attendanceByParticipant = collect();
        foreach ($pamongUsers as $user) {
            if ($attendance = $attendanceByUser->get($user->id)) {
                $attendanceByParticipant->put('pamong-' . $user->id, $attendance);
            }
        }
        foreach ($siswaList as $siswa) {
            if ($attendance = $attendanceBySiswa->get($siswa->id)) {
                $attendanceByParticipant->put('siswa-' . $siswa->id, $attendance);
            }
        }

        $filledParticipants = $participants
            ->filter(fn (array $participant) => $attendanceByParticipant->has($participant['key']))
            ->values();

        $missingParticipants = $participants
            ->reject(fn (array $participant) => $attendanceByParticipant->has($participant['key']))
            ->values();

        $statusCounts = [
            'hadir' => $attendanceByParticipant->where('status', 'hadir')->count(),
            'terlambat' => $attendanceByParticipant->where('status', 'terlambat')->count(),
            'izin' => $attendanceByParticipant->where('status', 'izin')->count(),
            'sakit' => $attendanceByParticipant->where('status', 'sakit')->count(),
            'alpha' => $attendanceByParticipant->where('status', 'alpha')->count(),
            'belum' => $missingParticipants->count(),
        ];

        return view('pamong-presensi.summary', compact(
            'date',
            'activeSchedules',
            'targetLabel',
            'includeSiswa',
            'includePamong',
            'pamongUsers',
            'siswaList',
            'studentGroupSummary',
            'pamongGroupSummary',
            'participants',
            'attendanceByParticipant',
            'filledParticipants',
            'missingParticipants',
            'statusCounts'
        ));
    }

    /**
     * Get presensi data for AJAX.
     */
    public function getData(Request $request): JsonResponse
    {
        $filters = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'user_id' => $request->user_id,
            'status' => $request->status,
        ];

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $this->backfillClosedPamongAlpha(
                $filters['start_date'],
                $filters['end_date'],
                $request->filled('user_id') ? $request->integer('user_id') : null
            );
        }

        $presensi = $this->pamongPresensiService->getData($filters);

        return response()->json([
            'success' => true,
            'data' => $presensi->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'nama' => $item->user->name,
                    'username' => $item->user->username,
                    'tanggal' => $item->tanggal->format('Y-m-d'),
                    'tanggal_formatted' => $item->tanggal->format('d M Y'),
                    'jam_masuk' => $item->jam_masuk?->format('H:i'),
                    'jam_keluar' => $item->jam_keluar?->format('H:i'),
                    'status' => $item->status,
                    'status_color' => $item->status_color,
                    'keterangan' => $item->keterangan,
                    'late_duration' => $item->late_duration_formatted,
                    'is_verified' => $item->is_verified,
                    'verified_by' => $item->verifier?->name,
                ];
            }),
        ]);
    }

    /**
     * Get statistics for AJAX.
     */
    public function getStats(Request $request): JsonResponse
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $userId = $request->filled('user_id') ? $request->integer('user_id') : null;

        $this->backfillClosedPamongAlpha($startDate, $endDate, $userId);

        $stats = $this->pamongPresensiService->getStatistics(
            $startDate,
            $endDate,
            $userId
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Store manual attendance record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpha',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $pamong = User::findOrFail($validated['user_id']);

        if (! auth()->user()->isAdmin() && $pamong->id !== auth()->id()) {
            abort(403, 'Anda hanya dapat mengisi presensi untuk akun sendiri.');
        }

        try {
            $presensi = $this->pamongPresensiService->recordManual(
                $pamong,
                $validated['tanggal'],
                $validated['status'],
                $validated['keterangan'],
                auth()->id()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Presensi pamong berhasil disimpan.',
                    'data' => $presensi,
                ]);
            }

            return back()->with('success', 'Presensi pamong berhasil disimpan.');
        } catch (\App\Exceptions\DuplicateAttendanceException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update attendance record.
     */
    public function update(Request $request, PamongPresensi $pamongPresensi)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengubah status presensi pamong.');
        }

        $validated = $request->validate([
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpha',
            'keterangan' => 'nullable|string|max:500',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_keluar' => 'nullable|date_format:H:i',
        ]);

        $pamongPresensi->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi pamong berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Presensi pamong berhasil diperbarui.');
    }

    /**
     * Delete attendance record.
     */
    public function destroy(PamongPresensi $pamongPresensi)
    {
        $pamongPresensi->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi pamong berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Presensi pamong berhasil dihapus.');
    }

    /**
     * Verify attendance record.
     */
    public function verify(PamongPresensi $pamongPresensi)
    {
        $this->pamongPresensiService->verifyAttendance($pamongPresensi->id, auth()->id());

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi pamong berhasil diverifikasi.',
            ]);
        }

        return back()->with('success', 'Presensi pamong berhasil diverifikasi.');
    }

    /**
     * Export attendance data to Excel.
     */
    public function export(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
        $userId = $request->filled('user_id') ? $request->integer('user_id') : null;

        $this->backfillClosedPamongAlpha($startDate, $endDate, $userId);

        $filename = 'presensi-pamong-' . $startDate . '-' . $endDate . '.xlsx';

        return (new PamongPresensiExport($startDate, $endDate, $userId))->download($filename);
    }

    public function downloadTemplate()
    {
        return (new PamongPresensiTemplateExport(true))->download('template-presensi-pamong.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
            'source_label' => 'nullable|string|max:120',
            'mark_verified' => 'nullable|boolean',
        ], [
            'file.required' => 'File harus diupload.',
            'file.mimes' => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new PamongPresensiImport([
                'source_label' => $request->input('source_label'),
                'mark_verified' => $request->boolean('mark_verified', true),
                'imported_by' => auth()->id(),
            ]);

            $results = $import->import($request->file('file'));
            $message = "Import presensi pamong selesai. Berhasil: {$results['success']}, Gagal: {$results['failed']}";

            return back()
                ->with($results['failed'] > 0 ? 'warning' : 'success', $message)
                ->with('pamong_import_errors', $results['errors']);
        } catch (\Exception $e) {
            \Log::error('Import Presensi Pamong Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal import presensi pamong: ' . $e->getMessage());
        }
    }

    /**
     * Show pamong card with QR code.
     */
    public function card(User $user)
    {
        // Debug: Check if user is pamong
        if (!$this->pamongQrService->isPamong($user)) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User bukan pamong',
                    'user_id' => $user->id,
                    'role' => $user->role?->name,
                ], 404);
            }
            abort(404, 'User bukan pamong');
        }

        $qrData = $this->pamongQrService->getQrData($user);

        return view('pamong-presensi.card', compact('user', 'qrData'));
    }

    /**
     * Show print-only pamong card sized for KTP.
     */
    public function cardPrint(User $user)
    {
        if (!$this->pamongQrService->isPamong($user)) {
            abort(404, 'User bukan pamong');
        }

        $qrData = $this->pamongQrService->getQrData($user);

        return view('pamong-presensi.card-print', compact('user', 'qrData'));
    }

    /**
     * Refresh QR token for pamong.
     */
    public function refreshQr(User $user)
    {
        if (!$this->pamongQrService->isPamong($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User bukan pamong',
            ], 400);
        }

        $qrData = $this->pamongQrService->refreshToken($user);

        return response()->json([
            'success' => true,
            'message' => 'QR Code berhasil di-refresh.',
            'data' => $qrData,
        ]);
    }

    protected function buildListingQuery(Request $request, Carbon $startDate, Carbon $endDate)
    {
        return PamongPresensi::query()
            ->select([
                'id',
                'user_id',
                'tanggal',
                'jam_masuk',
                'jam_keluar',
                'status',
                'keterangan',
                'is_verified',
                'verified_by',
                'verified_at',
                'metadata',
            ])
            ->with([
                'user:id,name,username,avatar_path,kelompok',
                'verifier:id,name',
            ])
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->when(! auth()->user()->isAdmin(), fn ($query) => $query->where('user_id', auth()->id()))
            ->when($request->user_id, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($request->status, function ($query, $status) {
                $status === 'izin_sakit'
                    ? $query->whereIn('status', ['izin', 'sakit'])
                    : $query->where('status', $status);
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk');
    }

    protected function buildPamongGroupPeriodSummary(Carbon $startDate, Carbon $endDate, User $currentUser)
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'kelompok'])
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::operationalRoleNames()))
            ->when(! $currentUser->isAdmin(), fn ($query) => $query->whereKey($currentUser->id))
            ->get();

        $attendance = PamongPresensi::query()
            ->select(['id', 'user_id', 'status'])
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('user_id', $users->pluck('id'))
            ->get();

        return collect(User::kelompokOptions())
            ->map(function (string $label, string $value) use ($users, $attendance) {
                $memberIds = $users->where('kelompok', $value)->pluck('id');
                $records = $attendance->whereIn('user_id', $memberIds);

                return [
                    'key' => $value,
                    'label' => $label,
                    'members' => $memberIds->count(),
                    'records' => $records->count(),
                    'hadir' => $records->where('status', 'hadir')->count(),
                    'terlambat' => $records->where('status', 'terlambat')->count(),
                    'izin_sakit' => $records->whereIn('status', ['izin', 'sakit'])->count(),
                    'alpha' => $records->where('status', 'alpha')->count(),
                ];
            })
            ->values();
    }

    protected function backfillClosedPamongAlpha(Carbon|string $startDate, Carbon|string $endDate, ?int $userId = null): int
    {
        $start = $startDate instanceof Carbon ? $startDate->format('Y-m-d') : Carbon::parse($startDate)->format('Y-m-d');
        $end = $endDate instanceof Carbon ? $endDate->format('Y-m-d') : Carbon::parse($endDate)->format('Y-m-d');

        $created = $this->pamongPresensiService->backfillClosedAlpha($start, $end, $userId);

        return $created;
    }
}
