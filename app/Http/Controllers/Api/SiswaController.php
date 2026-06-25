<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateSiswaDTO;
use App\DTOs\UpdateSiswaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\StoreSiswaRequest;
use App\Http\Requests\Siswa\UpdateSiswaRequest;
use App\Http\Resources\SiswaResource;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\Contracts\SiswaServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller untuk Siswa
 *
 * Controller ini menangani API request terkait siswa.
 * Business logic didelegasikan ke SiswaService.
 */
class SiswaController extends Controller
{
    public function __construct(
        protected SiswaServiceInterface $siswaService
    ) {}

    /**
     * Display a listing of students.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        if ($request->filled('kelas_id')) {
            $filters['kelas_id'] = $request->kelas_id;
        }

        if ($request->filled('status')) {
            $filters['status'] = $request->status;
        }

        $perPage = $request->get('per_page', 15);
        $students = $this->siswaService->paginate($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => SiswaResource::collection($students),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(StoreSiswaRequest $request): JsonResponse
    {
        // Check class capacity
        $kelas = Kelas::findOrFail($request->kelas_id);
        if ($kelas->isFull()) {
            return response()->json([
                'success' => false,
                'error' => 'Class is full',
                'message' => 'Cannot add more students to this class.',
                'code' => 'CLASS_FULL',
            ], 400);
        }

        $dto = CreateSiswaDTO::fromRequest($request);
        $siswa = $this->siswaService->create($dto);
        $siswa->load('kelas');

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => new SiswaResource($siswa),
        ], 201);
    }

    /**
     * Display the specified student.
     */
    public function show(Siswa $siswa): JsonResponse
    {
        $siswa->load(['kelas', 'presensi' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'data' => new SiswaResource($siswa),
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateSiswaRequest $request, Siswa $siswa): JsonResponse
    {
        // Check class capacity if changing class
        if ($request->kelas_id && $request->kelas_id != $siswa->kelas_id) {
            $kelas = Kelas::findOrFail($request->kelas_id);
            if ($kelas->isFull()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Target class is full',
                    'message' => 'Cannot move student to this class.',
                    'code' => 'CLASS_FULL',
                ], 400);
            }
        }

        $dto = UpdateSiswaDTO::fromRequest($request);
        $siswa = $this->siswaService->update($siswa->id, $dto);
        $siswa->load('kelas');

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => new SiswaResource($siswa),
        ]);
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Siswa $siswa): JsonResponse
    {
        // Check if student has attendance records
        if ($siswa->presensi()->count() > 0) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete student with attendance records',
                'message' => 'Consider marking as inactive instead.',
                'code' => 'HAS_ATTENDANCE_RECORDS',
            ], 400);
        }

        $this->siswaService->delete($siswa->id);

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully',
        ]);
    }

    /**
     * Generate QR code for student.
     */
    public function generateQr(Siswa $siswa): JsonResponse
    {
        if (! $siswa->isActive()) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot generate QR code for inactive student',
                'code' => 'STUDENT_INACTIVE',
            ], 400);
        }

        $qrData = $this->siswaService->generateQrCode($siswa->id);

        return response()->json([
            'success' => true,
            'message' => 'QR code generated successfully',
            'qr_data' => $qrData,
        ]);
    }

    /**
     * Get student statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->siswaService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get QR code data for student (frontend endpoint).
     */
    public function qrCode(Siswa $siswa): JsonResponse
    {
        if (! $siswa->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot generate QR code for inactive student',
                'code' => 'STUDENT_INACTIVE',
            ], 400);
        }

        $qrData = $this->siswaService->generateQrCode($siswa->id);

        return response()->json([
            'success' => true,
            'qr_data' => $qrData,
        ]);
    }

    /**
     * Get active students.
     */
    public function active(): JsonResponse
    {
        $students = $this->siswaService->getActive();

        return response()->json([
            'success' => true,
            'data' => SiswaResource::collection($students),
        ]);
    }

    /**
     * Get students by class.
     */
    public function byKelas(int $kelasId): JsonResponse
    {
        $students = $this->siswaService->getByKelas($kelasId);

        return response()->json([
            'success' => true,
            'data' => SiswaResource::collection($students),
        ]);
    }
}
