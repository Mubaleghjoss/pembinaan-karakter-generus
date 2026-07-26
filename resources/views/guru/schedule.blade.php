@extends('layouts.guru')

@section('title', 'Jadwal Guru')

@section('content')
<div class="space-y-5">
    <header>
        <p class="text-sm font-bold text-emerald-600">Penugasan pribadi</p>
        <h1 class="mt-1 text-2xl font-black">Jadwal Saya</h1>
        <p class="mt-1 text-sm text-gray-500">Konfirmasi dan permohonan perubahan dapat dilakukan dari setiap detail jadwal.</p>
    </header>

    <div class="space-y-3">
        @forelse($assignments as $assignment)
            @php
                $session = $assignment->session;
                $latestRequest = $assignment->requests->first();
            @endphp
            <article class="pkg-card p-4 transition hover:border-emerald-400 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $session->session_date->translatedFormat('F Y') }}</p>
                        <h2 class="mt-1 font-black">{{ $session->session_date->translatedFormat('l, d F Y') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} WIB · {{ $session->location ?: 'Lokasi menyusul' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $assignment->role === 'main' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">{{ $assignment->role === 'main' ? 'Utama' : 'Cadangan' }}</span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-gray-100 pt-3 text-sm dark:border-gray-800">
                    <div><span class="block text-xs text-gray-500">Rombel</span><span class="font-bold">{{ strtoupper($session->rombel) }}</span></div>
                    <div><span class="block text-xs text-gray-500">Status</span><span class="font-bold">{{ match($assignment->confirmation_status) { 'confirmed' => 'Bersedia', 'declined' => 'Berhalangan', default => 'Menunggu' } }}</span></div>
                    <div><span class="block text-xs text-gray-500">Materi</span><span class="font-bold">{{ $session->materials->count() }}</span></div>
                </div>
                @if($latestRequest)
                    <p class="mt-3 rounded-xl bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                        {{ $latestRequest->request_type === 'reschedule' ? 'Pengajuan jadwal ulang' : 'Laporan tidak bisa mengajar' }}:
                        {{ match($latestRequest->status) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Menunggu Admin' } }}
                    </p>
                @endif
                <a href="{{ route('guru.schedule.show', $assignment) }}" class="btn-primary mt-3 w-full justify-center">Buka Detail dan Konfirmasi</a>
            </article>
        @empty
            <section class="pkg-empty-state">
                <h2 class="pkg-empty-title">Belum ada jadwal</h2>
                <p class="pkg-empty-copy">Jadwal terbit yang ditugaskan kepada Anda akan tampil di sini.</p>
            </section>
        @endforelse
    </div>

    @if($assignments->hasPages())
        {{ $assignments->links() }}
    @endif
</div>
@endsection
