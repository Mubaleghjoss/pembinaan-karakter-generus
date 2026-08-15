@extends('layouts.siswa')

@section('title', 'Dashboard Siswa')

@section('content')
@php
    $biometricStatusValue = $biometricStatus['status'] ?? 'inactive';
    $biometricStatusTone = match ($biometricStatusValue) {
        'active' => 'text-emerald-600',
        'legacy' => 'text-amber-600',
        default => 'text-gray-500',
    };
    $biometricStatusLabel = match ($biometricStatusValue) {
        'active' => 'Aktif',
        'legacy' => 'Perlu daftar ulang di perangkat ini',
        default => 'Belum diaktifkan, tap untuk setup',
    };
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if($siswa->isActive())
    <div class="mb-6">
        @include('components.dashboard-qr-card', [
            'dashboardQrData' => $dashboardQrData,
            'dashboardQrIdentity' => $siswa->nama.' - '.$siswa->nis,
            'dashboardQrDownloadName' => 'barcode-presensi-'.\Illuminate\Support\Str::slug($siswa->nama ?: $siswa->nis).'.png',
            'dashboardIdCardUrl' => route('siswa.kartu'),
        ])
    </div>
    @else
    <div class="pkg-panel mb-6 border-sky-200 bg-sky-50/70 p-5 dark:border-sky-900 dark:bg-sky-950/30">
        <div class="flex items-start gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200" aria-hidden="true">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118.5 15c0 1.327-.213 2.604-.607 3.798A11.952 11.952 0 0012 20.5a11.952 11.952 0 00-5.893-1.702A12.072 12.072 0 015.5 15c0-1.533.286-3 .807-4.35L12 14z"/></svg>
            </span>
            <div><h2 class="text-balance font-bold text-gray-900 dark:text-white">Akun Alumni PKG</h2><p class="mt-1 text-pretty text-sm text-gray-600 dark:text-gray-300">Akun tetap dapat digunakan. Presensi dan QR siswa sudah dinonaktifkan, sedangkan Tugas PKG dan bacaan Al-Qur'an dikirim langsung untuk verifikasi Admin.</p></div>
        </div>
    </div>
    @endif

    <!-- Share Info Banners -->
    @if(isset($shareInfos))
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

    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Selamat Datang, {{ $siswa->nama }}!</h1>
        <p class="text-gray-600 dark:text-gray-400">NIS: {{ $siswa->nis }} | Kelas Sekolah: {{ $siswa->school_grade_label ?? 'Belum dikonfirmasi' }}</p>
    </div>

    @if(isset($journalTasks) && $journalTasks->isNotEmpty())
        <section class="pkg-panel mb-8 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tugas Jurnal RPP</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Event materi berikut sudah selesai dan memerlukan jurnal Anda.</p>
                </div>
                <a href="{{ route('siswa.materi-rpp-journals.index') }}" class="btn-secondary text-sm">Lihat Semua</a>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach($journalTasks as $task)
                    <a href="{{ route('siswa.materi-rpp-journals.show', $task) }}" class="pkg-card-soft p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $task->sourceMateri?->judul ?? $task->title }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $task->start_date?->format('d M Y') }} · Pertemuan {{ data_get($task->source_payload, 'number', '-') }}</p>
                        <span class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                            {{ $task->rppJournal ? 'Perlu Perbaikan' : 'Belum Diisi' }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Gamification Widget -->
    @if($gamificationStats)
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4 cursor-pointer group" @click="$dispatch('open-tier-modal')" title="Klik untuk lihat info level">
                <div class="text-4xl group-hover:scale-110 transition-transform">{{ $gamificationStats['current_level']->badge_icon_url ?? 'LVL' }}</div>
                <div>
                    <p class="text-indigo-200 text-sm">Level {{ $gamificationStats['current_level']->level ?? 1 }}</p>
                    <h2 class="text-xl font-bold group-hover:underline decoration-2 underline-offset-2">{{ $gamificationStats['current_level']->nama ?? 'Pemula' }}</h2>
                    <p class="text-indigo-300 text-xs mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity">Ketuk untuk info level</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold">{{ number_format($gamificationStats['points']->total_points ?? 0) }}</p>
                <p class="text-indigo-200 text-sm">Total Poin</p>
            </div>
        </div>
        
        @if($gamificationStats['next_level'])
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-1">
                <span>Progress ke {{ $gamificationStats['next_level']->nama }}</span>
                <span>{{ $gamificationStats['points_to_next'] }} poin lagi</span>
            </div>
            <div class="w-full bg-indigo-400/30 rounded-full h-2">
                <div class="bg-white rounded-full h-2" style="width: {{ $gamificationStats['progress_to_next'] }}%"></div>
            </div>
        </div>
        @endif
        
        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-4 text-sm">
                <span>Peringkat #{{ $gamificationStats['rank'] }}</span>
                <span>{{ $gamificationStats['total_badges'] }} Pin</span>
                <span>{{ $gamificationStats['attendance_streak'] }} Streak Hadir</span>
            </div>
            <a href="{{ route('siswa.gamification.dashboard') }}" class="text-sm bg-white/20 hover:bg-white/30 px-3 py-1 rounded-lg transition">
                Lihat Detail
            </a>
        </div>
    </div>

    {{-- Level Tier Modal --}}
    @if(isset($allLevels) && $allLevels->count() > 0)
        @include('components.level-tier-modal')
    @endif
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Karakter Progress -->
        <div class="pkg-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Progres Karakter Harian</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $karakterPercentage }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $checkedKarakter }}/{{ $totalKarakter }} karakter hari ini</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($karakterPercentage, 100) }}%"></div>
            </div>
            <a href="{{ route('siswa.tugas-pkg.index') }}" class="mt-4 inline-flex items-center gap-2 w-full justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Kerjakan Tugas PKG
            </a>
        </div>

        <!-- Pamong Info -->
        <div class="pkg-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Pamong Pembimbing</p>
                    @if($pamongList->isNotEmpty())
                        @foreach($pamongList as $pamong)
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">{{ $pamong->nama ?? $pamong->username }}</p>
                        @endforeach
                    @else
                        <p class="text-lg font-semibold text-gray-500 dark:text-gray-400 mt-1">Belum ditugaskan</p>
                    @endif
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
            @if($pamongList->isNotEmpty())
                @php $firstPamong = $pamongList->first(); @endphp
                <a href="{{ route('siswa.chat.index') }}?pamong_id={{ $firstPamong->id }}" class="mt-4 inline-flex items-center gap-2 w-full justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Chat dengan Pamong
                </a>
            @endif
        </div>

        <!-- Kelas Info -->
        <div class="pkg-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kelas Sekolah</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $siswa->school_grade_label ?? 'Belum dikonfirmasi' }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mb-8">
        <a href="{{ route('siswa.biometrik') }}" class="inline-flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition group">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-gray-800 dark:text-white text-sm">Login Biometrik</p>
                <p class="text-xs {{ $biometricStatusTone }}">
                    {{ $biometricStatusLabel }}
                </p>
            </div>
            <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Game Card -->
        <a href="{{ route('siswa.rpg.index') }}" class="mt-3 block bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 rounded-xl p-4 shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all group">
            <div class="flex items-center gap-4">
                <div class="text-2xl font-black uppercase tracking-wide text-white group-hover:scale-105 transition-transform">Game</div>
                <div class="flex-1 text-white">
                    <h3 class="font-bold text-lg">Ada Game Seru!</h3>
                    <p class="text-white/80 text-sm mt-0.5">Jelajahi peta, temui NPC, jawab pertanyaan, dan kumpulkan poin untuk naik level!</p>
                </div>
                <div class="text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Karakter -->
    <div class="pkg-card">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Karakter Terbaru</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Karakter yang baru diceklis oleh pamong</p>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($recentKarakter as $record)
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 {{ $record->isVerified() ? 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400' : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400' }} rounded-full">
                        @if($record->isVerified())
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $record->karakter->nama ?? '-' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $record->isVerified() ? 'Diverifikasi oleh: ' . ($record->verifier->username ?? 'Sistem') : 'Menunggu Verifikasi' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->checked_at->format('d M Y') }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $record->checked_at->format('H:i') }}</p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p>Belum ada karakter yang diceklis</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
