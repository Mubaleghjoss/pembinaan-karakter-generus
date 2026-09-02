<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KarakterLuhur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 — 29 Karakter Luhur (read-only).
 *
 * Sumber data: tabel `karakter_luhurs` (diisi KarakterLuhurSeeder dari
 * database/data/karakter29_seed.json). Endpoint ini TIDAK menulis apa pun;
 * aplikasi mobile hanya menampilkan materi bacaan.
 */
class KarakterLuhurController extends Controller
{
    /**
     * Daftar ringkas 29 karakter untuk layar indeks.
     *
     * Payload sengaja ringkas (tanpa dalil/studi kasus) supaya daftar cepat
     * dimuat; detail lengkap diambil per item lewat show().
     */
    public function index(Request $request): JsonResponse
    {
        $query = KarakterLuhur::query()->active();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->string('kategori')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('ringkas', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('nomor')->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (KarakterLuhur $k) => $this->summary($k))->values(),
            'meta' => [
                'total' => $items->count(),
                'kategori' => KarakterLuhur::query()->active()
                    ->whereNotNull('kategori')
                    ->distinct()
                    ->orderBy('kategori')
                    ->pluck('kategori')
                    ->values(),
            ],
        ]);
    }

    /**
     * Detail satu karakter — dipakai layar baca beranimasi di aplikasi.
     *
     * Diterima baik slug maupun nomor (1..29) agar navigasi "berikutnya"
     * di aplikasi bisa memakai nomor tanpa perlu tahu slug.
     */
    public function show(string $key): JsonResponse
    {
        $karakter = KarakterLuhur::query()
            ->active()
            ->when(
                ctype_digit($key),
                fn ($q) => $q->where('nomor', (int) $key),
                fn ($q) => $q->where('slug', $key)
            )
            ->first();

        if (! $karakter) {
            return response()->json([
                'success' => false,
                'error' => 'Not found',
                'message' => 'Karakter tidak ditemukan',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->detail($karakter),
        ]);
    }

    /**
     * Bentuk ringkas untuk daftar.
     */
    private function summary(KarakterLuhur $k): array
    {
        return [
            'id' => $k->id,
            'nomor' => $k->nomor,
            'slug' => $k->slug,
            'nama' => $k->nama,
            'nama_arab' => $k->nama_arab,
            'kategori' => $k->kategori,
            'ringkas' => $k->ringkas,
        ];
    }

    /**
     * Bentuk lengkap untuk layar baca.
     *
     * `penerapan` memakai penerapanList() supaya kunci selalu ada
     * (benar/salah/dampak_positif/dampak_negatif) walau datanya kosong —
     * klien tidak perlu menangani bentuk yang berubah-ubah.
     */
    private function detail(KarakterLuhur $k): array
    {
        $clean = fn ($value) => array_values(array_filter(
            array_map(
                fn ($item) => is_string($item) ? trim($item) : $item,
                (array) ($value ?? [])
            ),
            fn ($item) => filled($item)
        ));

        return [
            'id' => $k->id,
            'nomor' => $k->nomor,
            'slug' => $k->slug,
            'nama' => $k->nama,
            'nama_arab' => $k->nama_arab,
            'kategori' => $k->kategori,
            'ringkas' => $k->ringkas,
            'deskripsi' => $k->deskripsi,
            'definisi' => $k->definisi,
            'dalil_quran' => $clean($k->dalil_quran),
            'dalil_hadits' => $clean($k->dalil_hadits),
            'hikmah' => $clean($k->hikmah),
            'studi_kasus' => $k->studiKasusList(),
            'penerapan' => $k->penerapanList(),
            'tips_amal' => $clean($k->tips_amal),
            'has_penerapan' => $k->hasPenerapan(),
        ];
    }
}
