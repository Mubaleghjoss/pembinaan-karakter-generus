@extends('layouts.app')

@section('title', 'Presensi Pamong')

@section('content')
@php
    $canEditPamongPresensi = auth()->user()->isAdmin();
    $statusFilter = request('status');
    $cardBaseQuery = request()->except(['status', 'page']);
    $cardFilterUrl = fn (?string $status = null) => route(
        'pamong-presensi.index',
        array_merge($cardBaseQuery, $status ? ['status' => $status] : [])
    );
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
    @if(session('success'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        {{ session('error') }}
    </div>
    @endif

    @if(($autoAlphaCount ?? 0) > 0)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
        {{ $autoAlphaCount }} data alpha otomatis dibuat untuk pamong yang belum mengisi presensi setelah jadwal ditutup.
    </div>
    @endif

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Presensi Pamong</h1>
            <p class="text-gray-600 dark:text-gray-400">Rekap kehadiran pamong/guru</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pamong.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                Kelola Kartu QR
            </a>
            <a href="{{ route('pamong-presensi.export', request()->query()) }}" 
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ekspor Excel
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <a href="{{ $cardFilterUrl() }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter ? '' : 'ring-2 ring-gray-400 dark:ring-gray-500' }}" aria-current="{{ $statusFilter ? 'false' : 'true' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('hadir') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'hadir' ? 'ring-2 ring-green-500' : '' }}" aria-current="{{ $statusFilter === 'hadir' ? 'true' : 'false' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Hadir</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['hadir'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('terlambat') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'terlambat' ? 'ring-2 ring-yellow-500' : '' }}" aria-current="{{ $statusFilter === 'terlambat' ? 'true' : 'false' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Terlambat</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['terlambat'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('izin_sakit') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'izin_sakit' ? 'ring-2 ring-blue-500' : '' }}" aria-current="{{ $statusFilter === 'izin_sakit' ? 'true' : 'false' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Izin/Sakit</div>
            <div class="text-2xl font-bold text-blue-600">{{ $stats['izin'] + $stats['sakit'] }}</div>
        </a>
        <a href="{{ $cardFilterUrl('alpha') }}" class="pkg-card block p-4 transition hover:-translate-y-0.5 {{ $statusFilter === 'alpha' ? 'ring-2 ring-red-500' : '' }}" aria-current="{{ $statusFilter === 'alpha' ? 'true' : 'false' }}">
            <div class="text-sm text-gray-500 dark:text-gray-400">Alpha</div>
            <div class="text-2xl font-bold text-red-600">{{ $stats['alpha'] }}</div>
        </a>
    </div>

    <section class="pkg-card p-5">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Ringkasan Kelompok Pamong</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kehadiran periode {{ $startDate->format('d M Y') }} sampai {{ $endDate->format('d M Y') }}.
            </p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($pamongGroupSummary as $group)
                <article class="pkg-card-soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ $group['label'] }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $group['members'] }} pamong</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                            {{ $group['records'] }} data
                        </span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <div class="rounded-lg bg-white p-2.5 dark:bg-slate-950/60">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Hadir</dt>
                            <dd class="mt-1 text-lg font-bold text-emerald-600">{{ $group['hadir'] }}</dd>
                        </div>
                        <div class="rounded-lg bg-white p-2.5 dark:bg-slate-950/60">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Terlambat</dt>
                            <dd class="mt-1 text-lg font-bold text-amber-600">{{ $group['terlambat'] }}</dd>
                        </div>
                        <div class="rounded-lg bg-white p-2.5 dark:bg-slate-950/60">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Izin/Sakit</dt>
                            <dd class="mt-1 text-lg font-bold text-blue-600">{{ $group['izin_sakit'] }}</dd>
                        </div>
                        <div class="rounded-lg bg-white p-2.5 dark:bg-slate-950/60">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Alpha</dt>
                            <dd class="mt-1 text-lg font-bold text-red-600">{{ $group['alpha'] }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    <!-- Filters -->
    <div class="pkg-card p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full pkg-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full pkg-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pamong</label>
                <select name="user_id" class="w-full pkg-field">
                    <option value="">Semua Pamong</option>
                    @foreach($pamongUsers as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full pkg-field">
                    <option value="">Semua Status</option>
                    <option value="hadir" {{ request('status') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ request('status') == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin_sakit" {{ request('status') == 'izin_sakit' ? 'selected' : '' }}>Izin/Sakit</option>
                    <option value="izin" {{ request('status') == 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ request('status') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="alpha" {{ request('status') == 'alpha' ? 'selected' : '' }}>Alpha</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="pkg-card overflow-hidden">
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pamong</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelompok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jam Masuk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jam Keluar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($presensi as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap pkg-mobile-main" data-label="Pamong">
                            <div class="flex items-center">
                                @if($item->user->avatar_url)
                                    <img src="{{ $item->user->avatar_url }}" class="w-8 h-8 rounded-full mr-3">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 mr-3 flex items-center justify-center">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                            {{ substr($item->user->name, 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->user->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->user->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200" data-label="Kelompok">
                            {{ $item->user->kelompok_label ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" data-label="Tanggal">
                            {{ $item->tanggal->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" data-label="Jam Masuk">
                            {{ $item->jam_masuk?->format('H:i') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" data-label="Jam Keluar">
                            {{ $item->jam_keluar?->format('H:i') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Status">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($item->status === 'hadir') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($item->status === 'terlambat') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($item->status === 'izin') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($item->status === 'sakit') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($item->status) }}
                            </span>
                            @if($item->late_duration_formatted)
                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">({{ $item->late_duration_formatted }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate" data-label="Keterangan">
                            {{ $item->keterangan ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm pkg-mobile-actions" data-label="Aksi">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('pamong-presensi.card', $item->user) }}" class="text-purple-600 hover:text-purple-800" title="Lihat Kartu QR">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                </a>
                                @if($canEditPamongPresensi)
                                    <button type="button"
                                            onclick="document.getElementById('edit-pamong-presensi-{{ $item->id }}').showModal()"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit status">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 14.828 8 16l1.172-4 8.414-8.414z"/>
                                        </svg>
                                    </button>
                                    <dialog id="edit-pamong-presensi-{{ $item->id }}" class="pkg-modal w-full max-w-lg p-0 backdrop:bg-slate-900/60">
                                        <form action="{{ route('pamong-presensi.update', $item) }}" method="POST" class="space-y-4 p-6 text-left">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Presensi Pamong</h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->user->name }} - {{ $item->tanggal->format('d M Y') }}</p>
                                            </div>
                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                                    <select name="status" class="w-full pkg-field" required>
                                                        <option value="hadir" {{ $item->status === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                                        <option value="terlambat" {{ $item->status === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                                                        <option value="izin" {{ $item->status === 'izin' ? 'selected' : '' }}>Izin</option>
                                                        <option value="sakit" {{ $item->status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                                        <option value="alpha" {{ $item->status === 'alpha' ? 'selected' : '' }}>Alpha</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Masuk</label>
                                                    <input type="time" name="jam_masuk" value="{{ $item->jam_masuk?->format('H:i') }}" class="w-full pkg-field">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Keluar</label>
                                                    <input type="time" name="jam_keluar" value="{{ $item->jam_keluar?->format('H:i') }}" class="w-full pkg-field">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                                                    <textarea name="keterangan" rows="3" maxlength="500" class="w-full pkg-field">{{ $item->keterangan }}</textarea>
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="this.closest('dialog').close()" class="btn-secondary px-4 py-2">Batal</button>
                                                <button type="submit" class="btn-primary px-4 py-2">Simpan</button>
                                            </div>
                                        </form>
                                    </dialog>
                                @endif
                                @if(!$item->is_verified)
                                    <form action="{{ route('pamong-presensi.verify', $item) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Verifikasi">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-green-600" title="Terverifikasi">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                @endif
                                <form action="{{ route('pamong-presensi.destroy', $item) }}" method="POST" class="inline"
                                      data-confirm="Yakin ingin menghapus data ini?"
                                      data-confirm-title="Hapus data presensi pamong"
                                      data-confirm-button="Hapus"
                                      data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 pkg-mobile-empty" data-label="">
                            Tidak ada data presensi pamong
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($presensi, 'links'))
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $presensi->links() }}
            </div>
        @endif
    </div>

    <div class="pkg-card p-6">
        <div class="pkg-page-header">
            <div>
                <h2 class="pkg-page-heading text-xl">Import Historis Presensi Pamong</h2>
                <p class="pkg-page-subheading">Masukkan data presensi pamong sebelum aplikasi berjalan. Setiap impor diberi label sumber agar tetap mudah dilacak.</p>
            </div>
            <div class="pkg-page-actions">
                <a href="{{ route('pamong-presensi.import.template') }}" class="btn-secondary px-4 py-2">Unduh Template</a>
            </div>
        </div>

        @if(session('warning'))
            <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
                {{ session('warning') }}
                @if(session('pamong_import_errors'))
                    <ul class="mt-2 list-disc space-y-1 pl-4 text-xs">
                        @foreach(array_slice(session('pamong_import_errors'), 0, 5) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @if(count(session('pamong_import_errors')) > 5)
                            <li>... dan {{ count(session('pamong_import_errors')) - 5 }} error lain.</li>
                        @endif
                    </ul>
                @endif
            </div>
        @endif

        <form action="{{ route('pamong-presensi.import') }}" method="POST" enctype="multipart/form-data" class="pkg-filter-grid md:grid-cols-2">
            @csrf
            <div class="space-y-3">
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="block w-full text-sm text-gray-500 dark:text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-medium
                              file:bg-emerald-50 file:text-emerald-700
                              dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                <input type="text" name="source_label" maxlength="120" placeholder="Sumber data, misalnya Rekap Pamong 2025" class="pkg-field">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="mark_verified" value="1" checked class="rounded border-gray-300 text-indigo-600">
                    Tandai langsung sebagai terverifikasi
                </label>
            </div>
            <div class="flex flex-col justify-between gap-4">
                <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                    <p class="font-semibold text-slate-900 dark:text-white">Format kolom</p>
                    <p class="mt-2">Utamakan kolom <strong>username</strong>. Anda juga bisa mengisi <strong>email</strong>, lalu tanggal, status, jam masuk, jam keluar, dan keterangan.</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary px-4 py-2">Impor Presensi Pamong</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Manual Input -->
    <div id="manual-pamong" class="pkg-card p-4">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Absen Manual Pamong</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Gunakan form ini jika presensi pamong perlu dicatat tanpa scan QR.</p>
        </div>
        <form action="{{ route('pamong-presensi.store') }}" method="POST" class="grid grid-cols-1 gap-4 md:grid-cols-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pamong</label>
                <select name="user_id" class="w-full pkg-field" required>
                    @foreach($manualPamongUsers as $user)
                        <option value="{{ $user['id'] }}" {{ old('user_id', auth()->id()) == $user['id'] ? 'selected' : '' }}>
                            {{ $user['name'] ?: $user['username'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="{{ old('tanggal', $manualDate) }}" class="w-full pkg-field" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full pkg-field" required>
                    <option value="hadir" {{ old('status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ old('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ old('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ old('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="alpha" {{ old('status') === 'alpha' ? 'selected' : '' }}>Alpha</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan</label>
                <input type="text" name="keterangan" value="{{ old('keterangan') }}" maxlength="500" class="w-full pkg-field" placeholder="Opsional">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full px-4 py-2">Simpan Manual</button>
            </div>
        </form>
    </div>
</div>
@endsection


