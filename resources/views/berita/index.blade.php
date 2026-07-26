@extends('layouts.app')

@section('title', 'Berita & Kegiatan')

@section('content')
<div class="mx-auto w-full min-w-0 max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
    <!-- Header & Actions -->
    <div class="pkg-page-header">
        <div class="min-w-0">
            <h1 class="pkg-page-heading">Berita & Kegiatan</h1>
            <p class="pkg-page-subheading">Informasi terbaru seputar kegiatan PKG.</p>
        </div>
        
        @if(auth()->check() && auth()->user()->hasPamongCrudPermission('berita', 'create'))
        <div class="pkg-page-actions">
            <a href="{{ route('berita.create') }}" class="btn-primary inline-flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah Berita
            </a>
        </div>
        @endif
    </div>

    <!-- Featured Slider (React) -->
    <div class="mb-6 min-w-0 sm:mb-8">
        <div id="news-slider" data-news='@json($berita->items())'></div>
    </div>

    <!-- Search & Filter -->
    <div class="pkg-filter-bar mb-6">
        <form action="{{ route('berita.index') }}" method="GET" class="pkg-filter-grid sm:grid-cols-[minmax(0,1fr)_auto_auto]">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berita..." class="pkg-field pkg-field-icon-left">
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            @if(auth()->check() && auth()->user()->hasPamongMenuAccess('berita'))
            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="pkg-field">
                    <option value="">Semua Status</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>
                        Published
                    </option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>
                        Draft
                    </option>
                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>
                        Archived
                    </option>
                </select>
            </div>
            @endif
            <button type="submit" class="btn-primary inline-flex items-center justify-center">
                Cari
            </button>
        </form>
    </div>

    <!-- News Grid -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        @forelse($berita as $item)
        <article class="pkg-card group flex min-w-0 flex-col overflow-hidden transition-shadow duration-300 hover:shadow-lg">
            <div class="relative h-48 overflow-hidden">
                <img src="{{ $item->cover_path ? asset('storage/'.$item->cover_path) : 'https://via.placeholder.com/400x200' }}" alt="{{ $item->judul }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <div class="absolute top-4 right-4 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold text-gray-900 dark:text-white shadow-sm">
                    {{ $item->published_at ? $item->published_at->format('d M Y') : 'Draft' }}
                </div>
                @if(auth()->check() && auth()->user()->hasPamongMenuAccess('berita'))
                <div class="absolute top-4 left-4">
                    @if($item->status == 'published')
                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">Published</span>
                    @elseif($item->status == 'draft')
                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-white">Draft</span>
                    @else
                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500 text-white">Archived</span>
                    @endif
                </div>
                @endif
            </div>
            <div class="flex flex-1 flex-col p-4 sm:p-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                    <a href="{{ route('public.berita', $item->slug) }}">
                        {{ $item->judul }}
                    </a>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-3 text-sm">
                    {{ $item->excerpt }}
                </p>
                <div class="mt-auto flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('public.berita', $item->slug) }}" class="text-blue-600 dark:text-blue-400 font-medium text-sm hover:underline">
                        Baca Selengkapnya &rarr;
                    </a>
                    @if($item->pdf_path)
                    <span class="flex items-center text-xs text-gray-500" title="Ada lampiran PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        PDF
                    </span>
                    @endif
                </div>
            </div>
        </article>
        @empty
        <div class="pkg-empty-state col-span-full">
            <div class="pkg-empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-full w-full" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>
            <h3 class="pkg-empty-title">Belum ada berita</h3>
            <p class="pkg-empty-copy">Silakan cek kembali nanti.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $berita->links() }}
    </div>
</div>
@endsection
