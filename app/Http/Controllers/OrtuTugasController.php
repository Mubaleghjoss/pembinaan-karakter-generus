<?php

namespace App\Http\Controllers;

use App\Models\Karakter;
use App\Models\OrtuComment;
use App\Models\SiswaKarakterChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrtuTugasController extends Controller
{
    public function index()
    {
        $siswa = Auth::guard('ortu')->user();

        $checklists = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->with(['karakter', 'verifier'])
            ->orderByDesc('created_at')
            ->get();

        // Load ortu comments for these checklists
        $comments = OrtuComment::where('siswa_id', $siswa->id)
            ->whereIn('siswa_karakter_checklist_id', $checklists->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('siswa_karakter_checklist_id');

        // Get active tasks (Karakter)
        $allTasks = Karakter::active()->orderBy('nama')->get();

        // Get tasks completed today
        $todayCompletedIds = SiswaKarakterChecklist::where('siswa_id', $siswa->id)
            ->whereDate('checked_at', today())
            ->pluck('karakter_id')
            ->toArray();

        // Filter for pending tasks
        $pendingTasks = $allTasks->filter(function($task) use ($todayCompletedIds) {
            return !in_array($task->id, $todayCompletedIds);
        });

        return view('ortu.tugas.index', compact('siswa', 'checklists', 'comments', 'pendingTasks'));
    }

    public function addComment(Request $request, $checklistId)
    {
        $siswa = Auth::guard('ortu')->user();

        $request->validate([
            'comment' => 'required|string|min:3|max:1000',
        ]);

        // Verify checklist belongs to this siswa
        $checklist = SiswaKarakterChecklist::where('id', $checklistId)
            ->where('siswa_id', $siswa->id)
            ->firstOrFail();

        OrtuComment::create([
            'siswa_karakter_checklist_id' => $checklist->id,
            'siswa_id' => $siswa->id,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
