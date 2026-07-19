<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\AttendanceSchedule;
use App\Models\Materi;
use App\Models\MateriFolder;
use App\Models\ThemeSetting;
use App\Support\MateriFolderTree;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function index()
    {
        $theme = ThemeSetting::current();

        // Get published berita (check if status column exists)
        $berita = Berita::query()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate(9);

        $homeMateri = $this->applyPublicMateriOrdering($this->publicMateriBaseQuery())
            ->take(6)
            ->get();
        $homeMateriFolders = app(MateriFolderTree::class)->folderTree(
            includeInactiveFolders: false,
            includeInactiveMateri: false,
            includeEmptyRoots: true,
            includeUnfiled: false
        );
        $homeMateriCount = Materi::query()
            ->active()
            ->count();
        $homeMateriFolderCount = MateriFolder::query()
            ->active()
            ->root()
            ->count();

        return view('public.index', compact(
            'berita',
            'theme',
            'homeMateri',
            'homeMateriFolders',
            'homeMateriCount',
            'homeMateriFolderCount'
        ));
    }

    public function berita($slug)
    {
        $theme = ThemeSetting::current();

        $berita = Berita::where('slug', $slug)
            ->whereNotNull('published_at')
            ->firstOrFail();

        $berita->increment('view_count');
        $relatedNews = Berita::query()
            ->whereNotNull('published_at')
            ->where('id', '!=', $berita->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.berita-detail', compact('berita', 'theme', 'relatedNews'));
    }

    public function scanner()
    {
        $theme = ThemeSetting::current();
        $now = Carbon::now();
        $activeSchedules = AttendanceSchedule::query()
            ->where('is_active', true)
            ->overlappingDateRange($now, $now->copy()->addYear())
            ->orderBy('open_time')
            ->orderBy('id')
            ->get();
        $schedule = $activeSchedules->first();
        $dayNames = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];
        $currentDayEn = strtolower($now->format('l'));
        $currentDayId = $dayNames[$currentDayEn] ?? ucfirst($currentDayEn);
        $scheduleCards = $activeSchedules
            ->map(function (AttendanceSchedule $item) use ($dayNames, $currentDayEn, $currentDayId, $now) {
                $activeDays = collect($item->days ?? [])
                    ->map(fn (string $day) => $dayNames[$day] ?? ucfirst($day))
                    ->all();
                $isDateActive = $item->isDateActive($now);
                $isTodayActive = $isDateActive && (empty($item->days) || in_array($currentDayEn, $item->days ?? [], true));
                $isOpen = $item->isOpen();
                $openAtToday = Carbon::parse($item->open_time)
                    ->setDate($now->year, $now->month, $now->day);
                $closeAtToday = Carbon::parse($item->close_time)
                    ->setDate($now->year, $now->month, $now->day);
                $nextStart = $isOpen ? null : $this->nextScheduleStart($item, $now);
                $statusLabel = match (true) {
                    $isOpen => 'Presensi Dibuka',
                    ! $isDateActive => 'Menunggu Tanggal Aktif',
                    $isTodayActive && $now->lt($openAtToday) => 'Belum Waktunya',
                    $isTodayActive && $now->gt($closeAtToday) => 'Sudah Ditutup',
                    default => 'Menunggu Hari Aktif',
                };

                return [
                    'model' => $item,
                    'name' => $item->name,
                    'description' => $item->description,
                    'active_days' => $activeDays,
                    'date_range' => $item->dateRangeLabel(),
                    'is_today_active' => $isTodayActive,
                    'is_open' => $isOpen,
                    'status_label' => $statusLabel,
                    'target_label' => $item->targetLabel(),
                    'next_start' => $nextStart,
                    'next_start_text' => $this->formatNextScheduleStart($nextStart, $now),
                    'open_time' => Carbon::parse($item->open_time)->format('H:i'),
                    'late_threshold' => Carbon::parse($item->late_threshold)->format('H:i'),
                    'close_time' => Carbon::parse($item->close_time)->format('H:i'),
                ];
            })
            ->values();
        $isOpen = $scheduleCards->contains(fn (array $item) => $item['is_open']);
        $nextScheduleStart = $scheduleCards
            ->pluck('next_start')
            ->filter()
            ->sort()
            ->first();
        $scheduleStatusLabel = $isOpen ? 'Presensi Sedang Dibuka' : 'Belum Waktunya Presensi';
        $scheduleStatusHint = $isOpen
            ? 'Silakan scan QR Code sesuai kegiatan yang sedang dibuka.'
            : $this->formatNextScheduleStart($nextScheduleStart, $now);
        $activeDays = collect($schedule?->days ?? [])
            ->map(fn (string $day) => $dayNames[$day] ?? ucfirst($day))
            ->all();
        $isTodayActive = $schedule?->isDateActive($now) && (empty($schedule?->days) || in_array($currentDayEn, $schedule->days ?? [], true));

        return view('public.scanner', compact(
            'theme',
            'schedule',
            'scheduleCards',
            'isOpen',
            'scheduleStatusLabel',
            'scheduleStatusHint',
            'activeDays',
            'currentDayEn',
            'currentDayId',
            'isTodayActive'
        ));
    }

    public function materiIndex(Request $request)
    {
        if (auth()->guard('web')->check()) {
            $user = $request->user();

            if ($user && ! $user->isAdmin() && $user->usesPamongPermissionSystem() && ! $user->hasPamongMenuAccess('materi')) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'Anda tidak memiliki akses ke menu ini.');
            }

            return app(MateriController::class)->index($request);
        }

        $theme = ThemeSetting::current();

        $query = $this->publicMateriBaseQuery();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('bulan') && preg_match('/^\d{4}-\d{2}$/', (string) $request->input('bulan'))) {
            $month = Carbon::createFromFormat('Y-m', (string) $request->input('bulan'))->startOfMonth();
            $query->whereMonth('bulan', $month->month)
                ->whereYear('bulan', $month->year);
        }

        if ($request->filled('folder_id')) {
            $query->whereIn(
                'materi.materi_folder_id',
                app(MateriFolderTree::class)->folderAndDescendantIds($request->integer('folder_id'))
            );
        }

        $materi = $this->applyPublicMateriOrdering($query)
            ->paginate(12)
            ->withQueryString();

        $materiFolders = $this->publicMateriFolders();
        $materiFolderTree = app(MateriFolderTree::class)->folderTree(
            includeInactiveFolders: false,
            includeInactiveMateri: false,
            includeEmptyRoots: true,
            includeUnfiled: true
        );

        return view('public.materi-index', compact('theme', 'materi', 'materiFolders', 'materiFolderTree'));
    }

    public function materiShow(Request $request, Materi $materi)
    {
        if (!$materi->is_active) {
            abort(404);
        }

        $materi->loadMissing('folder.parent');
        $theme = ThemeSetting::current();
        $canAccessContent = $this->canAccessMateriContent();

        if (! $canAccessContent) {
            $request->session()->put('url.intended', route('public.materi.show', $materi));
        }

        return view('public.materi-detail', compact('materi', 'theme', 'canAccessContent'));
    }

    public function materiPdfDownload(Request $request, Materi $materi, int $index)
    {
        return $this->materiPdfResponse($request, $materi, $index, true);
    }

    public function materiPdfView(Request $request, Materi $materi, int $index)
    {
        return $this->materiPdfResponse($request, $materi, $index, false);
    }

    private function materiPdfResponse(Request $request, Materi $materi, int $index, bool $download)
    {
        if (! $materi->is_active && ! auth()->guard('web')->check()) {
            abort(404);
        }

        if (! $this->canAccessMateriContent()) {
            $detailUrl = route('public.materi.show', $materi);
            $request->session()->put('url.intended', $detailUrl);

            return redirect($detailUrl)
                ->with('warning', 'Silakan login terlebih dahulu untuk membuka atau mengunduh materi.');
        }

        $pdf = $materi->pdf_files[$index] ?? null;
        $path = is_array($pdf) ? ($pdf['path'] ?? null) : null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $name = $materi->pdfFileName($index);

        if ($download) {
            return Storage::disk('public')->download($path, $name, ['Content-Type' => 'application/pdf']);
        }

        return Storage::disk('public')->response($path, $name, ['Content-Type' => 'application/pdf'], 'inline');
    }

    private function canAccessMateriContent(): bool
    {
        return auth()->guard('web')->check()
            || auth()->guard('siswa')->check()
            || auth()->guard('ortu')->check();
    }

    private function publicMateriBaseQuery()
    {
        return Materi::query()
            ->with('folder.parent')
            ->active();
    }

    private function applyPublicMateriOrdering($query)
    {
        return $query
            ->leftJoin('materi_folders as folders', 'materi.materi_folder_id', '=', 'folders.id')
            ->leftJoin('materi_folders as parent_folders', 'folders.parent_id', '=', 'parent_folders.id')
            ->select('materi.*')
            ->orderByRaw('COALESCE(parent_folders.sort_order, folders.sort_order, 999999)')
            ->orderByRaw('COALESCE(parent_folders.name, folders.name)')
            ->orderByRaw('COALESCE(folders.sort_order, 999999)')
            ->orderBy('folders.name')
            ->orderByDesc('materi.bulan');
    }

    private function publicMateriFolders()
    {
        return app(MateriFolderTree::class)->folderOptions();
    }

    private function nextScheduleStart(AttendanceSchedule $schedule, Carbon $from): ?Carbon
    {
        $cursor = $from->copy()->startOfDay();
        $days = $schedule->days ?? [];
        $limit = $schedule->end_date?->copy()->startOfDay() ?: $from->copy()->addYear()->startOfDay();

        if ($schedule->start_date && $cursor->lt($schedule->start_date->copy()->startOfDay())) {
            $cursor = $schedule->start_date->copy()->startOfDay();
        }

        while ($cursor->lte($limit)) {
            $candidateDay = strtolower($cursor->format('l'));

            if (! $schedule->isDateActive($cursor) || (! empty($days) && ! in_array($candidateDay, $days, true))) {
                $cursor->addDay();
                continue;
            }

            $candidateStart = Carbon::parse($schedule->open_time)
                ->setDate($cursor->year, $cursor->month, $cursor->day);

            if ($candidateStart->greaterThan($from)) {
                return $candidateStart;
            }

            $cursor->addDay();
        }

        return null;
    }

    private function formatNextScheduleStart(?Carbon $nextStart, Carbon $from): string
    {
        if (! $nextStart) {
            return 'Belum ada waktu presensi berikutnya yang terjadwal.';
        }

        if ($nextStart->isSameDay($from)) {
            $minutes = max(1, (int) ceil($from->diffInSeconds($nextStart) / 60));

            if ($minutes < 60) {
                return "Presensi dimulai {$minutes} menit lagi.";
            }

            $hours = intdiv($minutes, 60);
            $remainingMinutes = $minutes % 60;

            return $remainingMinutes > 0
                ? "Presensi dimulai {$hours} jam {$remainingMinutes} menit lagi."
                : "Presensi dimulai {$hours} jam lagi.";
        }

        $days = max(1, $from->copy()->startOfDay()->diffInDays($nextStart->copy()->startOfDay()));
        $dayLabel = $days === 1 ? 'besok' : "{$days} hari lagi";

        return "Presensi berikutnya {$dayLabel}, {$nextStart->format('H:i')} WIB.";
    }
}
