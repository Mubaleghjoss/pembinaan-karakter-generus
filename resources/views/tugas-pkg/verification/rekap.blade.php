@extends('layouts.app')

@section('title', 'Rekap Karakter')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('tugas-pkg.verification') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 mb-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rekap Karakter</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-300">Ringkasan progress karakter siswa</p>
        </div>
        <div>
            <a href="{{ route('tugas-pkg.export', request()->query()) }}" class="btn-success px-4 py-2 font-medium flex items-center gap-2 rounded-lg text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ekspor Excel
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="pkg-card-soft rounded-2xl p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">Total Siswa</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ count($rekapData) }}</p>
        </div>
        <div class="pkg-card-soft rounded-2xl p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">Total Karakter</h3>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ $totalKarakter }}</p>
        </div>
        <div class="pkg-card-soft rounded-2xl p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">Rata-rata Progress</h3>
            @php
                $avgPercentage = count($rekapData) > 0 ? round(collect($rekapData)->avg('percentage'), 1) : 0;
            @endphp
            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ $avgPercentage }}%</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="pkg-filter-bar mb-6">
        <form method="GET" class="pkg-filter-grid md:grid-cols-[repeat(4,minmax(0,1fr))_auto_auto]">
            <select name="kelas_id" class="px-3 py-2 pkg-field text-sm">
                <option value="">Semua Kelas</option>
                @foreach($kelasOptions as $kelas)
                    <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama }}</option>
                @endforeach
            </select>
            @if(!auth()->user()->isTeacher() && $pamongOptions->isNotEmpty())
            <select name="pamong_id" class="px-3 py-2 pkg-field text-sm">
                <option value="">Semua Anggota Tim PKG</option>
                @foreach($pamongOptions as $pamong)
                    <option value="{{ $pamong->id }}" {{ request('pamong_id') == $pamong->id ? 'selected' : '' }}>{{ $pamong->name ?? $pamong->username }}</option>
                @endforeach
            </select>
            @endif
            <div>
                <input type="date" name="start_date" value="{{ request('start_date') }}" placeholder="Dari"
                    class="px-3 py-2 pkg-field text-sm">
            </div>
            <div>
                <input type="date" name="end_date" value="{{ request('end_date') }}" placeholder="Sampai"
                    class="px-3 py-2 pkg-field text-sm">
            </div>
            <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm">Filter</button>
            <a href="{{ route('tugas-pkg.rekap') }}" class="pkg-btn-secondary px-4 py-2 text-sm">Reset</a>
        </form>
    </div>

    <!-- Rekap Table -->
    <div class="pkg-panel">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">NIS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karakter Terceklis</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Ceklis</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Progress</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rekapData as $index => $data)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $data['siswa']->nis }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    @if($data['siswa']->foto)
                                        <img class="h-8 w-8 rounded-full object-cover" src="{{ asset('storage/' . $data['siswa']->foto) }}" alt="">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                            <span class="text-blue-600 dark:text-blue-300 font-medium text-xs">{{ substr($data['siswa']->nama, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $data['siswa']->nama }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ $data['siswa']->kelas->nama ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">
                            {{ $data['checked_count'] }}/{{ $data['total_karakter'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-300">
                            {{ $data['total_checks'] }}x
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $data['percentage'] >= 80 ? 'bg-green-500' : ($data['percentage'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                        style="width: {{ $data['percentage'] }}%"></div>
                                </div>
                                <span class="text-sm font-medium {{ $data['percentage'] >= 80 ? 'text-green-600 dark:text-green-400' : ($data['percentage'] >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $data['percentage'] }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <a href="{{ route('tugas-pkg.history', $data['siswa']) }}" class="pkg-btn-secondary px-3 py-1.5 text-xs">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-0">
                            <div class="pkg-empty-state">
                                <h3 class="pkg-empty-title">Belum ada data rekap</h3>
                                <p class="pkg-empty-copy">Coba ubah filter kelas, pamong, atau rentang tanggal untuk melihat ringkasan progres tugas PKG siswa.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

