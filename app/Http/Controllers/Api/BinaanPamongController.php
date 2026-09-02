<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiswaResource;
use App\Models\PamongSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Binaan Pamong — sumber data AKTIF pengelompokan siswa.
 *
 * Menggantikan `/kelas` (ditandai deprecated) untuk pertanyaan "siswa ini
 * dibina siapa". Sumbernya tabel pivot `pamong_siswa` dengan `ended_at` NULL;
 * penugasan yang sudah diakhiri tidak dihitung.
 *
 * Batas akses: admin (dan pamong yang dikecualikan) melihat seluruh pamong,
 * pamong biasa hanya melihat binaannya sendiri — memakai aturan yang sama
 * dengan `Siswa::scopeForUser` supaya tidak ada jalur bocor baru.
 */
class BinaanPamongController extends Controller
{
    /**
     * GET /binaan-pamong — daftar pamong + jumlah siswa binaan aktif.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $lihatSemua = $user->isAdmin() || $user->isPamongExcluded();

        // Hitung binaan aktif per pamong lewat satu query agregat.
        $jumlahPerPamong = PamongSiswa::query()
            ->active()
            ->when(! $lihatSemua, fn ($q) => $q->where('pamong_id', $user->id))
            ->selectRaw('pamong_id, COUNT(*) as total')
            ->groupBy('pamong_id')
            ->pluck('total', 'pamong_id');

        $pamongQuery = User::query()
            ->whereIn('id', $jumlahPerPamong->keys()->all() ?: [0]);

        if ($request->filled('search')) {
            $cari = $request->string('search')->toString();
            $pamongQuery->where(function ($q) use ($cari) {
                $q->where('name', 'like', "%{$cari}%")
                    ->orWhere('username', 'like', "%{$cari}%");
            });
        }

        $pamong = $pamongQuery
            ->orderByRaw('COALESCE(NULLIF(name, ""), username)')
            ->get(['id', 'name', 'username', 'status']);

        $data = $pamong->map(fn (User $p) => [
            'pamong_id' => $p->id,
            'nama' => $p->name ?: $p->username,
            'username' => $p->username,
            'is_active' => $p->isActive(),
            'jumlah_binaan' => (int) ($jumlahPerPamong[$p->id] ?? 0),
        ])->values();

        // Total harus mengikuti hasil pencarian, bukan seluruh data. Tanpa ini
        // kartu ringkasan pernah menampilkan "Pamong 0 · Total binaan 4".
        $totalBinaan = (int) $data->sum('jumlah_binaan');

        return response()->json([
            'success' => true,
            'message' => 'Daftar binaan pamong',
            'data' => $data,
            'meta' => [
                'total_pamong' => $data->count(),
                'total_binaan' => $totalBinaan,
                'scope' => $lihatSemua ? 'semua' : 'sendiri',
            ],
        ]);
    }

    /**
     * GET /binaan-pamong/{pamong}/siswa — siswa yang dibina satu pamong.
     */
    public function siswa(Request $request, User $pamong): JsonResponse
    {
        $user = $request->user();
        $lihatSemua = $user->isAdmin() || $user->isPamongExcluded();

        // Pamong biasa tidak boleh membaca binaan pamong lain.
        if (! $lihatSemua && $pamong->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat melihat siswa binaan sendiri.',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $query = Siswa::query()
            ->assignedTo($pamong->id)
            ->with('pamongAssignments.pamong:id,name,username');

        if ($request->filled('search')) {
            $cari = $request->string('search')->toString();
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                    ->orWhere('nis', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('school_grade')) {
            $query->bySchoolGrade($request->string('school_grade')->toString());
        }

        $siswa = $query->orderBy('nama')->paginate((int) $request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'message' => 'Siswa binaan '.($pamong->name ?: $pamong->username),
            'data' => SiswaResource::collection($siswa->items()),
            'meta' => [
                'pamong_id' => $pamong->id,
                'pamong_nama' => $pamong->name ?: $pamong->username,
                'current_page' => $siswa->currentPage(),
                'last_page' => $siswa->lastPage(),
                'per_page' => $siswa->perPage(),
                'total' => $siswa->total(),
            ],
        ]);
    }
}
