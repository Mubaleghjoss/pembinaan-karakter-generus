@extends('layouts.app')

@section('title', 'Ekspor Data')

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header">
            <div>
                <h1 class="pkg-page-heading">Ekspor Data</h1>
                <p class="pkg-page-subheading">Unduh data operasional dalam format CSV atau Excel sesuai kebutuhan laporan.</p>
            </div>
        </div>

        <div class="pkg-filter-bar mb-6">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="pkg-card-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Siswa aktif</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['active_siswa_count'] ?? 0) }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Kelas</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['kelas_count'] ?? 0) }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Presensi bulan ini</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['presensi_this_month_count'] ?? 0) }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Periode poin</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['period_count'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <section class="pkg-panel p-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Presensi Harian</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Data presensi mentah berdasarkan rentang tanggal.</p>
                </div>
                <form action="{{ route('export.presensi') }}" method="GET" class="pkg-filter-grid">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Dari tanggal</label>
                        <input type="date" name="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="pkg-field w-full px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Sampai tanggal</label>
                        <input type="date" name="end_date" value="{{ now()->format('Y-m-d') }}" class="pkg-field w-full px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary w-full justify-center">Unduh Presensi CSV</button>
                    </div>
                </form>
            </section>

            <section class="pkg-panel p-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Rekap Presensi</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ringkasan hadir, terlambat, izin, sakit, dan alpha per siswa.</p>
                </div>
                <form action="{{ route('export.rekap-presensi') }}" method="GET" class="pkg-filter-grid">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Dari tanggal</label>
                        <input type="date" name="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="pkg-field w-full px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Sampai tanggal</label>
                        <input type="date" name="end_date" value="{{ now()->format('Y-m-d') }}" class="pkg-field w-full px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Kelas</label>
                        <select name="kelas_id" class="pkg-field w-full px-3 py-2">
                            <option value="">Semua kelas</option>
                            @foreach($kelas as $item)
                                <option value="{{ $item->id }}">{{ $item->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary w-full justify-center">Unduh Rekap CSV</button>
                    </div>
                </form>
            </section>

            <section class="pkg-panel p-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Data Siswa</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Daftar siswa aktif beserta kelas, kelompok, kontak siswa, dan kontak wali.</p>
                </div>
                <form action="{{ route('export.siswa') }}" method="GET" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Kelas</label>
                        <select name="kelas_id" class="pkg-field w-full px-3 py-2">
                            <option value="">Semua kelas</option>
                            @foreach($kelas as $item)
                                <option value="{{ $item->id }}">{{ $item->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Unduh Data Siswa CSV</button>
                </form>
            </section>

            <section class="pkg-panel p-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Poin dan Peringkat</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Laporan Excel untuk leaderboard dan periode poin.</p>
                </div>
                <div class="space-y-4">
                    <form action="{{ route('export.leaderboard') }}" method="GET" class="space-y-3">
                        <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Periode</label>
                        <select name="period_id" class="pkg-field w-full px-3 py-2">
                            <option value="">Total semua periode</option>
                            @foreach($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->name }} - {{ ucfirst($period->status) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-primary w-full justify-center">Unduh Leaderboard Excel</button>
                    </form>
                    <a href="{{ route('export.period-collection') }}" class="btn-secondary flex w-full justify-center">Unduh Kumpulan Periode Excel</a>
                </div>
            </section>

            <section class="pkg-panel p-5 lg:col-span-2">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Rekap Karakter</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Progres tugas PKG dan karakter siswa.</p>
                    </div>
                    <a href="{{ route('tugas-pkg.export') }}" class="btn-secondary justify-center">Unduh Rekap Karakter CSV</a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
