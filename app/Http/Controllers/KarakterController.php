<?php

namespace App\Http\Controllers;

use App\Models\Karakter;
use App\Models\Badge;
use App\Support\InvalidKarakterChecklistCleaner;
use Illuminate\Http\Request;

class KarakterController extends Controller
{
    /**
     * Display a listing of karakter.
     */
    public function index(Request $request)
    {
        $expiredDeactivatedCount = Karakter::deactivateExpiredTasks();
        $query = Karakter::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($query->orderBy('nama')->get());
        }

        $karakter = $query->withCount('tracerKarakter as usage_count')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        $today = now()->toDateString();
        $taskSummary = [
            'total' => Karakter::query()->count(),
            'active' => Karakter::query()->where('is_active', true)->count(),
            'inactive' => Karakter::query()->where('is_active', false)->count(),
            'expired' => Karakter::query()
                ->whereNotNull('tanggal_selesai')
                ->whereDate('tanggal_selesai', '<', $today)
                ->count(),
            'with_proof' => Karakter::query()
                ->where(function ($builder) {
                    $builder->where('allows_photo_proof', true)
                        ->orWhere('allows_voice_note_proof', true);
                })
                ->count(),
        ];

        // Get character-category pins for the form info box
        $characterPins = Badge::active()->where('kategori', 'character')->get();

        return view('tugas-pkg.master.index', compact(
            'karakter',
            'characterPins',
            'expiredDeactivatedCount',
            'taskSummary'
        ));
    }

    /**
     * Store a newly created karakter.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:karakter,nama',
            'deskripsi' => 'nullable|string|max:1000',
            'kategori' => 'required|in:harian,mingguan,bulanan',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'poin' => 'required|integer|min:1|max:1000',
            'jenis_penyelesaian' => 'required|in:checklist,teks,klik',
            'target_teks' => 'nullable|string|max:1000',
            'target_klik' => 'required_if:jenis_penyelesaian,klik|nullable|integer|min:1|max:10000',
            'allows_photo_proof' => 'nullable|boolean',
            'photo_proof_bonus_points' => 'nullable|integer|min:0|max:1000',
            'photo_proof_instruction' => 'nullable|string|max:1000',
            'allows_voice_note_proof' => 'nullable|boolean',
            'voice_note_bonus_points' => 'nullable|integer|min:0|max:1000',
            'voice_note_instruction' => 'nullable|string|max:1000',
            'proof_requirement' => 'nullable|in:optional,required_any',
            'voice_note_max_seconds' => 'nullable|integer|min:1|max:1800',
        ]);

        $validated['is_active'] = true;
        $validated['allows_photo_proof'] = $request->boolean('allows_photo_proof');
        $validated['allows_voice_note_proof'] = $request->boolean('allows_voice_note_proof');
        $validated['proof_requirement'] = $request->input('proof_requirement', 'optional');

        // Clean up target fields if not applicable
        if ($validated['jenis_penyelesaian'] !== 'teks') {
            $validated['target_teks'] = null;
        }
        if ($validated['jenis_penyelesaian'] !== 'klik') {
            $validated['target_klik'] = null;
        }
        if (! $validated['allows_photo_proof']) {
            $validated['photo_proof_bonus_points'] = 0;
            $validated['photo_proof_instruction'] = null;
        }
        if (! $validated['allows_voice_note_proof']) {
            $validated['voice_note_bonus_points'] = 0;
            $validated['voice_note_instruction'] = null;
            $validated['voice_note_max_seconds'] = null;
        }
        if (! $validated['allows_photo_proof'] && ! $validated['allows_voice_note_proof']) {
            $validated['proof_requirement'] = 'optional';
        }

        $karakter = Karakter::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => $karakter]);
        }

        return redirect()->route('tugas-pkg.master')
            ->with('success', 'Karakter berhasil ditambahkan!');
    }

    /**
     * Update the specified karakter.
     */
    public function update(Request $request, Karakter $karakter)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:karakter,nama,' . $karakter->id,
            'deskripsi' => 'nullable|string|max:1000',
            'kategori' => 'required|in:harian,mingguan,bulanan',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'poin' => 'required|integer|min:1|max:1000',
            'is_active' => 'boolean',
            'jenis_penyelesaian' => 'required|in:checklist,teks,klik',
            'target_teks' => 'nullable|string|max:1000',
            'target_klik' => 'required_if:jenis_penyelesaian,klik|nullable|integer|min:1|max:10000',
            'allows_photo_proof' => 'nullable|boolean',
            'photo_proof_bonus_points' => 'nullable|integer|min:0|max:1000',
            'photo_proof_instruction' => 'nullable|string|max:1000',
            'allows_voice_note_proof' => 'nullable|boolean',
            'voice_note_bonus_points' => 'nullable|integer|min:0|max:1000',
            'voice_note_instruction' => 'nullable|string|max:1000',
            'proof_requirement' => 'nullable|in:optional,required_any',
            'voice_note_max_seconds' => 'nullable|integer|min:1|max:1800',
        ]);

        $validated['allows_photo_proof'] = $request->boolean('allows_photo_proof');
        $validated['allows_voice_note_proof'] = $request->boolean('allows_voice_note_proof');
        $validated['proof_requirement'] = $request->input('proof_requirement', 'optional');

        // Clean up target fields if not applicable
        if ($validated['jenis_penyelesaian'] !== 'teks') {
            $validated['target_teks'] = null;
        }
        if ($validated['jenis_penyelesaian'] !== 'klik') {
            $validated['target_klik'] = null;
        }
        if (! $validated['allows_photo_proof']) {
            $validated['photo_proof_bonus_points'] = 0;
            $validated['photo_proof_instruction'] = null;
        }
        if (! $validated['allows_voice_note_proof']) {
            $validated['voice_note_bonus_points'] = 0;
            $validated['voice_note_instruction'] = null;
            $validated['voice_note_max_seconds'] = null;
        }
        if (! $validated['allows_photo_proof'] && ! $validated['allows_voice_note_proof']) {
            $validated['proof_requirement'] = 'optional';
        }

        $karakter->update($validated);
        $removedInvalidCount = app(InvalidKarakterChecklistCleaner::class)
            ->cleanupForKarakter($karakter->id, auth()->id());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => $karakter]);
        }

        $message = 'Karakter berhasil diperbarui!';
        if ($removedInvalidCount > 0) {
            $message .= " {$removedInvalidCount} data pengerjaan di luar periode otomatis dihapus.";
        }

        return redirect()->route('tugas-pkg.master')
            ->with('success', $message);
    }

    /**
     * Soft delete (deactivate) the specified karakter.
     */
    public function destroy(Request $request, Karakter $karakter)
    {
        // Soft delete by deactivating
        $karakter->update(['is_active' => false]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('tugas-pkg.master')
            ->with('success', 'Karakter berhasil dinonaktifkan!');
    }

    /**
     * Toggle karakter active status.
     */
    public function toggleStatus(Request $request, Karakter $karakter)
    {
        $karakter->update(['is_active' => !$karakter->is_active]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => $karakter]);
        }

        $status = $karakter->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Karakter berhasil {$status}!");
    }

    /**
     * Handle bulk actions on multiple karakter items.
     */
    public function bulkAction(Request $request)
    {
        if (in_array($request->input('action'), ['verify', 'unverify', 'destroy'], true)) {
            return app(TracerKarakterController::class)->bulkAction($request);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:karakter,id',
            'action' => 'required|in:aktivasi,nonaktifkan,hapus,ubah_kategori,ubah_poin',
            'kategori' => 'required_if:action,ubah_kategori|in:harian,mingguan,bulanan',
            'poin' => 'required_if:action,ubah_poin|integer|min:1|max:1000',
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];
        $count = count($ids);

        switch ($action) {
            case 'aktivasi':
                Karakter::whereIn('id', $ids)->update(['is_active' => true]);
                $message = "{$count} tugas berhasil diaktifkan!";
                break;
            case 'nonaktifkan':
                Karakter::whereIn('id', $ids)->update(['is_active' => false]);
                $message = "{$count} tugas berhasil dinonaktifkan!";
                break;
            case 'hapus':
                Karakter::whereIn('id', $ids)->delete();
                $message = "{$count} tugas berhasil dihapus!";
                break;
            case 'ubah_kategori':
                Karakter::whereIn('id', $ids)->update(['kategori' => $validated['kategori']]);
                $message = "Kategori {$count} tugas berhasil diubah!";
                break;
            case 'ubah_poin':
                Karakter::whereIn('id', $ids)->update(['poin' => $validated['poin']]);
                $message = "Poin {$count} tugas berhasil diubah!";
                break;
            default:
                $message = 'Aksi tidak dikenali.';
        }

        return back()->with('success', $message);
    }
}
