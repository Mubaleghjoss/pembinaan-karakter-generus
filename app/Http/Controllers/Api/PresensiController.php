<?php

namespace App\Http\Controllers\Api;

use App\DTOs\RecordAttendanceDTO;
use App\DTOs\ScanQrDTO;
use App\DTOs\StatisticsFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presensi\ScanQrRequest;
use App\Http\Requests\Presensi\StorePresensiRequest;
use App\Http\Requests\Presensi\UpdatePresensiRequest;
use App\Http\Resources\PresensiResource;
use App\Models\Presensi;
use App\Services\Contracts\PresensiServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * API Controller untuk Presensi
 *
 * Controller ini menangani API request terkait presensi.
 * Business logic didelegasikan ke PresensiService.
 */
class PresensiController extends Controller
{
    public function __construct(
        protected PresensiServiceInterface $presensiService
    ) {}

    /**
     * Display attendance records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Presensi::query()
            ->select([
                'id',
                'siswa_id',
                'tanggal',
                'jam_masuk',
                'jam_keluar',
                'status',
                'is_verified',
                'verified_by',
                'verified_at',
                'keterangan',
            ])
            ->with([
                'siswa:id,nis,nama,school_grade,foto_path',
                'verifier:id,username',
            ]);

        if ($request->filled('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

        if ($request->filled('siswa_id')) {
            $query->where('siswa_id', $request->siswa_id);
        }

        if ($request->filled('school_grade')) $query->whereRelation('siswa', 'school_grade', $request->school_grade);
        if ($request->filled('pamong_id')) {
            $query->whereHas('siswa.pamongAssignments', fn ($assignment) => $assignment->where('pamong_id', $request->integer('pamong_id')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('verified')) {
            $query->where('is_verified', $request->verified);
        }

        $perPage = $request->get('per_page', 15);
        $presensi = $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PresensiResource::collection($presensi),
            'meta' => [
                'current_page' => $presensi->currentPage(),
                'last_page' => $presensi->lastPage(),
                'per_page' => $presensi->perPage(),
                'total' => $presensi->total(),
            ],
        ]);
    }

    /**
     * Scan QR code for attendance.
     */
    public function scanQr(ScanQrRequest $request): JsonResponse
    {
        $key = 'qr-scan.'.$request->ip();
        $maxAttempts = config('qrcode.rate_limit.scan_per_minute', 30);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'error' => 'Too many scan attempts',
                'message' => "Please try again in {$seconds} seconds.",
                'code' => 'RATE_LIMITED',
            ], 429);
        }

        try {
            $qrData = $request->validated('qr_data');

            $dto = new ScanQrDTO(
                studentId: $qrData['student_id'],
                token: $qrData['token'],
                location: $request->location,
                deviceInfo: json_encode($request->device_info),
                ipAddress: $request->ip()
            );

            $result = $this->presensiService->scanQrCode($dto);

            RateLimiter::clear($key);

            $presensi = $result['presensi'];
            $presensi->load('siswa');

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new PresensiResource($presensi),
                'student' => [
                    'nama' => $presensi->siswa->nama,
                    'nis' => $presensi->siswa->nis,
                    'school_grade' => $presensi->siswa->school_grade,
                    'school_grade_label' => $presensi->siswa->school_grade_label,
                ],
            ]);
        } catch (\App\Exceptions\QrTokenExpiredException $e) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'error' => 'QR code has expired',
                'code' => 'QR_EXPIRED',
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'error' => 'Student not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }
    }

    /**
     * Manual attendance entry.
     */
    public function store(StorePresensiRequest $request): JsonResponse
    {
        $dto = RecordAttendanceDTO::fromRequest($request);
        $presensi = $this->presensiService->recordAttendance($dto);
        $presensi->load(['siswa', 'verifier:id,username']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => new PresensiResource($presensi),
        ], 201);
    }

    /**
     * Update attendance record.
     */
    public function update(UpdatePresensiRequest $request, Presensi $presensi): JsonResponse
    {
        $data = $request->validated();

        if ($request->filled('jam_masuk')) {
            $data['jam_masuk'] = $presensi->tanggal->copy()->setTimeFromTimeString($request->jam_masuk);
        }

        if ($request->filled('jam_keluar')) {
            $data['jam_keluar'] = $presensi->tanggal->copy()->setTimeFromTimeString($request->jam_keluar);
        }

        $presensi->update($data);
        $presensi->load(['siswa', 'verifier:id,username']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => new PresensiResource($presensi),
        ]);
    }

    /**
     * Verify attendance record.
     */
    public function verify(Request $request, Presensi $presensi): JsonResponse
    {
        if ($presensi->is_verified) {
            return response()->json([
                'success' => false,
                'error' => 'Attendance is already verified',
                'code' => 'ALREADY_VERIFIED',
            ], 400);
        }

        $this->presensiService->verifyAttendance($presensi->id, $request->user()->id);
        $presensi->refresh()->load(['siswa', 'verifier:id,username']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance verified successfully',
            'data' => new PresensiResource($presensi),
        ]);
    }

    /**
     * Get attendance statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        $dto = new StatisticsFilterDTO(
            startDate: $request->start_date,
            endDate: $request->end_date,
            kelasId: $request->kelas_id
        );

        $stats = Cache::remember(
            sprintf('api:presensi:stats:%s:%s:%s', $request->start_date, $request->end_date, $request->kelas_id ?: 'all'),
            now()->addSeconds(60),
            fn () => $this->presensiService->getStatistics($dto)
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get today's attendance.
     */
    public function today(Request $request): JsonResponse
    {
        $presensi = $this->presensiService->getToday($request->kelas_id);

        return response()->json([
            'success' => true,
            'data' => PresensiResource::collection($presensi),
        ]);
    }

    /**
     * Get unverified attendance.
     */
    public function unverified(): JsonResponse
    {
        $presensi = $this->presensiService->getUnverified();

        return response()->json([
            'success' => true,
            'data' => PresensiResource::collection($presensi),
        ]);
    }
}
