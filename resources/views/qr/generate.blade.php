@extends('layouts.app')

@section('title', 'Generate QR Code - PKG Presensi')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="pkg-page-header mb-8">
        <div>
            <h1 class="pkg-page-heading">Generate QR Code</h1>
            <p class="pkg-page-subheading">
                Buat dan cetak QR code untuk siswa berdasarkan kelas.
            </p>
        </div>
    </div>

    <!-- Class List with Student Counts -->
    <div class="pkg-card overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Daftar Kelas Sekolah</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih kelas untuk generate QR code semua siswa</p>
        </div>
        <div class="p-6">
            @if($kelas->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($kelas as $k)
                <div class="pkg-card-soft p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $k->nama }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $k->tingkat ?? 'Tingkat -' }}</p>
                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                <span class="font-medium">{{ $k->siswa_count ?? $k->siswa->count() }}</span> siswa
                            </p>
                        </div>
                        <div class="flex flex-col space-y-2">
                            <form method="POST" action="{{ route('qr.generate.post') }}">
                                @csrf
                                <input type="hidden" name="type" value="class">
                                <input type="hidden" name="class_id" value="{{ $k->id }}">
                                <button type="submit" 
                                        class="btn-primary text-sm !px-3 !py-2"
                                        {{ ($k->siswa_count ?? $k->siswa->count()) == 0 ? 'disabled' : '' }}>
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    Generate
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="pkg-empty-state py-8">
                <svg class="pkg-empty-icon !h-12 !w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <p class="pkg-empty-title mt-2">Tidak ada kelas</p>
                <p class="pkg-empty-copy mt-1">Belum ada data kelas yang tersedia.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Individual Student Selection -->
    <div class="pkg-card overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Generate Individual</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Generate QR code untuk siswa tertentu</p>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('qr.generate.post') }}" class="flex flex-col sm:flex-row gap-4">
                @csrf
                <input type="hidden" name="type" value="single">
                <div class="flex-1">
                    <select name="student_id" id="student_id" required
                            class="block w-full rounded-md pkg-field pl-3 pr-10 py-2 text-base sm:text-sm">
                        <option value="">-- Pilih Siswa --</option>
                        @foreach($kelas as $k)
                            @if($k->siswa && $k->siswa->count() > 0)
                            <optgroup label="{{ $k->nama }}">
                                @foreach($k->siswa as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->nis }})</option>
                                @endforeach
                            </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button type="submit" 
                        class="btn-success text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Generate QR
                </button>
            </form>
        </div>
    </div>

    <!-- Generate All Students -->
    <div class="pkg-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Generate Semua Siswa</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Generate QR code untuk semua siswa aktif</p>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('qr.generate.post') }}">
                @csrf
                <input type="hidden" name="type" value="bulk">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Total: <span class="font-semibold text-gray-900 dark:text-white">{{ $totalSiswa ?? 0 }}</span> siswa aktif
                        </p>
                    </div>
                    <button type="submit" 
                            class="btn-primary text-sm"
                            {{ ($totalSiswa ?? 0) == 0 ? 'disabled' : '' }}>
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Generate Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

