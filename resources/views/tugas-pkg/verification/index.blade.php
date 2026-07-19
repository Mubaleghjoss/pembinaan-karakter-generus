@extends('layouts.app')

@section('title', 'Verifikasi Tugas PKG')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8" x-data="karakterManager()">
    <!-- Header -->
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Verifikasi Tugas PKG</h1>
            <p class="pkg-page-subheading">Ceklis, verifikasi, impor, dan kelola tugas PKG siswa dalam alur yang konsisten.</p>
        </div>
        @if(auth()->user()->hasPamongCrudPermission('tracer_karakter', 'export'))
        <div class="pkg-page-actions">
            <a href="{{ route('tugas-pkg.rekap') }}" class="pkg-btn-primary px-4 py-2 font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Rekap
            </a>
        </div>
        @endif
    </div>

    <!-- Navigasi utama halaman: mengikuti pola tab yang dipakai halaman admin lain -->
    @include('tugas-pkg.verification.partials.navigation')

    <div class="pkg-panel p-6 mb-6" x-data="{ analyticsOpen: false }">
        <div class="pkg-page-header !mb-0">
            <div>
                <h2 class="pkg-page-heading text-xl">Analitik Keaktifan Pamong</h2>
                <p class="pkg-page-subheading">Ringkasan aktivitas verifikasi tugas PKG dan presensi pamong.</p>
            </div>
            <div class="pkg-page-actions">
                <button type="button" @click="analyticsOpen = !analyticsOpen" class="pkg-btn-secondary px-4 py-2 text-sm">
                    <span x-show="!analyticsOpen">Tampilkan</span>
                    <span x-show="analyticsOpen">Sembunyikan</span>
                </button>
            </div>
        </div>

        <div x-show="analyticsOpen" x-transition class="mt-5 space-y-5">
            <form method="GET" class="pkg-filter-grid md:grid-cols-[repeat(2,minmax(0,1fr))_auto]">
                <input type="hidden" name="status" value="{{ request('status', 'unverified') }}">
                <input type="date" name="analytics_from" value="{{ $analyticsRange['from'] ?? now()->startOfMonth()->toDateString() }}" class="pkg-field">
                <input type="date" name="analytics_to" value="{{ $analyticsRange['to'] ?? now()->endOfMonth()->toDateString() }}" class="pkg-field">
                <button type="submit" class="btn-primary px-4 py-2">Update Analitik</button>
            </form>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
                <div class="pkg-card-soft p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pamong dipantau</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $analyticsSummary['total_pamong'] ?? 0 }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pamong aktif</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ $analyticsSummary['active_pamong'] ?? 0 }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total verifikasi</p>
                    <p class="mt-2 text-2xl font-bold text-sky-600">{{ $analyticsSummary['total_verifications'] ?? 0 }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Backlog</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ $analyticsSummary['pending_backlog'] ?? 0 }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Presensi masuk</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-600">{{ $analyticsSummary['attendance_total'] ?? 0 }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Presensi terverifikasi</p>
                    <p class="mt-2 text-2xl font-bold text-violet-600">{{ $analyticsSummary['verified_attendance'] ?? 0 }}</p>
                </div>
            </div>

            <div class="pkg-mobile-table overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Pamong</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Verifikasi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Rata-rata respon</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Backlog</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Presensi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($pamongAnalyticsRows as $row)
                        <tr>
                            <td class="pkg-mobile-main px-4 py-4" data-label="Pamong">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['username'] }} | {{ strtoupper($row['role_name']) }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white" data-label="Verifikasi">{{ $row['total_verifications'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white" data-label="Siswa">{{ $row['siswa_verified'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white" data-label="Rata-rata respon">
                                {{ is_null($row['avg_verification_minutes']) ? '-' : $row['avg_verification_minutes'] . ' menit' }}
                            </td>
                            <td class="px-4 py-4 text-sm {{ $row['pending_backlog'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}" data-label="Backlog">{{ $row['pending_backlog'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white" data-label="Presensi">
                                {{ $row['attendance_total'] }} total
                                <div class="text-xs text-gray-500 dark:text-gray-400">Hadir {{ $row['attendance_hadir'] }} | Terlambat {{ $row['attendance_terlambat'] }}</div>
                            </td>
                            <td class="px-4 py-4" data-label="Status">
                                <span class="pkg-status-badge {{ $row['activity_status'] === 'tinggi' ? 'pkg-status-success' : ($row['activity_status'] === 'sedang' ? 'pkg-status-info' : ($row['activity_status'] === 'rendah' ? 'pkg-status-warning' : 'pkg-status-neutral')) }}">
                                    {{ strtoupper($row['activity_status']) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="pkg-mobile-empty px-4 py-8">
                                <div class="pkg-empty-state">
                                    <h3 class="pkg-empty-title">Belum ada data analitik pamong</h3>
                                    <p class="pkg-empty-copy">Ubah rentang tanggal atau mulai gunakan verifikasi dan presensi pamong agar analitik bisa dihitung.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab: Ceklis Siswa -->

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error') || $errors->any())
    <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
        {{ session('error') ?? $errors->first() }}
    </div>
    @endif

    <div x-show="activeTab === 'siswa'" x-cloak>
    <!-- Filters -->
    <x-collapsible-section title="Filter siswa" description="Cari berdasarkan nama, NIS, atau kelas." :open="request()->filled('search') || request()->filled('kelas_id')" :compact="true" class="mb-6">
        <form method="GET" class="pkg-filter-grid md:grid-cols-[minmax(0,1fr)_auto_auto_auto]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/NIS..."
                class="flex-1 min-w-[200px] px-3 py-2 pkg-field text-sm">
            <select name="kelas_id" class="px-3 py-2 pkg-field text-sm">
                <option value="">Semua Kelas</option>
                @foreach($kelasOptions as $kelas)
                    <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama }}</option>
                @endforeach
            </select>
            <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm">Filter</button>
            <a href="{{ route('tugas-pkg.verification') }}" class="pkg-btn-secondary px-4 py-2 text-sm">Reset</a>
        </form>
    </x-collapsible-section>

    <!-- Student List -->
    <div class="pkg-card">
        <div class="pkg-mobile-table overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">NIS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($siswaList as $siswa)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" data-label="NIS">{{ $siswa->nis }}</td>
                        <td class="pkg-mobile-main px-6 py-4 whitespace-nowrap" data-label="Nama">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    @if($siswa->foto)
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $siswa->foto) }}" alt="">
                                    @else
                                        <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                            <span class="text-blue-600 dark:text-blue-300 font-medium">{{ substr($siswa->nama, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $siswa->nama }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300" data-label="Kelas">{{ $siswa->kelas->nama ?? '-' }}</td>
                        <td class="pkg-mobile-actions px-6 py-4 whitespace-nowrap text-center" data-label="Aksi">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('tugas-pkg.check', $siswa) }}" class="pkg-btn-primary px-3 py-1 text-sm">
                                    Pamong Bantu Ceklis
                                </a>
                                <a href="{{ route('tugas-pkg.history', $siswa) }}" class="pkg-btn-secondary px-3 py-1 text-sm">
                                    Riwayat
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="pkg-mobile-empty px-6 py-0">
                            <div class="pkg-empty-state">
                                <svg class="pkg-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="pkg-empty-title">
                                    @if(auth()->user()->isTeacher())
                                        Belum ada siswa yang ditugaskan
                                    @else
                                        Tidak ada siswa ditemukan
                                    @endif
                                </h3>
                                <p class="pkg-empty-copy">
                                    @if(auth()->user()->isTeacher())
                                        Hubungi admin bila daftar siswa binaan belum muncul di akun Anda.
                                    @else
                                        Coba ubah filter pencarian atau kelas untuk melihat data lain.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $siswaList->links() }}
        </div>
    </div>
    </div>

    <!-- Tab: Verifikasi Tugas PKG -->
    @include('tugas-pkg.verification.partials.verification-tab')

    <!-- Tab: Import Excel -->
    <div x-show="activeTab === 'import'" x-cloak>
        <div class="pkg-card p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Impor Data Tracer Karakter dari Excel</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Upload file Excel untuk import data ceklis karakter secara massal</p>
            
            @if(session('warning'))
                <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ session('warning') }}</p>
                            @if(session('import_errors'))
                                <ul class="mt-2 text-xs text-yellow-600 dark:text-yellow-400 list-disc list-inside">
                                    @foreach(array_slice(session('import_errors'), 0, 5) as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                    @if(count(session('import_errors')) > 5)
                                        <li>... dan {{ count(session('import_errors')) - 5 }} error lainnya</li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

                <!-- Unduh Template -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <h5 class="font-medium text-gray-900 dark:text-white mb-2">1. Unduh Template</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Unduh template Excel dan isi data sesuai format</p>
                    <a href="{{ route('tugas-pkg.import.template') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Unduh Template
                    </a>
                </div>

                <!-- Upload File -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <h5 class="font-medium text-gray-900 dark:text-white mb-2">2. Upload File</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Upload file Excel yang sudah diisi</p>
                    <form action="{{ route('tugas-pkg.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="block w-full text-sm text-gray-500 dark:text-gray-400
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-lg file:border-0
                                      file:text-sm file:font-medium
                                      file:bg-blue-50 file:text-blue-700
                                      dark:file:bg-blue-900/30 dark:file:text-blue-300
                                      hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Import Data
                        </button>
                    </form>
                </div>
            </div>

            <!-- Format Info -->
            <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <h5 class="font-medium text-green-900 dark:text-green-300 mb-2">Format Kolom Excel:</h5>
                <ul class="text-sm text-green-800 dark:text-green-400 space-y-1">
                    <li><strong>nis</strong> - NIS siswa (wajib)</li>
                    <li><strong>karakter</strong> - Nama karakter sesuai daftar karakter (wajib)</li>
                    <li><strong>tanggal</strong> - Format: YYYY-MM-DD atau DD/MM/YYYY (opsional, default hari ini)</li>
                    <li><strong>catatan</strong> - Catatan tambahan (opsional)</li>
                </ul>
            </div>
        </div>

    <!-- Tab: Kelola Karakter -->
    <div x-show="activeTab === 'karakter'" x-cloak>
        <div class="pkg-card">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Karakter</h2>
                <button @click="openCreateModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Tambah Karakter
                </button>
            </div>
            
            <div class="pkg-mobile-table overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Karakter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategori</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Poin</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Periode</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="item in karakterList" :key="item.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="pkg-mobile-main px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white" data-label="Nama" x-text="item.nama"></td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" data-label="Deskripsi" x-text="item.deskripsi || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Kategori">
                                    <span :class="{
                                        'bg-blue-100 text-blue-800': item.kategori === 'harian',
                                        'bg-purple-100 text-purple-800': item.kategori === 'mingguan',
                                        'bg-orange-100 text-orange-800': item.kategori === 'bulanan',
                                        'bg-gray-100 text-gray-800': !item.kategori
                                    }" class="pkg-status-badge" x-text="item.kategori === 'harian' ? 'Harian' : item.kategori === 'mingguan' ? 'Mingguan' : item.kategori === 'bulanan' ? 'Bulanan' : '-'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-green-600" data-label="Poin" x-text="'+' + (item.poin || 10)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-gray-500" data-label="Periode">
                                    <span x-show="item.tanggal_mulai || item.tanggal_selesai" x-text="(item.tanggal_mulai || '?') + ' - ' + (item.tanggal_selesai || '?')"></span>
                                    <span x-show="!item.tanggal_mulai && !item.tanggal_selesai">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Status">
                                    <span :class="item.is_active ? 'pkg-status-success' : 'pkg-status-danger'" class="pkg-status-badge" x-text="item.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                </td>
                                <td class="pkg-mobile-actions px-6 py-4 whitespace-nowrap text-center" data-label="Aksi">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal(item)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button @click="toggleStatus(item)" :class="item.is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'">
                                            <svg x-show="item.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <svg x-show="!item.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteKarakter(item)" class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="karakterList.length === 0">
                            <td colspan="7" class="pkg-mobile-empty px-6 py-0">
                                <div class="pkg-empty-state">
                                    <h3 class="pkg-empty-title">Belum ada data karakter</h3>
                                    <p class="pkg-empty-copy">Klik Tambah Karakter untuk membuat daftar karakter yang nanti dipakai pada tugas PKG.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit Karakter -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"></div>
            
            <div class="relative pkg-modal max-w-md w-full mx-auto transform transition-all">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="isEditing ? 'Edit Karakter' : 'Tambah Karakter Baru'"></h3>
                </div>
                
                <form @submit.prevent="saveKarakter()">
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Karakter <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.nama" required class="w-full px-3 py-2 pkg-field" placeholder="Contoh: Jujur, Disiplin, dll">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                            <textarea x-model="form.deskripsi" rows="2" class="w-full px-3 py-2 pkg-field" placeholder="Deskripsi karakter (opsional)"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori <span class="text-red-500">*</span></label>
                                <select x-model="form.kategori" required class="w-full px-3 py-2 pkg-field">
                                    <option value="harian">Harian</option>
                                    <option value="mingguan">Mingguan</option>
                                    <option value="bulanan">Bulanan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poin <span class="text-red-500">*</span></label>
                                <input type="number" x-model="form.poin" min="1" max="1000" required class="w-full px-3 py-2 pkg-field">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai</label>
                                <input type="date" x-model="form.tanggal_mulai" class="w-full px-3 py-2 pkg-field">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai</label>
                                <input type="date" x-model="form.tanggal_selesai" class="w-full px-3 py-2 pkg-field">
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :disabled="loading">
                            <span x-show="!loading" x-text="isEditing ? 'Update' : 'Simpan'"></span>
                            <span x-show="loading">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('components.image-preview-modal')

<script>
function karakterManager() {
    return {
        activeTab: new URLSearchParams(window.location.search).get('tab') || '{{ request()->routeIs('tugas-pkg.verification') ? 'verification' : 'siswa' }}',
        showModal: false,
        isEditing: false,
        loading: false,
        karakterList: [],
        form: {
            id: null,
            nama: '',
            deskripsi: '',
            kategori: 'harian',
            poin: 10,
            tanggal_mulai: '',
            tanggal_selesai: ''
        },

        async loadKarakter() {
            try {
                const response = await fetch('/karakter', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    this.karakterList = await response.json();
                } else {
                    console.error('Failed to load karakter:', response.status);
                }
            } catch (error) {
                console.error('Error loading karakter:', error);
            }
        },

        openCreateModal() {
            this.isEditing = false;
            this.form = { id: null, nama: '', deskripsi: '', kategori: 'harian', poin: 10, tanggal_mulai: '', tanggal_selesai: '' };
            this.showModal = true;
        },

        openEditModal(item) {
            this.isEditing = true;
            this.form = {
                id: item.id,
                nama: item.nama,
                deskripsi: item.deskripsi || '',
                kategori: item.kategori || 'harian',
                poin: item.poin || 10,
                tanggal_mulai: item.tanggal_mulai ? item.tanggal_mulai.split('T')[0] : '',
                tanggal_selesai: item.tanggal_selesai ? item.tanggal_selesai.split('T')[0] : ''
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.form = { id: null, nama: '', deskripsi: '', kategori: 'harian', poin: 10, tanggal_mulai: '', tanggal_selesai: '' };
        },

        async saveKarakter() {
            this.loading = true;
            try {
                const url = this.isEditing ? `/karakter/${this.form.id}` : '/karakter';
                const method = this.isEditing ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (response.ok) {
                    await this.loadKarakter();
                    this.closeModal();
                    window.showNotification(this.isEditing ? 'Karakter berhasil diupdate' : 'Karakter berhasil ditambahkan', 'success');
                } else {
                    const data = await response.json();
                    window.showNotification(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                console.error('Error saving karakter:', error);
                window.showNotification('Terjadi kesalahan saat menyimpan', 'error');
            }
            this.loading = false;
        },

        async toggleStatus(item) {
            try {
                const response = await fetch(`/karakter/${item.id}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    await this.loadKarakter();
                }
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },

        async deleteKarakter(item) {
            const confirmed = await window.showConfirmation(`Yakin ingin menghapus karakter "${item.nama}"?`, {
                title: 'Hapus karakter',
                confirmText: 'Hapus',
                tone: 'danger'
            });
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/karakter/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    await this.loadKarakter();
                    window.showNotification('Karakter berhasil dihapus', 'success');
                }
            } catch (error) {
                console.error('Error deleting karakter:', error);
            }
        }
    }
}
</script>
@endsection

