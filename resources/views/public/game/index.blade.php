@extends('layouts.public')

@section('title', 'Coba Game 29 Karakter')
@section('og_title', 'Coba Game 29 Karakter Luhur')
@section('og_description', 'Main Rangkai Kata & Tebak Karakter tanpa akun. Login untuk kumpulkan poin.')

@section('content')
<div class="bg-slate-50 py-8 dark:bg-slate-950 sm:py-10">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Mode coba (tanpa akun)</p>
            <h1 class="pkg-page-heading mt-2">Game 29 Karakter Luhur</h1>
            <p class="pkg-page-subheading mt-2">Main sepuasnya untuk belajar. Poin hanya tercatat kalau kamu login sebagai siswa.</p>
        </div>

        @if(session('error'))
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">{{ session('error') }}</div>
        @endif

        {{-- Banner ajakan login --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Mau poinnya dihitung dan masuk peringkat? Login dulu sebagai siswa.</p>
            <a href="{{ route('siswa.login') }}" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-700">Login Siswa</a>
        </div>

        @if($charCount < 4)
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
                        <h2 class="font-bold text-gray-900 dark:text-white">Rangkai Kata</h2>
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
                        <h2 class="font-bold text-gray-900 dark:text-white">Tebak Karakter</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pilih karakter tepat dari studi kasus.</p>
                    </div>
                </div>
                <a href="{{ route('public.game.play', 'tebak') }}" class="inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Coba Sekarang</a>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">Mode duel vs AI, lawan teman, dan Boss Online tersedia setelah login siswa.</p>
        @endif
    </div>
</div>
@endsection
