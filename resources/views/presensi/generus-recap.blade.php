@extends('layouts.app')

@section('title', 'Rekap Generus PKG')

@section('content')
<div class="mx-auto max-w-7xl space-y-3 px-4 py-5 sm:space-y-4 sm:px-6 lg:px-8">
    <x-breadcrumb :items="[
        ['title' => 'Presensi', 'url' => route('presensi.index', ['tab' => 'rekap'])],
        ['title' => 'Rekap Generus PKG'],
    ]" />

    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Rekap Generus PKG</h1>
            <p class="pkg-page-subheading">
                Ringkasan Tugas PKG, kehadiran, dan Target RPP per kelompok. Scope: {{ $scopeLabel }}.
            </p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('presensi.index', ['tab' => 'rekap']) }}" class="btn-secondary px-4 py-2">
                Kembali ke Presensi
            </a>
        </div>
    </div>

    <x-collapsible-section title="Filter Rekap Generus" description="Atur periode, semester target RPP, dan kelompok." compact>
        <form action="{{ route('presensi.generus-recap') }}" method="GET" class="pkg-filter-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="start_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
                <input id="start_date" name="start_date" type="date" value="{{ $startDate->format('Y-m-d') }}" class="w-full pkg-field">
            </div>
            <div>
                <label for="end_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Selesai</label>
                <input id="end_date" name="end_date" type="date" value="{{ $endDate->format('Y-m-d') }}" class="w-full pkg-field">
            </div>
            <div>
                <label for="semester" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Semester Target RPP</label>
                <select id="semester" name="semester" class="w-full pkg-field">
                    @foreach($semesterOptions as $value => $label)
                        <option value="{{ $value }}" {{ $semester === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="kelompok" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelompok</label>
                <select id="kelompok" name="kelompok" class="w-full pkg-field">
                    <option value="">Semua Kelompok</option>
                    @foreach($kelompokOptions as $value => $label)
                        <option value="{{ $value }}" {{ $selectedKelompok === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1 px-4 py-2">Terapkan</button>
                <a href="{{ route('presensi.generus-recap') }}" class="btn-secondary px-4 py-2">Reset</a>
            </div>
        </form>
    </x-collapsible-section>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="pkg-card p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Generus</p>
            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $totals['total_students'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Generus aktif dalam scope</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tugas PKG Terverifikasi</p>
            <p class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $totals['task']['verified'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $totals['task']['student_count'] }} Generus, {{ $totals['task']['pending'] }} menunggu verifikasi</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Catatan Kehadiran</p>
            <p class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400">{{ $totals['attendance']['present'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $totals['attendance']['percentage'] }}% dari {{ $totals['attendance']['records'] }} catatan periode</p>
        </div>
        <div class="pkg-card p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Target RPP Selesai</p>
            <p class="mt-1 text-2xl font-black text-violet-600 dark:text-violet-400">{{ $totals['rpp']['completed'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $totals['rpp']['completed'] }} dari {{ $totals['rpp']['expected'] }} target semester</p>
        </div>
    </div>

    <div class="pkg-card-soft p-4 text-sm text-gray-600 dark:text-gray-300">
        Tugas PKG dan kehadiran dihitung untuk periode {{ $startDate->format('d/m/Y') }}–{{ $endDate->format('d/m/Y') }}.
        Target RPP bersifat kumulatif untuk {{ $semesterOptions[$semester] }} dan menyesuaikan level setiap Generus.
    </div>

    @if($totals['total_students'] === 0)
        <div class="pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H2v-2a4 4 0 014-4h3m4-7a4 4 0 11-8 0 4 4 0 018 0zm6 4a3 3 0 100-6" />
            </svg>
            <h2 class="pkg-empty-title">Belum ada Generus dalam scope</h2>
            <p class="pkg-empty-copy">Periksa pembagian kelompok, status aktif siswa, atau penugasan siswa binaan.</p>
        </div>
    @else
        <x-collapsible-section title="Rekap per Kelompok" description="Persentase memakai pembagi pada masing-masing indikator." compact>
            <div class="overflow-x-auto pkg-mobile-table">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Kelompok</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Tugas PKG</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Kehadiran</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Target RPP</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($rows as $row)
                            <tr>
                                <td data-label="Kelompok" class="px-5 py-4 pkg-mobile-main">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $row['label'] }}</div>
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $row['total_students'] }} Generus aktif</div>
                                </td>
                                <td data-label="Tugas PKG" class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $row['task']['verified'] }} / {{ $row['task']['submitted'] }}</span>
                                        <span class="pkg-status-badge pkg-status-success">{{ $row['task']['percentage'] }}%</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['task']['student_count'] }} Generus terverifikasi; {{ $row['task']['pending'] }} tugas menunggu.
                                    </div>
                                </td>
                                <td data-label="Kehadiran" class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $row['attendance']['present'] }} / {{ $row['attendance']['records'] }}</span>
                                        <span class="pkg-status-badge pkg-status-info">{{ $row['attendance']['percentage'] }}%</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['attendance']['student_count'] }} Generus pernah hadir; {{ $row['attendance']['absent'] }} catatan tidak hadir/izin/sakit.
                                    </div>
                                </td>
                                <td data-label="Target RPP" class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $row['rpp']['completed'] }} / {{ $row['rpp']['expected'] }}</span>
                                        <span class="pkg-status-badge pkg-status-warning">{{ $row['rpp']['percentage'] }}%</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['rpp']['student_count'] }} Generus sudah ceklis; {{ $row['rpp']['remaining'] }} target tersisa.
                                        @if($row['rpp']['without_grade'] > 0)
                                            {{ $row['rpp']['without_grade'] }} Generus belum memiliki level.
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @if($rows->count() > 1)
                        <tfoot class="bg-gray-50 dark:bg-gray-800/80">
                            <tr>
                                <td class="px-5 py-4 font-bold text-gray-900 dark:text-white">Total {{ $totals['total_students'] }} Generus</td>
                                <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ $totals['task']['verified'] }} / {{ $totals['task']['submitted'] }} ({{ $totals['task']['percentage'] }}%)</td>
                                <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ $totals['attendance']['present'] }} / {{ $totals['attendance']['records'] }} ({{ $totals['attendance']['percentage'] }}%)</td>
                                <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">{{ $totals['rpp']['completed'] }} / {{ $totals['rpp']['expected'] }} ({{ $totals['rpp']['percentage'] }}%)</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-collapsible-section>
    @endif
</div>
@endsection
