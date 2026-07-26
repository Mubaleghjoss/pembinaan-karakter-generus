@extends('layouts.app')

@section('title', 'Berita & Kegiatan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Berita & Kegiatan</h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Informasi terbaru seputar kegiatan PKG</p>
        </div>
        
        @if(auth()->check() && auth()->user()->hasPamongCrudPermission('berita', 'create'))
        <div class="flex items-center gap-3">
            <a href="{{ route('berita.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah Berita
            </a>
        </div>
        @endif
    </div>

    <!-- Featured Slider (React) -->
    <div class="mb-12">
        <div id="news-slider" data-news='@json($berita->items())'></div>
    </div>

    <!-- Search & Filter -->
    <div class="mb-8">
        <form action="{{ route('berita.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 max-w-2xl mx-auto">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berita..." class="w-full pl-12 pr-4 py-3 rounded-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                <div class="absolute left-4 top-3.5 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            @if(auth()->check() && auth()->user()->hasPamongMenuAccess('berita'))
            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="py-3 px-4 rounded-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm">
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
            <button type="submit" class="sm:hidden px-6 py-3 bg-blue-600 text-white rounded-full font-medium hover:bg-blue-700 transition-colors">
                Cari
            </button>
        </form>
    </div>

    <!-- News Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($berita as $item)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 group">
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
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                    <a href="{{ route('public.berita', $item->slug) }}">
                        {{ $item->judul }}
                    </a>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-3 text-sm">
                    {{ $item->excerpt }}
                </p>
                <div class="flex items-center justify-between mt-auto">
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
        </div>
        @empty
        <div class="col-span-full text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum ada berita</h3>
            <p class="text-gray-500 dark:text-gray-400">Silakan cek kembali nanti.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $berita->links() }}
    </div>
</div>
@endsection
