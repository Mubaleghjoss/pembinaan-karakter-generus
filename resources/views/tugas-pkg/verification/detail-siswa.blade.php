@extends('layouts.app')

@section('title', 'Detail Riwayat Tugas Siswa PKG')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('tugas-pkg.verification', ['tab' => 'verification']) }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Detail Riwayat Tugas Siswa PKG</h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">Lihat riwayat penyelesaian tugas per siswa dan per karakter, termasuk semua bukti & catatan</p>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
        @endif

        <!-- Filters (auto-submit) -->
        <div class="pkg-panel p-6 mb-6">
            <form id="detailForm" method="GET" action="{{ route('tugas-pkg.detail-siswa') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Pilih Siswa</label>
                    <select name="siswa_id" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
                        <option value="">-- Pilih Siswa --</option>
                        @foreach($siswaOptions as $siswa)
                            <option value="{{ $siswa->id }}" {{ request('siswa_id') == $siswa->id ? 'selected' : '' }}>
                                {{ $siswa->nama }} ({{ $siswa->nis }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Pilih Tugas (opsional)</label>
                    <select name="karakter_id" onchange="this.form.submit()" class="w-full px-3 py-2 pkg-field">
                        <option value="">Semua Tugas</option>
                        @foreach($karakterOptions as $karakter)
                            <option value="{{ $karakter->id }}" {{ request('karakter_id') == $karakter->id ? 'selected' : '' }}>
                                {{ $karakter->nama }} ({{ $karakter->kategori_label }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        @if($selectedSiswa && $summary)
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            <div class="pkg-panel p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Dikerjakan</p>
                <p class="text-2xl font-bold text-blue-600">{{ $summary['total'] }}</p>
            </div>
            <div class="pkg-panel p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Terverifikasi</p>
                <p class="text-2xl font-bold text-green-600">{{ $summary['verified'] }}</p>
            </div>
            <div class="pkg-panel p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Menunggu</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $summary['unverified'] }}</p>
            </div>
            <div class="pkg-panel p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Dihapus</p>
                <p class="text-2xl font-bold text-red-500">{{ $summary['deleted'] ?? 0 }}</p>
            </div>
            <div class="pkg-panel p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pertama Kali</p>
                <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $summary['first_date'] }}</p>
            </div>
            <div class="pkg-panel p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Terakhir</p>
                <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $summary['last_date'] }}</p>
            </div>
        </div>

        <!-- Selected Info -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-sm font-bold text-blue-700 dark:text-blue-200">S</div>
                <div>
                    <p class="font-bold text-gray-900 dark:text-white">{{ $selectedSiswa->nama }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        NIS: {{ $selectedSiswa->nis }}
                        @if($selectedKarakter)
                            - Tugas: <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $selectedKarakter->nama }}</span>
                        @else
                            - Semua Tugas
                        @endif
                    </p>
                </div>
                </div>
                <a href="{{ route('pamong.chat.index', ['tab' => 'pribadi', 'siswa_id' => $selectedSiswa->id]) }}" class="btn-secondary whitespace-nowrap px-4 py-2 text-sm">
                    Chat siswa ini
                </a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700" x-data="{ activeTab: '{{ ($trashedRecords->count() > 0 && request('tab') === 'deleted') ? 'deleted' : 'active' }}' }">
            <nav class="pkg-task-tabs flex gap-4 overflow-x-auto" aria-label="Tabs">
                <button @click="activeTab = 'active'" 
                    :class="activeTab === 'active' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="shrink-0 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors">
                    Data Aktif ({{ $summary['total'] }})
                </button>
                @if($trashedRecords->count() > 0)
                <button @click="activeTab = 'deleted'" 
                    :class="activeTab === 'deleted' ? 'border-red-500 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="shrink-0 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors">
                    Data Terhapus ({{ $trashedRecords->count() }})
                </button>
                @endif
            </nav>

            <!-- Active Records Tab -->
            <div x-show="activeTab === 'active'" x-cloak>
                <div class="pkg-panel overflow-hidden mt-4">
                    <div class="pkg-mobile-table overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                    @if(!$selectedKarakter)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tugas</th>
                                    @endif
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bukti / Catatan</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Verifikator</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($records as $index => $record)
                                @php
                                    $requiresProof = ($record->karakter->proof_requirement ?? 'optional') === 'required_any';
                                    $voiceLimit = (int) ($record->karakter->voice_note_max_seconds ?? 0);
                                    $voiceTooLong = $record->has_voice_note && $voiceLimit > 0 && (int) ($record->voice_note_duration_seconds ?? 0) > $voiceLimit;
                                    $missingRequiredProof = $requiresProof && ! $record->has_proof;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400" data-label="No">
                                        {{ $records->firstItem() + $index }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap" data-label="Tanggal">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $record->checked_at ? $record->checked_at->isoFormat('D MMM YYYY') : '-' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $record->checked_at ? $record->checked_at->format('H:i') : '' }}
                                        </p>
                                    </td>
                                    @if(!$selectedKarakter)
                                    <td class="pkg-mobile-main px-4 py-4" data-label="Tugas">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $record->karakter->nama ?? '-' }}</p>
                                        <span class="pkg-status-badge {{ ($record->karakter->kategori ?? '') === 'harian' ? 'pkg-status-info' : (($record->karakter->kategori ?? '') === 'mingguan' ? 'pkg-status-neutral' : 'pkg-status-warning') }}">
                                            {{ $record->karakter->kategori_label ?? '-' }}
                                        </span>
                                    </td>
                                    @endif
                                    <td class="px-4 py-4" data-label="Bukti dan catatan">
                                        {{-- Catatan Siswa --}}
                                        @if($record->student_note)
                                        <div class="text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg border border-blue-200 dark:border-blue-700 max-w-xs mb-1.5">
                                            <span class="font-semibold text-xs block mb-0.5">Catatan Siswa:</span>
                                            {{ $record->student_note }}
                                        </div>
                                        @else
                                        <span class="text-xs text-gray-400 italic">Tidak ada catatan siswa</span>
                                        @endif

                                        @if($record->hasil_teks)
                                        <div class="text-sm bg-purple-50 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200 px-3 py-2 rounded-lg border border-purple-200 dark:border-purple-700 max-w-xs mb-1.5">
                                            <span class="font-semibold text-xs block mb-0.5">Jawaban Teks:</span>
                                            {{ $record->hasil_teks }}
                                        </div>
                                        @endif

                                        <div class="mb-2 flex flex-wrap items-center gap-2">
                                            @if($record->has_photo_proof)
                                                <span class="pkg-status-badge pkg-status-info">Foto</span>
                                            @endif
                                            @if($record->has_voice_note)
                                                <span class="pkg-status-badge pkg-status-neutral">Voice note</span>
                                            @endif
                                            @if($missingRequiredProof)
                                                <span class="pkg-status-badge pkg-status-warning">Bukti wajib belum ada</span>
                                            @elseif($voiceTooLong)
                                                <span class="pkg-status-badge pkg-status-danger">Voice note melebihi batas</span>
                                            @elseif($record->has_proof)
                                                <span class="pkg-status-badge pkg-status-success">Bukti valid</span>
                                            @else
                                                <span class="pkg-status-badge pkg-status-neutral">Tanpa bukti</span>
                                            @endif
                                        </div>

                                        @if($record->has_photo_proof)
                                        <div class="mb-1.5 flex flex-wrap items-start gap-3">
                                            <a href="{{ $record->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $record->proof_url }}" data-preview-alt="Bukti foto {{ $record->karakter->nama ?? 'tugas' }}" data-preview-title="Bukti foto - {{ $record->siswa->nama ?? 'Siswa' }}" data-preview-filename="{{ basename($record->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $record->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="block overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow dark:border-blue-800 dark:bg-slate-900">
                                                <img
                                                    src="{{ $record->proof_url }}"
                                                    alt="Bukti foto {{ $record->karakter->nama ?? 'tugas' }}"
                                                    loading="lazy"
                                                    class="h-16 w-16 object-cover"
                                                >
                                            </a>
                                            <div class="min-w-0">
                                                <a href="{{ $record->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $record->proof_url }}" data-preview-alt="Bukti foto {{ $record->karakter->nama ?? 'tugas' }}" data-preview-title="Bukti foto - {{ $record->siswa->nama ?? 'Siswa' }}" data-preview-filename="{{ basename($record->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $record->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                    Lihat bukti foto
                                                </a>
                                                <span class="mt-2 block text-xs text-gray-500 dark:text-gray-400">
                                                {{ $record->proof_compressed_size_kb ?? 0 }} KB
                                                </span>
                                            </div>
                                        </div>
                                        @endif
                                        @if($record->has_voice_note)
                                        <div class="mb-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 dark:border-violet-800 dark:bg-violet-900/20">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-violet-700 dark:text-violet-300">Voice note bukti</span>
                                                <span class="text-xs text-violet-600 dark:text-violet-400">
                                                    {{ $record->voice_note_size_kb ?? 0 }} KB
                                                    @if($record->voice_note_duration_label)
                                                        | {{ $record->voice_note_duration_label }}
                                                    @endif
                                                </span>
                                            </div>
                                            <audio controls preload="none" class="mt-2 w-full max-w-sm">
                                                <source src="{{ $record->voice_note_url }}">
                                            </audio>
                                            <a href="{{ $record->voice_note_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-white px-3 py-2 text-xs font-medium text-violet-700 transition hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                                Unduh voice note
                                            </a>
                                        </div>
                                        @endif
                                        @if(($record->proof_bonus_points ?? 0) > 0)
                                        <div class="mb-1.5 text-xs text-blue-600 dark:text-blue-400">
                                            Bonus bukti +{{ $record->proof_bonus_points }} poin
                                        </div>
                                        @endif

                                        {{-- Catatan Verifikator --}}
                                        @if($record->notes)
                                        <div class="text-xs bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2 py-1 rounded border border-green-200 dark:border-green-700 mb-1.5">
                                            <span class="font-semibold">Verifikator:</span> {{ $record->notes }}
                                        </div>
                                        @endif

                                        {{-- Komentar Ortu --}}
                                        @if($record->ortuComments && $record->ortuComments->count() > 0)
                                        @foreach($record->ortuComments as $oc)
                                        <div class="text-xs bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 px-2 py-1 rounded border border-teal-200 dark:border-teal-700 mb-1">
                                            <span class="font-semibold">Ortu:</span> {{ $oc->comment }}
                                            <span class="text-gray-400 dark:text-gray-500 ml-1 text-[10px]">{{ $oc->created_at->format('d/m H:i') }}</span>
                                        </div>
                                        @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center" data-label="Status">
                                        @if($record->verified_at)
                                        <span class="pkg-status-badge pkg-status-success">
                                            Terverifikasi
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $record->verified_at->isoFormat('D MMM HH:mm') }}
                                        </p>
                                        @else
                                        <span class="pkg-status-badge pkg-status-warning">
                                            Menunggu
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4" data-label="Verifikator">
                                        @if($record->verifier)
                                        <p class="text-sm text-gray-800 dark:text-white">{{ $record->verifier->username ?? $record->verifier->name ?? '-' }}</p>
                                        @else
                                        <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $selectedKarakter ? 5 : 6 }}" class="pkg-mobile-empty px-6 py-12 text-center">
                                        <div class="text-gray-400 dark:text-gray-500">
                                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="font-medium">Tidak ada data ditemukan</p>
                                            <p class="text-sm mt-1">Siswa belum mengerjakan tugas ini</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
                    <div class="p-4 border-t dark:border-gray-700">
                        {{ $records->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Deleted Records Tab -->
            @if($trashedRecords->count() > 0)
            <div x-show="activeTab === 'deleted'" x-cloak>
                <div class="pkg-panel overflow-hidden mt-4">
                    <div class="px-6 py-4 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-300">Arsip</span>
                            <div>
                                <h3 class="font-semibold text-red-800 dark:text-red-300">Data yang Dihapus</h3>
                                <p class="text-xs text-red-600 dark:text-red-400">Data berikut telah dihapus oleh pamong. Klik "Pulihkan" untuk mengembalikan data.</p>
                            </div>
                        </div>
                    </div>
                    <div class="pkg-mobile-table overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal Diceklis</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tugas</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status Sebelum Hapus</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dihapus</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($trashedRecords as $index => $trashed)
                                @php
                                    $trashedRequiresProof = ($trashed->karakter->proof_requirement ?? 'optional') === 'required_any';
                                    $trashedVoiceLimit = (int) ($trashed->karakter->voice_note_max_seconds ?? 0);
                                    $trashedVoiceTooLong = $trashed->has_voice_note && $trashedVoiceLimit > 0 && (int) ($trashed->voice_note_duration_seconds ?? 0) > $trashedVoiceLimit;
                                    $trashedMissingRequiredProof = $trashedRequiresProof && ! $trashed->has_proof;
                                @endphp
                                <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10">
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400" data-label="No">{{ $index + 1 }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap" data-label="Tanggal">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $trashed->checked_at ? $trashed->checked_at->isoFormat('D MMM YYYY') : '-' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $trashed->checked_at ? $trashed->checked_at->format('H:i') : '' }}
                                        </p>
                                    </td>
                                    <td class="pkg-mobile-main px-4 py-4" data-label="Tugas">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $trashed->karakter->nama ?? '-' }}</p>
                                        @if($trashed->student_note)
                                        <p class="text-xs text-blue-600 dark:text-blue-400 italic mt-0.5">Catatan: {{ Str::limit($trashed->student_note, 80) }}</p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            @if($trashed->has_photo_proof)
                                                <span class="pkg-status-badge pkg-status-info">Foto</span>
                                            @endif
                                            @if($trashed->has_voice_note)
                                                <span class="pkg-status-badge pkg-status-neutral">Voice note</span>
                                            @endif
                                            @if($trashedMissingRequiredProof)
                                                <span class="pkg-status-badge pkg-status-warning">Bukti wajib belum ada</span>
                                            @elseif($trashedVoiceTooLong)
                                                <span class="pkg-status-badge pkg-status-danger">Voice note melebihi batas</span>
                                            @elseif($trashed->has_proof)
                                                <span class="pkg-status-badge pkg-status-success">Bukti valid</span>
                                            @else
                                                <span class="pkg-status-badge pkg-status-neutral">Tanpa bukti</span>
                                            @endif
                                        </div>
                                        @if($trashed->has_photo_proof)
                                        <div class="mt-2 flex flex-wrap items-start gap-2">
                                            <a href="{{ $trashed->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $trashed->proof_url }}" data-preview-alt="Bukti foto {{ $trashed->karakter->nama ?? 'tugas' }}" data-preview-title="Bukti foto arsip - {{ $selectedSiswa->nama ?? 'Siswa' }}" data-preview-filename="{{ basename($trashed->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $trashed->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="block overflow-hidden rounded-lg border border-blue-200 bg-white shadow-sm transition hover:border-blue-300 hover:shadow dark:border-blue-800 dark:bg-slate-900">
                                                <img
                                                    src="{{ $trashed->proof_url }}"
                                                    alt="Bukti foto {{ $trashed->karakter->nama ?? 'tugas' }}"
                                                    loading="lazy"
                                                    class="h-12 w-12 object-cover"
                                                >
                                            </a>
                                            <div>
                                                <a href="{{ $trashed->proof_url }}" target="_blank" rel="noopener" data-preview-src="{{ $trashed->proof_url }}" data-preview-alt="Bukti foto {{ $trashed->karakter->nama ?? 'tugas' }}" data-preview-title="Bukti foto arsip - {{ $selectedSiswa->nama ?? 'Siswa' }}" data-preview-filename="{{ basename($trashed->proof_path ?? 'bukti-foto.jpg') }}" data-preview-meta="Diunggah: {{ $trashed->checked_at?->isoFormat('D MMM YYYY HH:mm') }}" onclick="return window.openImagePreviewFromLink ? window.openImagePreviewFromLink(this) : true;" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                    Lihat bukti
                                                </a>
                                            </div>
                                        </div>
                                        @endif
                                        @if($trashed->has_voice_note)
                                        <div class="mt-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 dark:border-violet-800 dark:bg-violet-900/20">
                                            <p class="text-xs font-semibold text-violet-700 dark:text-violet-300">Voice note bukti</p>
                                            <audio controls preload="none" class="mt-2 w-full max-w-xs">
                                                <source src="{{ $trashed->voice_note_url }}">
                                            </audio>
                                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-violet-600 dark:text-violet-400">
                                                <a href="{{ $trashed->voice_note_url }}" target="_blank" rel="noopener" class="font-medium underline">Unduh voice note</a>
                                                <span>
                                                    {{ $trashed->voice_note_size_kb ?? 0 }} KB
                                                    @if($trashed->voice_note_duration_label)
                                                        | {{ $trashed->voice_note_duration_label }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        @endif
                                        @if(($trashed->proof_bonus_points ?? 0) > 0)
                                        <span class="mt-2 block text-xs text-gray-500 dark:text-gray-400">Bonus +{{ $trashed->proof_bonus_points }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4" data-label="Status awal">
                                        @if($trashed->verified_at)
                                        <span class="pkg-status-badge pkg-status-success">
                                            Terverifikasi
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">oleh {{ $trashed->verifier->username ?? $trashed->verifier->name ?? '-' }}</p>
                                        @else
                                        <span class="pkg-status-badge pkg-status-warning">
                                            Menunggu
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4" data-label="Dihapus">
                                        <p class="text-xs text-red-600 dark:text-red-400 font-medium">
                                            {{ $trashed->deleted_at ? $trashed->deleted_at->isoFormat('D MMM YYYY HH:mm') : '-' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            oleh {{ $trashed->deletedByUser->username ?? $trashed->deletedByUser->name ?? '-' }}
                                        </p>
                                        @if($trashed->deleted_reason)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 italic">
                                            Alasan: "{{ Str::limit($trashed->deleted_reason, 100) }}"
                                        </p>
                                        @endif
                                    </td>
                                    <td class="pkg-mobile-actions px-4 py-4 text-center" data-label="Aksi">
                                        <form action="{{ route('tugas-pkg.verification.restore', $trashed->id) }}" method="POST"
                                              data-confirm="Pulihkan data ini? {{ $trashed->isVerified() ? 'Poin akan dikembalikan ke siswa.' : '' }}"
                                              data-confirm-title="Pulihkan data karakter"
                                              data-confirm-button="Pulihkan"
                                              data-confirm-tone="warning">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-lg transition-colors">
                                                Pulihkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @elseif(!request('siswa_id'))
        <!-- Empty State -->
        <div class="pkg-panel p-12 text-center">
            <div class="text-4xl mb-4 font-bold text-slate-400">0</div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Pilih Siswa untuk Melihat Riwayat</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                Pilih nama siswa dari dropdown di atas, lalu pilih tugas spesifik (opsional) untuk melihat semua riwayat penyelesaian beserta catatan dan bukti.
            </p>
        </div>
        @endif
    </div>
</div>

@include('components.image-preview-modal')
@endsection
