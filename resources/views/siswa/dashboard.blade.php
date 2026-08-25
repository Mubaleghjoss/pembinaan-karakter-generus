@extends('layouts.siswa')

@section('title', 'Dashboard')

@section('content')
@php
    $biometricStatusValue = $biometricStatus['status'] ?? 'inactive';
    $biometricStatusTone = match ($biometricStatusValue) {
        'active' => 'text-emerald-600 dark:text-emerald-400',
        'legacy' => 'text-amber-600 dark:text-amber-400',
        default => 'text-gray-500 dark:text-gray-400',
    };
    $biometricStatusLabel = match ($biometricStatusValue) {
        'active' => 'Aktif',
        'legacy' => 'Perlu daftar ulang',
        default => 'Belum aktif',
    };
    $pamongUtama = $pamongList->first();
@endphp
<div class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-6">
    {{-- Info dari pengurus --}}
    @if(isset($shareInfos))
        @foreach($shareInfos as $info)
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, {{ $info->auto_dismiss_seconds * 1000 }})"
             class="mb-4 flex items-start justify-between rounded-xl border px-4 py-3 shadow-sm
             @if($info->type === 'warning') bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-100
             @elseif($info->type === 'success') bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-100
             @else bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-100 @endif"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div>
                <p class="text-sm font-semibold">Info: {{ $info->title }}</p>
                <p class="mt-1 text-sm">{!! nl2br(e($info->message)) !!}</p>
            </div>
            <button @click="show = false" class="ml-4 flex-shrink-0 opacity-60 transition hover:opacity-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endforeach
    @endif

    {{-- Identitas --}}
    <div class="pkg-panel mb-4 flex items-center gap-3 p-4">
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 dark:bg-indigo-900">
            @if($siswa->foto_path)
                <img src="{{ asset('storage/' . $siswa->foto_path) }}" class="h-12 w-12 rounded-full object-cover" alt="Foto {{ $siswa->nama }}">
            @else
                <span class="text-xl font-bold text-indigo-700 dark:text-indigo-300">{{ mb_substr($siswa->nama, 0, 1) }}</span>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-lg font-bold text-gray-900 dark:text-white">{{ $siswa->nama }}</h1>
            <p class="truncate text-sm text-gray-600 dark:text-gray-400">
                NIS {{ $siswa->nis }} · {{ $siswa->school_grade_label ?? 'Kelas belum dikonfirmasi' }}
                @if($siswa->isGraduated()) · <span class="font-semibold text-sky-600 dark:text-sky-400">Alumni</span> @endif
            </p>
        </div>
        @if($gamificationStats)
            <a href="{{ route('siswa.gamification.dashboard') }}" class="shrink-0 rounded-xl bg-gray-50 px-3 py-2 text-right transition hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                <p class="text-sm font-black text-indigo-600 dark:text-indigo-300">{{ number_format($gamificationStats['points']->total_points ?? 0) }}</p>
                <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Poin · Lv {{ $gamificationStats['current_level']->level ?? 1 }}</p>
            </a>
        @endif
    </div>

    {{-- QR presensi / catatan alumni --}}
    @if($siswa->isActive())
        <div class="mb-4">
            @include('components.dashboard-qr-card', [
                'dashboardQrData' => $dashboardQrData,
                'dashboardQrIdentity' => $siswa->nama.' - '.$siswa->nis,
                'dashboardQrDownloadName' => 'barcode-presensi-'.\Illuminate\Support\Str::slug($siswa->nama ?: $siswa->nis).'.png',
                'dashboardIdCardUrl' => route('siswa.kartu'),
            ])
        </div>
    @else
        <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/30">
            <p class="font-bold text-sky-900 dark:text-sky-100">Akun Alumni PKG</p>
            <p class="mt-1 text-sm text-sky-800 dark:text-sky-100">Akun tetap dapat digunakan. Presensi dan QR sudah dinonaktifkan; Tugas PKG dan bacaan Al-Qur'an dikirim langsung untuk verifikasi Admin.</p>
        </div>
    @endif

    {{-- Fokus utama: Tugas PKG & Bacaan Al-Qur'an --}}
    <div class="mb-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/30 sm:p-5">
        <p class="text-base font-bold text-indigo-900 dark:text-indigo-100">Fokus hari ini 🎯</p>
        <ul class="mt-2 space-y-1.5 text-sm leading-6 text-indigo-800 dark:text-indigo-100">
            <li>• Kerjakan <span class="font-semibold">Tugas PKG</span> lalu minta verifikasi pamong.</li>
            <li>• Rutin <span class="font-semibold">membaca Al-Qur'an</span>, lalu lembar bacaannya <span class="font-semibold">discan/disetor</span> agar tercatat.</li>
        </ul>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            {{-- Tugas PKG --}}
            <div class="rounded-xl bg-white/80 p-3 dark:bg-gray-800/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tugas PKG Hari Ini</p>
                <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white">{{ $checkedKarakter }} / {{ $totalKarakter }} tugas · {{ $karakterPercentage }}%</p>
                <div class="mt-2 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ min($karakterPercentage, 100) }}%"></div>
                </div>
                <p class="mt-1.5 text-[11px] text-gray-600 dark:text-gray-400">
                    Terverifikasi {{ $verifiedTasks }}
                    @if($pendingTasks > 0) · <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $pendingTasks }} menunggu</span> @endif
                </p>
                <a href="{{ route('siswa.tugas-pkg.index') }}" class="mt-2 inline-flex text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">Kerjakan Tugas PKG →</a>
            </div>

            {{-- Bacaan Al-Qur'an --}}
            <div class="rounded-xl bg-white/80 p-3 dark:bg-gray-800/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bacaan Al-Qur'an</p>
                <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white">
                    {{ $quranSummary['verified_this_month'] }} setoran bulan ini
                    @if($quranSummary['pending'] > 0)
                        <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/50 dark:text-amber-200">{{ $quranSummary['pending'] }} menunggu</span>
                    @endif
                </p>
                <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-400">
                    @if($quranSummary['last_date'])
                        Terakhir discan: {{ $quranSummary['last_date']->translatedFormat('d M Y') }}
                    @else
                        Belum ada bacaan yang discan.
                    @endif
                </p>
                <a href="{{ route('siswa.quran.index') }}" class="mt-2 inline-flex text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">Setor Bacaan Qur'an →</a>
            </div>
        </div>
    </div>

    {{-- Jurnal RPP bila ada tugas --}}
    @if(isset($journalTasks) && $journalTasks->isNotEmpty())
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Jurnal Pertemuan</h2>
        <div class="mt-3 space-y-2">
            @foreach($journalTasks as $task)
                <a href="{{ route('siswa.materi-rpp-journals.show', $task['reminder']->id ?? $task['reminder']) }}"
                   class="block rounded-xl border border-gray-200 p-3 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $task['title'] ?? 'Jurnal pertemuan' }}</p>
                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $task['subtitle'] ?? 'Lengkapi jurnal pertemuan' }}</p>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Pamong & kontak --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pamong Pembimbing</p>
                <p class="mt-0.5 truncate text-base font-bold text-gray-900 dark:text-white">
                    {{ $pamongUtama ? ($pamongUtama->nama ?? $pamongUtama->username) : 'Belum ditugaskan' }}
                </p>
            </div>
            <a href="{{ route('siswa.chat.index') }}{{ $pamongUtama ? '?pamong_id=' . $pamongUtama->id : '' }}"
               class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700">
                {{ $pamongUtama ? 'Chat Pamong' : 'Buka Chat' }}
            </a>
        </div>
        @unless($pamongUtama)
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Pamongmu belum ditugaskan. Kamu masih bisa bertanya ke Pengurus PKG lewat menu Chat (tab Pengurus).</p>
        @endunless
    </div>

    {{-- Menu cepat --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <a href="{{ route('siswa.calendar.index') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Kalender</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Jadwal kegiatan</p>
        </a>
        <a href="{{ route('siswa.materi.index') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Materi</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Bahan belajar</p>
        </a>
        <a href="{{ route('siswa.kehadiran.index') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Kehadiran</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Riwayat presensi</p>
        </a>
        <a href="{{ route('siswa.gamification.dashboard') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Prestasi</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Poin, pin, sertifikat</p>
        </a>
    </div>

    {{-- Tambahan: biometrik & game --}}
    <div class="grid gap-3 sm:grid-cols-2">
        <a href="{{ route('siswa.biometrik') }}" class="pkg-panel flex items-center gap-3 p-4 transition hover:shadow-md">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                </svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Login Biometrik</p>
                <p class="text-xs {{ $biometricStatusTone }}">{{ $biometricStatusLabel }}</p>
            </div>
        </a>

        <a href="{{ route('siswa.rpg.index') }}" class="pkg-panel flex items-center gap-3 p-4 transition hover:shadow-md">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-sm font-black text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">GO</span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Game Petualangan</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">Belajar 29 karakter sambil bermain</p>
            </div>
        </a>
    </div>
</div>
@endsection
