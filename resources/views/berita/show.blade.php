@extends('layouts.app')

@section('title', $berita->judul)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('berita.index') }}" class="hover:text-blue-600">Berita</a>
        <span>/</span>
        <span class="text-gray-900 font-medium truncate max-w-xs">{{ $berita->judul }}</span>
    </div>

    <article class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Cover / Slider -->
        <div class="relative h-[300px] md:h-[500px] bg-gray-100 dark:bg-gray-900">
            @if($berita->images && count($berita->images) > 0)
                <!-- React Slider Component for Multiple Images -->
                <div id="news-slider" 
                     data-news='@json([array_merge($berita->toArray(), ['images' => $berita->images])])'>
                </div>
            @else
                <img src="{{ $berita->cover_path ? asset('storage/' . $berita->cover_path) : 'https://via.placeholder.com/1200x600' }}" 
                     alt="{{ $berita->judul }}" 
                     class="w-full h-full object-cover">
            @endif
        </div>

        <div class="p-6 md:p-10">
            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-4 mb-6 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ $berita->published_at ? $berita->published_at->format('d M Y') : 'Draft' }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    {{ $berita->author->nama ?? 'Admin' }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    {{ $berita->view_count }} Dilihat
                </span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-8 leading-tight">
                {{ $berita->judul }}
            </h1>

            <!-- Content -->
            <div class="prose prose-lg dark:prose-invert max-w-none mb-10">
                {!! nl2br(e($berita->isi)) !!}
            </div>

            <!-- Attachments -->
            @if($berita->pdf_path)
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-xl p-6 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-lg mr-4">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Dokumen Pendukung</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Dokumen PDF | {{ $berita->download_count }}x diunduh</p>
                    </div>
                </div>
                <a href="{{ route('berita.download', $berita) }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors shadow-lg shadow-blue-500/30 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh
                </a>
            </div>
            @endif

            <!-- Admin Actions -->
            @if(auth()->check() && auth()->user()->hasPamongCrudPermission('berita', 'edit'))
            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700 flex gap-4">
                <a href="{{ route('berita.edit', $berita) }}" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors">
                    Edit Berita
                </a>
                <form action="{{ route('berita.destroy', $berita) }}" method="POST" data-confirm="Yakin ingin menghapus berita ini?" data-confirm-title="Hapus berita" data-confirm-button="Hapus" data-confirm-tone="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                        Hapus Berita
                    </button>
                </form>
            </div>
            @endif
        </div>
    </article>
</div>
@endsection
