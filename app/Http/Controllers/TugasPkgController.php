<?php

namespace App\Http\Controllers;

use App\Models\Karakter;
use Illuminate\Http\Request;

class TugasPkgController extends Controller
{
    public function index(Request $request)
    {
        Karakter::deactivateExpiredTasks();
        $today = now()->toDateString();
        $search = $request->input('search');

        $query = Karakter::query()
            ->active()
            ->where(function ($builder) use ($today) {
                $builder->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $today);
            })
            ->where(function ($builder) use ($today) {
                $builder->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $today);
            })
            ->withCount([
                'checklists as total_dikerjakan_count' => function ($builder) {
                    $builder->whereNull('deleted_at');
                },
                'checklists as pending_verification_count' => function ($builder) {
                    $builder->whereNull('deleted_at')->whereNull('verified_at');
                },
                'checklists as verified_count' => function ($builder) {
                    $builder->whereNull('deleted_at')->whereNotNull('verified_at');
                },
            ]);

        if (!empty($search)) {
            $query->where(function ($builder) use ($search) {
                $builder->where('nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('karakter_id')) {
            $query->where('id', $request->karakter_id);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('jenis_penyelesaian')) {
            $query->where('jenis_penyelesaian', $request->jenis_penyelesaian);
        }

        $taskList = $query->orderBy('kategori')->orderBy('nama')->paginate(12);
        $karakterOptions = Karakter::active()->get();

        $summaryQuery = Karakter::query()
            ->active()
            ->where(function ($builder) use ($today) {
                $builder->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', $today);
            })
            ->where(function ($builder) use ($today) {
                $builder->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $today);
            });

        $activeSummary = [
            'total' => (clone $summaryQuery)->count(),
            'with_proof' => (clone $summaryQuery)
                ->where(function ($builder) {
                    $builder->where('allows_photo_proof', true)
                        ->orWhere('allows_voice_note_proof', true);
                })
                ->count(),
            'teks' => (clone $summaryQuery)->where('jenis_penyelesaian', 'teks')->count(),
            'klik' => (clone $summaryQuery)->where('jenis_penyelesaian', 'klik')->count(),
        ];

        return view('tugas-pkg.index', compact('taskList', 'karakterOptions', 'activeSummary'));
    }
}
