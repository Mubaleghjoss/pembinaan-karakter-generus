@extends('layouts.app')

@section('title', 'Ringkasan Presensi Aktif')

@section('content')
@php
    $totalTarget = $participants->count();
    $filledCount = $filledParticipants->count();
    $missingCount = $missingParticipants->count();
    $filledPercent = $totalTarget > 0 ? round(($filledCount / $totalTarget) * 100, 1) : 0;
    $statusLabels = [
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpha' => 'Alpha',
        'belum' => 'Belum',
    ];
    $statusClasses = [
        'hadir' => 'bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-200',
        'terlambat' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-200',
        'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200',
        'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-200',
        'alpha' => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200',
        'belum' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    ];
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Ringkasan Presensi Aktif</h1>
            <p class="pkg-page-subheading">
                Pantau peserta yang sudah dan belum presensi untuk target {{ $targetLabel }} pada {{ $date->format('d M Y') }}.
            </p>
        </div>
        <div class="pkg-page-actions">
            @if(!auth()->user()->isAdmin())
                <a href="{{ route('attendance-schedule.index') }}" class="btn-secondary px-4 py-2 text-sm">Jadwal Presensi</a>
            @endif
            @if($includeSiswa)
                <a href="{{ route('presensi.index', ['date' => $date->format('Y-m-d')]) }}" class="btn-secondary px-4 py-2 text-sm">Detail Siswa</a>
            @endif
            @if($includePamong)
                <a href="{{ route('pamong-presensi.index', ['start_date' => $date->format('Y-m-d'), 'end_date' => $date->format('Y-m-d')]) }}" class="btn-primary px-4 py-2 text-sm">Detail Pamong</a>
            @endif
        </div>
    </div>

    <div class="pkg-filter-bar">
        <form method="GET" action="{{ route('pamong-presensi.summary') }}" class="pkg-filter-grid items-end">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="pkg-field">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary px-4 py-2 text-sm">Tampilkan</button>
                <a href="{{ route('pamong-presensi.summary') }}" class="btn-secondary px-4 py-2 text-sm">Hari Ini</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="pkg-card p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Target Presensi</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $totalTarget }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $targetLabel }}</p>
        </div>
        <div class="pkg-card p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Sudah Presensi</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ $filledCount }}</p>
        </div>
        <div class="pkg-card p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum Presensi</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ $missingCount }}</p>
        </div>
        <div class="pkg-card p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Capaian</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $filledPercent }}%</p>
        </div>
    </div>

    <div class="pkg-card p-5">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kegiatan Aktif</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Jadwal aktif menentukan siapa yang wajib mengisi presensi.</p>
            </div>
        </div>

        @forelse($activeSchedules as $schedule)
            <div class="mb-3 rounded-xl border border-slate-200 bg-slate-50 p-4 last:mb-0 dark:border-slate-800 dark:bg-slate-900/70">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ $schedule->name }}</h3>
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">
                                {{ $schedule->targetLabel() }}
                            </span>
                        </div>
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $schedule->description ?: 'Belum ada deskripsi kegiatan.' }}</p>
                    </div>
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        {{ \Carbon\Carbon::parse($schedule->open_time)->format('H:i') }}
                        -
                        {{ \Carbon\Carbon::parse($schedule->close_time)->format('H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <div class="pkg-empty-state">
                <div class="pkg-empty-icon">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="pkg-empty-title">Belum ada jadwal aktif</h3>
                <p class="pkg-empty-copy">Aktifkan jadwal presensi agar target ringkasan bisa dihitung.</p>
            </div>
        @endforelse
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-6">
        @foreach($statusLabels as $status => $label)
            <div class="rounded-xl px-4 py-3 {{ $statusClasses[$status] }}">
                <p class="text-xs font-semibold uppercase">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold">{{ $statusCounts[$status] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    @if($includeSiswa)
        <div class="pkg-card p-5">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Ringkasan Kelompok Siswa</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total target siswa dan capaian presensi per kelompok.</p>
            </div>

            @if($studentGroupSummary->isEmpty())
                <div class="pkg-empty-state">
                    <h3 class="pkg-empty-title">Belum ada pembagian kelompok</h3>
                    <p class="pkg-empty-copy">Tetapkan kelompok siswa agar ringkasan per wilayah bisa ditampilkan.</p>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($studentGroupSummary as $group)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ $group['label'] }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Total {{ $group['total'] }} siswa</p>
                                </div>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">
                                    {{ $group['percent'] }}%
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                <div class="rounded-lg bg-white p-3 dark:bg-slate-950/60">
                                    <p class="text-gray-500 dark:text-gray-400">Sudah</p>
                                    <p class="text-xl font-bold text-green-600">{{ $group['filled'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white p-3 dark:bg-slate-950/60">
                                    <p class="text-gray-500 dark:text-gray-400">Belum</p>
                                    <p class="text-xl font-bold text-amber-600">{{ $group['missing'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white p-3 dark:bg-slate-950/60">
                                    <p class="text-gray-500 dark:text-gray-400">Hadir</p>
                                    <p class="text-xl font-bold text-green-600">{{ $group['hadir'] }}</p>
                                </div>
                                <div class="rounded-lg bg-white p-3 dark:bg-slate-950/60">
                                    <p class="text-gray-500 dark:text-gray-400">Terlambat</p>
                                    <p class="text-xl font-bold text-yellow-600">{{ $group['terlambat'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="pkg-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Sudah Presensi</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $filledCount }} peserta sudah memiliki record presensi.</p>
            </div>

            <div class="overflow-x-auto pkg-mobile-table">
                <table class="min-w-[760px] divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Peserta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Target</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Jam</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($filledParticipants as $participant)
                            @php($attendance = $attendanceByParticipant->get($participant['key']))
                            <tr>
                                <td data-label="Peserta" class="px-4 py-3 pkg-mobile-main">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $participant['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $participant['identifier'] }}</div>
                                </td>
                                <td data-label="Target" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $participant['type_label'] }}</span>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $participant['unit'] }}
                                        @if($participant['detail'])
                                            - {{ $participant['detail'] }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Jam" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    {{ $attendance?->jam_masuk?->format('H:i') ?? '-' }}
                                    @if($attendance?->jam_keluar)
                                        <span class="text-gray-400">/ {{ $attendance->jam_keluar->format('H:i') }}</span>
                                    @endif
                                </td>
                                <td data-label="Status" class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$attendance?->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $statusLabels[$attendance?->status] ?? ucfirst($attendance?->status ?? '-') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400 pkg-mobile-empty">Belum ada peserta yang presensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pkg-card overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Belum Presensi</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $missingCount }} peserta belum memiliki record presensi.</p>
            </div>

            <div class="overflow-x-auto pkg-mobile-table">
                <table class="min-w-[760px] divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Peserta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Target</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($missingParticipants as $participant)
                            <tr>
                                <td data-label="Peserta" class="px-4 py-3 pkg-mobile-main">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $participant['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $participant['identifier'] }}</div>
                                </td>
                                <td data-label="Target" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $participant['type_label'] }}</span>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $participant['unit'] }}
                                        @if($participant['detail'])
                                            - {{ $participant['detail'] }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Status" class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses['belum'] }}">Belum</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400 pkg-mobile-empty">Semua peserta target sudah memiliki record presensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
