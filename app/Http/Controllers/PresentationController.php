<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\Presentation;
use App\Models\PresentationAsset;
use App\Models\ThemeSetting;
use App\Services\PresentationExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PresentationController extends Controller
{
    public function __construct()
    {
        $this->middleware('pamong.permission:materi')->only(['index', 'preview', 'exportPdf', 'exportPptx']);
        $this->middleware('pamong.permission:materi,create')->only(['store']);
        $this->middleware('pamong.permission:materi,edit')->only(['edit', 'update', 'uploadAsset', 'togglePublish']);
        $this->middleware('pamong.permission:materi,delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $presentations = Presentation::query()
            ->with(['materi:id,judul', 'creator:id,name'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search): void {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('presentations.index', [
            'presentations' => $presentations,
            'materiOptions' => Materi::query()->active()->orderBy('judul')->get(['id', 'judul']),
            'canCreate' => $request->user()->hasPamongCrudPermission('materi', 'create'),
            'canEdit' => $request->user()->hasPamongCrudPermission('materi', 'edit'),
            'canDelete' => $request->user()->hasPamongCrudPermission('materi', 'delete'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'materi_id' => ['nullable', 'integer', 'exists:materi,id'],
        ]);

        $presentation = Presentation::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'slug' => $this->uniqueSlug($validated['title']),
            'background_color' => '#0f172a',
            'path_mode' => 'overview_between',
            'canvas_data' => $this->starterCanvas($validated['title']),
        ]);

        return redirect()->route('presentations.edit', $presentation)
            ->with('success', 'Presentasi dibuat. Susun frame dan isi materi pada kanvas.');
    }

    public function edit(Presentation $presentation): View
    {
        $presentation->load('assets');

        return view('presentations.edit', [
            'presentation' => $presentation,
            'editorPayload' => $this->viewerPayload($presentation),
        ]);
    }

    public function update(Request $request, Presentation $presentation): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'background_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'path_mode' => ['required', Rule::in(['direct', 'overview_between'])],
            'canvas_data' => ['required', 'array'],
            'canvas_data.width' => ['required', 'numeric', 'min:1200', 'max:7000'],
            'canvas_data.height' => ['required', 'numeric', 'min:800', 'max:12500'],
            'canvas_data.frames' => ['required', 'array', 'min:1', 'max:40'],
            'canvas_data.frames.*.elements' => ['present', 'array', 'max:60'],
        ]);

        $canvasData = $request->input('canvas_data', []);
        if (strlen(json_encode($canvasData)) > 750000) {
            throw ValidationException::withMessages([
                'canvas_data' => 'Isi presentasi terlalu besar. Kurangi jumlah frame atau elemen.',
            ]);
        }

        $presentation->update([
            'title' => trim($validated['title']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'background_color' => strtolower($validated['background_color']),
            'path_mode' => $validated['path_mode'],
            'canvas_data' => $this->normalizeCanvas($canvasData),
        ]);

        return response()->json([
            'message' => 'Presentasi tersimpan.',
            'updated_at' => $presentation->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    public function uploadAsset(Request $request, Presentation $presentation): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);
        $file = $validated['image'];
        $filename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("presentations/{$presentation->id}", $filename, 'public');

        $asset = $presentation->assets()->create([
            'uploaded_by' => $request->user()->id,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'asset' => [
                'id' => $asset->id,
                'url' => Storage::disk('public')->url($asset->path),
                'name' => $asset->original_name,
            ],
        ], 201);
    }

    public function preview(Presentation $presentation): View
    {
        $presentation->load('assets');

        return view('presentations.viewer', [
            'presentation' => $presentation,
            'viewerPayload' => $this->viewerPayload($presentation),
            'isPublicViewer' => false,
            'theme' => ThemeSetting::current(),
        ]);
    }

    public function exportPdf(
        Presentation $presentation,
        PresentationExportService $exportService
    ): Response {
        return $exportService->pdf($presentation);
    }

    public function exportPptx(
        Presentation $presentation,
        PresentationExportService $exportService
    ): BinaryFileResponse {
        return $exportService->pptx($presentation);
    }

    public function publicShow(Presentation $presentation): View
    {
        abort_unless($presentation->is_published, 404);
        $presentation->load('assets');

        return view('presentations.viewer', [
            'presentation' => $presentation,
            'viewerPayload' => $this->viewerPayload($presentation),
            'isPublicViewer' => true,
            'theme' => ThemeSetting::current(),
        ]);
    }

    public function togglePublish(Presentation $presentation): RedirectResponse
    {
        $publish = ! $presentation->is_published;
        $presentation->update([
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
        ]);

        return back()->with(
            'success',
            $publish ? 'Presentasi diterbitkan dan tautan publik sudah aktif.' : 'Presentasi ditarik dari publik.'
        );
    }

    public function destroy(Presentation $presentation): RedirectResponse
    {
        $paths = $presentation->assets()->pluck('path')->all();
        $presentation->delete();

        if ($paths) {
            Storage::disk('public')->delete($paths);
        }

        return redirect()->route('presentations.index')->with('success', 'Presentasi berhasil dihapus.');
    }

    private function viewerPayload(Presentation $presentation): array
    {
        return [
            'id' => $presentation->id,
            'title' => $presentation->title,
            'description' => $presentation->description,
            'backgroundColor' => $presentation->background_color,
            'pathMode' => $presentation->path_mode,
            'canvas' => $presentation->canvas_data,
            'assets' => $presentation->assets
                ->mapWithKeys(fn (PresentationAsset $asset) => [
                    (string) $asset->id => [
                        'url' => Storage::disk('public')->url($asset->path),
                        'name' => $asset->original_name,
                    ],
                ])
                ->all(),
        ];
    }

    private function starterCanvas(string $title): array
    {
        return [
            'version' => 1,
            'width' => 2400,
            'height' => 1400,
            'frames' => [[
                'id' => 'frame-'.Str::lower(Str::random(8)),
                'title' => 'Pembuka',
                'x' => 180,
                'y' => 260,
                'width' => 800,
                'height' => 450,
                'backgroundColor' => '#ffffff',
                'shape' => 'rounded',
                'borderRadius' => 22,
                'elements' => [[
                    'id' => 'element-'.Str::lower(Str::random(8)),
                    'type' => 'text',
                    'x' => 70,
                    'y' => 120,
                    'width' => 660,
                    'height' => 160,
                    'text' => $title,
                    'fontSize' => 52,
                    'color' => '#0f172a',
                    'backgroundColor' => 'transparent',
                    'align' => 'center',
                    'bold' => true,
                ]],
            ]],
        ];
    }

    private function normalizeCanvas(array $canvas): array
    {
        $number = static fn (mixed $value, float $min, float $max, float $fallback): float =>
            max($min, min($max, is_numeric($value) ? (float) $value : $fallback));
        $color = static fn (mixed $value, string $fallback): string =>
            is_string($value) && preg_match('/^(#[0-9a-fA-F]{6}|transparent)$/', $value)
                ? strtolower($value)
                : $fallback;
        $allowedTypes = ['text', 'image', 'logo', 'youtube', 'link', 'shape', 'diagram'];
        $frames = [];

        foreach (array_slice($canvas['frames'] ?? [], 0, 40) as $frameIndex => $frame) {
            if (! is_array($frame)) {
                continue;
            }

            $elements = [];
            foreach (array_slice($frame['elements'] ?? [], 0, 60) as $elementIndex => $element) {
                if (! is_array($element) || ! in_array($element['type'] ?? null, $allowedTypes, true)) {
                    continue;
                }

                $type = $element['type'];
                $normalized = [
                    'id' => Str::limit((string) ($element['id'] ?? "element-{$frameIndex}-{$elementIndex}"), 80, ''),
                    'type' => $type,
                    'x' => $number($element['x'] ?? null, 0, 2000, 60),
                    'y' => $number($element['y'] ?? null, 0, 1100, 60),
                    'width' => $number($element['width'] ?? null, 40, 1600, 300),
                    'height' => $number($element['height'] ?? null, 30, 900, 120),
                    'rotation' => $number($element['rotation'] ?? null, -180, 180, 0),
                    'color' => $color($element['color'] ?? null, '#0f172a'),
                    'backgroundColor' => $color($element['backgroundColor'] ?? null, 'transparent'),
                ];

                if ($type === 'text') {
                    $normalized += [
                        'text' => Str::limit((string) ($element['text'] ?? 'Teks'), 5000, ''),
                        'fontSize' => $number($element['fontSize'] ?? null, 10, 160, 32),
                        'align' => in_array($element['align'] ?? null, ['left', 'center', 'right'], true) ? $element['align'] : 'left',
                        'bold' => (bool) ($element['bold'] ?? false),
                    ];
                } elseif (in_array($type, ['image', 'logo'], true)) {
                    $normalized += [
                        'assetId' => max(0, (int) ($element['assetId'] ?? 0)),
                        'alt' => Str::limit((string) ($element['alt'] ?? ($type === 'logo' ? 'Logo presentasi' : 'Gambar presentasi')), 160, ''),
                        'fit' => in_array($element['fit'] ?? null, ['cover', 'contain'], true) ? $element['fit'] : 'cover',
                        'shape' => in_array($element['shape'] ?? null, ['circle', 'rounded', 'square', 'hexagon'], true)
                            ? $element['shape']
                            : ($type === 'logo' ? 'circle' : 'rounded'),
                    ];
                } elseif ($type === 'youtube') {
                    $youtubeUrl = Str::limit((string) ($element['youtubeUrl'] ?? ''), 500, '');
                    $normalized += [
                        'youtubeUrl' => $youtubeUrl,
                        'youtubeId' => $this->youtubeId($youtubeUrl),
                        'title' => Str::limit((string) ($element['title'] ?? 'Video YouTube'), 160, ''),
                    ];
                } elseif ($type === 'link') {
                    $normalized += [
                        'text' => Str::limit((string) ($element['text'] ?? 'Buka tautan'), 160, ''),
                        'url' => $this->safeWebUrl((string) ($element['url'] ?? '')),
                        'linkStyle' => in_array($element['linkStyle'] ?? null, ['button', 'card', 'text'], true)
                            ? $element['linkStyle']
                            : 'button',
                    ];
                } elseif ($type === 'shape') {
                    $normalized += [
                        'text' => Str::limit((string) ($element['text'] ?? ''), 1000, ''),
                        'shapeType' => in_array($element['shapeType'] ?? null, ['circle', 'rounded', 'rectangle', 'hexagon', 'custom'], true)
                            ? $element['shapeType']
                            : 'rounded',
                        'borderRadius' => $number($element['borderRadius'] ?? null, 0, 240, 24),
                        'fontSize' => $number($element['fontSize'] ?? null, 10, 160, 28),
                    ];
                } else {
                    $normalized += [
                        'diagramType' => in_array($element['diagramType'] ?? null, ['process', 'cycle', 'hierarchy', 'radial'], true)
                            ? $element['diagramType']
                            : 'process',
                        'centerText' => Str::limit((string) ($element['centerText'] ?? 'Logo / Tema'), 120, ''),
                        'nodeShape' => in_array($element['nodeShape'] ?? null, ['circle', 'rounded', 'square', 'hexagon'], true)
                            ? $element['nodeShape']
                            : 'circle',
                        'items' => collect(array_slice($element['items'] ?? [], 0, 8))
                            ->map(fn ($item) => Str::limit((string) $item, 120, ''))
                            ->filter()
                            ->values()
                            ->all(),
                    ];
                }

                $elements[] = $normalized;
            }

            $frames[] = [
                'id' => Str::limit((string) ($frame['id'] ?? "frame-{$frameIndex}"), 80, ''),
                'title' => Str::limit((string) ($frame['title'] ?? 'Frame '.($frameIndex + 1)), 120, ''),
                'x' => $number($frame['x'] ?? null, 0, 5000, 100 + ($frameIndex * 160)),
                'y' => $number($frame['y'] ?? null, 0, 11000, 180 + ($frameIndex * 120)),
                'width' => $number($frame['width'] ?? null, 320, 1600, 800),
                'height' => $number($frame['height'] ?? null, 180, 900, 450),
                'backgroundColor' => $color($frame['backgroundColor'] ?? null, '#ffffff'),
                'shape' => in_array($frame['shape'] ?? null, ['rounded', 'rectangle', 'circle', 'hexagon', 'custom'], true)
                    ? $frame['shape']
                    : 'rounded',
                'borderRadius' => $number($frame['borderRadius'] ?? null, 0, 240, 22),
                'elements' => $elements,
            ];
        }

        if (! $frames) {
            $frames = $this->starterCanvas('Presentasi Baru')['frames'];
        }

        $requestedWidth = (int) $number($canvas['width'] ?? null, 1200, 7000, 2400);
        $requestedHeight = (int) $number($canvas['height'] ?? null, 800, 12500, 1400);
        $requiredWidth = (int) min(7000, max(array_map(
            static fn (array $frame): float => $frame['x'] + $frame['width'] + 120,
            $frames
        )));
        $requiredHeight = (int) min(12500, max(array_map(
            static fn (array $frame): float => $frame['y'] + $frame['height'] + 120,
            $frames
        )));

        return [
            'version' => 1,
            'width' => max($requestedWidth, $requiredWidth),
            'height' => max($requestedHeight, $requiredHeight),
            'frames' => $frames,
        ];
    }

    private function uniqueSlug(string $title): string
    {
        do {
            $slug = Str::slug($title).'-'.Str::lower(Str::random(7));
        } while (Presentation::query()->where('slug', $slug)->exists());

        return $slug;
    }

    private function youtubeId(string $url): string
    {
        return preg_match(
            '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~i',
            trim($url),
            $matches
        ) ? $matches[1] : '';
    }

    private function safeWebUrl(string $url): string
    {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? Str::limit($url, 1000, '')
            : '';
    }
}
