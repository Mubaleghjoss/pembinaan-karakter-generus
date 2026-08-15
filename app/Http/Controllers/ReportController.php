<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use App\Support\TargetGrade;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role.permission:view_reports');
    }

    public function index()
    {
        $user = Auth::user();

        return view('reports.index', [
            'user' => $user->load('role'),
            'pageTitle' => 'Laporan Presensi',
            'schoolGradeOptions' => TargetGrade::schoolClassOptions(),
            'pamongOptions' => $user->isTeacher()
                ? collect()
                : User::query()->whereHas('role', fn ($query) => $query->whereIn('name', User::operationalRoleNames()))
                    ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        [$filters, $cacheKey] = $this->resolveFilters($request, 'summary');

        $data = Cache::remember($cacheKey, 120, function () use ($filters) {
            $totalSiswa = $this->activeSiswaQuery($filters)->count();
            $totalPresensi = $this->presensiQuery($filters)->count();
            $presenceSummary = $this->presensiQuery($filters)
                ->selectRaw("
                    SUM(CASE WHEN presensi.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN presensi.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat
                ")
                ->first();

            $totalHari = $this->totalDaysInRange($filters);
            $attendanceOpportunities = max(1, $totalSiswa * $totalHari);
            $totalMasuk = (int) ($presenceSummary->hadir ?? 0) + (int) ($presenceSummary->terlambat ?? 0);

            return [
                'total_siswa' => $totalSiswa,
                'total_presensi' => $totalPresensi,
                'persentase_kehadiran' => round(($totalMasuk / $attendanceOpportunities) * 100, 1),
                'rata_rata_harian' => round($totalPresensi / max(1, $totalHari), 1),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function statusChart(Request $request): JsonResponse
    {
        [$filters, $cacheKey] = $this->resolveFilters($request, 'status-chart');

        $data = Cache::remember($cacheKey, 120, function () use ($filters) {
            $rows = $this->presensiQuery($filters)
                ->selectRaw('presensi.status, COUNT(*) as total')
                ->groupBy('presensi.status')
                ->pluck('total', 'status');

            return [
                'hadir' => (int) ($rows['hadir'] ?? 0),
                'terlambat' => (int) ($rows['terlambat'] ?? 0),
                'tidak_hadir' => (int) ($rows['alpha'] ?? 0),
                'izin' => (int) ($rows['izin'] ?? 0),
                'sakit' => (int) ($rows['sakit'] ?? 0),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function trendChart(Request $request): JsonResponse
    {
        [$filters, $cacheKey] = $this->resolveFilters($request, 'trend-chart');

        $data = Cache::remember($cacheKey, 120, function () use ($filters) {
            $rows = $this->presensiQuery($filters)
                ->selectRaw('presensi.tanggal, presensi.status, COUNT(*) as total')
                ->groupBy('presensi.tanggal', 'presensi.status')
                ->orderBy('presensi.tanggal')
                ->get();

            $grouped = $rows->groupBy(fn ($row) => Carbon::parse($row->tanggal)->toDateString());
            $labels = [];
            $hadir = [];
            $terlambat = [];
            $tidakHadir = [];

            foreach (CarbonPeriod::create($filters['tanggal_mulai'], $filters['tanggal_selesai']) as $date) {
                $key = $date->toDateString();
                $statusRows = $grouped->get($key, collect())->pluck('total', 'status');

                $labels[] = $date->translatedFormat('d M');
                $hadir[] = (int) ($statusRows['hadir'] ?? 0);
                $terlambat[] = (int) ($statusRows['terlambat'] ?? 0);
                $tidakHadir[] = (int) ($statusRows['alpha'] ?? 0);
            }

            return [
                'labels' => $labels,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'tidak_hadir' => $tidakHadir,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function classPerformance(Request $request): JsonResponse
    {
        [$filters, $cacheKey] = $this->resolveFilters($request, 'class-performance');

        $data = Cache::remember($cacheKey, 120, function () use ($filters) {
            $totalHari = $this->totalDaysInRange($filters);

            $studentCounts = $this->activeSiswaQuery($filters)
                ->selectRaw('school_grade, COUNT(*) as total_siswa')
                ->groupBy('school_grade')
                ->pluck('total_siswa', 'school_grade');

            $attendanceRows = $this->presensiQuery($filters)
                ->selectRaw("
                    siswa.school_grade,
                    SUM(CASE WHEN presensi.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN presensi.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN presensi.status = 'alpha' THEN 1 ELSE 0 END) as tidak_hadir
                ")
                ->groupBy('siswa.school_grade')
                ->get()
                ->keyBy('school_grade');

            $classRows = collect(TargetGrade::schoolClassOptions())
                ->when($filters['school_grade'], fn ($labels) => $labels->only($filters['school_grade']))
                ->map(function ($label, $grade) use ($attendanceRows, $studentCounts, $totalHari) {
                    $attendance = $attendanceRows->get($grade);
                    $hadir = (int) ($attendance->hadir ?? 0);
                    $terlambat = (int) ($attendance->terlambat ?? 0);
                    $tidakHadir = (int) ($attendance->tidak_hadir ?? 0);
                    $totalSiswa = (int) ($studentCounts[$grade] ?? 0);
                    $attendanceOpportunities = max(1, $totalSiswa * $totalHari);

                    return [
                        'id' => $grade,
                        'nama' => $label,
                        'total_siswa' => $totalSiswa,
                        'hadir' => $hadir,
                        'terlambat' => $terlambat,
                        'tidak_hadir' => $tidakHadir,
                        'persentase_kehadiran' => round((($hadir + $terlambat) / $attendanceOpportunities) * 100, 1),
                    ];
                })->values()->all();

            $pamongCounts = DB::table('pamong_siswa')
                ->join('siswa', 'siswa.id', '=', 'pamong_siswa.siswa_id')
                ->whereNull('pamong_siswa.ended_at')
                ->where('siswa.status', 'active')->where('siswa.is_active', true)
                ->when($filters['school_grade'], fn ($query, $grade) => $query->where('siswa.school_grade', $grade))
                ->when($filters['pamong_id'], fn ($query, $pamongId) => $query->where('pamong_siswa.pamong_id', $pamongId))
                ->when(Auth::user()->isTeacher(), fn ($query) => $query->where('pamong_siswa.pamong_id', Auth::id()))
                ->selectRaw('pamong_siswa.pamong_id, COUNT(DISTINCT siswa.id) as total_siswa')
                ->groupBy('pamong_siswa.pamong_id')->pluck('total_siswa', 'pamong_id');

            $pamongAttendance = DB::table('pamong_siswa')
                ->join('siswa', 'siswa.id', '=', 'pamong_siswa.siswa_id')
                ->leftJoin('presensi', function ($join) use ($filters) {
                    $join->on('presensi.siswa_id', '=', 'siswa.id')
                        ->whereBetween('presensi.tanggal', [$filters['tanggal_mulai'], $filters['tanggal_selesai']]);
                })
                ->whereNull('pamong_siswa.ended_at')
                ->where('siswa.status', 'active')->where('siswa.is_active', true)
                ->when($filters['school_grade'], fn ($query, $grade) => $query->where('siswa.school_grade', $grade))
                ->when($filters['pamong_id'], fn ($query, $pamongId) => $query->where('pamong_siswa.pamong_id', $pamongId))
                ->when(Auth::user()->isTeacher(), fn ($query) => $query->where('pamong_siswa.pamong_id', Auth::id()))
                ->selectRaw("pamong_siswa.pamong_id,
                    SUM(CASE WHEN presensi.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN presensi.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN presensi.status = 'alpha' THEN 1 ELSE 0 END) as tidak_hadir")
                ->groupBy('pamong_siswa.pamong_id')->get()->keyBy('pamong_id');

            $pamongRows = User::query()->whereIn('id', $pamongCounts->keys())
                ->orderBy('name')->get(['id', 'name', 'username'])
                ->map(function (User $pamong) use ($pamongCounts, $pamongAttendance, $totalHari) {
                    $attendance = $pamongAttendance->get($pamong->id);
                    $totalSiswa = (int) ($pamongCounts[$pamong->id] ?? 0);
                    $hadir = (int) ($attendance->hadir ?? 0);
                    $terlambat = (int) ($attendance->terlambat ?? 0);

                    return [
                        'id' => $pamong->id,
                        'nama' => $pamong->name ?: $pamong->username,
                        'total_siswa' => $totalSiswa,
                        'hadir' => $hadir,
                        'terlambat' => $terlambat,
                        'tidak_hadir' => (int) ($attendance->tidak_hadir ?? 0),
                        'persentase_kehadiran' => round((($hadir + $terlambat) / max(1, $totalSiswa * $totalHari)) * 100, 1),
                    ];
                })->values()->all();

            return ['classes' => $classRows, 'pamongs' => $pamongRows];
        });

        return response()->json(['data' => $data['classes'], 'pamong_data' => $data['pamongs']]);
    }

    public function topStudents(Request $request): JsonResponse
    {
        [$filters, $cacheKey] = $this->resolveFilters($request, 'top-students');

        $data = Cache::remember($cacheKey, 120, function () use ($filters) {
            $totalHari = $this->totalDaysInRange($filters);

            $students = $this->activeSiswaQuery($filters)
                ->select('siswa.id', 'siswa.nama', 'siswa.nis', 'siswa.foto_path', 'siswa.school_grade')
                ->withCount([
                    'presensi as total_hadir' => fn ($query) => $query
                        ->whereBetween('tanggal', [$filters['tanggal_mulai'], $filters['tanggal_selesai']])
                        ->whereIn('status', ['hadir', 'terlambat']),
                ])
                ->orderByDesc('total_hadir')
                ->orderBy('nama')
                ->limit(10)
                ->get();

            return $students->map(function ($siswa) use ($totalHari) {
                $attendanceOpportunities = max(1, $totalHari);

                return [
                    'id' => $siswa->id,
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis,
                    'foto_path' => $siswa->foto_path,
                    'school_grade' => $siswa->school_grade,
                    'school_grade_label' => $siswa->school_grade_label,
                    'total_hadir' => (int) $siswa->total_hadir,
                    'persentase_kehadiran' => round(((int) $siswa->total_hadir / $attendanceOpportunities) * 100, 1),
                ];
            })->all();
        });

        return response()->json(['data' => $data]);
    }

    public function export(Request $request)
    {
        [$filters] = $this->resolveFilters($request, 'export');

        $format = $request->string('format')->lower()->value();
        if (! in_array($format, ['excel', 'pdf'], true)) {
            $format = 'excel';
        }

        $dataset = $this->buildExportDataset($filters);

        return $format === 'pdf'
            ? $this->exportPrintableHtml($dataset, $filters)
            : $this->exportExcel($dataset, $filters);
    }

    protected function resolveFilters(Request $request, string $suffix): array
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'school_grade' => ['nullable', 'string', 'in:'.implode(',', array_keys(TargetGrade::schoolClassOptions()))],
            'pamong_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $start = Carbon::parse($validated['tanggal_mulai'] ?? now()->toDateString())->startOfDay();
        $end = Carbon::parse($validated['tanggal_selesai'] ?? $start->toDateString())->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $filters = [
            'tanggal_mulai' => $start->toDateString(),
            'tanggal_selesai' => $end->toDateString(),
            'school_grade' => $validated['school_grade'] ?? null,
            'pamong_id' => $validated['pamong_id'] ?? null,
        ];

        $cacheKey = 'reports:'.Auth::id().':'.$suffix.':'.sha1(json_encode($filters));

        return [$filters, $cacheKey];
    }

    protected function activeSiswaQuery(array $filters)
    {
        return Siswa::query()
            ->active()
            ->when($filters['school_grade'], fn ($query, $grade) => $query->where('school_grade', $grade))
            ->when($filters['pamong_id'], fn ($query, $pamongId) => $query->byPamong($pamongId))
            ->when(Auth::user()->isTeacher(), fn ($query) => $query->whereIn('id', Auth::user()->getAssignedSiswaIds()));
    }

    protected function presensiQuery(array $filters)
    {
        return Presensi::query()
            ->join('siswa', 'siswa.id', '=', 'presensi.siswa_id')
            ->where('siswa.status', 'active')
            ->where('siswa.is_active', true)
            ->when($filters['school_grade'], fn ($query, $grade) => $query->where('siswa.school_grade', $grade))
            ->when($filters['pamong_id'], fn ($query, $pamongId) => $query->whereExists(function ($inner) use ($pamongId) {
                $inner->selectRaw('1')->from('pamong_siswa')->whereColumn('pamong_siswa.siswa_id', 'siswa.id')
                    ->where('pamong_siswa.pamong_id', $pamongId)->whereNull('pamong_siswa.ended_at');
            }))
            ->when(Auth::user()->isTeacher(), fn ($query) => $query->whereIn('siswa.id', Auth::user()->getAssignedSiswaIds()))
            ->whereBetween('presensi.tanggal', [$filters['tanggal_mulai'], $filters['tanggal_selesai']]);
    }

    protected function totalDaysInRange(array $filters): int
    {
        return max(
            1,
            Carbon::parse($filters['tanggal_mulai'])->diffInDays(Carbon::parse($filters['tanggal_selesai'])) + 1
        );
    }

    protected function buildExportDataset(array $filters): array
    {
        $summary = $this->summary(new Request($filters))->getData(true)['data'];
        $statusChart = $this->statusChart(new Request($filters))->getData(true)['data'];
        $classPerformance = $this->classPerformance(new Request($filters))->getData(true)['data'];
        $topStudents = $this->topStudents(new Request($filters))->getData(true)['data'];

        $records = Presensi::query()
            ->with('siswa:id,nis,nama,school_grade')
            ->whereHas('siswa', function ($query) use ($filters) {
                $query->active()
                    ->when($filters['school_grade'], fn ($inner, $grade) => $inner->where('school_grade', $grade))
                    ->when($filters['pamong_id'], fn ($inner, $pamongId) => $inner->byPamong($pamongId))
                    ->when(Auth::user()->isTeacher(), fn ($inner) => $inner->whereIn('id', Auth::user()->getAssignedSiswaIds()));
            })
            ->whereBetween('tanggal', [$filters['tanggal_mulai'], $filters['tanggal_selesai']])
            ->orderBy('tanggal')
            ->orderBy('siswa_id')
            ->get();

        return compact('summary', 'statusChart', 'classPerformance', 'topStudents', 'records');
    }

    protected function exportExcel(array $dataset, array $filters): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Ringkasan');
        $summarySheet->fromArray([
            ['Laporan Presensi'],
            ['Periode', $filters['tanggal_mulai'].' s/d '.$filters['tanggal_selesai']],
            ['Kelas Sekolah', $filters['school_grade'] ? TargetGrade::schoolClassLabel($filters['school_grade']) : 'Semua Kelas Sekolah'],
            [],
            ['Metrik', 'Nilai'],
            ['Total Siswa', $dataset['summary']['total_siswa']],
            ['Total Presensi', $dataset['summary']['total_presensi']],
            ['% Kehadiran', $dataset['summary']['persentase_kehadiran']],
            ['Rata-rata Harian', $dataset['summary']['rata_rata_harian']],
        ]);

        $recordsSheet = $spreadsheet->createSheet();
        $recordsSheet->setTitle('Data Presensi');
        $recordsSheet->fromArray([
            ['Tanggal', 'NIS', 'Nama', 'Kelas Sekolah', 'Status', 'Jam Masuk', 'Jam Keluar', 'Keterangan'],
        ]);

        $row = 2;
        foreach ($dataset['records'] as $record) {
            $recordsSheet->fromArray([
                [
                    optional($record->tanggal)->format('Y-m-d'),
                    $record->siswa?->nis,
                    $record->siswa?->nama,
                    $record->siswa?->school_grade_label,
                    ucfirst((string) $record->status),
                    optional($record->jam_masuk)->format('H:i:s'),
                    optional($record->jam_keluar)->format('H:i:s'),
                    $record->keterangan,
                ],
            ], null, "A{$row}");
            $row++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'laporan-presensi-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function exportPrintableHtml(array $dataset, array $filters)
    {
        $className = $filters['school_grade']
            ? TargetGrade::schoolClassLabel($filters['school_grade'])
            : 'Semua Kelas Sekolah';

        return response()
            ->view('reports.export-pdf', [
                'filters' => $filters,
                'dataset' => $dataset,
                'className' => $className,
            ])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
