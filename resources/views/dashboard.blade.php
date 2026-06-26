@extends('layouts.app')

@section('title', 'Dashboard - PKG Presensi')

@section('content')
<div class="min-h-screen" x-data="dashboardData('{{ route('dashboard.secondary-panels') }}')" x-init="loadSecondaryPanels()">
    <!-- Hero Section -->
    <div class="w-full px-4 pt-4 sm:px-6 lg:px-8 lg:pt-6">
        <div class="pkg-hero-shell rounded-[2rem] px-6 py-7 sm:px-8 lg:px-10 lg:py-10" data-reveal="zoom">
            <div class="relative z-10 grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.8fr)] lg:items-center">
                <div>
                    <span class="pkg-glass-badge text-sm font-semibold">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.75)]"></span>
                        Ringkasan aktivitas PKG hari ini
                    </span>
                    <h1 class="mt-5 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl lg:text-5xl">
                        Dashboard operasional yang lebih rapi, fokus, dan mudah dipantau.
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300 sm:text-lg">
                        Selamat datang, <span class="font-semibold text-slate-900 dark:text-white">{{ auth()->user()->username }}</span>.
                        Semua ringkasan presensi, tugas, dan tindak lanjut penting dirangkum dalam tampilan yang lebih konsisten.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <div class="pkg-inline-stat" data-reveal="left">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/12 text-emerald-600 dark:bg-emerald-500/18 dark:text-emerald-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Status</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $hasScheduleToday ? 'Agenda aktif hari ini' : 'Mode ringkasan umum' }}</p>
                            </div>
                        </div>
                        <div class="pkg-inline-stat" data-reveal="right">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/12 text-sky-600 dark:bg-sky-500/18 dark:text-sky-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Hari Ini</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="pkg-section-surface p-5" data-reveal="left">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Presensi siswa</p>
                        <p class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $totalSiswa }}</p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Total siswa yang sudah terdata di sistem.</p>
                    </div>
                    <div class="pkg-section-surface p-5" data-reveal="right">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tindak lanjut</p>
                        <p class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $laporanPending }}</p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Laporan penyaksian yang masih menunggu aksi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full px-4 pb-6 pt-6 sm:px-6 lg:px-8">
        <!-- Pamong Attendance Alert (Only for Teacher when schedule is open) -->
        @if(auth()->user()->usesPamongPermissionSystem() && $attendanceScheduleOpen)
            @if($myAttendanceToday)
                <!-- Already Checked In -->
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-lg p-4 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-green-800 dark:text-green-300">
                                Anda Sudah Presensi Hari Ini
                            </h3>
                            <p class="text-sm text-green-700 dark:text-green-400 mt-1">
                                Status: <span class="font-medium capitalize">{{ $myAttendanceToday->status }}</span> |
                                Jam Masuk: <span class="font-medium">{{ $myAttendanceToday->jam_masuk ? \Carbon\Carbon::parse($myAttendanceToday->jam_masuk)->format('H:i') : '-' }}</span>
                                @if($myAttendanceToday->jam_keluar)
                                    Jam Keluar: <span class="font-medium">{{ \Carbon\Carbon::parse($myAttendanceToday->jam_keluar)->format('H:i') }}</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('pamong-presensi.index') }}" class="flex-shrink-0 text-sm font-medium text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100 underline">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            @else
                <!-- Not Checked In Yet -->
                <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-lg p-4 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">
                                Anda Belum Presensi Hari Ini
                            </h3>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                                Silakan scan QR code untuk mencatat kehadiran Anda.
                            </p>
                        </div>
                        <a href="{{ route('public.scanner') }}" class="flex-shrink-0 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors text-sm">
                            Scan Sekarang
                        </a>
                    </div>
                </div>
            @endif
        @endif

        <!-- Share Info Banners -->
        @if(isset($shareInfos) && $shareInfos->count() > 0)
        @foreach($shareInfos as $info)
        <div x-data="{ show: true }" x-show="show" 
             x-init="setTimeout(() => show = false, {{ $info->auto_dismiss_seconds * 1000 }})"
             class="mb-4 rounded-xl border px-4 py-3 shadow-sm flex items-start justify-between
             @if($info->type === 'warning') bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200
             @elseif($info->type === 'success') bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200
             @else bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 @endif"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div>
                <p class="font-semibold text-sm">Info: {{ $info->title }}</p>
                <p class="text-sm mt-1">{!! nl2br(e($info->message)) !!}</p>
            </div>
            <button @click="show = false" class="ml-4 flex-shrink-0 opacity-60 hover:opacity-100 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endforeach
        @endif

        @if(isset($journalTasks) && $journalTasks->isNotEmpty())
            <section class="pkg-panel mb-6 p-5 shadow-lg" data-reveal="up">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Jurnal RPP Perlu Ditindaklanjuti</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Event selesai yang perlu diisi atau dikonfirmasi.</p>
                    </div>
                    <a href="{{ route('materi-rpp-journals.index') }}" class="btn-secondary text-sm">Lihat Semua</a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach($journalTasks as $task)
                        <a href="{{ route('materi-rpp-journals.schedule', $task) }}" class="pkg-card-soft p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $task->sourceMateri?->judul ?? $task->title }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $task->start_date?->format('d M Y') }} · Pertemuan {{ data_get($task->source_payload, 'number', '-') }}</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                    {{ $task->rppJournal ? 'Tinjau' : 'Isi' }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
            <!-- Total Siswa (always visible) -->
            <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">Total</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $totalSiswa }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Siswa Terdaftar</p>
            </div>

            @if($hasScheduleToday)
            <!-- Hadir -->
            <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                 @click="showStudentList('hadir', 'Siswa Hadir Hari Ini', {{ json_encode($attendanceStats['siswa_hadir']) }})">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">{{ $attendanceStats['percentage'] }}%</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $attendanceStats['hadir'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Hadir Hari Ini</p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Klik untuk lihat daftar -></p>
            </div>

            <!-- Terlambat -->
            <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                 @click="showStudentList('terlambat', 'Siswa Terlambat Hari Ini', {{ json_encode($attendanceStats['siswa_terlambat']) }})">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-yellow-600 bg-yellow-100 px-2 py-1 rounded-full">Late</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $attendanceStats['terlambat'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Terlambat</p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Klik untuk lihat daftar -></p>
            </div>

            <!-- Alpha -->
            <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                 @click="showStudentList('alpha', 'Siswa Belum Hadir Hari Ini', {{ json_encode($attendanceStats['siswa_alpha']) }})">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-red-100 dark:bg-red-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-1 rounded-full">Absen</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $attendanceStats['alpha'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum Hadir</p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Klik untuk lihat daftar -></p>
            </div>
            @else
            <!-- LEADERBOARD shown when no schedule today -->
            <div class="lg:col-span-3 pkg-panel-lg pkg-metric-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                            <span class="text-xl">🏆</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Leaderboard Siswa Binaan</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tidak ada jadwal PKG hari ini</p>
                        </div>
                    </div>
                </div>
                @if($topStudents->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($topStudents as $index => $sp)
                    <div class="flex items-center gap-3 p-3 rounded-lg {{ $index < 3 ? 'bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20' : 'bg-gray-50 dark:bg-gray-700' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0
                            {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : ($index === 1 ? 'bg-gray-300 text-gray-700' : ($index === 2 ? 'bg-orange-300 text-orange-800' : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300')) }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 dark:text-white text-sm truncate">{{ $sp->siswa->nama ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Level {{ $sp->level }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">{{ number_format($sp->total_points) }}</p>
                            <p class="text-[10px] text-gray-500">poin</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Belum ada data poin siswa</p>
                @endif
            </div>
            @endif
        </div>

        <!-- Pamong Attendance Cards (Admin Only) -->
        @if(auth()->user()->hasRole('admin') && !empty($pamongAttendanceStats))
        <div class="mb-6" data-reveal="up">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 px-1">Presensi Pamong Hari Ini</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Pamong -->
                <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">Total</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $pamongAttendanceStats['total'] }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pamong Aktif</p>
                </div>

                <!-- Hadir -->
                <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                     @click="showPamongList('hadir', 'Pamong Hadir Hari Ini', {{ json_encode($pamongAttendanceStats['pamong_hadir']) }})">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">{{ $pamongAttendanceStats['percentage'] }}%</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $pamongAttendanceStats['hadir'] }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hadir Hari Ini</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Klik untuk lihat daftar -></p>
                </div>

                <!-- Terlambat -->
                <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                     @click="showPamongList('terlambat', 'Pamong Terlambat Hari Ini', {{ json_encode($pamongAttendanceStats['pamong_terlambat']) }})">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-yellow-600 bg-yellow-100 px-2 py-1 rounded-full">Late</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $pamongAttendanceStats['terlambat'] }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Terlambat</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Klik untuk lihat daftar -></p>
                </div>

                <!-- Alpha -->
                <div class="pkg-panel-lg pkg-metric-card p-6 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                     @click="showPamongList('alpha', 'Pamong Belum Hadir Hari Ini', {{ json_encode($pamongAttendanceStats['pamong_alpha']) }})">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-red-100 dark:bg-red-900 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-1 rounded-full">Absen</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $pamongAttendanceStats['alpha'] }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum Hadir</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">Klik untuk lihat daftar -></p>
                </div>
            </div>
        </div>
        @endif

        <!-- Second Row Stats -->
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-3" data-reveal="up">
            <!-- Tugas PKG Aktif -->
            <div class="pkg-panel-lg p-6">
                <div class="flex items-center gap-4">
                    <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-xl">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tugas PKG Aktif</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalTugasSubmitted }}</p>
                        <p class="text-xs text-orange-600">{{ $pendingVerifications }} belum diverifikasi</p>
                    </div>
                </div>
            </div>

            <!-- Tugas PKG Harian -->
            <a href="{{ route('karakter-harian.index') }}" class="pkg-panel-lg p-6 hover:shadow-xl transition-all group">
                <div class="flex items-center gap-4">
                    <div class="bg-indigo-100 dark:bg-indigo-900 p-4 rounded-xl group-hover:scale-110 transition">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tugas PKG Harian</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $karakterByKategori['total'] }}</p>
                        <p class="text-xs text-indigo-600">
                            {{ $karakterByKategori['harian'] }} harian
                            @if($karakterByKategori['mingguan'] > 0), {{ $karakterByKategori['mingguan'] }} mingguan @endif
                            @if($karakterByKategori['bulanan'] > 0), {{ $karakterByKategori['bulanan'] }} bulanan @endif
                        </p>
                    </div>
                </div>
            </a>

            <!-- Laporan Penyaksian Widget -->
            <div class="pkg-panel-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Laporan Penyaksian</p>
                    <a href="{{ route('laporan-penyaksian.index') }}" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Lihat Semua</a>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $laporanPending }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Menunggu Tindak Lanjut</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-8" data-reveal="up">
            <div x-show="secondaryPanelsLoading" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="pkg-panel-lg p-6">
                        <div class="h-6 w-40 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-6"></div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="h-28 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-28 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-28 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-28 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                        </div>
                    </div>
                    <div class="pkg-panel-lg p-6">
                        <div class="h-6 w-36 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-4"></div>
                        <div class="space-y-3">
                            <div class="h-14 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-14 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-14 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="pkg-panel-lg p-6">
                        <div class="h-5 w-32 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-4"></div>
                        <div class="space-y-3">
                            <div class="h-12 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-12 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-12 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                        </div>
                    </div>
                    <div class="pkg-panel-lg p-6">
                        <div class="h-5 w-40 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-4"></div>
                        <div class="space-y-3">
                            <div class="h-12 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                            <div class="h-12 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="!secondaryPanelsLoading && secondaryPanelsHtml" x-html="secondaryPanelsHtml"></div>
            <div x-show="secondaryPanelsError" class="pkg-panel p-4 text-sm text-red-600 dark:text-red-400" x-text="secondaryPanelsError"></div>
        </div>
    </div>
    
    <!-- Modal Daftar Siswa -->
    <div x-show="showModal" 
         x-cloak
         @click.self="showModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="pkg-modal max-w-2xl w-full max-h-[80vh] overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90">
            
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                 :class="{
                     'bg-green-50 dark:bg-green-900/20': modalType === 'hadir',
                     'bg-yellow-50 dark:bg-yellow-900/20': modalType === 'terlambat',
                     'bg-red-50 dark:bg-red-900/20': modalType === 'alpha'
                 }">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="modalTitle"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Search -->
            <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Cari nama atau NIS..."
                       class="w-full px-4 py-2 pkg-field">
            </div>
            
            <!-- Content -->
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
                <template x-if="filteredStudents.length === 0">
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">Tidak ada siswa</p>
                    </div>
                </template>
                
                <div class="space-y-2">
                    <template x-for="(siswa, index) in filteredStudents" :key="siswa.id">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                                    <span x-text="siswa.nama?.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white truncate" x-text="siswa.nama"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="'NIS: ' + (siswa.nis || '-')"></p>
                                        </div>
                                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 flex-shrink-0" x-text="'#' + (index + 1)"></div>
                                    </div>
                                    <template x-if="siswa.kelompok || siswa.alamat">
                                        <div class="mt-2 flex items-start gap-1.5">
                                            <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 capitalize" x-text="siswa.kelompok || siswa.alamat"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Total: <span class="font-bold" x-text="filteredStudents.length"></span> siswa
                    </p>
                    <button @click="showModal = false" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Daftar Pamong -->
    <div x-show="showPamongModal" 
         x-cloak
         @click.self="showPamongModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="pkg-modal max-w-2xl w-full max-h-[80vh] overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90">
            
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                 :class="{
                     'bg-green-50 dark:bg-green-900/20': pamongModalType === 'hadir',
                     'bg-yellow-50 dark:bg-yellow-900/20': pamongModalType === 'terlambat',
                     'bg-red-50 dark:bg-red-900/20': pamongModalType === 'alpha'
                 }">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="pamongModalTitle"></h3>
                <button @click="showPamongModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Search -->
            <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                <input type="text" 
                       x-model="pamongSearchQuery"
                       placeholder="Cari nama pamong..."
                       class="w-full px-4 py-2 pkg-field">
            </div>
            
            <!-- Content -->
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
                <template x-if="filteredPamong.length === 0">
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">Tidak ada pamong</p>
                    </div>
                </template>
                
                <div class="space-y-2">
                    <template x-for="(pamong, index) in filteredPamong" :key="pamong.id">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                                    <span x-text="(pamong.name || pamong.username)?.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white truncate" x-text="pamong.name || pamong.username"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="'@' + (pamong.username || '-')"></p>
                                        </div>
                                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 flex-shrink-0" x-text="'#' + (index + 1)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Total: <span class="font-bold" x-text="filteredPamong.length"></span> pamong
                    </p>
                    <button @click="showPamongModal = false" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardData(secondaryPanelsUrl) {
    return {
        secondaryPanelsUrl,
        secondaryPanelsHtml: '',
        secondaryPanelsLoading: true,
        secondaryPanelsError: '',
        // Siswa modal
        showModal: false,
        modalTitle: '',
        modalType: '',
        studentList: [],
        searchQuery: '',
        
        // Pamong modal
        showPamongModal: false,
        pamongModalTitle: '',
        pamongModalType: '',
        pamongList: [],
        pamongSearchQuery: '',
        
        get filteredStudents() {
            if (!this.searchQuery) return this.studentList;
            
            const query = this.searchQuery.toLowerCase();
            return this.studentList.filter(siswa => 
                siswa.nama?.toLowerCase().includes(query) || 
                siswa.nis?.toLowerCase().includes(query)
            );
        },
        
        get filteredPamong() {
            if (!this.pamongSearchQuery) return this.pamongList;
            
            const query = this.pamongSearchQuery.toLowerCase();
            return this.pamongList.filter(pamong => 
                pamong.name?.toLowerCase().includes(query) || 
                pamong.username?.toLowerCase().includes(query)
            );
        },
        
        async loadSecondaryPanels() {
            try {
                const response = await fetch(this.secondaryPanelsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) {
                    throw new Error('Gagal memuat panel tambahan dashboard');
                }
                this.secondaryPanelsHtml = await response.text();
                window.pkgRefreshScrollReveal?.();
            } catch (error) {
                console.error(error);
                this.secondaryPanelsError = 'Panel tambahan dashboard gagal dimuat. Silakan refresh halaman.';
            } finally {
                this.secondaryPanelsLoading = false;
            }
        },

        showStudentList(type, title, students) {
            this.modalType = type;
            this.modalTitle = title;
            this.studentList = students || [];
            this.searchQuery = '';
            this.showModal = true;
        },
        
        showPamongList(type, title, pamongs) {
            this.pamongModalType = type;
            this.pamongModalTitle = title;
            this.pamongList = pamongs || [];
            this.pamongSearchQuery = '';
            this.showPamongModal = true;
        }
    };
}
</script>
@endpush
@endsection
