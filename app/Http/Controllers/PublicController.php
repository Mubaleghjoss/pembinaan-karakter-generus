<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\AttendanceSchedule;
use App\Models\Materi;
use App\Models\MateriFolder;
use App\Models\ThemeSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        return view('public.index', compact('berita', 'theme'));
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

        $query = Materi::query()
            ->with('folder')
            ->active();

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
            $query->where('materi_folder_id', $request->integer('folder_id'));
        }

        $materi = $query
            ->leftJoin('materi_folders', 'materi.materi_folder_id', '=', 'materi_folders.id')
            ->select('materi.*')
            ->orderByRaw('COALESCE(materi_folders.sort_order, 999999)')
            ->orderBy('materi_folders.name')
            ->orderByDesc('materi.bulan')
            ->paginate(12)
            ->withQueryString();

        $materiFolders = MateriFolder::query()
            ->active()
            ->withCount(['materi as materi_count' => fn ($query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('public.materi-index', compact('theme', 'materi', 'materiFolders'));
    }

    public function materiShow(Materi $materi)
    {
        if (!$materi->is_active) {
            abort(404);
        }

        $theme = ThemeSetting::current();

        return view('public.materi-detail', compact('materi', 'theme'));
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
