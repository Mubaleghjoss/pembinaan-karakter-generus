<?php

namespace App\Http\Controllers;

use App\DTOs\RecordAttendanceDTO;
use App\Exceptions\DuplicateAttendanceException;
use App\Models\PamongPresensi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Contracts\PamongPresensiServiceInterface;
use App\Services\Contracts\PresensiServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualAttendanceController extends Controller
{
    public function __construct(
        protected PresensiServiceInterface $presensiService,
        protected PamongPresensiServiceInterface $pamongPresensiService
    ) {
        $this->middleware('pamong.permission:manual_attendance,view')->only(['index', 'students']);
        $this->middleware('pamong.permission:manual_attendance,create')->only(['storeSiswa', 'storePamong']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        $pamongUsers = $user->isAdmin()
            ? User::query()
                ->select(['id', 'name', 'username', 'role_id'])
                ->with('role:id,name,display_name')
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
                ->orderBy('name')
                ->orderBy('username')
                ->get()
            : collect([$user->only(['id', 'name', 'username'])]);

        $latestSiswaRecords = $this->latestSiswaRecords($user);
        $latestPamongRecords = $this->latestPamongRecords($user);

        return view('manual-attendance.index', compact(
            'today',
            'pamongUsers',
            'latestSiswaRecords',
            'latestPamongRecords'
        ));
    }

    public function students(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = trim($validated['search'] ?? '');
        $user = $request->user();
        $canAccessAllStudents = $user->canAccessAllManualAttendanceStudents();
        $limit = (int) ($validated['per_page'] ?? ($canAccessAllStudents ? 50 : 20));

        $students = Siswa::query()
            ->select(['id', 'nis', 'nama', 'school_grade', 'foto_path', 'status', 'is_active'])
            ->active()
            ->forManualAttendance($user)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nama', 'like', '%' . $search . '%')
                        ->orWhere('nis', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('nama')
            ->limit($limit)
            ->get()
            ->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'kelas' => $siswa->school_grade_label,
                'foto_url' => $siswa->foto_path ? asset('storage/' . $siswa->foto_path) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $students,
            'meta' => [
                'can_access_all_students' => $canAccessAllStudents,
                'limit' => $limit,
            ],
        ]);
    }

    public function storeSiswa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'string', 'in:hadir,terlambat,izin,sakit,alpha'],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i', 'after:jam_masuk'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $siswa = Siswa::query()->findOrFail($validated['siswa_id']);
        $user = $request->user();

        if (! $user->canRecordManualAttendanceFor($siswa)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat mengisi presensi siswa binaan.',
            ], 403);
        }

        try {
            $presensi = $this->presensiService->recordAttendance(RecordAttendanceDTO::fromArray([
                'siswa_id' => $siswa->id,
                'tanggal' => $validated['tanggal'],
                'status' => $validated['status'],
                'jam_masuk' => $validated['jam_masuk'] ?? null,
                'jam_keluar' => $validated['jam_keluar'] ?? null,
                'keterangan' => trim($validated['keterangan'] ?? '') ?: null,
                'verified_by' => $user->id,
            ]))->load('siswa.kelas');

            return response()->json([
                'success' => true,
                'message' => 'Presensi siswa berhasil disimpan.',
                'data' => [
                    'id' => $presensi->id,
                    'nama' => $presensi->siswa?->nama,
                    'kelas' => $presensi->siswa?->kelas?->nama,
                    'tanggal' => $presensi->tanggal?->format('d M Y') ?? Carbon::parse($validated['tanggal'])->format('d M Y'),
                    'status' => $presensi->status,
                    'jam_masuk' => $presensi->jam_masuk?->format('H:i'),
                ],
            ]);
        } catch (DuplicateAttendanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storePamong(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'string', 'in:hadir,terlambat,izin,sakit,alpha'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $currentUser = $request->user();
        $pamong = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
            ->findOrFail($validated['user_id']);

        if (! $currentUser->isAdmin() && $pamong->id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat mengisi presensi untuk akun sendiri.',
            ], 403);
        }

        try {
            $presensi = $this->pamongPresensiService->recordManual(
                $pamong,
                $validated['tanggal'],
                $validated['status'],
                trim($validated['keterangan'] ?? '') ?: null,
                $currentUser->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Presensi pamong berhasil disimpan.',
                'data' => [
                    'id' => $presensi->id,
                    'nama' => $pamong->name ?: $pamong->username,
                    'tanggal' => $presensi->tanggal?->format('d M Y') ?? Carbon::parse($validated['tanggal'])->format('d M Y'),
                    'status' => $presensi->status,
                    'jam_masuk' => $presensi->jam_masuk?->format('H:i'),
                ],
            ]);
        } catch (DuplicateAttendanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    protected function latestSiswaRecords(User $user)
    {
        return \App\Models\Presensi::query()
            ->with('siswa.kelas:id,nama')
            ->whereHas('siswa', fn ($query) => $query->forManualAttendance($user))
            ->latest('tanggal')
            ->latest('jam_masuk')
            ->limit(6)
            ->get();
    }

    protected function latestPamongRecords(User $user)
    {
        return PamongPresensi::query()
            ->with('user:id,name,username')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('user_id', $user->id))
            ->latest('tanggal')
            ->latest('jam_masuk')
            ->limit(6)
            ->get();
    }
}
