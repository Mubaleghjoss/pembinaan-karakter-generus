@extends('layouts.public')

@section('title', $karakter->nama . ' - 29 Karakter Luhur')
@section('og_title', $karakter->nama . ' - 29 Karakter Luhur')
@section('og_description', $karakter->ringkas ?: \Illuminate\Support\Str::limit(strip_tags((string) $karakter->definisi), 150))

@php
    $dalilQuran = array_values(array_filter((array) ($karakter->dalil_quran ?? []), fn ($d) => filled($d['arab'] ?? null) || filled($d['terjemahan'] ?? null)));
    $dalilHadits = array_values(array_filter((array) ($karakter->dalil_hadits ?? []), fn ($d) => filled($d['arab'] ?? null) || filled($d['terjemahan'] ?? null)));
    $hikmah = array_values(array_filter((array) ($karakter->hikmah ?? []), fn ($s) => filled($s)));
    $studiKasus = $karakter->studiKasusList();
    $tips = array_values(array_filter((array) ($karakter->tips_amal ?? []), fn ($s) => filled($s)));
@endphp

@section('content')
<section class="bg-slate-50 py-10 dark:bg-slate-950">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <div class="mb-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400" data-reveal="up">
            <a href="{{ route('public.karakter.index') }}" class="font-semibold text-emerald-600 hover:underline dark:text-emerald-300">29 Karakter Luhur</a>
            <span>/</span>
            <span class="text-slate-700 dark:text-slate-200">{{ $karakter->nama }}</span>
        </div>

        {{-- Header --}}
        <div class="pkg-public-hero-card p-6 sm:p-8 mb-6" data-reveal="zoom">
            <div class="relative z-10 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-xl font-black text-white">{{ $karakter->nomor }}</span>
                        <div>
                            <h1 class="pkg-page-heading !mb-0">{{ $karakter->nama }}</h1>
                            @if($karakter->kategori)
                                <span class="mt-1 inline-flex rounded-full bg-white/70 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $karakter->kategori }}</span>
                            @endif
                        </div>
                    </div>
                    @if($karakter->ringkas)
                        <p class="pkg-page-subheading mt-4">{{ $karakter->ringkas }}</p>
                    @endif
                </div>
                @if($karakter->nama_arab)
                    <span class="text-3xl font-bold text-slate-800 dark:text-slate-100" dir="rtl">{{ $karakter->nama_arab }}</span>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            {{-- Definisi / penjelasan --}}
            @if($karakter->definisi || $karakter->deskripsi)
                <div class="pkg-panel p-5 sm:p-6" data-reveal="up">
                    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Penjelasan</h2>
                    @if($karakter->definisi)
                        <p class="leading-7 text-slate-600 dark:text-slate-300">{{ $karakter->definisi }}</p>
                    @endif
                    @if($karakter->deskripsi && $karakter->deskripsi !== $karakter->definisi)
                        <p class="mt-3 leading-7 text-slate-600 dark:text-slate-300">{{ $karakter->deskripsi }}</p>
                    @endif
                </div>
            @endif

            {{-- Dalil Al-Qur'an --}}
            @if(count($dalilQuran))
                <div class="pkg-panel p-5 sm:p-6" data-reveal="up">
                    <h2 class="mb-4 text-lg font-bold text-emerald-700 dark:text-emerald-300">Dalil Al-Qur'an</h2>
                    <div class="space-y-4">
                        @foreach($dalilQuran as $d)
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                                @if(!empty($d['arab']))
                                    <p class="text-right text-2xl leading-loose text-slate-900 dark:text-white" dir="rtl">{{ $d['arab'] }}</p>
                                @endif
                                @if(!empty($d['terjemahan']))
                                    <p class="mt-2 leading-7 text-slate-600 dark:text-slate-300">"{{ $d['terjemahan'] }}"</p>
                                @endif
                                @if(!empty($d['sumber']))
                                    <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">— {{ $d['sumber'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Dalil Hadits --}}
            @if(count($dalilHadits))
                <div class="pkg-panel p-5 sm:p-6" data-reveal="up">
                    <h2 class="mb-4 text-lg font-bold text-sky-700 dark:text-sky-300">Dalil Hadits</h2>
                    <div class="space-y-4">
                        @foreach($dalilHadits as $d)
                            <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-900/50 dark:bg-sky-950/30">
                                @if(!empty($d['arab']))
                                    <p class="text-right text-2xl leading-loose text-slate-900 dark:text-white" dir="rtl">{{ $d['arab'] }}</p>
                                @endif
                                @if(!empty($d['terjemahan']))
                                    <p class="mt-2 leading-7 text-slate-600 dark:text-slate-300">"{{ $d['terjemahan'] }}"</p>
                                @endif
                                @if(!empty($d['sumber']))
                                    <p class="mt-1 text-sm font-semibold text-sky-600 dark:text-sky-400">— {{ $d['sumber'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Contoh penerapan (studi kasus) --}}
            @if(count($studiKasus))
                <div class="pkg-panel p-5 sm:p-6" data-reveal="up">
                    <h2 class="mb-4 text-lg font-bold text-amber-700 dark:text-amber-300">Contoh Penerapan</h2>
                    <ul class="space-y-2">
                        @foreach($studiKasus as $s)
                            <li class="flex gap-3 rounded-lg bg-amber-50/60 p-3 text-slate-700 dark:bg-amber-950/20 dark:text-slate-300">
                                <span class="mt-0.5 flex-shrink-0 text-amber-500">✓</span>
                                <span class="leading-6">{{ $s }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Hikmah --}}
            @if(count($hikmah))
                <div class="pkg-panel p-5 sm:p-6" data-reveal="up">
                    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Hikmah</h2>
                    <ul class="space-y-2">
                        @foreach($hikmah as $s)
                            <li class="flex gap-3 text-slate-600 dark:text-slate-300"><span class="mt-0.5 text-emerald-500">•</span><span class="leading-6">{{ $s }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Tips amal --}}
            @if(count($tips))
                <div class="pkg-panel p-5 sm:p-6" data-reveal="up">
                    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-white">Tips Mengamalkan</h2>
                    <ul class="space-y-2">
                        @foreach($tips as $s)
                            <li class="flex gap-3 text-slate-600 dark:text-slate-300"><span class="mt-0.5 text-sky-500">→</span><span class="leading-6">{{ $s }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Navigasi prev/next --}}
        <div class="mt-8 flex items-center justify-between gap-3" data-reveal="up">
            @if($prev)
                <a href="{{ route('public.karakter.show', $prev->slug) }}" class="btn-secondary flex-1 justify-start sm:flex-none">
                    <span class="truncate">← {{ $prev->nomor }}. {{ $prev->nama }}</span>
                </a>
            @else
                <span></span>
            @endif
            @if($next)
                <a href="{{ route('public.karakter.show', $next->slug) }}" class="btn-secondary flex-1 justify-end sm:flex-none">
                    <span class="truncate">{{ $next->nomor }}. {{ $next->nama }} →</span>
                </a>
            @endif
        </div>

        <div class="mt-4 text-center" data-reveal="up">
            <a href="{{ route('public.karakter.index') }}" class="text-sm font-semibold text-emerald-600 hover:underline dark:text-emerald-300">Kembali ke daftar 29 karakter</a>
        </div>
    </div>
</section>
@endsection
