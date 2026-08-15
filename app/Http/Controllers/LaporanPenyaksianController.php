<?php

namespace App\Http\Controllers;

use App\Models\LaporanPenyaksian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LaporanPenyaksianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['create', 'store', 'getSiswaList', 'getPamongList', 'getGenerusList']);
    }

    /**
     * Display list of reports for admin/pamong.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $canBulkDelete = $user->isAdmin();
        $query = LaporanPenyaksian::with(['siswa.kelas', 'pamong', 'penindak']);

        // Teacher tetap dibatasi ke siswa binaan, pengurus PKG melihat cakupan global.
        if ($user->isTeacher()) {
            $query->forPamong($user->id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelapor', 'like', "%{$search}%")
                  ->orWhere('nama_generus', 'like', "%{$search}%")
                  ->orWhereHas('siswa', function ($sq) use ($search) {
                      $sq->where('nama', 'like', "%{$search}%")
                         ->orWhere('nis', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_kejadian', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_kejadian', '<=', $request->tanggal_sampai);
        }

        $laporan = $query->latest()->paginate(15)->withQueryString();

        $stats = Cache::remember(
            'laporan-penyaksian:stats:' . ($user->isTeacher() ? 'pamong:' . $user->id : 'global'),
            now()->addSeconds(90),
            function () use ($user) {
                $statsBaseQuery = LaporanPenyaksian::query();

                if ($user->isTeacher()) {
                    $statsBaseQuery->forPamong($user->id);
                }

                $summary = (clone $statsBaseQuery)
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                return [
                    'total' => (int) $summary->sum(),
                    'pending' => (int) ($summary['pending'] ?? 0),
                    'ditindaklanjuti' => (int) ($summary['ditindaklanjuti'] ?? 0),
                    'selesai' => (int) ($summary['selesai'] ?? 0),
                ];
            }
        );

        return view('laporan-penyaksian.index', compact('laporan', 'stats', 'canBulkDelete'));
    }

    /**
     * Show public form for reporting (like Google Form).
     */
    public function create()
    {
        $theme = \App\Models\ThemeSetting::current();
        return view('laporan-penyaksian.create', compact('theme'));
    }

    /**
     * Store a new report from public form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pelapor' => 'required|string|max:255',
            'email_pelapor' => 'nullable|email|max:255',
            'phone_pelapor' => 'nullable|string|max:20',
            'siswa_id' => 'nullable|exists:siswa,id',
            'pamong_id' => 'nullable|exists:users,id',
            'nama_generus' => 'required|string|max:255',
            'karakter_belum_optimal' => 'required|string',
            'tanggal_kejadian' => 'required|date',
            'deskripsi_kejadian' => 'nullable|string',
        ], [
            'nama_pelapor.required' => 'Nama pelapor wajib diisi.',
            'nama_generus.required' => 'Nama generus/pamong wajib diisi.',
            'karakter_belum_optimal.required' => 'Karakter yang belum optimal wajib diisi.',
            'tanggal_kejadian.required' => 'Tanggal kejadian wajib diisi.',
        ]);

        $laporan = LaporanPenyaksian::create($validated);
        $this->forgetReportStatsCache($laporan);

        return redirect()->route('laporan-penyaksian.create')
            ->with('success', 'Laporan berhasil dikirim. Terima kasih atas partisipasi Anda dalam pembinaan generus.');
    }

    /**
     * Show report detail.
     */
    public function show(LaporanPenyaksian $laporanPenyaksian)
    {
        $laporanPenyaksian->load(['siswa.kelas', 'siswa.pamongAssignments.pamong', 'penindak']);
        $this->authorizeReportAccess($laporanPenyaksian);
        
        return view('laporan-penyaksian.show', compact('laporanPenyaksian'));
    }

    /**
     * Update report status and follow-up.
     */
    public function update(Request $request, LaporanPenyaksian $laporanPenyaksian)
    {
        $this->authorizeReportAccess($laporanPenyaksian);

        $validated = $request->validate([
            'status' => 'required|in:pending,ditindaklanjuti,selesai',
            'catatan_tindak_lanjut' => 'nullable|string',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'catatan_tindak_lanjut' => $validated['catatan_tindak_lanjut'],
        ];

        // Set who followed up and when
        if ($validated['status'] !== 'pending' && !$laporanPenyaksian->ditindaklanjuti_oleh) {
            $updateData['ditindaklanjuti_oleh'] = auth()->id();
            $updateData['ditindaklanjuti_at'] = now();
        }

        $laporanPenyaksian->update($updateData);
        $this->forgetReportStatsCache($laporanPenyaksian);

        return redirect()->route('laporan-penyaksian.show', $laporanPenyaksian)
            ->with('success', 'Status laporan berhasil diperbarui.');
    }

    /**
     * Delete a report.
     */
    public function destroy(LaporanPenyaksian $laporanPenyaksian)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->forgetReportStatsCache($laporanPenyaksian);
        $laporanPenyaksian->delete();

        return redirect()->route('laporan-penyaksian.index')
            ->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Delete multiple reports selected by an administrator.
     */
    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:laporan_penyaksian,id'],
        ], [
            'ids.required' => 'Pilih minimal satu laporan yang akan dihapus.',
            'ids.min' => 'Pilih minimal satu laporan yang akan dihapus.',
        ]);

        $laporan = LaporanPenyaksian::query()
            ->whereKey($validated['ids'])
            ->get();

        DB::transaction(function () use ($laporan) {
            foreach ($laporan as $item) {
                $this->forgetReportStatsCache($item);
                $item->delete();
            }
        });

        return redirect()
            ->route('laporan-penyaksian.index')
            ->with('success', $laporan->count() . ' laporan berhasil dihapus.');
    }

    /**
     * Get siswa list for autocomplete in public form.
     */
    public function getSiswaList(Request $request)
    {
        $search = $request->get('q', '');
        
        $siswa = Siswa::active()
            ->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'nis', 'nama', 'foto_path', 'school_grade']);

        return response()->json($siswa->map(function ($s) {
            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'nama' => $s->nama,
                'kelas' => $s->school_grade_label,
                'foto_url' => $s->foto_path ? asset('storage/' . $s->foto_path) : null,
                'type' => 'siswa',
            ];
        }));
    }

    /**
     * Get pamong list for autocomplete in public form.
     */
    public function getPamongList(Request $request)
    {
        $search = $request->get('q', '');
        
        $pamong = User::with('role:id,name')
            ->whereHas('role', fn($q) => $q->whereIn('name', User::operationalRoleNames()))
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'username', 'name', 'email', 'avatar_path', 'role_id']);

        return response()->json($pamong->map(function ($p) {
            return [
                'id' => $p->id,
                'nama' => $p->name ?? $p->username,
                'username' => $p->username,
                'email' => $p->email,
                'foto_url' => $p->avatar_path ? asset('storage/' . $p->avatar_path) : null,
                'type' => 'pamong',
            ];
        }));
    }

    /**
     * Get combined siswa and pamong list for autocomplete.
     */
    public function getGenerusList(Request $request)
    {
        $search = $request->get('q', '');
        $results = [];
        
        // Get siswa
        $siswa = Siswa::with('kelas')
            ->active()
            ->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get();

        foreach ($siswa as $s) {
            $results[] = [
                'id' => $s->id,
                'nama' => $s->nama,
                'kelas' => $s->alamat ?: 'Alamat tidak tersedia',
                'nis' => '',
                'foto_url' => $s->foto_path ? asset('storage/' . $s->foto_path) : null,
                'type' => 'siswa',
                'label' => 'Siswa',
            ];
        }

        // Get pamong
        $pamong = User::with('role:id,name')
            ->whereHas('role', fn($q) => $q->whereIn('name', User::operationalRoleNames()))
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        foreach ($pamong as $p) {
            $results[] = [
                'id' => $p->id,
                'nama' => $p->name ?? $p->username,
                'kelas' => ($p->role?->name === User::ROLE_PKG_MANAGER ? 'Pengurus PKG' : 'Pamong'),
                'nis' => '',
                'foto_url' => $p->avatar_path ? asset('storage/' . $p->avatar_path) : null,
                'type' => 'pamong',
                'label' => $p->role?->name === User::ROLE_PKG_MANAGER ? 'Pengurus PKG' : 'Pamong',
            ];
        }

        return response()->json($results);
    }

    protected function authorizeReportAccess(LaporanPenyaksian $laporanPenyaksian): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isTeacher()) {
            return;
        }

        $visible = LaporanPenyaksian::query()
            ->whereKey($laporanPenyaksian->getKey())
            ->forPamong($user->id)
            ->exists();

        abort_unless($visible, 403);
    }

    protected function forgetReportStatsCache(LaporanPenyaksian $laporanPenyaksian): void
    {
        Cache::forget('laporan-penyaksian:stats:global');

        $pamongIds = [];
        if ($laporanPenyaksian->pamong_id) {
            $pamongIds[] = $laporanPenyaksian->pamong_id;
        }

        if ($laporanPenyaksian->siswa_id) {
            $laporanPenyaksian->loadMissing('siswa.pamongAssignments');

            foreach ($laporanPenyaksian->siswa?->pamongAssignments ?? [] as $assignment) {
                $pamongIds[] = $assignment->pamong_id;
            }
        }

        foreach (array_unique(array_filter($pamongIds)) as $pamongId) {
            Cache::forget('laporan-penyaksian:stats:pamong:' . $pamongId);
        }
    }
}
