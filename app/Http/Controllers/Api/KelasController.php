<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KelasPamong;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    /**
     * Display a listing of classes with multi-pamong support
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kelas::with('pamong')
            ->withCount([
                'siswa as siswa_count' => fn ($query) => $query->where('is_active', true),
            ]);

        // Search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_kelas', 'like', "%{$search}%")
                    ->orWhere('tingkat', 'like', "%{$search}%")
                    ->orWhereHas('pamong', function($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                           ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by tingkat
        if ($request->has('tingkat') && ! empty($request->tingkat)) {
            $query->where('tingkat', $request->tingkat);
        }

        // Filter by status
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Filter by pamong (multi-pamong support)
        if ($request->has('pamong_id') && ! empty($request->pamong_id)) {
            $query->whereHas('pamong', fn($q) => $q->where('users.id', $request->pamong_id));
        }

        // Order by name
        $query->orderBy('tingkat')->orderBy('nama');

        // Pagination
        $perPage = $request->get('per_page', 100);
        $classes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data kelas arsip. Gunakan Binaan Pamong dan Kelas Sekolah untuk data aktif.',
            'deprecated' => true,
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'from' => $classes->firstItem(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'to' => $classes->lastItem(),
                'total' => $classes->total(),
            ],
        ])->header('Deprecation', 'true');
    }
    
    /**
     * Get class statistics
     */
    public function stats(): JsonResponse
    {
        $totalKelas = Kelas::count();
        $kelasAktif = Kelas::where('is_active', true)->count();
        $totalPamong = User::whereHas('role', fn($q) => $q->where('name', 'teacher'))->count();
        $totalSiswa = Siswa::active()->count();
        
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

    /**
     * Store a newly created class with multi-pamong support
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Pembuatan kelas lama telah dinonaktifkan. Gunakan Kelas Sekolah dan Binaan Pamong.',
        ], 410);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'kode_kelas' => 'nullable|string|max:20|unique:kelas,kode_kelas',
            'pamong_ids' => 'nullable|array|max:5',
            'pamong_ids.*' => 'exists:users,id',
            'kapasitas' => 'nullable|integer|min:1|max:100',
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Set default values
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['kapasitas'] = $validated['kapasitas'] ?? 30;

        // Generate kode_kelas if not provided
        if (empty($validated['kode_kelas'])) {
            $validated['kode_kelas'] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $validated['nama']));
            // Ensure unique
            $baseKode = $validated['kode_kelas'];
            $counter = 1;
            while (Kelas::where('kode_kelas', $validated['kode_kelas'])->exists()) {
                $validated['kode_kelas'] = $baseKode . $counter;
                $counter++;
            }
        }

        // Remove pamong_ids from validated data before creating kelas
        $pamongIds = $validated['pamong_ids'] ?? [];
        unset($validated['pamong_ids']);

        $kelas = Kelas::create($validated);
        
        // Attach pamong (multi-pamong support)
        if (!empty($pamongIds)) {
            foreach ($pamongIds as $pamongId) {
                KelasPamong::create([
                    'kelas_id' => $kelas->id,
                    'pamong_id' => $pamongId,
                    'role' => 'pengajar',
                ]);
            }
        }
        
        $kelas->load('pamong');

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dibuat',
            'data' => $kelas,
        ], 201);
    }

    /**
     * Display the specified class with pamong list
     */
    public function show(Kelas $kelas): JsonResponse
    {
        $kelas->load(['pamong', 'siswa' => function ($query) {
            $query->where('is_active', true)->orderBy('nama');
        }])->loadCount([
            'siswa as siswa_count' => fn ($query) => $query->where('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data kelas arsip.',
            'deprecated' => true,
            'data' => $kelas,
        ])->header('Deprecation', 'true');
    }

    /**
     * Update the specified class with multi-pamong support
     */
    public function update(Request $request, Kelas $kelas): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Perubahan kelas lama telah dinonaktifkan. Gunakan Kelas Sekolah dan Binaan Pamong.',
        ], 410);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'kode_kelas' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('kelas', 'kode_kelas')->ignore($kelas->id),
            ],
            'pamong_ids' => 'nullable|array|max:5',
            'pamong_ids.*' => 'exists:users,id',
            'kapasitas' => 'nullable|integer|min:1|max:100',
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Check if reducing capacity would exceed current student count
        if (isset($validated['kapasitas'])) {
            $currentStudentCount = $kelas->siswa()->where('is_active', true)->count();
            if ($validated['kapasitas'] < $currentStudentCount) {
                return response()->json([
                    'success' => false,
                    'message' => "Kapasitas tidak boleh kurang dari jumlah siswa aktif ({$currentStudentCount})",
                    'errors' => [
                        'kapasitas' => ["Kapasitas tidak boleh kurang dari siswa aktif ({$currentStudentCount})"],
                    ],
                ], 422);
            }
        }

        // Remove pamong_ids from validated data before updating kelas
        $pamongIds = $validated['pamong_ids'] ?? [];
        unset($validated['pamong_ids']);

        $kelas->update($validated);
        
        // Sync pamong (multi-pamong support)
        // Delete existing and re-create
        KelasPamong::where('kelas_id', $kelas->id)->delete();
        if (!empty($pamongIds)) {
            foreach ($pamongIds as $pamongId) {
                KelasPamong::create([
                    'kelas_id' => $kelas->id,
                    'pamong_id' => $pamongId,
                    'role' => 'pengajar',
                ]);
            }
        }
        
        $kelas->load('pamong');

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil diupdate',
            'data' => $kelas,
        ]);
    }

    /**
     * Remove the specified class
     */
    public function destroy(Kelas $kelas): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan kelas lama telah dinonaktifkan karena data dipertahankan sebagai arsip.',
        ], 410);

        // Check if class has students
        $studentCount = $kelas->siswa()->count();
        if ($studentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat menghapus kelas dengan {$studentCount} siswa. Pindahkan siswa terlebih dahulu.",
                'errors' => [
                    'students' => ['Kelas masih memiliki siswa'],
                ],
            ], 422);
        }

        // Delete pamong assignments first
        KelasPamong::where('kelas_id', $kelas->id)->delete();
        
        $kelas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dihapus',
        ]);
    }

    /**
     * Get class statistics
     */
    public function statistics(): JsonResponse
    {
        $classSummary = Kelas::query()
            ->selectRaw('COUNT(*) as total_classes')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_classes')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_classes')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 THEN kapasitas ELSE 0 END), 0) as total_capacity')
            ->first();

        $stats = [
            'total_classes' => (int) ($classSummary->total_classes ?? 0),
            'active_classes' => (int) ($classSummary->active_classes ?? 0),
            'inactive_classes' => (int) ($classSummary->inactive_classes ?? 0),
            'classes_by_tingkat' => Kelas::selectRaw('tingkat, COUNT(*) as count')
                ->groupBy('tingkat')
                ->orderBy('tingkat')
                ->get(),
            'total_capacity' => (int) ($classSummary->total_capacity ?? 0),
            'total_students' => Siswa::active()->count(),
            'classes_with_students' => Kelas::whereHas('siswa', function ($query) {
                $query->where('is_active', true);
            })->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Class statistics retrieved successfully',
            'data' => $stats,
        ]);
    }

    /**
     * Get available tingkat options
     */
    public function tingkatOptions(): JsonResponse
    {
        $tingkatOptions = [
            'X' => 'Kelas X',
            'XI' => 'Kelas XI',
            'XII' => 'Kelas XII',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Tingkat options retrieved successfully',
            'data' => $tingkatOptions,
        ]);
    }
}
