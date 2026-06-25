@extends('layouts.public')

@section('title', 'Beranda - ' . ($theme->app_name ?? 'PKG Presensi'))

@section('content')
<!-- Hero Section -->
<div class="gradient-primary text-white py-10 md:py-20 shadow-[0_24px_80px_rgba(15,23,42,0.16)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl md:text-5xl lg:text-6xl font-black mb-4 md:mb-6 animate-fade-in">
                Selamat Datang di<br>
                <span class="text-yellow-300">{{ $theme->app_name }}</span>
            </h1>
            <p class="text-lg md:text-xl lg:text-2xl text-white/90 mb-6 md:mb-10 max-w-3xl mx-auto">
                {{ $theme->app_description }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center flex-wrap">
                <a href="{{ route('public.scanner') }}" class="bg-white text-primary px-8 py-4 rounded-full font-bold text-lg hover:bg-yellow-300 hover:text-gray-900 transition-all shadow-2xl hover-lift inline-flex items-center justify-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                    Scan Presensi Sekarang
                </a>
                <a href="{{ route('laporan-penyaksian.create') }}" class="bg-emerald-500 text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-emerald-600 transition-all shadow-2xl hover-lift inline-flex items-center justify-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Lapor PKG
                </a>
                <a href="#berita" class="bg-white/20 backdrop-blur-lg text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-white/30 transition-all shadow-2xl hover-lift inline-flex items-center justify-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                    Lihat Berita
                </a>
            </div>
        </div>
    </div>
</div>

@include('public.partials.calendar-widget', [
    'calendarId' => 'home-calendar',
    'calendarTitle' => 'Kalender Aktivitas PKG',
    'calendarSubtitle' => 'Lihat agenda presensi, RPP materi, dan tenggat kegiatan PKG.',
    'calendarSectionClass' => 'py-10 md:py-20',
    'showCalendarLink' => true,
])

<!-- News Section -->
<div id="berita" class="py-10 md:py-20 bg-gray-50/80 dark:bg-transparent">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header items-center text-center mb-8 md:mb-16">
            <div>
                <h2 class="pkg-page-heading text-3xl md:text-4xl">Berita & Kegiatan Terbaru</h2>
                <p class="pkg-page-subheading text-base md:text-xl">Informasi terkini seputar kegiatan PKG.</p>
            </div>
        </div>

        @if($berita->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($berita as $item)
            <div class="pkg-panel overflow-hidden hover-lift">
                <div class="relative h-48 overflow-hidden">
                    @if($item->cover_path)
                        <img src="{{ asset('storage/' . $item->cover_path) }}" alt="{{ $item->judul }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                            </svg>
                        </div>
                    @endif
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold text-gray-900 dark:bg-slate-950/90 dark:text-white">
                        {{ $item->published_at ? $item->published_at->format('d M Y') : 'Draft' }}
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
                        {{ $item->judul }}
                    </h3>
                    <p class="text-gray-600 dark:text-slate-300 mb-4 line-clamp-3 text-sm">
                        {{ Str::limit(strip_tags($item->isi), 120) }}
                    </p>
                    <a href="{{ route('public.berita', $item->slug) }}" class="inline-flex items-center text-primary font-bold hover:text-secondary transition-colors">
                        Baca Selengkapnya
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-12">
            {{ $berita->links() }}
        </div>
        @else
        <div class="pkg-empty-state">
            <svg class="pkg-empty-icon !w-24 !h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
            </svg>
            <p class="pkg-empty-title text-2xl text-gray-400 dark:text-slate-200">Belum Ada Berita</p>
            <p class="pkg-empty-copy">Silakan cek kembali nanti untuk informasi terbaru.</p>
        </div>
        @endif
    </div>
</div>

<!-- CTA Section -->
<div class="gradient-primary text-white py-10 md:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-black mb-6">Siap Melakukan Presensi?</h2>
        <p class="text-xl text-white/90 mb-10">Scan QR Code Anda sekarang untuk mencatat kehadiran</p>
        <a href="{{ route('public.scanner') }}" class="bg-white text-primary px-10 py-5 rounded-full font-bold text-xl hover:bg-yellow-300 hover:text-gray-900 transition-all shadow-2xl hover-lift inline-flex items-center">
            <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
            Mulai Scan QR Code
        </a>
    </div>
</div>
@endsection
