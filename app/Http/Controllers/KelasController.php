<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\User;
use App\Models\Siswa;
use App\Models\KelasPamong;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KelasController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of Kelas with multi-pamong support.
     */
    public function index(): View
    {
        // Get all kelas with pamong and student count
        $kelasList = Kelas::with('pamong')
            ->withCount(['siswa' => fn($q) => $q->where('is_active', true)])
            ->orderBy('nama')
            ->get();

        // Statistics
        $totalKelas = $kelasList->count();
        $kelasAktif = $kelasList->where('is_active', true)->count();
        $totalPamong = User::whereHas('role', fn($q) => $q->where('name', 'teacher'))->count();
        $totalSiswa = Siswa::where('is_active', true)->count();

        // Get all pamong for dropdown
        $pamongList = User::whereHas('role', fn($q) => $q->where('name', 'teacher'))
            ->where('status', 'active')
            ->orderBy('username')
            ->get();

        return view('kelas.index', compact(
            'kelasList',
            'totalKelas',
            'kelasAktif',
            'totalPamong',
            'totalSiswa',
            'pamongList'
        ));
    }

    /**
     * Store a newly created Kelas.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'kode_kelas' => 'nullable|string|max:20|unique:kelas,kode_kelas',
            'kapasitas' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'pamong_ids' => 'nullable|array',
            'pamong_ids.*' => 'exists:users,id',
        ], [
            'nama.required' => 'Nama kelas wajib diisi.',
            'kode_kelas.unique' => 'Kode kelas sudah digunakan.',
        ]);

        $kelas = Kelas::create([
            'nama' => $validated['nama'],
            'kode_kelas' => $validated['kode_kelas'] ?? null,
            'kapasitas' => $validated['kapasitas'] ?? 30,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Assign pamong if provided
        if (!empty($validated['pamong_ids'])) {
            foreach ($validated['pamong_ids'] as $pamongId) {
                KelasPamong::create([
                    'kelas_id' => $kelas->id,
                    'pamong_id' => $pamongId,
                    'role' => 'pengajar',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil ditambahkan.',
            'data' => $kelas->load('pamong'),
        ]);
    }

    /**
     * Update the specified Kelas.
     */
    public function update(Request $request, Kelas $kela): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'kode_kelas' => 'nullable|string|max:20|unique:kelas,kode_kelas,' . $kela->id,
            'kapasitas' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'pamong_ids' => 'nullable|array',
            'pamong_ids.*' => 'exists:users,id',
        ], [
            'nama.required' => 'Nama kelas wajib diisi.',
            'kode_kelas.unique' => 'Kode kelas sudah digunakan.',
        ]);

        $kela->update([
            'nama' => $validated['nama'],
            'kode_kelas' => $validated['kode_kelas'] ?? $kela->kode_kelas,
            'kapasitas' => $validated['kapasitas'] ?? $kela->kapasitas,
            'is_active' => $validated['is_active'] ?? $kela->is_active,
        ]);

        // Update pamong assignments if provided
        if (isset($validated['pamong_ids'])) {
            // Remove existing assignments
            KelasPamong::where('kelas_id', $kela->id)->delete();
            
            // Add new assignments
            foreach ($validated['pamong_ids'] as $pamongId) {
                KelasPamong::create([
                    'kelas_id' => $kela->id,
                    'pamong_id' => $pamongId,
                    'role' => 'pengajar',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil diperbarui.',
            'data' => $kela->fresh()->load('pamong'),
        ]);
    }

    /**
     * Remove the specified Kelas.
     */
    public function destroy(Kelas $kela): JsonResponse
    {
        // Check if kelas has students
        $studentCount = $kela->siswa()->where('is_active', true)->count();
        if ($studentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat menghapus kelas. Masih ada {$studentCount} siswa aktif di kelas ini.",
            ], 400);
        }

        // Remove pamong assignments
        KelasPamong::where('kelas_id', $kela->id)->delete();
        
        $kela->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }

    /**
     * Toggle kelas active status.
     */
    public function toggleStatus(Kelas $kela): JsonResponse
    {
        $kela->update(['is_active' => !$kela->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Status kelas berhasil diubah.',
            'data' => ['is_active' => $kela->is_active],
        ]);
    }

    /**
     * Get list of Kelas for dropdown/select.
     */
    public function getList(Request $request)
    {
        $kelasList = Kelas::with('pamong')
            ->where('is_active', true)
            ->withCount(['siswa' => fn($q) => $q->where('is_active', true)])
            ->orderBy('nama')
            ->get()
            ->map(function ($kelas) {
                return [
                    'id' => $kelas->id,
                    'nama' => $kelas->nama,
                    'kode_kelas' => $kelas->kode_kelas,
                    'kapasitas' => $kelas->kapasitas,
                    'jumlah_siswa' => $kelas->siswa_count,
                    'pamong' => $kelas->pamong->map(fn($p) => [
                        'id' => $p->id,
                        'nama' => $p->name ?? $p->username,
                        'role' => $p->pivot->role ?? 'pengajar',
                    ]),
                ];
            });

        // Include statistics if requested
        if ($request->filled('with_stats')) {
            $totalKelas = Kelas::count();
            $kelasAktif = Kelas::where('is_active', true)->count();
            $totalPamong = User::whereHas('role', fn($q) => $q->where('name', 'teacher'))->count();
            $totalSiswa = Siswa::where('is_active', true)->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_kelas' => $totalKelas,
                    'active_kelas' => $kelasAktif,
                    'total_pamong' => $totalPamong,
                    'total_siswa' => $totalSiswa,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $kelasList,
        ]);
    }
}
