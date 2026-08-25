@extends('layouts.ortu')

@section('title', 'Kehadiran PKG')

@section('content')
@php
    $hadir = (int) ($statusCounts['hadir'] ?? 0);
    $terlambat = (int) ($statusCounts['terlambat'] ?? 0);
    $izin = (int) ($statusCounts['izin'] ?? 0);
    $sakit = (int) ($statusCounts['sakit'] ?? 0);
    $alpha = (int) ($statusCounts['alpha'] ?? 0);
    $ikutSerta = $hadir + $terlambat;
    $persentase = $totalKegiatan > 0 ? round($ikutSerta / $totalKegiatan * 100) : 0;

    $statusTone = [
        'hadir' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
        'terlambat' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
        'izin' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200',
        'sakit' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
        'alpha' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
    ];
@endphp
<div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 sm:py-6">
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kehadiran PKG</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Rincian kehadiran {{ $siswa->nama }} beserta poin yang masuk ke leaderboard.</p>
    </div>

    {{-- Ringkasan utama --}}
    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pkg-panel p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Kegiatan</p>
            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $totalKegiatan }}</p>
        </div>
        <div class="pkg-panel p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Hadir</p>
            <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $hadir }}</p>
            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">+{{ $terlambat }} terlambat</p>
        </div>
        <div class="pkg-panel p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-600 dark:text-teal-300">Tingkat Ikut Serta</p>
            <p class="mt-1 text-2xl font-black text-teal-700 dark:text-teal-300">{{ $persentase }}%</p>
            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $ikutSerta }} dari {{ $totalKegiatan }} kegiatan</p>
        </div>
        <div class="pkg-panel p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Poin Leaderboard</p>
            <p class="mt-1 text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ number_format($totalPoints) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">dari {{ $totalHadir }} kali presensi berpoin</p>
        </div>
    </div>

    {{-- Rincian status --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Rincian Status</h2>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach(['hadir' => $hadir, 'terlambat' => $terlambat, 'izin' => $izin, 'sakit' => $sakit, 'alpha' => $alpha] as $key => $value)
                <div class="rounded-xl p-3 text-center {{ $statusTone[$key] }}">
                    <p class="text-lg font-black">{{ $value }}</p>
                    <p class="text-[11px] font-semibold">{{ $statusLabels[$key] ?? $key }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Poin kehadiran otomatis ditambahkan ke total poin ananda di leaderboard setiap kali presensi tercatat.
        </p>
    </div>

    {{-- Riwayat per kegiatan --}}
    <div class="pkg-panel overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white">Riwayat Presensi</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Tanggal kegiatan, jam scan, waktu pencatatan, dan poin yang masuk.</p>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($presensi as $p)
                @php
                    $poin = ($pointsByPresensi[$p->id] ?? collect())->sum('points');
                    $poinRow = ($pointsByPresensi[$p->id] ?? collect())->first();
                @endphp
                <div class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $p->tanggal->translatedFormat('l, d F Y') }}
                            </p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $statusTone[$p->status] ?? $statusTone['alpha'] }}">
                                    {{ $statusLabels[$p->status] ?? $p->status }}
                                </span>
                                @if($p->is_verified)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Terverifikasi</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            @if($poin > 0)
                                <p class="text-lg font-black text-emerald-600 dark:text-emerald-400">+{{ $poin }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">poin leaderboard</p>
                            @else
                                <p class="text-sm font-bold text-gray-400 dark:text-gray-500">0</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">tanpa poin</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs sm:grid-cols-3">
                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/60">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Jam Scan Masuk</p>
                            <p class="mt-0.5 font-bold text-gray-900 dark:text-white">{{ $p->jam_masuk ? $p->jam_masuk->format('H:i') : '—' }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/60">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dicatat Sistem</p>
                            <p class="mt-0.5 font-bold text-gray-900 dark:text-white">{{ $p->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/60">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Poin Dicatat</p>
                            <p class="mt-0.5 font-bold text-gray-900 dark:text-white">{{ $poinRow?->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                        </div>
                    </div>

                    @if($p->keterangan)
                        <p class="mt-2 rounded-lg bg-amber-50 p-2.5 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">{{ $p->keterangan }}</p>
                    @endif
                </div>
            @empty
                <div class="px-5 py-10 text-center text-gray-600 dark:text-gray-400">
                    Belum ada data presensi PKG untuk ananda.
                </div>
            @endforelse
        </div>

        @if($presensi->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            {{ $presensi->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
