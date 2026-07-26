@extends('layouts.guru')

@section('title', 'Jadwal Guru')

@section('content')
<div class="space-y-5">
    <header><p class="text-sm font-bold text-emerald-600">Penugasan pribadi</p><h1 class="mt-1 text-2xl font-black">Jadwal Saya</h1><p class="mt-1 text-sm text-gray-500">Hanya jadwal yang sudah diterbitkan Admin.</p></header>
    <div class="space-y-3">
        @forelse($assignments as $assignment)
            @php
                $session = $assignment->session;
            @endphp
            <a href="{{ route('guru.schedule.show', $assignment) }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 hover:border-emerald-400 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $session->session_date->translatedFormat('F Y') }}</p><h2 class="mt-1 font-black">{{ $session->session_date->translatedFormat('l, d F Y') }}</h2><p class="mt-1 text-sm text-gray-500">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} WIB · {{ $session->location ?: 'Lokasi menyusul' }}</p></div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $assignment->role === 'main' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">{{ $assignment->role === 'main' ? 'Utama' : 'Cadangan' }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-sm dark:border-gray-800"><span class="font-bold">Rombel {{ strtoupper($session->rombel) }}</span><span class="text-gray-500">{{ $session->materials->count() }} materi</span></div>
            </a>
        @empty
            <section class="pkg-empty-state"><h2 class="pkg-empty-title">Belum ada jadwal</h2><p class="pkg-empty-copy">Jadwal terbit yang ditugaskan kepada Anda akan tampil di sini.</p></section>
        @endforelse
    </div>
    @if($assignments->hasPages()){{ $assignments->links() }}@endif
</div>
@endsection
