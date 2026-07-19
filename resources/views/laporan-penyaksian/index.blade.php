@extends('layouts.app')

@section('title', 'Laporan Penyaksian - PKG')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Laporan Penyaksian</h1>
            <p class="pkg-page-subheading">Kelola laporan penyaksian perilaku generus.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
        <div class="pkg-card p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Laporan</div>
        </div>
        <div class="pkg-card p-4 bg-yellow-50 dark:bg-yellow-900/30">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</div>
            <div class="text-sm text-yellow-600 dark:text-yellow-400">Menunggu</div>
        </div>
        <div class="pkg-card p-4 bg-blue-50 dark:bg-blue-900/30">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['ditindaklanjuti'] }}</div>
            <div class="text-sm text-blue-600 dark:text-blue-400">Ditindaklanjuti</div>
        </div>
        <div class="pkg-card p-4 bg-green-50 dark:bg-green-900/30">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['selesai'] }}</div>
            <div class="text-sm text-green-600 dark:text-green-400">Selesai</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="pkg-filter-bar mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama..."
                   class="pkg-field">
            <select name="status" class="pkg-field">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                <option value="ditindaklanjuti" {{ request('status') == 'ditindaklanjuti' ? 'selected' : '' }}>Ditindaklanjuti</option>
                <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
            <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}" placeholder="Dari tanggal"
                   class="pkg-field">
            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}" placeholder="Sampai tanggal"
                   class="pkg-field">
            <button type="submit" class="btn-primary text-sm !px-4 !py-2">Filter</button>
        </form>
    </div>

    <!-- Table -->
    <div @if($canBulkDelete) x-data="{ selected: [], pageIds: @js($laporan->pluck('id')->values()->all()) }" @endif>
        @if($canBulkDelete)
        <form
            action="{{ route('laporan-penyaksian.bulk-destroy') }}"
            method="POST"
            data-no-csrf-handler
            data-confirm-title="Hapus laporan terpilih"
            data-confirm-button="Hapus Semua"
            data-confirm-tone="danger"
            x-bind:data-confirm="`Yakin ingin menghapus ${selected.length} laporan terpilih? Tindakan ini tidak dapat dibatalkan.`"
        >
            @csrf
            @method('DELETE')

            <div class="pkg-card-soft mb-3 flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between" x-cloak x-show="pageIds.length > 0">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <input
                        type="checkbox"
                        class="pkg-check"
                        aria-label="Pilih semua laporan pada halaman ini"
                        x-bind:checked="pageIds.length > 0 && selected.length === pageIds.length"
                        x-effect="$el.indeterminate = selected.length > 0 && selected.length < pageIds.length"
                        x-on:change="selected = $event.target.checked ? [...pageIds] : []"
                    >
                    Pilih semua di halaman ini
                </label>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500 dark:text-slate-400" x-text="`${selected.length} dipilih`"></span>
                    <button type="submit" class="btn-danger text-sm" x-bind:disabled="selected.length === 0" x-text="`Hapus Terpilih (${selected.length})`"></button>
                </div>
            </div>
        @endif

    <div class="pkg-card overflow-x-auto pkg-mobile-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @if($canBulkDelete)
                    <th class="w-12 px-4 py-3 text-left">
                        <span class="sr-only">Pilih laporan</span>
                    </th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pelapor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Generus</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karakter</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($laporan as $l)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    @if($canBulkDelete)
                    <td data-label="Pilih" class="px-4 py-4">
                        <input
                            type="checkbox"
                            name="ids[]"
                            value="{{ $l->id }}"
                            class="pkg-check"
                            x-model.number="selected"
                            aria-label="Pilih laporan {{ $l->nama_generus }}"
                        >
                    </td>
                    @endif
                    <td data-label="Tanggal" class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                        {{ $l->tanggal_kejadian->format('d/m/Y') }}
                    </td>
                    <td data-label="Pelapor" class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                        {{ $l->nama_pelapor }}
                    </td>
                    <td data-label="Generus" class="px-4 py-4 pkg-mobile-main">
                        <div class="flex items-center gap-2">
                            @if($l->siswa)
                            <div class="flex-shrink-0 h-8 w-8 rounded-full overflow-hidden bg-gray-200">
                                @if($l->siswa->foto_path)
                                <img src="{{ asset('storage/' . $l->siswa->foto_path) }}" class="h-full w-full object-cover">
                                @else
                                <div class="h-full w-full flex items-center justify-center bg-emerald-500 text-white font-bold">
                                    {{ substr($l->siswa->nama, 0, 1) }}
                                </div>
                                @endif
                            </div>
                            @elseif($l->pamong)
                            <div class="flex-shrink-0 h-8 w-8 rounded-full overflow-hidden bg-gray-200">
                                @if($l->pamong->avatar_path)
                                <img src="{{ asset('storage/' . $l->pamong->avatar_path) }}" class="h-full w-full object-cover">
                                @else
                                <div class="h-full w-full flex items-center justify-center bg-blue-500 text-white font-bold">
                                    {{ substr($l->pamong->name ?? $l->pamong->username, 0, 1) }}
                                </div>
                                @endif
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $l->nama_generus }}</div>
                                    @if($l->siswa_id)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 flex-shrink-0">
                                        Siswa
                                    </span>
                                    @elseif($l->pamong_id)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 flex-shrink-0">
                                        Pamong
                                    </span>
                                    @endif
                                </div>
                                @if($l->siswa)
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $l->siswa->kelas?->nama }}</div>
                                @elseif($l->pamong)
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $l->pamong->username }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="Karakter" class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">
                        {{ Str::limit($l->karakter_belum_optimal, 50) }}
                    </td>
                    <td data-label="Status" class="px-4 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            @if($l->status == 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($l->status == 'ditindaklanjuti') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @endif">
                            {{ $l->status_label }}
                        </span>
                    </td>
                    <td data-label="Aksi" class="px-4 py-4 text-right pkg-mobile-actions">
                        <a href="{{ route('laporan-penyaksian.show', $l) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canBulkDelete ? 7 : 6 }}" class="px-4 py-8 pkg-mobile-empty">
                        <div class="pkg-empty-state py-8">
                            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="pkg-empty-title">Belum ada laporan penyaksian</p>
                            <p class="pkg-empty-copy">Data laporan akan muncul di sini saat sudah ada pengiriman.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

        @if($canBulkDelete)
        </form>
        @endif
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $laporan->links() }}
    </div>
</div>
@endsection


