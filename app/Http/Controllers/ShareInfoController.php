<?php

namespace App\Http\Controllers;

use App\Models\ShareInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareInfoController extends Controller
{
    public function index()
    {
        $shareInfos = ShareInfo::with('creator')->orderByDesc('created_at')->get();
        return view('settings.partials.share-info', compact('shareInfos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'type' => 'required|in:info,warning,success',
            'auto_dismiss_seconds' => 'required|integer|min:5|max:300',
            'target' => 'required|in:all,siswa,ortu,pamong',
        ]);

        ShareInfo::create([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'auto_dismiss_seconds' => $request->auto_dismiss_seconds,
            'target' => $request->target,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('settings.index', ['tab' => 'share_info'])
            ->with('success', 'Info berhasil dibuat.');
    }

    public function update(Request $request, ShareInfo $shareInfo)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'type' => 'required|in:info,warning,success',
            'auto_dismiss_seconds' => 'required|integer|min:5|max:300',
            'target' => 'required|in:all,siswa,ortu,pamong',
        ]);

        $shareInfo->update($request->only(['title', 'message', 'type', 'auto_dismiss_seconds', 'target']));

        return redirect()->route('settings.index', ['tab' => 'share_info'])
            ->with('success', 'Info berhasil diperbarui.');
    }

    public function toggle(ShareInfo $shareInfo)
    {
        $shareInfo->update(['is_active' => !$shareInfo->is_active]);

        return redirect()->route('settings.index', ['tab' => 'share_info'])
            ->with('success', 'Status info berhasil diubah.');
    }

    public function destroy(ShareInfo $shareInfo)
    {
        $shareInfo->delete();

        return redirect()->route('settings.index', ['tab' => 'share_info'])
            ->with('success', 'Info berhasil dihapus.');
    }
}
