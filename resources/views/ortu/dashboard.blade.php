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
    $taskPercent = $totalTasks > 0 ? round(($verifiedTasks / max($totalTasks, 1)) * 100) : 0;
@endphp
<div class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-6">
    {{-- Info dari pengurus --}}
    @foreach($shareInfos as $info)
    <div x-data="{ show: true }" x-show="show"
         x-init="setTimeout(() => show = false, {{ $info->auto_dismiss_seconds * 1000 }})"
         class="mb-4 flex items-start justify-between rounded-xl border px-4 py-3 shadow-sm
         @if($info->type === 'warning') bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200
         @elseif($info->type === 'success') bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200
         @else bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 @endif"
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

    {{-- Identitas anak (ringkas) --}}
    <div class="pkg-panel mb-4 flex items-center gap-3 p-4">
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-teal-100 dark:bg-teal-900">
            @if($siswa->foto_path)
                <img src="{{ asset('storage/' . $siswa->foto_path) }}" class="h-12 w-12 rounded-full object-cover" alt="Foto {{ $siswa->nama }}">
            @else
                <span class="text-xl font-bold text-teal-700 dark:text-teal-300">{{ mb_substr($siswa->nama, 0, 1) }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold text-gray-900 dark:text-white">{{ $siswa->nama }}</h1>
            <p class="truncate text-sm text-gray-600 dark:text-gray-400">NIS {{ $siswa->nis }} · {{ $siswa->school_grade_label ?? 'Kelas belum dikonfirmasi' }}</p>
        </div>
    </div>

    {{-- Pesan pengingat untuk orang tua --}}
    <div class="mb-4 rounded-2xl border border-teal-200 bg-teal-50 p-4 dark:border-teal-900/60 dark:bg-teal-950/30 sm:p-5">
        <p class="text-base font-bold text-teal-900 dark:text-teal-100">Bapak/Ibu, mari dampingi ananda 🤲</p>
        <ul class="mt-2 space-y-1.5 text-sm leading-6 text-teal-900/90 dark:text-teal-100/90">
            <li>• Ingatkan ananda mengerjakan <span class="font-semibold">Tugas PKG</span> dan meminta verifikasi pamong.</li>
            <li>• Biasakan <span class="font-semibold">membaca Al-Qur'an setiap hari</span>, lalu lembar bacaannya <span class="font-semibold">discan/disetor</span> agar tercatat.</li>
        </ul>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <div class="rounded-xl bg-white/80 p-3 dark:bg-gray-800/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tugas PKG</p>
                <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white">{{ $verifiedTasks }} / {{ $totalTasks }} terverifikasi</p>
                <div class="mt-2 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full bg-teal-500" style="width: {{ $taskPercent }}%"></div>
                </div>
                <a href="{{ route('ortu.tugas') }}" class="mt-2 inline-flex text-sm font-semibold text-teal-700 hover:underline dark:text-teal-300">Cek Tugas PKG →</a>
            </div>
            <div class="rounded-xl bg-white/80 p-3 dark:bg-gray-800/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bacaan Al-Qur'an</p>
                <p class="mt-0.5 text-sm font-bold text-gray-900 dark:text-white">
                    {{ $quranSummary['verified_this_month'] }} setoran bulan ini
                    @if($quranSummary['pending'] > 0)
                        <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/50 dark:text-amber-200">{{ $quranSummary['pending'] }} menunggu</span>
                    @endif
                </p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    @if($quranSummary['last_date'])
                        Terakhir discan: {{ $quranSummary['last_date']->translatedFormat('d M Y') }}
                    @else
                        Belum ada bacaan yang discan.
                    @endif
                </p>
                <a href="{{ route('ortu.quran.index') }}" class="mt-2 inline-flex text-sm font-semibold text-teal-700 hover:underline dark:text-teal-300">Pantau Bacaan Qur'an →</a>
            </div>
        </div>
    </div>

    {{-- Rekap presensi PKG per bulan --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Rekap Presensi PKG</h2>
            <a href="{{ route('ortu.kehadiran') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-300">Riwayat lengkap →</a>
        </div>

        @if(count($attendanceMonths) === 0)
            <p class="mt-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                Belum ada data presensi PKG untuk ananda.
            </p>
        @else
            {{-- Total keseluruhan --}}
            <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5">
                @foreach([
                    ['Hadir', $attendanceTotals['hadir'], 'text-emerald-700 dark:text-emerald-300', 'bg-emerald-50 dark:bg-emerald-900/20'],
                    ['Terlambat', $attendanceTotals['terlambat'], 'text-amber-700 dark:text-amber-300', 'bg-amber-50 dark:bg-amber-900/20'],
                    ['Izin', $attendanceTotals['izin'], 'text-sky-700 dark:text-sky-300', 'bg-sky-50 dark:bg-sky-900/20'],
                    ['Sakit', $attendanceTotals['sakit'], 'text-rose-700 dark:text-rose-300', 'bg-rose-50 dark:bg-rose-900/20'],
                    ['Tidak Hadir', $attendanceTotals['alpha'], 'text-gray-700 dark:text-gray-300', 'bg-gray-100 dark:bg-gray-800'],
                ] as [$label, $value, $tone, $bg])
                    <div class="rounded-xl {{ $bg }} p-2.5 text-center">
                        <p class="text-lg font-black {{ $tone }}">{{ $value }}</p>
                        <p class="text-[11px] font-semibold text-gray-600 dark:text-gray-400">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Total sejak program PKG dimulai (November 2024): {{ $attendanceTotals['total'] }} kegiatan tercatat.</p>

            {{-- Per bulan --}}
            <div class="mt-4 space-y-2">
                @foreach($attendanceMonths as $month)
                    @php $sudahAdaData = $month['total'] > 0; @endphp
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $month['label'] }}</p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if($month['kegiatan'] > 0)
                                    <span class="rounded-full bg-teal-100 px-2 py-0.5 text-[11px] font-semibold text-teal-700 dark:bg-teal-900/40 dark:text-teal-200">{{ $month['kegiatan'] }} kegiatan PKG</span>
                                @endif
                                @if($sudahAdaData)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $month['total'] }} tercatat</span>
                                @endif
                            </div>
                        </div>
                        @if($sudahAdaData)
                            <div class="mt-2 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                                @if($month['hadir'] > 0)
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">Hadir {{ $month['hadir'] }}</span>
                                @endif
                                @if($month['terlambat'] > 0)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Terlambat {{ $month['terlambat'] }}</span>
                                @endif
                                @if($month['izin'] > 0)
                                    <span class="rounded-full bg-sky-100 px-2 py-1 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200">Izin {{ $month['izin'] }}</span>
                                @endif
                                @if($month['sakit'] > 0)
                                    <span class="rounded-full bg-rose-100 px-2 py-1 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200">Sakit {{ $month['sakit'] }}</span>
                                @endif
                                @if($month['alpha'] > 0)
                                    <span class="rounded-full bg-gray-200 px-2 py-1 text-gray-700 dark:bg-gray-700 dark:text-gray-200">Tidak Hadir {{ $month['alpha'] }}</span>
                                @endif
                            </div>
                        @else
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Belum ada catatan presensi ananda di bulan ini.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Aksi cepat --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <a href="{{ route('ortu.jadwal') }}" class="pkg-panel p-3 text-center transition-all hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Kalender</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Jadwal kegiatan</p>
        </a>
        <a href="{{ route('ortu.materi.index') }}" class="pkg-panel p-3 text-center transition-all hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Materi</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Bahan bacaan</p>
        </a>
        <a href="{{ route('public.karakter.index') }}" target="_blank" rel="noopener" class="pkg-panel p-3 text-center transition-all hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">29 Karakter</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Referensi luhur</p>
        </a>
        <a href="{{ route('ortu.chat') }}" class="pkg-panel p-3 text-center transition-all hover:shadow-md">
            <p class="text-sm font-bold text-gray-900 dark:text-white">Chat Pamong</p>
            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Tanya perkembangan</p>
        </a>
    </div>

    {{-- Berita --}}
    @if($berita->count() > 0)
    <div class="pkg-panel p-4 sm:p-5">
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Berita Terbaru</h2>
        <div class="space-y-2">
            @foreach($berita as $item)
            <a href="{{ route('public.berita', $item->slug) }}" target="_blank" rel="noopener" class="block rounded-lg p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->judul }}</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $item->published_at?->diffForHumans() }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
