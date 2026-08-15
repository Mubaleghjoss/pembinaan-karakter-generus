<?php

namespace App\Http\Controllers;

use App\Models\TracerKarakter;
use App\Models\Karakter;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\PamongPresensi;
use App\Models\PamongSiswa;
use App\Models\User;
use App\Imports\TracerKarakterImport;
use App\Exports\TracerKarakterTemplateExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\SiswaKarakterChecklist;

/**
 * Controller for managing tracer karakter (character tracking) functionality.
 * 
 * **Feature: website-settings, Requirements 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4**
 */
class TracerKarakterController extends Controller
{
    /**
     * Display list of students for pamong to track karakter.
     * Also handles verification tab.
     * 
     * **Validates: Requirements 6.1**
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get students based on user role
        if ($user->isTeacher()) {
            // Pamong can only see assigned students
            $siswaQuery = Siswa::whereIn('id', $user->getAssignedSiswaIds());
        } else {
            // Admin can see all students
            $siswaQuery = Siswa::active();
        }
        
        // Filter by kelas if provided
        if ($request->filled('kelas_id')) {
            $siswaQuery->where('kelas_id', $request->kelas_id);
        }
        
        // Search by name or NIS
        if ($request->filled('search')) {
            $search = $request->search;
            $siswaQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        
        $siswaList = $siswaQuery->with('kelas')->paginate(20);
        $kelasOptions = Kelas::where('is_active', true)->get();
        
        // Get verification stats for badge
        $statsQuery = \App\Models\SiswaKarakterChecklist::query();
        if ($user->isTeacher()) {
            $statsQuery->whereIn('siswa_id', $user->getAssignedSiswaIds());
        }
        
        $stats = [
            'total' => $statsQuery->count(),
            'verified' => (clone $statsQuery)->verified()->count(),
            'unverified' => (clone $statsQuery)->unverified()->count(),
        ];
        
        // Always load checklists for verification tab (so data shows without filter)
        $checklistQuery = \App\Models\SiswaKarakterChecklist::with(['siswa.alumniReviewer:id,name', 'karakter', 'verifier', 'ortuComments'])
            ->orderBy('checked_at', 'desc');
        
        if ($user->isTeacher()) {
            $checklistQuery->whereIn('siswa_id', $user->getAssignedSiswaIds());
        }
        
        // Apply verification filters (default to 'unverified' to show pending tasks first)
        $status = $request->input('status', 'unverified');
        if ($status === 'verified') {
            $checklistQuery->verified();
        } elseif ($status === 'unverified') {
            $checklistQuery->unverified();
        }
        // status === 'all' = no filter (show all)
        
        // Filter by siswa
        if ($request->filled('siswa_id')) {
            $checklistQuery->where('siswa_id', $request->siswa_id);
        }

        if ($request->filled('karakter_id')) {
            $checklistQuery->where('karakter_id', $request->karakter_id);
        }
        
        if ($request->has('date_from') && $request->date_from) {
            $checklistQuery->whereDate('checked_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $checklistQuery->whereDate('checked_at', '<=', $request->date_to);
        }
        
        $checklists = $status === 'unverified'
            ? $checklistQuery->get()
            : $checklistQuery->paginate(20);
        
        // Get siswa options for verification filter
        $siswaOptions = $user->isTeacher() 
            ? Siswa::whereIn('id', $user->getAssignedSiswaIds())->get()
            : Siswa::query()->where('is_active', true)->whereIn('status', ['active', 'graduated'])->get();
        $karakterOptions = Karakter::active()->orderBy('nama')->get();

        [$analyticsSummary, $pamongAnalyticsRows, $analyticsRange] = $this->buildPamongAnalytics($request, $user);

        return view('tugas-pkg.verification.index', compact(
            'siswaList',
            'kelasOptions',
            'stats',
            'checklists',
            'siswaOptions',
            'karakterOptions',
            'analyticsSummary',
            'pamongAnalyticsRows',
            'analyticsRange'
        ));
    }

    protected function buildPamongAnalytics(Request $request, User $viewer): array
    {
        $startDate = $request->input('analytics_from', now()->startOfMonth()->toDateString());
        $endDate = $request->input('analytics_to', now()->endOfMonth()->toDateString());

        $candidatePamongs = User::query()
            ->select('id', 'name', 'username', 'role_id')
            ->with('role:id,name')
            ->when(
                $viewer->isTeacher(),
                fn ($query) => $query->where('id', $viewer->id),
                fn ($query) => $query->whereHas('role', fn ($role) => $role->whereIn('name', array_merge(User::operationalRoleNames(), [User::ROLE_ADMIN])))
            )
            ->orderBy('name')
            ->get();

        $assignedStudentIds = $viewer->isTeacher() ? $viewer->getAssignedSiswaIds() : null;

        $verificationBase = SiswaKarakterChecklist::query()
            ->whereNotNull('verified_by')
            ->whereNotNull('verified_at')
            ->whereBetween('verified_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->when($assignedStudentIds !== null, fn ($query) => $query->whereIn('siswa_id', $assignedStudentIds));

        $verificationSummary = (clone $verificationBase)
            ->selectRaw('verified_by, COUNT(*) as total_verifications, COUNT(DISTINCT siswa_id) as siswa_verified')
            ->groupBy('verified_by')
            ->get()
            ->keyBy('verified_by');

        $verificationResponseTimes = (clone $verificationBase)
            ->select('verified_by', 'checked_at', 'verified_at')
            ->whereNotNull('checked_at')
            ->get()
            ->groupBy('verified_by')
            ->map(function ($rows) {
                $minutes = $rows
                    ->map(function ($row) {
                        $diff = $row->checked_at?->diffInMinutes($row->verified_at, false);
                        return $diff !== false && $diff >= 0 ? $diff : null;
                    })
                    ->filter();

                return $minutes->isNotEmpty() ? round($minutes->avg(), 1) : null;
            });

        $pendingBacklog = PamongSiswa::query()
            ->whereNull('pamong_siswa.ended_at')
            ->join('siswa_karakter_checklist as skc', 'skc.siswa_id', '=', 'pamong_siswa.siswa_id')
            ->selectRaw('pamong_siswa.pamong_id, COUNT(skc.id) as pending_backlog')
            ->whereNull('skc.verified_at')
            ->whereNull('skc.deleted_at')
            ->when($assignedStudentIds !== null, fn ($query) => $query->whereIn('skc.siswa_id', $assignedStudentIds))
            ->groupBy('pamong_siswa.pamong_id')
            ->pluck('pending_backlog', 'pamong_id');

        $attendanceBase = PamongPresensi::query()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($viewer->isTeacher(), fn ($query) => $query->where('user_id', $viewer->id));

        $attendanceSummary = (clone $attendanceBase)
            ->selectRaw("
                user_id,
                COUNT(*) as attendance_total,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as attendance_hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as attendance_terlambat,
                SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_attendance
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $rows = $candidatePamongs->map(function ($pamong) use ($verificationSummary, $verificationResponseTimes, $pendingBacklog, $attendanceSummary) {
            $verification = $verificationSummary->get($pamong->id);
            $attendance = $attendanceSummary->get($pamong->id);

            $totalVerifications = (int) ($verification->total_verifications ?? 0);
            $attendanceTotal = (int) ($attendance->attendance_total ?? 0);
            $attendanceHadir = (int) ($attendance->attendance_hadir ?? 0);
            $attendanceTerlambat = (int) ($attendance->attendance_terlambat ?? 0);
            $verifiedAttendance = (int) ($attendance->verified_attendance ?? 0);
            $pending = (int) ($pendingBacklog[$pamong->id] ?? 0);
            $score = ($totalVerifications * 3) + $attendanceTotal + $verifiedAttendance;

            return [
                'id' => $pamong->id,
                'name' => $pamong->name,
                'username' => $pamong->username,
                'role_name' => $pamong->role->name ?? 'user',
                'total_verifications' => $totalVerifications,
                'siswa_verified' => (int) ($verification->siswa_verified ?? 0),
                'avg_verification_minutes' => $verificationResponseTimes->get($pamong->id),
                'pending_backlog' => $pending,
                'attendance_total' => $attendanceTotal,
                'attendance_hadir' => $attendanceHadir,
                'attendance_terlambat' => $attendanceTerlambat,
                'verified_attendance' => $verifiedAttendance,
                'activity_score' => $score,
                'activity_status' => $score >= 20 ? 'tinggi' : ($score >= 8 ? 'sedang' : ($score > 0 ? 'rendah' : 'belum aktif')),
            ];
        })->sortByDesc('activity_score')->values();

        $summary = [
            'total_pamong' => $rows->count(),
            'active_pamong' => $rows->where('activity_score', '>', 0)->count(),
            'total_verifications' => $rows->sum('total_verifications'),
            'pending_backlog' => $rows->sum('pending_backlog'),
            'attendance_total' => $rows->sum('attendance_total'),
            'verified_attendance' => $rows->sum('verified_attendance'),
        ];

        return [$summary, $rows, ['from' => $startDate, 'to' => $endDate]];
    }


    /**
     * Show karakter checklist form for a specific student.
     * 
     * **Validates: Requirements 6.2**
     */
    public function checkKarakter(Siswa $siswa)
    {
        $user = Auth::user();
        
        // Verify pamong access control
        if ($user->isTeacher() && !$user->isAssignedTo($siswa)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        // Get active karakter available today.
        $karakterList = Karakter::active()
            ->availableOn(today())
            ->get();
        
        // Get today's checked karakter for this student
        $todayChecked = TracerKarakter::where('siswa_id', $siswa->id)
            ->whereDate('checked_at', today())
            ->pluck('karakter_id')
            ->toArray();
        
        return view('tugas-pkg.verification.check', compact('siswa', 'karakterList', 'todayChecked'));
    }

    /**
     * Store karakter check record.
     * 
     * **Validates: Requirements 6.3, 6.5**
     */
    public function storeCheck(Request $request, Siswa $siswa)
    {
        $user = Auth::user();
        
        // Verify pamong access control
        if ($user->isTeacher() && !$user->isAssignedTo($siswa)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        $request->validate([
            'karakter_ids' => 'required|array|min:1',
            'karakter_ids.*' => 'exists:karakter,id',
            'catatan' => 'required|string|min:5|max:1000',
        ]);
        
        $checkedAt = now();
        $totalPoints = 0;
        $availableKarakter = Karakter::active()
            ->availableOn($checkedAt)
            ->whereIn('id', $request->karakter_ids)
            ->pluck('id')
            ->all();

        $invalidKarakterIds = array_diff($request->karakter_ids, $availableKarakter);

        if (! empty($invalidKarakterIds)) {
            return back()->with('error', 'Ada tugas yang berada di luar periode aktif, sehingga tidak bisa dicatat.');
        }
        
        foreach ($request->karakter_ids as $karakterId) {
            // Create TracerKarakter record (pamong tracking)
            $tracerKarakter = TracerKarakter::create([
                'siswa_id' => $siswa->id,
                'karakter_id' => $karakterId,
                'pamong_id' => $user->id,
                'checked_at' => $checkedAt,
                'catatan' => $request->catatan,
            ]);
            
            // Also create SiswaKarakterChecklist (auto-verified by pamong)
            // so the siswa can see it on their tugas PKG page
            $existingChecklist = \App\Models\SiswaKarakterChecklist::where('siswa_id', $siswa->id)
                ->where('karakter_id', $karakterId)
                ->whereDate('checked_at', $checkedAt->toDateString())
                ->first();

            if ($existingChecklist) {
                $existingChecklist->update([
                    'verified_by' => $user->id,
                    'verified_at' => $checkedAt,
                    'notes' => $request->catatan ?? 'Diceklis oleh pamong ' . $user->username,
                ]);
                $checklist = $existingChecklist;
            } else {
                $checklist = \App\Models\SiswaKarakterChecklist::create([
                    'siswa_id' => $siswa->id,
                    'karakter_id' => $karakterId,
                    'checked_at' => $checkedAt,
                    'verified_by' => $user->id,
                    'verified_at' => $checkedAt,
                    'notes' => $request->catatan ?? 'Diceklis oleh pamong ' . $user->username,
                ]);
            }

            // Award gamification points
            $karakter = \App\Models\Karakter::find($karakterId);
            $points = (int) ($checklist->awarded_points ?? ($karakter->poin ?? 10));
            $totalPoints += $points;

            try {
                $gamificationService = app(\App\Services\GamificationService::class);
                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                $siswaPoint->addPoints(
                    $points,
                    'character',
                    'Ceklis oleh pamong: ' . ($karakter->nama ?? 'karakter') . ' (+' . $points . ' poin)',
                    $checklist,
                    $this->buildChecklistPointMetadata($checklist)
                );
            } catch (\Exception $e) {
                \Log::warning('Gamification character points failed: ' . $e->getMessage());
            }
        }
        
        // Log activity
        if ($user->usesPamongPermissionSystem()) {
            \App\Models\PamongActivityLog::log(
                userId: $user->id,
                action: 'create',
                description: 'Ceklis karakter untuk siswa ' . $siswa->nama . ' (' . count($request->karakter_ids) . ' karakter, +' . $totalPoints . ' poin)',
                module: 'tracer_karakter',
                metadata: ['siswa_id' => $siswa->id, 'count' => count($request->karakter_ids), 'points' => $totalPoints],
                ipAddress: $request->ip()
            );
        }
        
        return redirect()->route('tugas-pkg.verification')
            ->with('success', 'Karakter berhasil diceklis untuk ' . $siswa->nama . '. +' . $totalPoints . ' poin diberikan.');
    }

    /**
     * Display karakter history for a specific student.
     * 
     * **Validates: Requirements 6.4**
     */
    public function history(Request $request, Siswa $siswa)
    {
        $user = Auth::user();
        
        // Verify pamong access control
        if ($user->isTeacher() && !$user->isAssignedTo($siswa)) {
            abort(403, 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        $query = TracerKarakter::where('siswa_id', $siswa->id)
            ->with(['karakter', 'pamong']);
        
        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('checked_at', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('checked_at', '<=', $request->end_date);
        }
        
        $history = $query->orderBy('checked_at', 'desc')->paginate(20);
        
        // Calculate karakter summary
        $totalKarakter = Karakter::active()->count();
        $checkedKarakter = TracerKarakter::where('siswa_id', $siswa->id)
            ->distinct('karakter_id')
            ->count('karakter_id');
        $percentage = $totalKarakter > 0 ? round(($checkedKarakter / $totalKarakter) * 100, 1) : 0;
        
        return view('tugas-pkg.verification.history', compact('siswa', 'history', 'totalKarakter', 'checkedKarakter', 'percentage'));
    }


    /**
     * Display rekap karakter report.
     * 
     * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
     */
    public function rekap(Request $request)
    {
        $user = Auth::user();
        $assignedSiswaIds = $user->isTeacher() ? $user->getAssignedSiswaIds() : null;
        $filters = $request->only(['kelas_id', 'pamong_id', 'start_date', 'end_date']);
        $cacheKey = 'tracer_rekap:' . md5(json_encode([
            'user_id' => $user->id,
            'role' => $user->role?->name,
            'filters' => $filters,
        ]));

        $payload = Cache::remember($cacheKey, now()->addSeconds(90), function () use ($user, $assignedSiswaIds, $request) {
            $siswaQuery = Siswa::active();

            if ($assignedSiswaIds !== null) {
                $siswaQuery->whereIn('id', $assignedSiswaIds);
            }

            if ($request->filled('kelas_id')) {
                $siswaQuery->where('kelas_id', $request->kelas_id);
            }

            if (! $user->isTeacher() && $request->filled('pamong_id')) {
                $pamongSiswaIds = \App\Models\PamongSiswa::active()->where('pamong_id', $request->pamong_id)
                    ->pluck('siswa_id');
                $siswaQuery->whereIn('id', $pamongSiswaIds);
            }

            $siswaList = $siswaQuery->with('kelas')->get();
            $totalKarakter = Karakter::active()->count();

            $statsQuery = TracerKarakter::query();
            if ($siswaList->isNotEmpty()) {
                $statsQuery->whereIn('siswa_id', $siswaList->pluck('id'));
            } else {
                $statsQuery->whereRaw('1 = 0');
            }

            if ($request->filled('start_date')) {
                $statsQuery->whereDate('checked_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $statsQuery->whereDate('checked_at', '<=', $request->end_date);
            }

            $statsBySiswa = $statsQuery
                ->select('siswa_id')
                ->selectRaw('COUNT(*) as total_checks')
                ->selectRaw('COUNT(DISTINCT karakter_id) as checked_count')
                ->groupBy('siswa_id')
                ->get()
                ->keyBy('siswa_id');

            $rekapData = $siswaList
                ->map(function ($siswa) use ($statsBySiswa, $totalKarakter) {
                    $stats = $statsBySiswa->get($siswa->id);
                    $checkedCount = (int) ($stats->checked_count ?? 0);
                    $totalChecks = (int) ($stats->total_checks ?? 0);
                    $percentage = $totalKarakter > 0 ? round(($checkedCount / $totalKarakter) * 100, 1) : 0;

                    return [
                        'siswa' => $siswa,
                        'checked_count' => $checkedCount,
                        'total_checks' => $totalChecks,
                        'total_karakter' => $totalKarakter,
                        'percentage' => $percentage,
                    ];
                })
                ->sortByDesc('percentage')
                ->values();
            $rekapSummary = [
                'total_siswa' => $rekapData->count(),
                'average_percentage' => $rekapData->isNotEmpty()
                    ? round((float) $rekapData->avg('percentage'), 1)
                    : 0,
            ];

            $kelasOptions = Kelas::where('is_active', true)->get();
            $pamongOptions = ! $user->isTeacher()
                ? \App\Models\User::whereHas('role', fn($q) => $q->whereIn('name', User::operationalRoleNames()))->get()
                : collect();

            return compact('rekapData', 'rekapSummary', 'kelasOptions', 'pamongOptions', 'totalKarakter');
        });

        return view('tugas-pkg.verification.rekap', $payload);
    }

    /**
     * Export rekap karakter to Excel.
     * 
     * **Validates: Requirements 7.5**
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Get students based on user role
        if ($user->isTeacher()) {
            $siswaQuery = Siswa::whereIn('id', $user->getAssignedSiswaIds());
        } else {
            $siswaQuery = Siswa::active();
        }
        
        // Apply filters
        if ($request->filled('kelas_id')) {
            $siswaQuery->where('kelas_id', $request->kelas_id);
        }
        
        if (! $user->isTeacher() && $request->filled('pamong_id')) {
            $pamongSiswaIds = \App\Models\PamongSiswa::active()->where('pamong_id', $request->pamong_id)
                ->pluck('siswa_id');
            $siswaQuery->whereIn('id', $pamongSiswaIds);
        }
        
        $siswaList = $siswaQuery
            ->select(['id', 'nis', 'nama', 'kelas_id'])
            ->with('kelas:id,nama')
            ->get();
        $karakterList = Karakter::active()->get(['id', 'nama']);
        $totalKarakter = $karakterList->count();
        
        // Date range
        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate = $request->filled('end_date') ? $request->end_date : null;

        $checkedKarakterMap = TracerKarakter::query()
            ->select(['siswa_id', 'karakter_id'])
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->when($startDate, fn($q) => $q->whereDate('checked_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('checked_at', '<=', $endDate))
            ->get()
            ->groupBy('siswa_id')
            ->map(fn($items) => $items->pluck('karakter_id')->unique()->values()->all());
        
        // Build export data
        $exportData = [];
        $headers = ['No', 'NIS', 'Nama', 'Kelas'];
        
        foreach ($karakterList as $karakter) {
            $headers[] = $karakter->nama;
        }
        $headers[] = 'Total';
        $headers[] = 'Persentase';
        
        $exportData[] = $headers;
        
        $no = 1;
        foreach ($siswaList as $siswa) {
            $row = [
                $no++,
                $siswa->nis,
                $siswa->nama,
                $siswa->kelas->nama ?? '-',
            ];
            
            $checkedKarakterIds = $checkedKarakterMap->get($siswa->id, []);
            
            $checkedCount = 0;
            foreach ($karakterList as $karakter) {
                $isChecked = in_array($karakter->id, $checkedKarakterIds);
                $row[] = $isChecked ? 'YA' : '-';
                if ($isChecked) $checkedCount++;
            }
            
            $row[] = $checkedCount . '/' . $totalKarakter;
            $row[] = $totalKarakter > 0 ? round(($checkedCount / $totalKarakter) * 100, 1) . '%' : '0%';
            
            $exportData[] = $row;
        }
        
        // Generate CSV (simple export without external library)
        $filename = 'rekap-karakter-' . date('Y-m-d') . '.csv';
        
        $callback = function() use ($exportData) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download template Excel untuk import tracer karakter.
     */
    public function downloadTemplate()
    {
        $export = new TracerKarakterTemplateExport(true);
        return $export->download('template-tracer-karakter.xlsx');
    }

    /**
     * Import data tracer karakter dari file Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'File harus diupload.',
            'file.mimes' => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new TracerKarakterImport();
            $results = $import->import($request->file('file'));
            
            $message = "Import selesai. Berhasil: {$results['success']}, Gagal: {$results['failed']}";
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $results,
                ]);
            }

            if ($results['failed'] > 0) {
                return back()->with('warning', $message)->with('import_errors', $results['errors']);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Import Tracer Karakter Error: ' . $e->getMessage());
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal import data: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal import data: ' . $e->getMessage());
        }
    }

    /**
     * Display active karakter items (browse page with check/verify/cancel actions).
     */
    public function karakterHarian(Request $request)
    {
        $user = Auth::user();
        
        // Get siswa binaan IDs
        if ($user->isTeacher()) {
            $siswaIds = $user->getAssignedSiswaIds();
        } else {
            $siswaIds = Siswa::active()->pluck('id')->toArray();
        }
        
        // Get available karakter grouped by category
        $allKarakter = Karakter::active()->get()->filter(fn($k) => $k->isAvailable());
        $harianList = $allKarakter->where('kategori', 'harian')->values();
        $mingguanList = $allKarakter->where('kategori', 'mingguan')->values();
        $bulananList = $allKarakter->where('kategori', 'bulanan')->values();
        
        // Get checklists for verification (siswa binaan only)
        $checklistQuery = \App\Models\SiswaKarakterChecklist::with(['siswa', 'karakter', 'verifier', 'ortuComments'])
            ->whereIn('siswa_id', $siswaIds)
            ->orderBy('checked_at', 'desc');
        
        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $checklistQuery->verified();
            } elseif ($request->status === 'unverified') {
                $checklistQuery->unverified();
            }
        }
        
        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $checklistQuery->whereHas('karakter', fn($q) => $q->where('kategori', $request->kategori));
        }
        
        $checklists = $checklistQuery->paginate(20);
        
        // Stats
        $statsBase = \App\Models\SiswaKarakterChecklist::whereIn('siswa_id', $siswaIds);
        $stats = [
            'total' => $statsBase->count(),
            'verified' => (clone $statsBase)->verified()->count(),
            'unverified' => (clone $statsBase)->unverified()->count(),
        ];
        
        return view('tugas-pkg.verification.karakter-harian', compact(
            'harianList', 'mingguanList', 'bulananList',
            'checklists', 'stats'
        ));
    }

    /**
     * Detail view: per-student, per-task completion history.
     */
    public function detailSiswa(Request $request)
    {
        $user = Auth::user();

        // Get siswa options based on role
        if ($user->isTeacher()) {
            $siswaOptions = Siswa::whereIn('id', $user->getAssignedSiswaIds())->orderBy('nama')->get();
        } else {
            $siswaOptions = Siswa::query()->where('is_active', true)->whereIn('status', ['active', 'graduated'])->orderBy('nama')->get();
        }

        // Get all active karakter
        $karakterOptions = Karakter::where('is_active', true)->orderBy('nama')->get();

        $records = collect();
        $trashedRecords = collect();
        $selectedSiswa = null;
        $selectedKarakter = null;
        $summary = null;

        if ($request->filled('siswa_id')) {
            $selectedSiswa = Siswa::find($request->siswa_id);
            
            $query = SiswaKarakterChecklist::with(['karakter', 'verifier', 'ortuComments'])
                ->where('siswa_id', $request->siswa_id)
                ->orderBy('checked_at', 'desc');

            if ($request->filled('karakter_id')) {
                $selectedKarakter = Karakter::find($request->karakter_id);
                $query->where('karakter_id', $request->karakter_id);
            }

            $records = $query->paginate(30);

            // Get soft-deleted (trashed) records for restore tab
            $trashedQuery = SiswaKarakterChecklist::onlyTrashed()
                ->with(['karakter', 'verifier', 'deletedByUser'])
                ->where('siswa_id', $request->siswa_id)
                ->orderBy('deleted_at', 'desc');

            if ($request->filled('karakter_id')) {
                $trashedQuery->where('karakter_id', $request->karakter_id);
            }

            $trashedRecords = $trashedQuery->get();

            // Build summary stats
            $summaryQuery = SiswaKarakterChecklist::where('siswa_id', $request->siswa_id);
            if ($request->filled('karakter_id')) {
                $summaryQuery->where('karakter_id', $request->karakter_id);
            }
            
            $totalRecords = $summaryQuery->count();
            $verifiedRecords = (clone $summaryQuery)->whereNotNull('verified_at')->count();
            $unverifiedRecords = $totalRecords - $verifiedRecords;
            $firstDate = (clone $summaryQuery)->min('checked_at');
            $lastDate = (clone $summaryQuery)->max('checked_at');

            $summary = [
                'total' => $totalRecords,
                'verified' => $verifiedRecords,
                'unverified' => $unverifiedRecords,
                'deleted' => $trashedRecords->count(),
                'first_date' => $firstDate ? \Carbon\Carbon::parse($firstDate)->isoFormat('D MMM YYYY') : '-',
                'last_date' => $lastDate ? \Carbon\Carbon::parse($lastDate)->isoFormat('D MMM YYYY') : '-',
            ];
        }

        return view('tugas-pkg.verification.detail-siswa', compact(
            'siswaOptions', 'karakterOptions', 'records',
            'selectedSiswa', 'selectedKarakter', 'summary', 'trashedRecords'
        ));
    }
    /**
     * Handle bulk actions for karakter checklists.
     * 
     * **Validates: Requirements 6.5**
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:siswa_karakter_checklist,id',
            'action' => 'required|in:verify,unverify,destroy',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $ids = array_values(array_unique($request->ids));
        $action = $request->action;
        $reason = $request->reason;
        $notes = $request->notes;
        $user = Auth::user();
        $actor = Auth::user()->username ?? Auth::user()->name ?? 'Admin';
        $count = 0;

        DB::beginTransaction();
        try {
            $checklistsQuery = SiswaKarakterChecklist::whereIn('id', $ids)->with(['siswa', 'karakter']);

            if ($user?->isTeacher()) {
                $checklistsQuery->whereIn('siswa_id', $user->getAssignedSiswaIds());
            }

            $checklists = $checklistsQuery->get();
            $gamificationService = app(\App\Services\GamificationService::class);

            foreach ($checklists as $checklist) {
                if ($action === 'verify') {
                    if (!$checklist->isVerified()) {
                        $checklist->update([
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                            'notes' => $notes,
                        ]);

                        // Award points
                        $siswa = $checklist->siswa;
                        $karakter = $checklist->karakter;
                        if ($siswa && $karakter) {
                            try {
                                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                                $points = (int) ($checklist->awarded_points ?? ($karakter->poin ?? 10));
                                $siswaPoint->addPoints(
                                    $points,
                                    'character',
                                    'Verifikasi tugas PKG: ' . $karakter->nama . ' (+' . $points . ' poin' . ($checklist->proof_bonus_points > 0 ? ', termasuk bonus bukti +' . $checklist->proof_bonus_points : '') . ')',
                                    $checklist,
                                    $this->buildChecklistPointMetadata($checklist)
                                );
                                
                                // Check category completion bonus (need to instantiate controller/service or copy logic)
                                // Ideally this logic should be in a service, for now we skip bonus in bulk or copy simple logic
                            } catch (\Exception $e) {
                                \Log::warning('Gamification error on bulk verify: ' . $e->getMessage());
                            }
                        }
                        $count++;
                    }
                } elseif ($action === 'unverify') {
                    if ($checklist->isVerified()) {
                        // Reverse points
                        $siswa = $checklist->siswa;
                        $karakter = $checklist->karakter;
                        $pointsToReverse = (int) ($checklist->awarded_points ?? ($karakter->poin ?? 10));

                        if ($siswa && $karakter) {
                            try {
                                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                                $siswaPoint->addPoints(
                                    -$pointsToReverse,
                                    'character',
                                    'Batal verifikasi massal: ' . $karakter->nama . ' (-' . $pointsToReverse . ' poin). Alasan: ' . ($reason ?? 'Bulk action') . ' (oleh ' . $actor . ')',
                                    $checklist,
                                    $this->buildChecklistPointMetadata($checklist)
                                );
                            } catch (\Exception $e) {
                                \Log::warning('Gamification error on bulk unverify: ' . $e->getMessage());
                            }
                        }

                        $checklist->update([
                            'verified_by' => null,
                            'verified_at' => null,
                            'notes' => null,
                        ]);
                        $checklist->clearStoredEvidenceFiles();
                        $count++;
                    }
                } elseif ($action === 'destroy') {
                    // Reverse points if verified
                    if ($checklist->isVerified()) {
                        $siswa = $checklist->siswa;
                        $karakter = $checklist->karakter;
                        $pointsToReverse = (int) ($checklist->awarded_points ?? ($karakter->poin ?? 10));

                        if ($siswa && $karakter) {
                            try {
                                $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);
                                $siswaPoint->addPoints(
                                    -$pointsToReverse,
                                    'character',
                                    'Hapus data massal: ' . $karakter->nama . ' (-' . $pointsToReverse . ' poin). Alasan: ' . ($reason ?? 'Bulk action') . ' (oleh ' . $actor . ')',
                                    $checklist,
                                    $this->buildChecklistPointMetadata($checklist)
                                );
                            } catch (\Exception $e) {
                                \Log::warning('Gamification error on bulk destroy: ' . $e->getMessage());
                            }
                        }
                    }
                    $checklist->update([
                        'deleted_by' => Auth::id(),
                        'deleted_reason' => $reason ?? 'Bulk action oleh ' . $actor,
                    ]);
                    $checklist->clearStoredEvidenceFiles();
                    $checklist->delete();
                    $count++;
                }
            }

            if ($count === 0) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->with('error', 'Tidak ada tugas yang berubah. Cek ulang pilihan tugas, status verifikasi, atau hak akses pamong.');
            }

            DB::commit();

            // Log activity for pamong
            if ($user && $user->usesPamongPermissionSystem() && $count > 0) {
                $actionMap = [
                    'verify' => ['action' => 'verify', 'desc' => "Memverifikasi {$count} tugas karakter"],
                    'unverify' => ['action' => 'verify', 'desc' => "Membatalkan verifikasi {$count} tugas karakter"],
                    'destroy' => ['action' => 'delete', 'desc' => "Menghapus {$count} data karakter"],
                ];
                $logInfo = $actionMap[$action] ?? null;
                if ($logInfo) {
                    \App\Models\PamongActivityLog::log(
                        userId: $user->id,
                        action: $logInfo['action'],
                        description: $logInfo['desc'],
                        module: 'tracer_karakter',
                        metadata: ['action' => $action, 'count' => $count, 'ids' => $ids],
                        ipAddress: $request->ip()
                    );
                }
            }

            $message = '';
            if ($action === 'verify') $message = "$count data berhasil diverifikasi.";
            if ($action === 'unverify') $message = "$count data berhasil dibatalkan verifikasinya.";
            if ($action === 'destroy') $message = "$count data berhasil dihapus.";

            return redirect()->route('tugas-pkg.verification', ['tab' => 'verification'])->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function buildChecklistPointMetadata(SiswaKarakterChecklist $checklist): array
    {
        return [
            'checklist_id' => $checklist->id,
            'base_points' => (int) ($checklist->karakter?->poin ?? 10),
            'photo_proof_bonus_points' => (int) ($checklist->photo_proof_bonus_points ?? 0),
            'voice_note_bonus_points' => (int) ($checklist->voice_note_bonus_points ?? 0),
            'proof_bonus_points' => (int) ($checklist->proof_bonus_points ?? 0),
            'proof_uploaded' => $checklist->has_proof,
            'photo_uploaded' => $checklist->has_photo_proof,
            'voice_note_uploaded' => $checklist->has_voice_note,
        ];
    }
}
