@extends('layouts.public')

@section('title', $presentation->title)
@section('og_title', $presentation->title)
@section('og_description', $presentation->description ?: 'Presentasi materi PKG Panunggangan')

@section('content')
<section class="bg-slate-950">
    <div
        id="presentation-viewer"
        class="pkg-presentation-viewer"
        data-presentation-payload="{{ base64_encode(json_encode($viewerPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
    >
        <div class="pkg-presentation-viewer-bar">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white">{{ $presentation->title }}</p>
                <p class="truncate text-xs text-slate-400">{{ $presentation->description ?: 'Presentasi Materi PKG' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if(! $isPublicViewer)
                    <a href="{{ route('presentations.export.pdf', $presentation) }}" class="pkg-viewer-control">PDF</a>
                    <a href="{{ route('presentations.export.pptx', $presentation) }}" class="pkg-viewer-control">PPTX</a>
                    <a href="{{ route('presentations.edit', $presentation) }}" class="pkg-viewer-control">Edit</a>
                @endif
                <button type="button" class="pkg-viewer-control" data-viewer-fullscreen>Layar Penuh</button>
            </div>
        </div>

        <div class="pkg-presentation-viewer-viewport" data-viewer-viewport>
            <div class="pkg-presentation-stage" data-viewer-stage></div>
        </div>

        <div class="pkg-presentation-viewer-controls">
            <button type="button" class="pkg-viewer-control" data-viewer-prev aria-label="Langkah sebelumnya">Sebelumnya</button>
            <button type="button" class="pkg-viewer-control pkg-viewer-control-primary" data-viewer-home>Overview</button>
            <span class="min-w-20 text-center text-xs font-semibold text-slate-300" data-viewer-progress>Overview</span>
            <button type="button" class="pkg-viewer-control" data-viewer-next aria-label="Langkah berikutnya">Berikutnya</button>
        </div>
    </div>
</section>

@vite('resources/js/presentation-viewer.js')
@endsection
