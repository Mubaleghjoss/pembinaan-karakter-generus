<?php

namespace App\Http\Controllers;

use App\Models\KarakterLuhur;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;

/**
 * Referensi publik 29 Karakter Luhur.
 * Data dari Bank 29 Karakter (admin CRUD di admin.karakter-luhur.*).
 * Halaman ini read-only + search untuk pengunjung.
 */
class PublicKarakterController extends Controller
{
    public function index(Request $request)
    {
        $theme = ThemeSetting::current();
        $search = trim((string) $request->input('q', ''));

        $query = KarakterLuhur::where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nama_arab', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%")
                    ->orWhere('ringkas', 'like', "%{$search}%")
                    ->orWhere('definisi', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $karakters = $query->orderBy('nomor')->orderBy('nama')->get();
        $total = KarakterLuhur::where('is_active', true)->count();

        return view('public.karakter.index', compact('theme', 'karakters', 'search', 'total'));
    }

    public function show(Request $request, string $slug)
    {
        $theme = ThemeSetting::current();
        $karakter = KarakterLuhur::where('is_active', true)->where('slug', $slug)->firstOrFail();

        $prev = KarakterLuhur::where('is_active', true)
            ->where('nomor', '<', $karakter->nomor)
            ->orderByDesc('nomor')->first();
        $next = KarakterLuhur::where('is_active', true)
            ->where('nomor', '>', $karakter->nomor)
            ->orderBy('nomor')->first();

        return view('public.karakter.show', compact('theme', 'karakter', 'prev', 'next'));
    }
}
