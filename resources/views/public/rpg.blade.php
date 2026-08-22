@php
    $themeLabels = [
        'grass' => 'Padang Rumput',
        'desert' => 'Gurun',
        'castle' => 'Kastil',
        'forest' => 'Hutan',
        'snow' => 'Salju',
    ];

    $themeStyles = [
        'grass' => 'from-emerald-400 via-green-500 to-lime-500',
        'desert' => 'from-amber-300 via-orange-400 to-yellow-500',
        'castle' => 'from-slate-500 via-slate-700 to-gray-900',
        'forest' => 'from-green-700 via-emerald-800 to-teal-900',
        'snow' => 'from-sky-200 via-cyan-300 to-blue-500',
    ];

    $difficultyLabels = [
        'easy' => 'Santai',
        'medium' => 'Seru',
        'hard' => 'Tantangan',
    ];

    $difficultyStyles = [
        'easy' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
        'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'hard' => 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200',
    ];
@endphp

@extends('layouts.public')

@section('title', 'Game 29 Karakter')
@section('og_title', 'Game 29 Karakter PKG Panunggangan')
@section('og_description', 'Pilih map dan mainkan RPG Quest 3D sebagai tamu.')

@section('content')
<div class="bg-slate-50 py-8 dark:bg-slate-950 sm:py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header mb-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-300">Mode tamu</p>
                <h1 class="pkg-page-heading mt-2">Game 29 Karakter</h1>
                <p class="pkg-page-subheading mt-2">
                    Main game edukatif dan petualangan 3D langsung sebagai tamu. Login siswa untuk kumpulkan poin & naik peringkat.
                </p>
            </div>
            <div class="pkg-page-actions">
                @if(Auth::guard('siswa')->check())
                    <a href="{{ route('siswa.game.index') }}" class="btn-primary">
                        Masuk Game Siswa
                    </a>
                @else
                    <a href="{{ route('siswa.login') }}" class="btn-primary">
                        Login untuk simpan poin
                    </a>
                @endif
                <a href="{{ route('public.index') }}" class="btn-secondary">
                    Beranda
                </a>
            </div>
        </div>

        {{-- ========================= GAME EDUKATIF ========================= --}}
        <div class="mb-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Game Edukatif</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-200">{{ $karakterCount ?? 0 }} karakter</span>
            </div>

            @if(($karakterCount ?? 0) < 4)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                    Bank karakter belum cukup untuk dimainkan. Coba lagi nanti.
                </div>
            @else
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/></svg>
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Rangkai Kata</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Susun huruf jadi nama karakter dari petunjuk.</p>
                        </div>
                    </div>
                    <a href="{{ route('public.game.play', 'rangkai') }}" class="inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Coba Sekarang</a>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Tebak Karakter</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pilih karakter tepat dari studi kasus.</p>
                        </div>
                    </div>
                    <a href="{{ route('public.game.play', 'tebak') }}" class="inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Coba Sekarang</a>
                </div>
            </div>
            @if(!Auth::guard('siswa')->check())
                <p class="mt-3 text-center text-xs text-gray-400">Mode duel vs AI, lawan teman, dan Boss Online tersedia setelah login siswa.</p>
            @endif
            @endif
        </div>

        {{-- ========================= PETUALANGAN 3D ========================= --}}
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Petualangan 3D</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Jelajahi peta 3D, temui NPC, jawab tantangan.</p>
            </div>
            @if(!Auth::guard('siswa')->check())
                <a href="{{ route('siswa.login') }}" class="btn-secondary">
                    Login untuk simpan poin
                </a>
            @endif
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Map 3D</p>
                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $maps->count() }}</p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Map aktif yang bisa dicoba tanpa akun.</p>
            </div>
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">NPC</p>
                <p class="mt-3 text-3xl font-black text-emerald-600 dark:text-emerald-300">{{ $totalChallenges }}</p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Tantangan yang muncul di arena.</p>
            </div>
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Gameplay</p>
                <p class="mt-3 text-3xl font-black text-sky-600 dark:text-sky-300">3D</p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Gerak, musuh, pickup, dan tembakan otomatis seirama dengan siswa.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($maps as $map)
                <article class="pkg-panel overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="relative h-32 overflow-hidden bg-gradient-to-br {{ $themeStyles[$map->background_theme] ?? 'from-slate-500 via-slate-600 to-slate-800' }}">
                        <div class="absolute inset-0 bg-black/10"></div>
                        <div class="absolute left-4 top-4">
                            <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-800 shadow-sm">
                                {{ $themeLabels[$map->background_theme] ?? 'Tema Map' }}
                            </span>
                        </div>
                        <div class="absolute right-4 top-4">
                            <span class="rounded-full bg-slate-950/45 px-3 py-1 text-xs font-bold text-white backdrop-blur">
                                {{ $map->grid_size }} x {{ $map->grid_size }}
                            </span>
                        </div>
                        <div class="absolute bottom-4 left-4 right-4">
                            <h3 class="line-clamp-1 text-xl font-black text-white drop-shadow">{{ $map->nama }}</h3>
                        </div>
                    </div>

                    <div class="p-4">
                        <p class="line-clamp-2 min-h-[2.5rem] text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {{ $map->deskripsi ?: 'Jelajahi peta, jawab NPC, ambil pickup, dan hindari musuh di arena 3D.' }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $difficultyStyles[$map->difficulty] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200' }}">
                                {{ $difficultyLabels[$map->difficulty] ?? 'Seru' }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700 dark:bg-sky-950 dark:text-sky-200">
                                {{ $map->npc_count }} NPC
                            </span>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                Mode 3D
                            </span>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @if(Auth::guard('siswa')->check())
                                <a href="{{ route('siswa.rpg.play', $map) }}" class="btn-primary">
                                    Main Versi Siswa
                                </a>
                            @else
                                <a href="{{ route('public.rpg.play', $map) }}?view=3d" class="btn-primary">
                                    Main 3D Tanpa Akun
                                </a>
                                <a href="{{ route('siswa.login') }}" class="btn-secondary">
                                    Simpan Poin
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="pkg-empty-state col-span-full">
                    <div class="pkg-empty-icon">MAP</div>
                    <h2 class="pkg-empty-title">Belum ada map aktif</h2>
                    <p class="pkg-empty-copy">
                        Map RPG akan muncul di sini setelah admin mengaktifkannya.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
