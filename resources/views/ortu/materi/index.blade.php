@extends('layouts.ortu')

@section('title', 'Materi Pembelajaran')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Materi Pembelajaran</h1>
            <p class="pkg-page-subheading">Pilih folder karakter untuk melihat materi yang dibagikan.</p>
        </div>
    </div>

    @if($materiFolders->isEmpty())
        <div class="pkg-card pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <h3 class="pkg-empty-title">Belum Ada Materi</h3>
            <p class="pkg-empty-copy">Materi pembelajaran belum tersedia saat ini.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($materiFolders as $folder)
                <section class="pkg-panel p-5">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $folder->name }}</h2>
                            @if($folder->description)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $folder->description }}</p>
                            @endif
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800 dark:bg-teal-900/40 dark:text-teal-200">{{ $folder->materi_count }} materi</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($folder->materi as $item)
                            <a href="{{ route('ortu.materi.show', $item) }}" class="pkg-card-soft p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $item->bulan?->format('F Y') ?? 'Periode belum diatur' }}</div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $item->judul }}</h3>
                                <p class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->deskripsi ?? 'Tidak ada deskripsi' }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if($item->hasPdfFiles())
                                        <span class="rounded bg-red-100 px-2 py-1 text-xs text-red-800 dark:bg-red-900 dark:text-red-200">PDF</span>
                                    @endif
                                    @if($item->video_url)
                                        <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-200">Video</span>
                                    @endif
                                    @if($item->isRppPublished())
                                        <span class="rounded bg-teal-100 px-2 py-1 text-xs text-teal-800 dark:bg-teal-900 dark:text-teal-200">RPP</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
