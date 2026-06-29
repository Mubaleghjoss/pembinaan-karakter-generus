<?php

namespace App\Services;

use App\DTOs\RecordAttendanceDTO;
use App\DTOs\ScanQrDTO;
use App\DTOs\StatisticsFilterDTO;
use App\Exceptions\DuplicateAttendanceException;
use App\Exceptions\QrTokenExpiredException;
use App\Models\AttendanceSchedule;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Contracts\PresensiRepositoryInterface;
use App\Repositories\Contracts\SiswaRepositoryInterface;
use App\Services\Contracts\PresensiServiceInterface;
use App\Services\Contracts\QrTokenServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Service untuk mengelola Presensi/Kehadiran
 *
 * Service ini menangani semua operasi bisnis terkait presensi siswa
 * termasuk pencatatan kehadiran, scan QR, dan statistik.
 */
class PresensiService implements PresensiServiceInterface
{
    public function __construct(
        protected PresensiRepositoryInterface $presensiRepository,
        protected SiswaRepositoryInterface $siswaRepository,
        protected QrTokenServiceInterface $qrTokenService
    ) {}

    /**
     * Mencatat kehadiran siswa
     *
     * @param  RecordAttendanceDTO  $dto  Data kehadiran yang akan dicatat
     * @return Presensi Record presensi yang berhasil dibuat
     *
     * @throws DuplicateAttendanceException Jika sudah ada presensi untuk siswa di tanggal yang sama
     */
    public function recordAttendance(RecordAttendanceDTO $dto): Presensi
    {
        // Cek apakah sudah ada presensi untuk siswa di tanggal yang sama
        $existing = $this->presensiRepository->findByStudentAndDate(
            $dto->siswaId,
            $dto->tanggal
        );

        if ($existing) {
            throw new DuplicateAttendanceException(
                "Presensi untuk siswa ID {$dto->siswaId} pada tanggal {$dto->tanggal} sudah ada"
            );
        }

        // Tentukan status berdasarkan jam masuk jika status adalah 'hadir'
        $status = $dto->status;
        if ($status === 'hadir' && $dto->jamMasuk) {
            $status = $this->determineAttendanceStatus($dto->jamMasuk);
        }

        // Buat record presensi
        return $this->presensiRepository->create([
            'siswa_id' => $dto->siswaId,
            'tanggal' => $dto->tanggal,
            'jam_masuk' => $dto->jamMasuk,
            'jam_keluar' => $dto->jamKeluar,
            'status' => $status,
            'keterangan' => $dto->keterangan,
            'is_verified' => $dto->verifiedBy !== null,
            'verified_by' => $dto->verifiedBy,
            'verified_at' => $dto->verifiedBy ? Carbon::now() : null,
        ]);
    }

    /**
     * Memproses scan QR code untuk presensi
     *
     * @param  ScanQrDTO  $dto  Data scan QR code
     * @return array Hasil scan dengan status dan data presensi
     *
     * @throws QrTokenExpiredException Jika token QR sudah expired
     */
    public function scanQrCode(ScanQrDTO $dto): array
    {
        // Cari siswa
        $siswa = $this->siswaRepository->findById($dto->studentId);

        if (! $siswa) {
            throw new ModelNotFoundException("Siswa dengan ID {$dto->studentId} tidak ditemukan");
        }

        // Verifikasi token
        if (! $this->qrTokenService->verify($siswa, $dto->token)) {
            throw new QrTokenExpiredException('QR token tidak valid atau sudah kadaluarsa');
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Cek apakah sudah ada presensi hari ini
        $existing = $this->presensiRepository->findByStudentAndDate($siswa->id, $today);

        if ($existing) {
            // Update jam keluar jika belum ada
            if (! $existing->jam_keluar && $existing->status === 'hadir') {
                $presensi = $this->presensiRepository->update($existing->id, [
                    'jam_keluar' => $now,
                    'scan_location' => $dto->location,
                    'scan_device_info' => $dto->deviceInfo,
                    'scan_ip_address' => $dto->ipAddress,
                ]);

                return [
                    'status' => 'checkout',
                    'message' => 'Berhasil mencatat jam keluar',
                    'presensi' => $presensi,
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
        $presensi = $this->presensiRepository->create([
            'siswa_id' => $siswa->id,
            'tanggal' => $today,
            'jam_masuk' => $now,
            'status' => $status,
            'qr_code_used' => $dto->token,
            'scan_location' => $dto->location,
            'scan_device_info' => $dto->deviceInfo,
            'scan_ip_address' => $dto->ipAddress,
            'is_verified' => false,
        ]);

        // Record QR scan di siswa
        $siswa->recordQrScan();

        // Award gamification points
        try {
            $gamificationService = app(GamificationService::class);
            $gamificationService->awardAttendancePoints($siswa, $status, $presensi);
        } catch (\Exception $e) {
            // Log error but don't fail the attendance
            \Log::warning('Gamification points failed: ' . $e->getMessage());
        }

        return [
            'status' => 'checkin',
            'message' => $status === 'terlambat'
                ? 'Berhasil mencatat kehadiran (Terlambat)'
                : 'Berhasil mencatat kehadiran',
            'presensi' => $presensi,
        ];
    }

    public function recordFaceAttendance(Siswa $siswa, array $metadata): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $existing = $this->presensiRepository->findByStudentAndDate($siswa->id, $today);

        if ($existing) {
            if (! $existing->jam_keluar && in_array($existing->status, ['hadir', 'terlambat'], true)) {
                $nextMetadata = array_merge($existing->metadata ?? [], [
                    'face_checkout' => $metadata['face'] ?? $metadata,
                ]);

                $presensi = $this->presensiRepository->update($existing->id, [
                    'jam_keluar' => $now,
                    'scan_location' => $metadata['scan_location'] ?? null,
                    'scan_device_info' => $metadata['scan_device_info'] ?? null,
                    'scan_ip_address' => $metadata['scan_ip_address'] ?? null,
                    'metadata' => $nextMetadata,
                ]);

                return [
                    'status' => 'checkout',
                    'message' => 'Berhasil mencatat jam keluar',
                    'presensi' => $presensi,
                ];
            }

            return [
                'status' => 'already_present',
                'message' => 'Presensi hari ini sudah tercatat',
                'presensi' => $existing,
            ];
        }

        $status = $this->determineAttendanceStatus($now->format('H:i'));

        $presensi = $this->presensiRepository->create([
            'siswa_id' => $siswa->id,
            'tanggal' => $today,
            'jam_masuk' => $now,
            'status' => $status,
            'qr_code_used' => null,
            'scan_location' => $metadata['scan_location'] ?? null,
            'scan_device_info' => $metadata['scan_device_info'] ?? null,
            'scan_ip_address' => $metadata['scan_ip_address'] ?? null,
            'is_verified' => false,
            'metadata' => array_merge(['attendance_method' => 'face'], $metadata),
        ]);

        try {
            $gamificationService = app(GamificationService::class);
            $gamificationService->awardAttendancePoints($siswa, $status, $presensi);
        } catch (\Exception $e) {
            \Log::warning('Gamification points failed: ' . $e->getMessage());
        }

        return [
            'status' => 'checkin',
            'message' => $status === 'terlambat'
                ? 'Berhasil mencatat kehadiran dengan scan wajah (Terlambat)'
                : 'Berhasil mencatat kehadiran dengan scan wajah',
            'presensi' => $presensi,
        ];
    }

    /**
     * Mendapatkan statistik kehadiran
     *
     * @param  StatisticsFilterDTO  $dto  Filter untuk statistik
     * @return array Statistik kehadiran
     */
    public function getStatistics(StatisticsFilterDTO $dto): array
    {
        return $this->presensiRepository->getStatistics(
            $dto->startDate,
            $dto->endDate,
            $dto->kelasId
        );
    }

    /**
     * Membuat alpha otomatis setelah jadwal presensi siswa ditutup.
     */
    public function backfillClosedAlpha(string $startDate, string $endDate, ?int $kelasId = null): int
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
                    ->orWhereIn('target_audience', [AttendanceSchedule::TARGET_ALL, AttendanceSchedule::TARGET_SISWA]);
            })
            ->orderBy('close_time')
            ->orderBy('id')
            ->get();

        if ($schedules->isEmpty()) {
            return 0;
        }

        $siswaIds = Siswa::query()
            ->select('id')
            ->active()
            ->when($kelasId, fn ($query, $id) => $query->where('kelas_id', $id))
            ->orderBy('id')
            ->pluck('id');

        if ($siswaIds->isEmpty()) {
            return 0;
        }

        $created = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $schedule = $this->closedSiswaScheduleForDate($schedules, $cursor, $now);

            if (! $schedule) {
                $cursor->addDay();
                continue;
            }

            $dateString = $cursor->toDateString();
            $existingSiswaIds = Presensi::query()
                ->whereDate('tanggal', $dateString)
                ->whereIn('siswa_id', $siswaIds)
                ->pluck('siswa_id');

            $missingSiswaIds = $siswaIds->diff($existingSiswaIds)->values();

            if ($missingSiswaIds->isEmpty()) {
                $cursor->addDay();
                continue;
            }

            $timestamp = $now->copy();
            $rows = $missingSiswaIds
                ->map(fn (int $missingSiswaId) => [
                    'siswa_id' => $missingSiswaId,
                    'tanggal' => $dateString,
                    'jam_masuk' => null,
                    'jam_keluar' => null,
                    'status' => 'alpha',
                    'qr_code_used' => null,
                    'scan_location' => null,
                    'scan_device_info' => null,
                    'scan_ip_address' => null,
                    'is_verified' => false,
                    'verified_by' => null,
                    'verified_at' => null,
                    'keterangan' => 'Tidak hadir otomatis karena presensi siswa tidak diisi sampai jadwal ditutup.',
                    'metadata' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            foreach (array_chunk($rows, 100) as $chunk) {
                $created += Presensi::query()->insertOrIgnore($chunk);
            }

            $cursor->addDay();
        }

        return $created;
    }

    /**
     * Memverifikasi record presensi
     *
     * @param  int  $presensiId  ID presensi yang akan diverifikasi
     * @param  int  $verifierId  ID user yang memverifikasi
     * @return Presensi Record presensi yang sudah diverifikasi
     *
     * @throws ModelNotFoundException Jika presensi tidak ditemukan
     */
    public function verifyAttendance(int $presensiId, int $verifierId): Presensi
    {
        $presensi = $this->presensiRepository->findById($presensiId);

        if (! $presensi) {
            throw new ModelNotFoundException("Presensi dengan ID {$presensiId} tidak ditemukan");
        }

        return $this->presensiRepository->update($presensiId, [
            'is_verified' => true,
            'verified_by' => $verifierId,
            'verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Mendapatkan presensi hari ini
     *
     * @param  int|null  $kelasId  Filter berdasarkan kelas (opsional)
     * @return Collection Koleksi presensi hari ini
     */
    public function getToday(?int $kelasId = null): Collection
    {
        return $this->presensiRepository->getToday($kelasId);
    }

    /**
     * Mendapatkan presensi yang belum diverifikasi
     *
     * @return Collection Koleksi presensi yang belum diverifikasi
     */
    public function getUnverified(): Collection
    {
        return $this->presensiRepository->getUnverified();
    }

    /**
     * Menentukan status kehadiran berdasarkan jam masuk
     *
     * @param  string  $jamMasuk  Jam masuk dalam format H:i atau H:i:s
     * @return string Status kehadiran ('hadir' atau 'terlambat')
     */
    protected function determineAttendanceStatus(string $jamMasuk): string
    {
        /*
         * Attendance Status Determination Logic:
         *
         * Status ditentukan berdasarkan perbandingan jam masuk dengan batas waktu:
         * - 'hadir': Jika jam masuk <= batas waktu terlambat
         * - 'terlambat': Jika jam masuk > batas waktu terlambat
         *
         * Batas waktu diambil dari AttendanceSchedule yang aktif
         * Fallback ke config jika tidak ada schedule aktif
         */
        
        // Get active schedule
        $schedule = AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_SISWA);
        
        if ($schedule && $schedule->late_threshold) {
            $batasTerlambat = Carbon::parse($schedule->late_threshold)->format('H:i');
        } else {
            $batasTerlambat = config('presensi.batas_terlambat', '07:30');
        }

        // Parse jam masuk - support format H:i (07:30) atau H:i:s (07:30:00)
        $jamMasukCarbon = Carbon::createFromFormat(
            strlen($jamMasuk) === 5 ? 'H:i' : 'H:i:s',
            $jamMasuk
        );

        $batasCarbon = Carbon::createFromFormat('H:i', $batasTerlambat);

        // Bandingkan: gt = greater than (lebih dari)
        return $jamMasukCarbon->gt($batasCarbon) ? 'terlambat' : 'hadir';
    }

    /**
     * Calculate late duration in minutes
     *
     * @param  string  $jamMasuk  Jam masuk dalam format H:i atau H:i:s
     * @return int|null Durasi keterlambatan dalam menit, null jika tidak terlambat
     */
    public function calculateLateDuration(string $jamMasuk): ?int
    {
        $schedule = AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_SISWA);
        
        if (!$schedule || !$schedule->late_threshold) {
            return null;
        }

        // Parse jam masuk - use today as base date
        $today = Carbon::today();
        $jamMasukCarbon = Carbon::createFromFormat(
            strlen($jamMasuk) === 5 ? 'H:i' : 'H:i:s',
            $jamMasuk
        )->setDate($today->year, $today->month, $today->day);

        // Parse late threshold and set same date
        $batasCarbon = Carbon::parse($schedule->late_threshold)
            ->setDate($today->year, $today->month, $today->day);

        if ($jamMasukCarbon->gt($batasCarbon)) {
            // Use absolute difference
            return (int) abs($jamMasukCarbon->diffInMinutes($batasCarbon, false));
        }

        return null;
    }

    protected function closedSiswaScheduleForDate(Collection $schedules, Carbon $date, Carbon $now): ?AttendanceSchedule
    {
        $latestSchedule = $schedules
            ->filter(fn (AttendanceSchedule $schedule) => $this->scheduleAppliesToSiswaDate($schedule, $date))
            ->sortByDesc(fn (AttendanceSchedule $schedule) => Carbon::parse($schedule->close_time)->format('H:i:s'))
            ->first();

        if (! $latestSchedule) {
            return null;
        }

        $closeAt = Carbon::parse($latestSchedule->close_time)
            ->setDate($date->year, $date->month, $date->day);

        return $now->gt($closeAt) ? $latestSchedule : null;
    }

    protected function scheduleAppliesToSiswaDate(AttendanceSchedule $schedule, Carbon $date): bool
    {
        if (! $schedule->targetsSiswa() || ! $schedule->isDateActive($date)) {
            return false;
        }

        $days = $schedule->days ?? [];
        $dayName = strtolower($date->format('l'));

        return empty($days) || in_array($dayName, $days, true);
    }
}
