@extends('layouts.app')

@section('title', 'Pengaturan Jadwal Presensi')

@section('content')
@php
    $canCreateSchedule = auth()->user()->hasPamongCrudPermission('jadwal', 'create');
    $canEditSchedule = auth()->user()->hasPamongCrudPermission('jadwal', 'edit');
    $canDeleteSchedule = auth()->user()->hasPamongCrudPermission('jadwal', 'delete');
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Jadwal Presensi</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Kelola waktu scan QR code dan target peserta presensi.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('pamong-presensi.summary') }}"
               class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Ringkasan Presensi Aktif
            </a>
            <a href="{{ route('schedule-reminder.index') }}" 
               class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Jadwal Kalender
            </a>
            @if($canCreateSchedule)
            <a href="{{ route('attendance-schedule.create') }}" 
               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Jadwal Baru
            </a>
            @endif
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                </svg>
                <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                </svg>
                <p class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Active Schedule Card -->
    @if($activeSchedule)
        <div class="mb-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-8 text-white">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-3 py-1 bg-green-400 text-green-900 rounded-full text-sm font-bold">Aktif</span>
                        <h2 class="text-2xl font-bold">{{ $activeSchedule->name }}</h2>
                        <span class="px-3 py-1 bg-white/20 text-white rounded-full text-sm font-bold">{{ $activeSchedule->targetLabel() }}</span>
                    </div>
                    
                    @if($activeSchedule->description)
                        <p class="text-blue-100 mb-6">{{ $activeSchedule->description }}</p>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div class="text-blue-200 text-sm mb-1">Tanggal Berlaku</div>
                            <div class="text-xl font-bold leading-snug break-words">{{ $activeSchedule->dateRangeLabel() }}</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div class="text-blue-200 text-sm mb-1">Waktu Presensi Dimulai</div>
                            <div class="text-2xl font-bold">{{ \Carbon\Carbon::parse($activeSchedule->open_time)->format('H:i') }}</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div class="text-blue-200 text-sm mb-1">Batas Tepat Waktu</div>
                            <div class="text-2xl font-bold">{{ \Carbon\Carbon::parse($activeSchedule->late_threshold)->format('H:i') }}</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div class="text-blue-200 text-sm mb-1">Waktu Presensi Ditutup</div>
                            <div class="text-2xl font-bold">{{ \Carbon\Carbon::parse($activeSchedule->close_time)->format('H:i') }}</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-blue-200 text-sm mb-2">Hari Aktif:</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'] as $day => $label)
                                <span class="px-3 py-1 rounded-full text-sm font-medium {{ in_array($day, $activeSchedule->days ?? []) ? 'bg-white text-blue-600' : 'bg-white/20 text-blue-200' }}">
                                    {{ $label }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-blue-100">
                        Jadwal terdekat:
                        <span class="font-semibold">
                            {{ optional($activeSchedule->nextOccurrence())->translatedFormat('l, d F Y') ?? 'Belum terdeteksi' }}
                        </span>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('pamong-presensi.summary') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50">
                            Lihat Ringkasan Hari Ini
                        </a>
                        <a href="{{ route('pamong-presensi.index', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="inline-flex items-center rounded-lg bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/30 transition hover:bg-white/20">
                            Lihat Data Detail
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-8 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-400 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <svg class="w-8 h-8 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-200 mb-1">Belum Ada Jadwal Aktif</h3>
                    <p class="text-yellow-700 dark:text-yellow-300">Silakan buat jadwal baru atau aktifkan jadwal yang berlaku untuk tanggal hari ini.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="pkg-panel-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Jadwal Pengingat Kalender</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Data dari halaman Jadwal Pengingat. Jadwal aktif otomatis tampil di kalender sesuai target.</p>
                </div>
                <a href="{{ route('schedule-reminder.index') }}" class="btn-secondary text-sm">Kelola Pengingat</a>
            </div>
        </div>
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-[760px] w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jadwal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($calendarReminders as $reminder)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td data-label="Jadwal" class="px-6 py-4 pkg-mobile-main">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $reminder->title }}</div>
                                @if($reminder->location)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Lokasi: {{ $reminder->location }}</div>
                                @endif
                            </td>
                            <td data-label="Tanggal" class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $reminder->start_date->format('d M Y') }}
                                @if($reminder->end_date)
                                    - {{ $reminder->end_date->format('d M Y') }}
                                @endif
                                @if($reminder->time_range)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Jam: {{ $reminder->time_range }}</div>
                                @endif
                            </td>
                            <td data-label="Target" class="px-6 py-4">
                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-700 dark:bg-purple-950/50 dark:text-purple-200">
                                    {{ $reminder->target_audience === 'all' ? 'Semua' : ($reminder->target_audience === 'siswa' ? 'Siswa' : 'Pamong') }}
                                </span>
                            </td>
                            <td data-label="Status" class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $reminder->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ $reminder->is_active ? 'Aktif di Kalender' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td data-label="Aksi" class="px-6 py-4 text-right text-sm font-medium pkg-mobile-actions">
                                <a href="{{ route('schedule-reminder.edit', $reminder) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                    Edit Pengingat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center pkg-mobile-empty">
                                <div class="pkg-empty-state">
                                    <p class="pkg-empty-title">Belum ada jadwal pengingat</p>
                                    <p class="pkg-empty-copy">Buat jadwal pengingat agar agenda umum ikut muncul di kalender.</p>
                                    <a href="{{ route('schedule-reminder.create') }}" class="mt-3 inline-flex text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">Buat Pengingat</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schedule List -->
    <div class="pkg-panel-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Semua Jadwal</h3>
        </div>

        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-[860px] w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jam Operasional</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hari</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($schedules as $schedule)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td data-label="Nama" class="px-6 py-4 pkg-mobile-main">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $schedule->name }}</div>
                                @if($schedule->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($schedule->description, 50) }}</div>
                                @endif
                            </td>
                            <td data-label="Target" class="px-6 py-4">
                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">
                                    {{ $schedule->targetLabel() }}
                                </span>
                            </td>
                            <td data-label="Jam Operasional" class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <div>{{ \Carbon\Carbon::parse($schedule->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->close_time)->format('H:i') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Batas tepat waktu: {{ \Carbon\Carbon::parse($schedule->late_threshold)->format('H:i') }}</div>
                            </td>
                            <td data-label="Hari" class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                <div>{{ count($schedule->days ?? []) }} hari</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Tanggal: {{ $schedule->dateRangeLabel() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Terdekat: {{ optional($schedule->nextOccurrence())->translatedFormat('d M Y') ?? '-' }}
                                </div>
                            </td>
                            <td data-label="Status" class="px-6 py-4">
                                @if($schedule->is_active)
                                    <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs font-bold">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-full text-xs font-medium">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td data-label="Aksi" class="px-6 py-4 text-right text-sm font-medium pkg-mobile-actions">
                                <div class="flex items-center justify-end gap-2">
                                    @if(!$schedule->is_active)
                                        @if($canEditSchedule)
                                        <form action="{{ route('attendance-schedule.activate', $schedule) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-medium">
                                                Aktifkan
                                            </button>
                                        </form>
                                        @endif
                                    @else
                                        @if($canEditSchedule)
                                        <form action="{{ route('attendance-schedule.deactivate', $schedule) }}" method="POST" class="inline"
                                              data-confirm="Yakin ingin menonaktifkan jadwal ini? Presensi tidak akan berjalan jika tidak ada jadwal aktif."
                                              data-confirm-title="Nonaktifkan jadwal"
                                              data-confirm-button="Nonaktifkan"
                                              data-confirm-tone="warning">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300 font-medium">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                    
                                    @if($canEditSchedule)
                                    <a href="{{ route('attendance-schedule.edit', $schedule) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                        Edit
                                    </a>
                                    @endif
                                    
                                    @if(!$schedule->is_active && $canDeleteSchedule)
                                        <form action="{{ route('attendance-schedule.destroy', $schedule) }}" method="POST" class="inline" 
                                              data-confirm="Yakin ingin menghapus jadwal ini?"
                                              data-confirm-title="Hapus jadwal"
                                              data-confirm-button="Hapus"
                                              data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center pkg-mobile-empty">
                                <div class="text-gray-400 dark:text-gray-500">
                                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-lg font-medium">Belum ada jadwal presensi</p>
                                    <p class="text-sm mt-1">Klik tombol "Buat Jadwal Baru" untuk menambahkan</p>
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

