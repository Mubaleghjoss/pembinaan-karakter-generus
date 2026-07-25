@extends('layouts.app')

@section('title', 'Editor Presentasi - '.$presentation->title)

@section('content')
<div
    id="presentation-editor"
    class="pkg-presentation-editor"
    data-presentation-payload="{{ base64_encode(json_encode($editorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
    data-save-url="{{ route('presentations.update', $presentation) }}"
    data-upload-url="{{ route('presentations.assets.store', $presentation) }}"
    data-preview-url="{{ route('presentations.preview', $presentation) }}"
>
    <header class="pkg-page-header">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('presentations.index') }}" class="hover:text-emerald-600">Presentasi</a>
                <span>/</span>
                <span class="truncate">{{ $presentation->title }}</span>
            </div>
            <h1 class="pkg-page-heading mt-1">Editor Presentasi</h1>
            <p class="pkg-page-subheading">Gunakan overview untuk mengatur posisi frame, lalu fokuskan frame untuk mengisi materinya.</p>
        </div>
        <div class="pkg-page-actions">
            <span class="text-sm text-gray-500 dark:text-gray-400" data-save-status>Semua perubahan tersimpan</span>
            <button type="button" class="btn-secondary" data-editor-overview>Overview</button>
            <button type="button" class="btn-secondary" data-editor-fit>Pas Tampilan</button>
            <a href="{{ route('presentations.preview', $presentation) }}" target="_blank" rel="noopener" class="btn-secondary" data-save-before-open>Pratinjau</a>
            <a href="{{ route('presentations.export.pdf', $presentation) }}" class="btn-secondary" data-export-link>Unduh PDF</a>
            <a href="{{ route('presentations.export.pptx', $presentation) }}" class="btn-secondary" data-export-link>Unduh PPTX</a>
            @if($presentation->is_published)
                <a href="{{ route('public.presentations.show', $presentation) }}" target="_blank" rel="noopener" class="btn-secondary" data-save-before-open>Tautan Publik</a>
            @endif
            <form method="POST" action="{{ route('presentations.publish', $presentation) }}" data-publish-form>
                @csrf @method('PATCH')
                <button class="btn-secondary">{{ $presentation->is_published ? 'Tarik Publikasi' : 'Terbitkan' }}</button>
            </form>
            <button type="button" class="btn-primary" data-editor-save>Simpan</button>
        </div>
    </header>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="pkg-panel p-4">
        <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_minmax(180px,0.7fr)_160px_200px]">
            <div>
                <label class="form-label" for="editor-title">Judul</label>
                <input id="editor-title" class="pkg-field w-full" maxlength="160" data-editor-title>
            </div>
            <div>
                <label class="form-label" for="editor-description">Deskripsi</label>
                <input id="editor-description" class="pkg-field w-full" maxlength="1000" data-editor-description>
            </div>
            <div>
                <label class="form-label" for="editor-background">Warna overview</label>
                <input id="editor-background" type="color" class="pkg-field h-11 w-full p-1" data-editor-background>
            </div>
            <div>
                <label class="form-label" for="editor-path-mode">Alur slideshow</label>
                <select id="editor-path-mode" class="pkg-field w-full" data-editor-path-mode>
                    <option value="overview_between">Kembali ke overview</option>
                    <option value="direct">Langsung frame berikutnya</option>
                </select>
            </div>
        </div>
    </section>

    <section class="mt-4 flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-frame>Tambah Frame</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-text>Tambah Teks</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-image>Masukkan Gambar</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-logo>Masukkan Logo</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-youtube>Tambah YouTube</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-link>Tambah Tautan</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-shape>Tambah Bentuk</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-add-diagram>Tambah Diagram</button>
        <button type="button" class="btn-secondary !px-3 !py-2 text-sm" data-arrange-frames>Rapikan Frame</button>
        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" data-image-input>
        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" data-logo-input>
        <span class="ml-auto self-center text-xs text-gray-500 dark:text-gray-400">Cubit dua jari untuk zoom, geser dua jari untuk memindahkan kanvas, lalu tap frame untuk fokus.</span>
    </section>

    <section class="mt-4 grid min-h-[680px] gap-4 xl:grid-cols-[240px_minmax(0,1fr)_280px]">
        <aside class="pkg-panel overflow-hidden">
            <div class="border-b border-gray-200 p-4 dark:border-slate-700">
                <h2 class="font-bold text-gray-900 dark:text-white">Alur Frame</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Urutan ini menjadi alur slideshow.</p>
            </div>
            <div class="max-h-[620px] space-y-2 overflow-y-auto p-3" data-frame-list></div>
        </aside>

        <div class="pkg-presentation-viewport" data-editor-viewport>
            <div class="pkg-presentation-stage" data-editor-stage></div>
            <div class="pkg-presentation-hint" data-editor-hint>Mode Overview</div>
        </div>

        <aside class="pkg-panel overflow-hidden">
            <div class="border-b border-gray-200 p-4 dark:border-slate-700">
                <h2 class="font-bold text-gray-900 dark:text-white">Properti</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Atur frame atau elemen yang sedang dipilih.</p>
            </div>
            <div class="max-h-[620px] overflow-y-auto p-4" data-editor-inspector></div>
        </aside>
    </section>
</div>

@vite('resources/js/presentation-editor.js')
@endsection
