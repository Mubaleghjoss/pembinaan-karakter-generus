@extends('layouts.app')

@section('title', 'Presentasi Materi')

@section('content')
<div class="space-y-6">
    <header class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Presentasi Materi</h1>
            <p class="pkg-page-subheading">Susun materi pada kanvas overview, atur alur frame, lalu bagikan sebagai slideshow publik.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($canCreate)
        <details class="pkg-panel p-5" @if($presentations->isEmpty()) open @endif>
            <summary class="cursor-pointer text-lg font-bold text-gray-900 dark:text-white">Buat Presentasi Baru</summary>
            <form method="POST" action="{{ route('presentations.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label for="presentation-title" class="form-label">Judul presentasi</label>
                    <input id="presentation-title" name="title" value="{{ old('title') }}" class="pkg-field w-full" maxlength="160" required>
                </div>
                <div>
                    <label for="presentation-materi" class="form-label">Tautkan ke materi (opsional)</label>
                    <select id="presentation-materi" name="materi_id" class="pkg-field w-full">
                        <option value="">Presentasi mandiri</option>
                        @foreach($materiOptions as $materi)
                            <option value="{{ $materi->id }}" @selected((string) old('materi_id') === (string) $materi->id)>{{ $materi->judul }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="presentation-description" class="form-label">Deskripsi singkat</label>
                    <textarea id="presentation-description" name="description" rows="3" class="pkg-field w-full" maxlength="1000">{{ old('description') }}</textarea>
                </div>
                <button class="btn-primary justify-center md:col-span-2">Buat dan Buka Editor</button>
            </form>
        </details>
    @endif

    <form method="GET" action="{{ route('presentations.index') }}" class="pkg-filter-bar">
        <div class="pkg-filter-grid md:grid-cols-[minmax(0,1fr)_auto]">
            <div>
                <label for="presentation-search" class="form-label">Cari presentasi</label>
                <input id="presentation-search" name="search" value="{{ request('search') }}" class="pkg-field w-full" placeholder="Judul atau deskripsi">
            </div>
            <button class="btn-secondary self-end">Cari</button>
        </div>
    </form>

    @if($presentations->isEmpty())
        <section class="pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
            </svg>
            <h2 class="pkg-empty-title">Belum ada presentasi</h2>
            <p class="pkg-empty-copy">Buat presentasi pertama untuk mulai menyusun materi secara visual.</p>
        </section>
    @else
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($presentations as $presentation)
                <article class="pkg-card overflow-hidden">
                    <div class="h-2" style="background: {{ $presentation->background_color }}"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-black text-gray-900 dark:text-white">{{ $presentation->title }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $presentation->materi?->judul ?? 'Presentasi mandiri' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $presentation->is_published ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                {{ $presentation->is_published ? 'Publik' : 'Draft' }}
                            </span>
                        </div>
                        <p class="mt-4 line-clamp-2 min-h-10 text-sm leading-5 text-gray-600 dark:text-gray-300">
                            {{ $presentation->description ?: 'Belum ada deskripsi.' }}
                        </p>
                        <p class="mt-4 text-xs text-gray-500">Diperbarui {{ $presentation->updated_at->diffForHumans() }}</p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @if($canEdit)
                                <a href="{{ route('presentations.edit', $presentation) }}" class="btn-primary !px-3 !py-2 text-sm">Buka Editor</a>
                            @endif
                            <a href="{{ route('presentations.preview', $presentation) }}" target="_blank" rel="noopener" class="btn-secondary !px-3 !py-2 text-sm">Pratinjau</a>
                            <a href="{{ route('presentations.export.pdf', $presentation) }}" class="btn-secondary !px-3 !py-2 text-sm">Unduh PDF</a>
                            <a href="{{ route('presentations.export.pptx', $presentation) }}" class="btn-secondary !px-3 !py-2 text-sm">Unduh PPTX</a>
                            @if($canEdit)
                                <form method="POST" action="{{ route('presentations.publish', $presentation) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn-secondary !px-3 !py-2 text-sm">{{ $presentation->is_published ? 'Tarik Publikasi' : 'Terbitkan' }}</button>
                                </form>
                            @endif
                        </div>

                        @if($presentation->is_published)
                            <div class="mt-4 rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/30">
                                <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">Tautan publik hanya-baca</p>
                                <a href="{{ route('public.presentations.show', $presentation) }}" target="_blank" rel="noopener" class="mt-1 block break-all text-sm text-emerald-700 underline dark:text-emerald-300">
                                    {{ route('public.presentations.show', $presentation) }}
                                </a>
                            </div>
                        @endif

                        @if($canDelete)
                            <form method="POST" action="{{ route('presentations.destroy', $presentation) }}" class="mt-4" onsubmit="return window.confirm('Hapus presentasi ini beserta seluruh gambar yang diunggah?')">
                                @csrf @method('DELETE')
                                <button class="text-sm font-semibold text-red-600 hover:text-red-700 dark:text-red-400">Hapus Presentasi</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>

        {{ $presentations->links() }}
    @endif
</div>
@endsection
