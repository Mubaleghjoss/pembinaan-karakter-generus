<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

            $classes = Kelas::query()
                ->select('id', 'nama')
                ->active()
                ->when($filters['kelas_id'], fn ($query, $kelasId) => $query->where('id', $kelasId))
                ->withCount([
                    'siswa as total_siswa' => fn ($query) => $query->active(),
                ])
                ->orderBy('nama')
                ->get()
                ->keyBy('id');

            $attendanceRows = $this->presensiQuery($filters)
                ->selectRaw("
                    siswa.kelas_id,
                    SUM(CASE WHEN presensi.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN presensi.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN presensi.status = 'alpha' THEN 1 ELSE 0 END) as tidak_hadir
                ")
                ->groupBy('siswa.kelas_id')
                ->get()
                ->keyBy('kelas_id');

            return $classes->map(function ($kelas) use ($attendanceRows, $totalHari) {
                $attendance = $attendanceRows->get($kelas->id);
                $hadir = (int) ($attendance->hadir ?? 0);
                $terlambat = (int) ($attendance->terlambat ?? 0);
                $tidakHadir = (int) ($attendance->tidak_hadir ?? 0);
                $attendanceOpportunities = max(1, ((int) $kelas->total_siswa) * $totalHari);

                return [
                    'id' => $kelas->id,
                    'nama' => $kelas->nama,
                    'total_siswa' => (int) $kelas->total_siswa,
                    'hadir' => $hadir,
                    'terlambat' => $terlambat,
                    'tidak_hadir' => $tidakHadir,
                    'persentase_kehadiran' => round((($hadir + $terlambat) / $attendanceOpportunities) * 100, 1),
                ];
            })->values()->all();
        });

        return response()->json(['data' => $data]);
    }

    public function topStudents(Request $request): JsonResponse
    {
        [$filters, $cacheKey] = $this->resolveFilters($request, 'top-students');

        $data = Cache::remember($cacheKey, 120, function () use ($filters) {
            $totalHari = $this->totalDaysInRange($filters);

            $students = $this->activeSiswaQuery($filters)
                ->select('siswa.id', 'siswa.nama', 'siswa.nis', 'siswa.foto_path', 'siswa.kelas_id')
                ->with(['kelas:id,nama'])
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
                    'kelas' => $siswa->kelas ? [
                        'id' => $siswa->kelas->id,
                        'nama' => $siswa->kelas->nama,
                    ] : null,
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
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);

        $start = Carbon::parse($validated['tanggal_mulai'] ?? now()->toDateString())->startOfDay();
        $end = Carbon::parse($validated['tanggal_selesai'] ?? $start->toDateString())->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $filters = [
            'tanggal_mulai' => $start->toDateString(),
            'tanggal_selesai' => $end->toDateString(),
            'kelas_id' => $validated['kelas_id'] ?? null,
        ];

        $cacheKey = 'reports:'.Auth::id().':'.$suffix.':'.sha1(json_encode($filters));

        return [$filters, $cacheKey];
    }

    protected function activeSiswaQuery(array $filters)
    {
        return Siswa::query()
            ->active()
            ->when($filters['kelas_id'], fn ($query, $kelasId) => $query->where('kelas_id', $kelasId));
    }

    protected function presensiQuery(array $filters)
    {
        return Presensi::query()
            ->join('siswa', 'siswa.id', '=', 'presensi.siswa_id')
            ->where('siswa.status', 'active')
            ->where('siswa.is_active', true)
            ->when($filters['kelas_id'], fn ($query, $kelasId) => $query->where('siswa.kelas_id', $kelasId))
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
            ->with(['siswa.kelas:id,nama'])
            ->whereHas('siswa', function ($query) use ($filters) {
                $query->active()
                    ->when($filters['kelas_id'], fn ($inner, $kelasId) => $inner->where('kelas_id', $kelasId));
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
            ['Kelas', $filters['kelas_id'] ? optional(Kelas::find($filters['kelas_id']))->nama : 'Semua Kelas'],
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
            ['Tanggal', 'NIS', 'Nama', 'Kelas', 'Status', 'Jam Masuk', 'Jam Keluar', 'Keterangan'],
        ]);

        $row = 2;
        foreach ($dataset['records'] as $record) {
            $recordsSheet->fromArray([
                [
                    optional($record->tanggal)->format('Y-m-d'),
                    $record->siswa?->nis,
                    $record->siswa?->nama,
                    $record->siswa?->kelas?->nama,
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
        $className = $filters['kelas_id']
            ? optional(Kelas::find($filters['kelas_id']))->nama
            : 'Semua Kelas';

        return response()
            ->view('reports.export-pdf', [
                'filters' => $filters,
                'dataset' => $dataset,
                'className' => $className,
            ])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
