<?php

namespace App\Services;

use App\Exceptions\DuplicateAttendanceException;
use App\Models\AttendanceSchedule;
use App\Models\PamongPresensi;
use App\Models\User;
use App\Services\Contracts\PamongPresensiServiceInterface;
use App\Services\Contracts\PamongQrServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Service untuk mengelola Presensi Pamong
 *
 * Service ini menangani semua operasi bisnis terkait presensi pamong
 * termasuk pencatatan kehadiran, scan QR, dan statistik.
 */
class PamongPresensiService implements PamongPresensiServiceInterface
{
    public function __construct(
        protected PamongQrServiceInterface $pamongQrService
    ) {}

    /**
     * Mencatat kehadiran pamong via QR scan
     *
     * @param User $pamong User pamong yang akan dicatat kehadirannya
     * @param string $token Token QR yang digunakan
     * @param array $metadata Data tambahan (location, device_info, ip_address)
     * @return array Hasil scan dengan status dan data presensi
     *
     * @throws DuplicateAttendanceException Jika sudah ada presensi untuk pamong di tanggal yang sama
     */
    public function recordAttendance(User $pamong, string $token, array $metadata = []): array
    {
        // Verifikasi token
        if (!$this->pamongQrService->verifyToken($pamong, $token)) {
            throw new \InvalidArgumentException('QR token tidak valid');
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Cek apakah sudah ada presensi hari ini
        $existing = PamongPresensi::where('user_id', $pamong->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing) {
            // Update jam keluar jika belum ada
            if (!$existing->jam_keluar && in_array($existing->status, ['hadir', 'terlambat'])) {
                $existing->update([
                    'jam_keluar' => $now,
                ]);

                return [
                    'status' => 'checkout',
                    'message' => 'Berhasil mencatat jam keluar',
                    'presensi' => $existing->fresh(),
                ];
            }

            return [
                'status' => 'already_present',
                'message' => 'Presensi hari ini sudah tercatat',
                'presensi' => $existing,
            ];
        }

        // Tentukan status berdasarkan jam masuk
        $status = $this->determineAttendanceStatus($now->format('H:i'));

        // Buat presensi baru
        $presensi = PamongPresensi::create([
            'user_id' => $pamong->id,
            'tanggal' => $today,
            'jam_masuk' => $now,
            'status' => $status,
            'qr_code_used' => $token,
            'is_verified' => false,
        ]);

        return [
            'status' => 'checkin',
            'message' => $status === 'terlambat'
                ? 'Berhasil mencatat kehadiran (Terlambat)'
                : 'Berhasil mencatat kehadiran',
            'presensi' => $presensi,
        ];
    }

    /**
     * Mendapatkan statistik kehadiran pamong
     *
     * @param string $startDate Tanggal mulai (Y-m-d)
     * @param string $endDate Tanggal akhir (Y-m-d)
     * @param int|null $userId Filter berdasarkan user ID (opsional)
     * @return array Statistik kehadiran
     */
    public function getStatistics(string $startDate, string $endDate, ?int $userId = null): array
    {
        $query = PamongPresensi::whereBetween('tanggal', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $summary = $query
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
            ")
            ->first();

        return [
            'total' => (int) ($summary->total ?? 0),
            'hadir' => (int) ($summary->hadir ?? 0),
            'terlambat' => (int) ($summary->terlambat ?? 0),
            'izin' => (int) ($summary->izin ?? 0),
            'sakit' => (int) ($summary->sakit ?? 0),
            'alpha' => (int) ($summary->alpha ?? 0),
        ];
    }

    /**
     * Membuat alpha otomatis setelah jadwal presensi pamong ditutup.
     */
    public function backfillClosedAlpha(string $startDate, string $endDate, ?int $userId = null): int
    {
        $now = Carbon::now();
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->gt($now)) {
            return 0;
        }

        if ($end->gt($now)) {
            $end = $now->copy()->startOfDay();
        }

        $schedules = AttendanceSchedule::query()
            ->where('is_active', true)
            ->overlappingDateRange($start, $end)
            ->where(function ($query) {
                $query->whereNull('target_audience')
                    ->orWhereIn('target_audience', [AttendanceSchedule::TARGET_ALL, AttendanceSchedule::TARGET_PAMONG]);
            })
            ->orderBy('close_time')
            ->orderBy('id')
            ->get();

        if ($schedules->isEmpty()) {
            return 0;
        }

        $userIds = User::query()
            ->select('id')
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
            ->when($userId, fn ($query, $id) => $query->whereKey($id))
            ->orderBy('id')
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        $created = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $schedule = $this->closedPamongScheduleForDate($schedules, $cursor, $now);

            if (! $schedule) {
                $cursor->addDay();
                continue;
            }

            $dateString = $cursor->toDateString();
            $existingUserIds = PamongPresensi::query()
                ->whereDate('tanggal', $dateString)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id');

            $missingUserIds = $userIds->diff($existingUserIds)->values();

            if ($missingUserIds->isEmpty()) {
                $cursor->addDay();
                continue;
            }

            $timestamp = $now->copy();
            $rows = $missingUserIds
                ->map(fn (int $missingUserId) => [
                    'user_id' => $missingUserId,
                    'tanggal' => $dateString,
                    'jam_masuk' => null,
                    'jam_keluar' => null,
                    'status' => 'alpha',
                    'keterangan' => 'Alpha otomatis karena presensi pamong tidak diisi sampai jadwal ditutup.',
                    'qr_code_used' => null,
                    'is_verified' => false,
                    'verified_by' => null,
                    'verified_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            foreach (array_chunk($rows, 100) as $chunk) {
                $created += PamongPresensi::query()->insertOrIgnore($chunk);
            }

            $cursor->addDay();
        }

        return $created;
    }

    /**
     * Mendapatkan presensi pamong hari ini
     *
     * @return Collection Koleksi presensi pamong hari ini
     */
    public function getToday(): Collection
    {
        return PamongPresensi::with('user')
            ->today()
            ->orderBy('jam_masuk', 'desc')
            ->get();
    }

    /**
     * Mendapatkan data presensi pamong dengan filter
     *
     * @param array $filters Filter (start_date, end_date, user_id, status)
     * @return Collection Koleksi presensi pamong
     */
    public function getData(array $filters = []): Collection
    {
        $query = PamongPresensi::query()
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
            ])
            ->with([
                'user:id,name,username,avatar_path',
                'verifier:id,name',
            ]);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $filters['status'] === 'izin_sakit'
                ? $query->whereIn('status', ['izin', 'sakit'])
                : $query->byStatus($filters['status']);
        }

        return $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->get();
    }

    /**
     * Verifikasi record presensi pamong
     *
     * @param int $presensiId ID presensi yang akan diverifikasi
     * @param int $verifierId ID user yang memverifikasi
     * @return PamongPresensi Record presensi yang sudah diverifikasi
     *
     * @throws ModelNotFoundException Jika presensi tidak ditemukan
     */
    public function verifyAttendance(int $presensiId, int $verifierId): PamongPresensi
    {
        $presensi = PamongPresensi::findOrFail($presensiId);

        $presensi->update([
            'is_verified' => true,
            'verified_by' => $verifierId,
            'verified_at' => Carbon::now(),
        ]);

        return $presensi->fresh();
    }

    /**
     * Menentukan status kehadiran berdasarkan jam masuk
     *
     * @param string $jamMasuk Jam masuk dalam format H:i atau H:i:s
     * @return string Status kehadiran ('hadir' atau 'terlambat')
     */
    protected function determineAttendanceStatus(string $jamMasuk): string
    {
        // Get active schedule
        $schedule = AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_PAMONG);

        if ($schedule && $schedule->late_threshold) {
            $batasTerlambat = Carbon::parse($schedule->late_threshold)->format('H:i');
        } else {
            $batasTerlambat = config('presensi.batas_terlambat', '07:30');
        }

        // Parse jam masuk
        $jamMasukCarbon = Carbon::createFromFormat(
            strlen($jamMasuk) === 5 ? 'H:i' : 'H:i:s',
            $jamMasuk
        );

        $batasCarbon = Carbon::createFromFormat('H:i', $batasTerlambat);

        return $jamMasukCarbon->gt($batasCarbon) ? 'terlambat' : 'hadir';
    }

    protected function closedPamongScheduleForDate(Collection $schedules, Carbon $date, Carbon $now): ?AttendanceSchedule
    {
        $latestSchedule = $schedules
            ->filter(fn (AttendanceSchedule $schedule) => $this->scheduleAppliesToPamongDate($schedule, $date))
            ->sortByDesc(fn (AttendanceSchedule $schedule) => Carbon::parse($schedule->close_time)->format('H:i:s'))
            ->first();

        if (! $latestSchedule) {
            return null;
        }

        $closeAt = Carbon::parse($latestSchedule->close_time)
            ->setDate($date->year, $date->month, $date->day);

        return $now->gt($closeAt) ? $latestSchedule : null;
    }

    protected function scheduleAppliesToPamongDate(AttendanceSchedule $schedule, Carbon $date): bool
    {
        if (! $schedule->targetsPamong() || ! $schedule->isDateActive($date)) {
            return false;
        }

        $days = $schedule->days ?? [];
        $dayName = strtolower($date->format('l'));

        return empty($days) || in_array($dayName, $days, true);
    }

    /**
     * Mencatat presensi manual (tanpa QR)
     *
     * @param User $pamong User pamong
     * @param string $tanggal Tanggal presensi (Y-m-d)
     * @param string $status Status presensi
     * @param string|null $keterangan Keterangan tambahan
     * @param int|null $verifierId ID user yang memverifikasi
     * @return PamongPresensi Record presensi yang dibuat
     */
    public function recordManual(
        User $pamong,
        string $tanggal,
        string $status,
        ?string $keterangan = null,
        ?int $verifierId = null
    ): PamongPresensi {
        // Cek duplikat
        $existing = PamongPresensi::where('user_id', $pamong->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($existing) {
            throw new DuplicateAttendanceException(
                "Presensi untuk pamong {$pamong->name} pada tanggal {$tanggal} sudah ada"
            );
        }

        return PamongPresensi::create([
            'user_id' => $pamong->id,
            'tanggal' => $tanggal,
            'jam_masuk' => in_array($status, ['hadir', 'terlambat']) ? Carbon::now() : null,
            'status' => $status,
            'keterangan' => $keterangan,
            'is_verified' => $verifierId !== null,
            'verified_by' => $verifierId,
            'verified_at' => $verifierId ? Carbon::now() : null,
        ]);
    }
}
