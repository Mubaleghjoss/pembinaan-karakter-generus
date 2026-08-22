@extends('layouts.public')

@section('title', '29 Karakter Luhur - ' . ($theme->app_name ?? 'PKG Presensi'))
@section('og_title', '29 Karakter Luhur')
@section('og_description', 'Referensi 29 karakter luhur: penjelasan, dalil Al-Qur'."'".'an & hadits, serta contoh penerapan.')

@section('content')
<section class="bg-slate-50 py-10 dark:bg-slate-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-public-hero-card p-6 sm:p-8 lg:p-10 mb-6" data-reveal="zoom">
            <div class="relative z-10">
                <span class="pkg-glass-badge text-sm font-semibold">Referensi karakter</span>
                <h1 class="pkg-page-heading mt-5">29 Karakter Luhur</h1>
                <p class="pkg-page-subheading">Poin-poin karakter luhur beserta penjelasan, dalil Al-Qur'an &amp; hadits, dan contoh penerapan sehari-hari.</p>
            </div>
        </div>

        @include('public.partials.calendar-materi-tabs', ['activePublicTab' => 'karakter'])

        {{-- Search --}}
        <form method="GET" action="{{ route('public.karakter.index') }}" class="pkg-filter-bar mb-6" data-reveal="up">
            <div class="flex flex-1 flex-wrap gap-3">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari karakter, kategori, atau kata kunci..."
                    class="pkg-field min-w-0 flex-1"
                >
                <button type="submit" class="btn-primary">Cari</button>
                @if($search !== '')
                    <a href="{{ route('public.karakter.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($search !== '')
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Menampilkan {{ $karakters->count() }} hasil untuk "<span class="font-semibold">{{ $search }}</span>" dari {{ $total }} karakter.</p>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($karakters as $item)
                <a href="{{ route('public.karakter.show', $item->slug) }}"
                   class="pkg-panel group flex flex-col overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-lg" data-reveal="up">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-lg font-black text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                            {{ $item->nomor }}
                        </span>
                        @if($item->nama_arab)
                            <span class="text-xl font-semibold text-slate-700 dark:text-slate-200" dir="rtl">{{ $item->nama_arab }}</span>
                        @endif
                    </div>
                    <h3 class="mt-3 text-lg font-black text-slate-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-300">{{ $item->nama }}</h3>
                    @if($item->kategori)
                        <span class="mt-1 inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $item->kategori }}</span>
                    @endif
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $item->ringkas ?: $item->definisi }}</p>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 dark:text-emerald-300">
                        Baca selengkapnya
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>
            @empty
                <div class="pkg-empty-state col-span-full">
                    <div class="pkg-empty-icon">29</div>
                    <h2 class="pkg-empty-title">Belum ada data karakter</h2>
                    <p class="pkg-empty-copy">Data 29 karakter luhur belum tersedia atau tidak cocok dengan pencarian.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
