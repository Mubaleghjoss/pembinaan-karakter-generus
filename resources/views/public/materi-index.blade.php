@extends('layouts.public')

@section('title', 'Materi - ' . ($theme->app_name ?? 'PKG Presensi'))
@section('og_title', 'Materi PKG')
@section('og_description', 'Daftar materi PKG yang tersedia untuk pengunjung.')

@section('content')
<section class="bg-slate-50 py-10 dark:bg-slate-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-public-hero-card p-6 sm:p-8 lg:p-10 mb-6" data-reveal="zoom">
            <div class="relative z-10 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(260px,0.72fr)] lg:items-center">
                <div class="pkg-page-header !mb-0">
                    <div>
                        <span class="pkg-glass-badge text-sm font-semibold">Pusat materi pembelajaran</span>
                        <h1 class="pkg-page-heading mt-5">Materi PKG</h1>
                        <p class="pkg-page-subheading">Daftar materi dapat dilihat semua pengunjung. Login diperlukan untuk membuka PDF dan video.</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="pkg-inline-stat">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Koleksi</p>
                            <p class="text-lg font-black text-slate-950 dark:text-white">{{ $materi->total() }}</p>
                        </div>
                    </div>
                    <div class="pkg-inline-stat">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Folder</p>
                            <p class="text-lg font-black text-slate-950 dark:text-white">{{ $materiFolders->count() }}</p>
                        </div>
                    </div>
                    <div class="pkg-inline-stat">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Akses</p>
                            <p class="text-lg font-black text-slate-950 dark:text-white">Perlu Login</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('public.partials.calendar-materi-tabs', ['activePublicTab' => 'materi'])

        <form method="GET" action="{{ route('materi.index') }}" class="pkg-filter-bar mb-6" data-reveal="up">
            <div class="flex flex-wrap gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari materi..."
                    class="pkg-field min-w-[220px] flex-1 text-sm"
                >
                <select name="folder_id" class="pkg-field text-sm">
                    <option value="">Semua Folder</option>
                    @foreach($materiFolders as $folder)
                        <option value="{{ $folder->id }}" @selected((int) request('folder_id') === $folder->id)>
                            {{ $folder->display_name ?? $folder->name }}
                        </option>
                    @endforeach
                </select>
                <input type="month" name="bulan" value="{{ request('bulan') }}" class="pkg-field text-sm">
                <button type="submit" class="btn-primary text-sm">Filter</button>
                <a href="{{ route('materi.index') }}" class="btn-secondary text-sm">Reset</a>
            </div>
        </form>

        @if($materiFolders->isNotEmpty())
            <div class="mb-6 flex gap-2 overflow-x-auto pb-1" data-reveal="up">
                <a href="{{ route('materi.index', request()->except('folder_id', 'page')) }}" class="btn-secondary shrink-0 px-3 py-2 text-xs {{ request()->filled('folder_id') ? '' : 'ring-2 ring-emerald-500' }}">
                    Semua Folder
                </a>
                @foreach($materiFolders as $folder)
                    <a
                        href="{{ route('materi.index', array_merge(request()->except('page'), ['folder_id' => $folder->id])) }}"
                        class="btn-secondary shrink-0 px-3 py-2 text-xs {{ (int) request('folder_id') === $folder->id ? 'ring-2 ring-emerald-500' : '' }}"
                    >
                        {{ $folder->display_name ?? $folder->name }}
                        <span class="ml-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[11px] text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $folder->total_materi_count ?? $folder->materi_count }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if(($materiFolderTree ?? collect())->isNotEmpty())
            <section class="mb-8" data-reveal="up">
                <div class="pkg-page-header mb-4">
                    <div>
                        <h2 class="pkg-page-heading text-2xl">Folder Materi</h2>
                        <p class="pkg-page-subheading">Buka folder utama untuk melihat kategori dan materi di dalamnya.</p>
                    </div>
                </div>
                @include('materi.partials.read-only-folder-tree', [
                    'folders' => $materiFolderTree,
                    'detailRouteName' => 'public.materi.show',
                ])
            </section>
        @endif

        @if($materi->isEmpty())
            <div class="pkg-empty-state" data-reveal="up">
                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                </svg>
                <p class="pkg-empty-title">Belum ada materi</p>
                <p class="pkg-empty-copy">Materi publik belum tersedia sesuai filter yang dipilih.</p>
            </div>
        @else
            <div class="pkg-page-header mb-4" data-reveal="up">
                <div>
                    <h2 class="pkg-page-heading text-2xl">Daftar Materi</h2>
                    <p class="pkg-page-subheading">Daftar materi sesuai filter aktif.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($materi as $item)
                    <article class="pkg-list-card flex h-full flex-col overflow-hidden" data-reveal="up">
                        <div class="border-b border-slate-200 bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-white dark:border-slate-800">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-white/80">
                                <span>{{ $item->bulan?->translatedFormat('F Y') ?? 'Tanpa bulan' }}</span>
                                @if($item->folder)
                                    <span class="rounded-full bg-white/15 px-2 py-1">{{ $item->folder->display_name }}</span>
                                @endif
                            </div>
                            <h2 class="mt-3 line-clamp-2 text-xl font-bold">{{ $item->judul }}</h2>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <p class="line-clamp-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ Str::limit($item->deskripsi, 160) }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($item->hasPdfFiles())
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-200">
                                        {{ $item->pdf_count }} PDF
                                    </span>
                                @endif
                                @if($item->has_video_links)
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                                        Video
                                    </span>
                                @endif
                                @if($item->isRppPublished())
                                    <span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-900/30 dark:text-teal-200">
                                        RPP
                                    </span>
                                @endif
                            </div>

                            <div class="mt-auto pt-5">
                                <a href="{{ route('public.materi.show', $item) }}" class="btn-primary w-full justify-center text-sm">
                                    Lihat Materi
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $materi->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
