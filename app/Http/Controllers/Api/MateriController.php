<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\MateriFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * API v1 — Materi (read-only untuk aplikasi mobile).
 *
 * Keputusan desain (disetujui user): aplikasi hanya MENAMPILKAN materi.
 * Pembuatan/penyuntingan materi tetap di panel web admin karena melibatkan
 * unggah PDF multi-berkas, tautan video, dan konfigurasi RPP (penjadwalan
 * guru) yang tidak praktis di layar ponsel.
 */
class MateriController extends Controller
{
    /**
     * Daftar materi aktif, terbaru dulu.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Materi::query()->with('folder')->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('folder_id')) {
            $query->where('materi_folder_id', $request->integer('folder_id'));
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 50);

        $materi = $query
            ->orderByDesc('calendar_date')
            ->orderByDesc('bulan')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($materi->items())
                ->map(fn (Materi $m) => $this->summary($m))
                ->values(),
            'meta' => [
                'current_page' => $materi->currentPage(),
                'last_page' => $materi->lastPage(),
                'per_page' => $materi->perPage(),
                'total' => $materi->total(),
            ],
        ]);
    }

    /**
     * Folder materi (untuk filter di aplikasi).
     */
    public function folders(): JsonResponse
    {
        $folders = MateriFolder::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $folders->map(fn (MateriFolder $f) => [
                'id' => $f->id,
                'nama' => $f->display_name,
                'parent_id' => $f->parent_id,
                'materi_count' => $f->materi()->where('is_active', true)->count(),
            ])->values(),
        ]);
    }

    /**
     * Detail satu materi: metadata + daftar PDF + tautan video.
     */
    public function show(Materi $materi): JsonResponse
    {
        if (! $materi->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Not found',
                'message' => 'Materi tidak tersedia',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->detail($materi),
        ]);
    }

    private function summary(Materi $m): array
    {
        return [
            'id' => $m->id,
            'judul' => $m->judul,
            'deskripsi' => $m->deskripsi,
            'bulan' => $m->bulan?->toDateString(),
            'calendar_date' => $m->calendar_date?->toDateString(),
            'folder' => $m->folder ? [
                'id' => $m->folder->id,
                'nama' => $m->folder->display_name,
            ] : null,
            'pdf_count' => $m->pdf_count,
            'video_count' => count($m->video_link_urls),
        ];
    }

    private function detail(Materi $m): array
    {
        // pdf_path adalah array (cast) berisi path relatif di disk 'public'.
        // URL dibuat absolut supaya bisa dibuka langsung oleh klien mobile.
        $pdfs = [];
        foreach ($m->pdf_files as $index => $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $pdfs[] = [
                'index' => $index,
                'nama' => $m->pdfFileName($index),
                'url' => Storage::disk('public')->url($path),
                'exists' => Storage::disk('public')->exists($path),
            ];
        }

        return array_merge($this->summary($m), [
            'pdfs' => $pdfs,
            'videos' => collect($m->video_items)->map(fn ($item) => [
                'url' => $item['url'] ?? null,
                'embed_url' => $item['embed_url'] ?? null,
                'source' => $item['source'] ?? null,
            ])->values(),
            'has_rpp' => $m->hasRpp(),
            'rpp_published' => $m->isRppPublished(),
        ]);
    }
}
