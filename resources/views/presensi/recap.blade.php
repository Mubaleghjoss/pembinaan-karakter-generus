@extends('layouts.app')

@section('title', 'Laporan Presensi - PKG Presensi')

@section('content')
@php
    $statusFilter = request('status');
    $cardBaseQuery = request()->except(['status', 'page']);
    $cardFilterUrl = fn (?string $status = null) => route(
        'presensi.recap',
        array_merge($cardBaseQuery, $status ? ['status' => $status] : [])
    );
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading text-3xl">Laporan Presensi</h1>
            <p class="pkg-page-subheading">Rekapitulasi presensi siswa, pamong, dan pengurus PKG per periode.</p>
        </div>
        <div class="pkg-page-actions">
            <button type="button" onclick="window.print()" class="btn-secondary px-4 py-2">
                Cetak
            </button>
        </div>
    </div>

    <div class="pkg-filter-bar">
        <form action="{{ route('presensi.recap') }}" method="GET" class="pkg-filter-grid grid-cols-1 md:grid-cols-4 xl:grid-cols-8">
            <div>
                <label for="start_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" class="w-full pkg-field">
            </div>

            <div>
                <label for="end_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Selesai</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" class="w-full pkg-field">
            </div>

            <div>
                <label for="audience" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Data</label>
                <select name="audience" id="audience" class="w-full pkg-field">
                    <option value="all" {{ $audience === 'all' ? 'selected' : '' }}>Semua Data</option>
                    <option value="siswa" {{ $audience === 'siswa' ? 'selected' : '' }}>Siswa</option>
                    <option value="pamong" {{ $audience === 'pamong' ? 'selected' : '' }}>Pamong/Pengurus</option>
                </select>
            </div>

            <div>
                <label for="kelas_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas Siswa</label>
                <select name="kelas_id" id="kelas_id" class="w-full pkg-field">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="kelompok" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelompok Siswa</label>
                <select name="kelompok" id="kelompok" class="w-full pkg-field">
                    <option value="">Semua Kelompok</option>
                    @foreach($kelompokOptions as $value => $label)
                        <option value="{{ $value }}" {{ $kelompok === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Presensi</label>
                <select name="status" id="status" class="w-full pkg-field">
                    <option value="">Semua Status</option>
                    <option value="hadir" {{ $statusFilter === 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ $statusFilter === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin_sakit" {{ $statusFilter === 'izin_sakit' ? 'selected' : '' }}>Izin/Sakit</option>
                    <option value="izin" {{ $statusFilter === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ $statusFilter === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="alpha" {{ in_array($statusFilter, ['alpha', 'tidak_hadir'], true) ? 'selected' : '' }}>Tidak Hadir</option>
                </select>
            </div>

            <div>
                <label for="pamong_role" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Pamong</label>
                <select name="pamong_role" id="pamong_role" class="w-full pkg-field">
                    <option value="">Semua Status</option>
                    @foreach($pamongRoleOptions as $value => $label)
                        <option value="{{ $value }}" {{ $pamongRole === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="team_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bidang</label>
                <select name="team_id" id="team_id" class="w-full pkg-field">
                    <option value="">Semua Bidang</option>
                    @foreach($teamOptions as $team)
                        <option value="{{ $team->id }}" {{ $teamId === $team->id ? 'selected' : '' }}>
                            {{ $team->short_name ?: $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 md:col-span-4 xl:col-span-8">
                <button type="submit" class="btn-primary px-4 py-2">Terapkan Filter</button>
                <a href="{{ route('presensi.recap') }}" class="btn-secondary px-4 py-2">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <a href="{{ $cardFilterUrl() }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter ? '' : 'ring-2 ring-gray-400 dark:ring-gray-500' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Data</div>
            <div class="mt-1 text-3xl font-black text-gray-900 dark:text-white">{{ $recap['total'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('hadir') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'hadir' ? 'ring-2 ring-green-500' : '' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Hadir</div>
            <div class="mt-1 text-3xl font-black text-green-600">{{ $recap['hadir'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('terlambat') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'terlambat' ? 'ring-2 ring-yellow-500' : '' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Terlambat</div>
            <div class="mt-1 text-3xl font-black text-yellow-600">{{ $recap['terlambat'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('izin_sakit') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'izin_sakit' ? 'ring-2 ring-blue-500' : '' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Izin/Sakit</div>
            <div class="mt-1 text-3xl font-black text-blue-600">{{ $recap['izin'] + $recap['sakit'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('alpha') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ in_array($statusFilter, ['alpha', 'tidak_hadir'], true) ? 'ring-2 ring-red-500' : '' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Tidak Hadir</div>
            <div class="mt-1 text-3xl font-black text-red-600">{{ $recap['alpha'] }}</div>
        </a>
        <div class="pkg-card p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Persentase Hadir</div>
            <div class="mt-1 text-3xl font-black text-emerald-600">{{ $recap['persentase'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="pkg-card-soft p-4">
            <div class="text-sm font-semibold text-gray-900 dark:text-white">Siswa</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Total</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $typeRecap['siswa']['total'] }}</div>
                </div>
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Hadir</div>
                    <div class="text-xl font-bold text-green-600">{{ $typeRecap['siswa']['hadir'] + $typeRecap['siswa']['terlambat'] }}</div>
                </div>
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Tidak Hadir</div>
                    <div class="text-xl font-bold text-red-600">{{ $typeRecap['siswa']['alpha'] }}</div>
                </div>
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Persentase</div>
                    <div class="text-xl font-bold text-emerald-600">{{ $typeRecap['siswa']['persentase'] }}</div>
                </div>
            </div>
        </div>

        <div class="pkg-card-soft p-4">
            <div class="text-sm font-semibold text-gray-900 dark:text-white">Pamong/Pengurus PKG</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Total</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $typeRecap['pamong']['total'] }}</div>
                </div>
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Hadir</div>
                    <div class="text-xl font-bold text-green-600">{{ $typeRecap['pamong']['hadir'] + $typeRecap['pamong']['terlambat'] }}</div>
                </div>
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Tidak Hadir</div>
                    <div class="text-xl font-bold text-red-600">{{ $typeRecap['pamong']['alpha'] }}</div>
                </div>
                <div>
                    <div class="text-gray-500 dark:text-gray-400">Persentase</div>
                    <div class="text-xl font-bold text-emerald-600">{{ $typeRecap['pamong']['persentase'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="pkg-panel-lg overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Detail Presensi</h2>
        </div>

        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Jenis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Kelas/Bidang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Kelompok/Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Jam Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Jam Keluar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Bukti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($records as $record)
                        <tr>
                            <td data-label="Tanggal" class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $record['date'] }}
                            </td>
                            <td data-label="Jenis" class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $record['type_label'] }}
                            </td>
                            <td data-label="Nama" class="whitespace-nowrap px-6 py-4 pkg-mobile-main">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record['name'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $record['identifier'] }}</div>
                            </td>
                            <td data-label="Kelas/Bidang" class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $record['unit'] }}
                            </td>
                            <td data-label="Kelompok/Status" class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $record['group'] }}
                            </td>
                            <td data-label="Jam Masuk" class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $record['jam_masuk'] }}
                            </td>
                            <td data-label="Jam Keluar" class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $record['jam_keluar'] }}
                            </td>
                            <td data-label="Status" class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $record['status_class'] }}">
                                    {{ $record['status_label'] }}
                                </span>
                            </td>
                            <td data-label="Bukti" class="whitespace-nowrap px-6 py-4">
                                @if($record['face_proof'] ?? null)
                                    <div class="flex items-center gap-3">
                                        @if($record['face_proof']['proof_url'] ?? null)
                                            <a href="{{ $record['face_proof']['proof_url'] }}" target="_blank" rel="noopener" class="block h-12 w-12 overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900">
                                                <img src="{{ $record['face_proof']['proof_url'] }}" alt="Bukti scan wajah" class="h-full w-full object-cover">
                                            </a>
                                        @endif
                                        <div class="space-y-1">
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-200">
                                                Scan Wajah
                                            </span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $record['face_proof']['similarity_percent'] ?? '-' }}% cocok -
                                                {{ round((float) ($record['face_proof']['distance_meters'] ?? 0)) }} m
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td data-label="Keterangan" class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $record['keterangan'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-0 pkg-mobile-empty">
                                <div class="pkg-empty-state">
                                    <svg class="pkg-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <h3 class="pkg-empty-title">Belum ada data presensi sesuai filter ini</h3>
                                    <p class="pkg-empty-copy">Ubah periode, jenis data, kelompok, bidang, atau status untuk menampilkan rekap lain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection
