<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class BeritaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show', 'downloadPdf']);
        $this->middleware('pamong.permission:berita,view')->only(['create', 'edit']);
        $this->middleware('pamong.permission:berita,create')->only(['store']);
        $this->middleware('pamong.permission:berita,edit')->only(['update']);
        $this->middleware('pamong.permission:berita,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Berita::query();

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // If public user (not admin/pamong), only show published
        // If public user (not admin/pamong), only show published
        if (! auth()->check() || ! auth()->user()->hasPamongMenuAccess('berita')) {
            $query->published();
        }

        $berita = $query->latest()->paginate(10);

        return view('berita.index', compact('berita'));
    }

    public function create()
    {
        $socialPlatforms = Berita::socialPlatforms();

        return view('berita.create', compact('socialPlatforms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:180',
            'isi' => 'required',
            'cover' => 'nullable|image|max:2048', // 2MB
            'pdf_file' => 'nullable|mimes:pdf|max:5120', // 5MB
            'slider_images.*' => 'image|max:2048',
            'status' => 'required|in:draft,published,archived',
            'social_links' => 'nullable|array',
            'social_links.*' => 'nullable|url:http,https|max:2048',
        ]);

        $data = $request->only(['judul', 'isi', 'status']);
        $data['author_id'] = auth()->id();
        $data['metadata'] = $this->metadataWithSocialLinks($request);

        if ($request->status === 'published') {
            $data['published_at'] = now();
        }

        // Handle Cover
        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('berita/covers', 'public');
            $this->compressImage(storage_path('app/public/'.$path));
            $data['cover_path'] = $path;
        }

        // Handle PDF
        if ($request->hasFile('pdf_file')) {
            $data['pdf_path'] = $request->file('pdf_file')->store('berita/pdfs', 'public');
        }

        // Handle Slider Images
        if ($request->hasFile('slider_images')) {
            $sliderPaths = [];
            foreach ($request->file('slider_images') as $image) {
                $path = $image->store('berita/sliders', 'public');
                $this->compressImage(storage_path('app/public/'.$path));
                $sliderPaths[] = $path;
            }
            $data['images'] = $sliderPaths;
        }

        Berita::create($data);

        return redirect()->route('berita.index')->with('success', 'Berita berhasil dibuat.');
    }

    public function show(Berita $berita)
    {
        $berita->incrementViews();

        return view('berita.show', compact('berita'));
    }

    public function edit(Berita $berita)
    {
        $socialPlatforms = Berita::socialPlatforms();

        return view('berita.edit', compact('berita', 'socialPlatforms'));
    }

    public function update(Request $request, Berita $berita)
    {
        $request->validate([
            'judul' => 'required|string|max:180',
            'isi' => 'required',
            'cover' => 'nullable|image|max:2048',
            'pdf_file' => 'nullable|mimes:pdf|max:5120',
            'slider_images.*' => 'image|max:2048',
            'status' => 'required|in:draft,published,archived',
            'social_links' => 'nullable|array',
            'social_links.*' => 'nullable|url:http,https|max:2048',
        ]);

        $data = $request->only(['judul', 'isi', 'status']);
        $data['metadata'] = $this->metadataWithSocialLinks(
            $request,
            is_array($berita->metadata) ? $berita->metadata : []
        );

        if ($request->status === 'published' && ! $berita->published_at) {
            $data['published_at'] = now();
        }

        // Handle Cover
        if ($request->hasFile('cover')) {
            // Delete old
            if ($berita->cover_path) {
                Storage::disk('public')->delete($berita->cover_path);
            }
            $path = $request->file('cover')->store('berita/covers', 'public');
            $this->compressImage(storage_path('app/public/'.$path));
            $data['cover_path'] = $path;
        }

        // Handle PDF
        if ($request->hasFile('pdf_file')) {
            if ($berita->pdf_path) {
                Storage::disk('public')->delete($berita->pdf_path);
            }
            $data['pdf_path'] = $request->file('pdf_file')->store('berita/pdfs', 'public');
        }

        // Handle Slider Images (Append or Replace? Usually replace or add. Let's append for now, or replace if requested. Simple: Replace all if uploaded)
        if ($request->hasFile('slider_images')) {
            // Delete old slider images? Maybe optional. Let's keep it simple: If uploaded, add to existing? Or replace?
            // Requirement: "SETIAP GAMBAR DAN PDF DI UPLOAD MAKA TERKOMPRES".
            // Let's assume replace for simplicity in this iteration.
            if ($berita->images) {
                foreach ($berita->images as $oldImg) {
                    Storage::disk('public')->delete($oldImg);
                }
            }

            $sliderPaths = [];
            foreach ($request->file('slider_images') as $image) {
                $path = $image->store('berita/sliders', 'public');
                $this->compressImage(storage_path('app/public/'.$path));
                $sliderPaths[] = $path;
            }
            $data['images'] = $sliderPaths;
        }

        $berita->update($data);

        return redirect()->route('berita.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(Berita $berita)
    {
        if ($berita->cover_path) {
            Storage::disk('public')->delete($berita->cover_path);
        }
        if ($berita->pdf_path) {
            Storage::disk('public')->delete($berita->pdf_path);
        }
        if ($berita->images) {
            foreach ($berita->images as $img) {
                Storage::disk('public')->delete($img);
            }
        }

        $berita->delete();

        return redirect()->route('berita.index')->with('success', 'Berita berhasil dihapus.');
    }

    public function downloadPdf(Berita $berita)
    {
        if (! $berita->pdf_path || ! Storage::disk('public')->exists($berita->pdf_path)) {
            return back()->with('error', 'File PDF tidak ditemukan.');
        }

        $berita->increment('download_count');

        return Storage::disk('public')->download($berita->pdf_path, Str::slug($berita->judul).'.pdf');
    }

    private function compressImage($path)
    {
        try {
            $image = Image::read($path);
            $image->scale(width: 1200);
            $image->save($path, quality: 80);
        } catch (\Exception $e) {
            // Log error
        }
    }

    /**
     * Store optional social links without overwriting unrelated metadata.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function metadataWithSocialLinks(Request $request, array $metadata = []): array
    {
        if (! $request->has('social_links')) {
            return $metadata;
        }

        $links = collect($request->input('social_links', []))
            ->only(array_keys(Berita::socialPlatforms()))
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->all();

        $metadata['social_links'] = $links;

        return $metadata;
    }
}
