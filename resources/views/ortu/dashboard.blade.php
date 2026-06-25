@extends('layouts.ortu')

@section('title', 'Dashboard')

@section('content')
@php
    $biometricStatusOrtu = $biometricStatusOrtu ?? ($hasBiometricOrtu ? 'active' : 'inactive');
    $biometricOrtuTone = match ($biometricStatusOrtu) {
        'active' => 'text-emerald-600',
        'legacy' => 'text-amber-600',
        default => 'text-gray-400',
    };
    $biometricOrtuLabel = match ($biometricStatusOrtu) {
        'active' => 'Aktif',
        'legacy' => 'Perlu daftar ulang',
        default => 'Belum aktif',
    };
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Share Info Banners -->
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

    <!-- Student Info Card -->
    <div class="pkg-panel p-6 mb-6">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-full bg-teal-100 dark:bg-teal-900 flex items-center justify-center flex-shrink-0 overflow-hidden">
                @if($siswa->foto_path)
                    <img src="{{ asset('storage/' . $siswa->foto_path) }}" class="w-16 h-16 rounded-full object-cover">
                @else
                    <span class="text-teal-700 dark:text-teal-300 font-bold text-2xl">{{ substr($siswa->nama, 0, 1) }}</span>
                @endif
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $siswa->nama }}</h2>
                <p class="text-gray-600 dark:text-gray-400">NIS: {{ $siswa->nis }} | {{ $siswa->kelas->nama ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Gamification Widget -->
    @if($gamificationStats)
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white mb-6 shadow-lg">
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
        
        <div class="mt-4 flex items-center gap-4 text-sm bg-white/10 p-3 rounded-lg">
            <span>Peringkat #{{ $gamificationStats['rank'] }}</span>
            <span>{{ $gamificationStats['total_badges'] }} Pin</span>
            <span>{{ $gamificationStats['attendance_streak'] }} Streak Hadir</span>
        </div>
    </div>

    {{-- Level Tier Modal --}}
    @if(isset($allLevels) && $allLevels->count() > 0)
        @include('components.level-tier-modal')
    @endif
    @endif

    <!-- Grid: Attendance + Tasks -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Today's Attendance -->
        <div class="pkg-panel p-6">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase mb-3">Presensi Hari Ini</h3>
            @php
                $hasScheduleToday = false;
                if ($activeSchedule) {
                    $todayDay = strtolower(now()->format('l'));
                    $hasScheduleToday = empty($activeSchedule->days) || in_array($todayDay, $activeSchedule->days);
                }
            @endphp
            @if(!$activeSchedule || !$hasScheduleToday)
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <span class="text-sm font-semibold text-gray-600">OFF</span>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">Tidak Ada Jadwal</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tidak ada jadwal PKG hari ini</p>
                    </div>
                </div>
            @elseif($todayPresensi)
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center
                        @if($todayPresensi->status === 'hadir') bg-green-100 dark:bg-green-900/30
                        @elseif($todayPresensi->status === 'izin') bg-yellow-100 dark:bg-yellow-900/30
                        @elseif($todayPresensi->status === 'sakit') bg-red-100 dark:bg-red-900/30
                        @else bg-gray-100 dark:bg-gray-700 @endif">
                        <span class="text-2xl">
                            @if($todayPresensi->status === 'hadir') HADIR
                            @elseif($todayPresensi->status === 'izin') IZIN
                            @elseif($todayPresensi->status === 'sakit') SAKIT
                            @else ALPA @endif
                        </span>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ ucfirst($todayPresensi->status) }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if($todayPresensi->jam_masuk) Masuk: {{ $todayPresensi->jam_masuk }} @endif
                            @if($todayPresensi->jam_keluar) | Keluar: {{ $todayPresensi->jam_keluar }} @endif
                        </p>
                    </div>
                </div>
            @else
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <span class="text-sm font-semibold text-gray-600">WAIT</span>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">Belum Absen</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Anak belum melakukan presensi hari ini</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- PKG Tasks Summary -->
        <div class="pkg-panel p-6">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase mb-3">Tugas PKG</h3>
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                    <span class="text-sm font-semibold text-teal-700 dark:text-teal-300">PKG</span>
                </div>
                <div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $verifiedTasks }} / {{ $totalTasks }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tugas terverifikasi</p>
                </div>
            </div>
            @if($totalTasks > 0)
            <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-teal-500 h-2 rounded-full transition-all" style="width: {{ ($verifiedTasks / max($totalTasks, 1)) * 100 }}%"></div>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('ortu.jadwal') }}" class="pkg-panel p-5 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">JDW</span>
            </div>
            <p class="font-semibold text-gray-900 dark:text-white">Lihat Jadwal</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Kalender aktivitas anak</p>
        </a>
        <a href="{{ route('ortu.tugas') }}" class="pkg-panel p-5 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <span class="text-sm font-semibold text-teal-700 dark:text-teal-300">TGS</span>
            </div>
            <p class="font-semibold text-gray-900 dark:text-white">Cek Tugas PKG</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Pantau tugas anak</p>
        </a>
        <a href="{{ route('ortu.chat') }}" class="pkg-panel p-5 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">CHAT</span>
            </div>
            <p class="font-semibold text-gray-900 dark:text-white">Chat Pamong</p>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Komunikasi dengan pamong</p>
        </a>
        <a href="{{ route('ortu.biometrik') }}" class="pkg-panel p-5 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">BIO</span>
            </div>
            <p class="font-semibold text-gray-900 dark:text-white">Biometrik</p>
            <p class="text-xs {{ $biometricOrtuTone }} mt-1">{{ $biometricOrtuLabel }}</p>
        </a>
    </div>

    <!-- Latest Berita -->
    @if($berita->count() > 0)
    <div class="pkg-panel p-6">
        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase mb-4">Berita Terbaru</h3>
        <div class="space-y-3">
            @foreach($berita as $item)
            <a href="{{ route('public.berita', $item->slug) }}" target="_blank" class="block p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $item->judul }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $item->published_at?->diffForHumans() }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
