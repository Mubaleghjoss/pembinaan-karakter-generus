<?php

namespace App\Http\Controllers;

use App\Models\KarakterLuhur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Bank 29 Karakter Luhur — CRUD untuk admin.
 * Ini sumber data semua mode game (rangkai kata, tebak karakter).
 */
class KarakterLuhurController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $items = KarakterLuhur::query()
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('nama', 'like', "%{$search}%")
                    ->orWhere('nama_arab', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%");
            }))
            ->orderBy('nomor')
            ->get();

        return view('admin.karakter-luhur.index', [
            'items' => $items,
            'search' => $search,
            'totalActive' => KarakterLuhur::where('is_active', true)->count(),
            'total' => KarakterLuhur::count(),
        ]);
    }

    public function create()
    {
        return view('admin.karakter-luhur.form', [
            'item' => new KarakterLuhur([
                'nomor' => (int) (KarakterLuhur::max('nomor') + 1),
                'is_active' => true,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['nama']);

        KarakterLuhur::create($data);

        return redirect()->route('admin.karakter-luhur.index')
            ->with('success', 'Karakter "'.$data['nama'].'" berhasil ditambahkan.');
    }

    public function edit(KarakterLuhur $karakterLuhur)
    {
        return view('admin.karakter-luhur.form', [
            'item' => $karakterLuhur,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, KarakterLuhur $karakterLuhur): RedirectResponse
    {
        $data = $this->validated($request, $karakterLuhur->id);
        $data['slug'] = $data['slug'] ?: Str::slug($data['nama']);

        $karakterLuhur->update($data);

        return redirect()->route('admin.karakter-luhur.index')
            ->with('success', 'Karakter "'.$karakterLuhur->nama.'" berhasil diperbarui.');
    }

    public function destroy(KarakterLuhur $karakterLuhur): RedirectResponse
    {
        $nama = $karakterLuhur->nama;
        $karakterLuhur->delete();

        return redirect()->route('admin.karakter-luhur.index')
            ->with('success', 'Karakter "'.$nama.'" dihapus.');
    }

    public function toggle(KarakterLuhur $karakterLuhur): RedirectResponse
    {
        $karakterLuhur->update(['is_active' => ! $karakterLuhur->is_active]);

        return redirect()->route('admin.karakter-luhur.index')
            ->with('success', 'Status "'.$karakterLuhur->nama.'" diubah menjadi '.($karakterLuhur->is_active ? 'aktif' : 'nonaktif').'.');
    }

    /**
     * Validasi + normalisasi field JSON (dalil, studi kasus, dll).
     * Field list (hikmah/studi kasus/tips) dikirim sebagai textarea 1 baris = 1 item.
     * Dalil dikirim sebagai array baris (arab|terjemahan|sumber) via input berulang.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'nomor' => ['required', 'integer', 'min:1', 'max:999'],
            'slug' => ['nullable', 'string', 'max:120'],
            'nama' => ['required', 'string', 'max:150'],
            'nama_arab' => ['nullable', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:120'],
            'ringkas' => ['nullable', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'definisi' => ['nullable', 'string', 'max:5000'],
            'hikmah_text' => ['nullable', 'string', 'max:5000'],
            'studi_kasus_text' => ['nullable', 'string', 'max:8000'],
            'tips_amal_text' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            // dalil dikirim sebagai array paralel
            'dalil_quran_arab' => ['nullable', 'array'],
            'dalil_quran_terjemahan' => ['nullable', 'array'],
            'dalil_quran_sumber' => ['nullable', 'array'],
            'dalil_hadits_arab' => ['nullable', 'array'],
            'dalil_hadits_terjemahan' => ['nullable', 'array'],
            'dalil_hadits_sumber' => ['nullable', 'array'],
        ]);

        return [
            'nomor' => (int) $validated['nomor'],
            'slug' => $validated['slug'] ?? null,
            'nama' => $validated['nama'],
            'nama_arab' => $validated['nama_arab'] ?? null,
            'kategori' => $validated['kategori'] ?? null,
            'ringkas' => $validated['ringkas'] ?? null,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'definisi' => $validated['definisi'] ?? null,
            'hikmah' => $this->linesToArray($validated['hikmah_text'] ?? ''),
            'studi_kasus' => $this->linesToArray($validated['studi_kasus_text'] ?? ''),
            'tips_amal' => $this->linesToArray($validated['tips_amal_text'] ?? ''),
            'dalil_quran' => $this->buildDalil(
                $request->input('dalil_quran_arab', []),
                $request->input('dalil_quran_terjemahan', []),
                $request->input('dalil_quran_sumber', [])
            ),
            'dalil_hadits' => $this->buildDalil(
                $request->input('dalil_hadits_arab', []),
                $request->input('dalil_hadits_terjemahan', []),
                $request->input('dalil_hadits_sumber', [])
            ),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function linesToArray(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $text)),
            fn ($line) => $line !== ''
        ));
    }

    private function buildDalil(array $arab, array $terjemahan, array $sumber): array
    {
        $result = [];
        $count = max(count($arab), count($terjemahan), count($sumber));
        for ($i = 0; $i < $count; $i++) {
            $a = trim((string) ($arab[$i] ?? ''));
            $t = trim((string) ($terjemahan[$i] ?? ''));
            $s = trim((string) ($sumber[$i] ?? ''));
            if ($a === '' && $t === '' && $s === '') {
                continue;
            }
            $result[] = ['arab' => $a, 'terjemahan' => $t, 'sumber' => $s];
        }
        return $result;
    }
}
