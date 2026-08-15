@extends('layouts.app')

@section('title', 'Riwayat Tugas PKG - ' . $siswa->nama)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
    <!-- Header -->
    <div class="pkg-page-header">
        <div>
            <a href="{{ route('tugas-pkg.verification') }}" class="mb-2 flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <h1 class="pkg-page-heading">Riwayat Tugas PKG</h1>
            <p class="pkg-page-subheading">Lihat progres dan catatan tugas siswa pada rentang tanggal tertentu.</p>
        </div>
    </div>

    <!-- Student Info & Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 pkg-panel p-6">
            <div class="flex items-center gap-4">
                @if($siswa->foto)
                    <img class="h-16 w-16 rounded-full object-cover" src="{{ asset('storage/' . $siswa->foto) }}" alt="">
                @else
                    <div class="h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <span class="text-2xl text-blue-600 dark:text-blue-300 font-medium">{{ substr($siswa->nama, 0, 1) }}</span>
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $siswa->nama }}</h2>
                    <p class="text-gray-600 dark:text-gray-300">NIS: {{ $siswa->nis }} | Kelas Sekolah: {{ $siswa->school_grade_label }} | Level PKG: {{ $siswa->target_grade_label }}</p>
                </div>
            </div>
        </div>
        
        <!-- Progress Summary -->
        <div class="pkg-panel p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300 mb-2">Progress Karakter</h3>
            <div class="flex items-end gap-2">
                <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $percentage }}%</span>
                <span class="text-gray-500 dark:text-gray-300 mb-1">({{ $checkedKarakter }}/{{ $totalKarakter }})</span>
            </div>
            <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <x-collapsible-section title="Filter riwayat" description="Batasi riwayat berdasarkan rentang tanggal." :open="request()->filled('start_date') || request()->filled('end_date')" :compact="true" class="mb-6">
        <form method="GET" class="pkg-filter-grid md:grid-cols-[repeat(2,minmax(0,1fr))_auto]">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-300 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="px-3 py-2 pkg-field text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="px-3 py-2 pkg-field text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm">Filter</button>
                <a href="{{ route('tugas-pkg.history', $siswa) }}" class="pkg-btn-secondary px-4 py-2 text-sm">Reset</a>
            </div>
        </form>
    </x-collapsible-section>

    <!-- History Table -->
    <div class="pkg-panel">
        <div class="pkg-mobile-table overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karakter</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Diceklis Oleh</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Catatan</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($history as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" data-label="Tanggal">
                            {{ $record->checked_at->format('d M Y H:i') }}
                        </td>
                        <td class="pkg-mobile-main px-6 py-4 whitespace-nowrap" data-label="Tugas">
                            <span class="pkg-status-badge pkg-status-info">
                                {{ $record->karakter->nama ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300" data-label="Diceklis oleh">
                            {{ $record->pamong->username ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300" data-label="Catatan">
                            {{ $record->catatan ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="pkg-mobile-empty px-6 py-0">
                            <div class="pkg-empty-state">
                                <h3 class="pkg-empty-title">Belum ada riwayat tugas</h3>
                                <p class="pkg-empty-copy">Riwayat tugas PKG untuk siswa ini belum tersedia pada rentang tanggal yang dipilih.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $history->links() }}
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 grid gap-2 sm:flex sm:justify-center sm:gap-3">
        <a href="{{ route('tugas-pkg.check', $siswa) }}" class="pkg-btn-primary flex items-center justify-center gap-2 px-6 py-3 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pamong Bantu Ceklis
        </a>
        <a href="{{ route('tugas-pkg.detail-siswa', ['siswa_id' => $siswa->id]) }}" class="pkg-btn-secondary flex items-center justify-center gap-2 px-6 py-3 font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Detail Riwayat Lengkap
        </a>
    </div>
</div>
@endsection

