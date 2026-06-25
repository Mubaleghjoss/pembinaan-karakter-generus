<?php

namespace App\Http\Controllers;

use App\Models\PersiapanAcara;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersiapanAcaraController extends Controller
{
    public function index()
    {
        $acaras = PersiapanAcara::with(['pjAcara', 'creator'])
            ->orderByDesc('created_at')
            ->get();

        $users = User::whereIn('role_id', [1, 2])->get();
        $pjCategories = PersiapanAcara::PJ_CATEGORIES;

        return view('persiapan-acara.index', compact('acaras', 'users', 'pjCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul_acara' => 'required|string|max:255',
            'nomor_ke' => 'nullable|integer',
            'deskripsi_acara' => 'nullable|string',
            'waktu_acara' => 'nullable|date',
            'waktu_selesai' => 'nullable|string|max:20',
            'tempat' => 'nullable|string|max:255',
            'peserta' => 'nullable|string|max:255',
            'pakaian' => 'nullable|string|max:255',
            'materi_pemateri' => 'nullable|array',
            'materi_pemateri.*.materi' => 'nullable|string',
            'materi_pemateri.*.pemateri' => 'nullable|string',
            'perlengkapan' => 'nullable|array',
            'perlengkapan.*' => 'nullable|string',
            'catatan_tambahan' => 'nullable|array',
            'catatan_tambahan.*' => 'nullable|string',
            'rundown' => 'nullable|array',
            'rundown.*.waktu' => 'nullable|string',
            'rundown.*.kegiatan' => 'nullable|string',
            'rundown.*.detail' => 'nullable|string',
            'tim_dokumentasi' => 'nullable|array',
            'tim_dokumentasi.*' => 'exists:users,id',
            'panitia' => 'nullable|array',
            'panitia.*' => 'nullable|array',
            'panitia.*.*' => 'exists:users,id',
        ]);

        // Clean empty items
        $this->cleanArrayFields($validated);

        $validated['created_by'] = Auth::id();

        PersiapanAcara::create($validated);

        return redirect()->route('persiapan-acara.index')->with('success', 'Persiapan acara berhasil ditambahkan!');
    }

    public function update(Request $request, PersiapanAcara $persiapanAcara)
    {
        $validated = $request->validate([
            'judul_acara' => 'required|string|max:255',
            'nomor_ke' => 'nullable|integer',
            'deskripsi_acara' => 'nullable|string',
            'waktu_acara' => 'nullable|date',
            'waktu_selesai' => 'nullable|string|max:20',
            'tempat' => 'nullable|string|max:255',
            'peserta' => 'nullable|string|max:255',
            'pakaian' => 'nullable|string|max:255',
            'materi_pemateri' => 'nullable|array',
            'materi_pemateri.*.materi' => 'nullable|string',
            'materi_pemateri.*.pemateri' => 'nullable|string',
            'perlengkapan' => 'nullable|array',
            'perlengkapan.*' => 'nullable|string',
            'catatan_tambahan' => 'nullable|array',
            'catatan_tambahan.*' => 'nullable|string',
            'rundown' => 'nullable|array',
            'rundown.*.waktu' => 'nullable|string',
            'rundown.*.kegiatan' => 'nullable|string',
            'rundown.*.detail' => 'nullable|string',
            'tim_dokumentasi' => 'nullable|array',
            'tim_dokumentasi.*' => 'exists:users,id',
            'panitia' => 'nullable|array',
            'panitia.*' => 'nullable|array',
            'panitia.*.*' => 'exists:users,id',
        ]);

        // Clean empty items
        $this->cleanArrayFields($validated);

        // If panitia is not submitted, set to empty (unchecked all)
        if (!isset($validated['panitia'])) {
            $validated['panitia'] = [];
        }
        if (!isset($validated['tim_dokumentasi'])) {
            $validated['tim_dokumentasi'] = [];
        }

        $persiapanAcara->update($validated);

        return redirect()->route('persiapan-acara.index')->with('success', 'Persiapan acara berhasil diperbarui!');
    }

    public function destroy(PersiapanAcara $persiapanAcara)
    {
        $persiapanAcara->delete();
        return redirect()->route('persiapan-acara.index')->with('success', 'Persiapan acara berhasil dihapus!');
    }

    private function cleanArrayFields(array &$validated): void
    {
        if (isset($validated['materi_pemateri'])) {
            $validated['materi_pemateri'] = array_values(array_filter($validated['materi_pemateri'], fn($item) => !empty($item['materi']) || !empty($item['pemateri'])));
        }
        if (isset($validated['perlengkapan'])) {
            $validated['perlengkapan'] = array_values(array_filter($validated['perlengkapan'], fn($item) => !empty($item)));
        }
        if (isset($validated['catatan_tambahan'])) {
            $validated['catatan_tambahan'] = array_values(array_filter($validated['catatan_tambahan'], fn($item) => !empty($item)));
        }
        if (isset($validated['rundown'])) {
            $validated['rundown'] = array_values(array_filter($validated['rundown'], fn($item) => !empty($item['waktu']) || !empty($item['kegiatan'])));
        }
    }
}
