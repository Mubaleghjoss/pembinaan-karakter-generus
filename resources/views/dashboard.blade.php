@extends('layouts.app')

@section('title', 'Dashboard - PKG')

@section('content')
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $queue = collect($verificationQueue ?? [])
        ->filter(fn ($item) => $isAdmin || $user->hasPamongMenuAccess($item['menu']))
        ->values();
    $totalQueue = $queue->sum('count');
    $canManualAttendance = $isAdmin
        || ($user->hasPamongMenuAccess('manual_attendance') && $user->hasPamongCrudPermission('manual_attendance', 'create'));

    $queueTone = [
        'emerald' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/25',
        'teal' => 'border-teal-200 bg-teal-50 dark:border-teal-800 dark:bg-teal-900/25',
        'sky' => 'border-sky-200 bg-sky-50 dark:border-sky-800 dark:bg-sky-900/25',
        'rose' => 'border-rose-200 bg-rose-50 dark:border-rose-800 dark:bg-rose-900/25',
        'amber' => 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/25',
    ];
    $queueText = [
        'emerald' => 'text-emerald-700 dark:text-emerald-300',
        'teal' => 'text-teal-700 dark:text-teal-300',
        'sky' => 'text-sky-700 dark:text-sky-300',
        'rose' => 'text-rose-700 dark:text-rose-300',
        'amber' => 'text-amber-700 dark:text-amber-300',
    ];
@endphp
<div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-6"
     x-data="dashboardData('{{ route('dashboard.secondary-panels') }}')"
     x-init="loadSecondaryPanels()">

    {{-- Info dari admin --}}
    @if(isset($shareInfos) && $shareInfos->count() > 0)
        @foreach($shareInfos as $info)
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, {{ $info->auto_dismiss_seconds * 1000 }})"
             class="mb-4 flex items-start justify-between rounded-xl border px-4 py-3 shadow-sm
             @if($info->type === 'warning') bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-100
             @elseif($info->type === 'success') bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-100
             @else bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-100 @endif">
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

    {{-- Identitas + badge peran --}}
    <div class="pkg-panel mb-4 flex flex-wrap items-center gap-3 p-4">
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 dark:bg-indigo-900">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" class="h-12 w-12 rounded-full object-cover" alt="Foto {{ $user->username }}">
            @else
                <span class="text-xl font-bold text-indigo-700 dark:text-indigo-300">{{ mb_substr($user->name ?: $user->username, 0, 1) }}</span>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-lg font-bold text-gray-900 dark:text-white">{{ $user->name ?: $user->username }}</h1>
            <div class="mt-1">
                <x-role-badges :user="$user" size="xs" :max-duty="3" />
            </div>
        </div>
        @if($dashboardQrData)
            <a href="{{ route('pamong-presensi.index') }}" class="shrink-0 rounded-xl bg-gray-50 px-3 py-2 text-center transition hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                <p class="text-xs font-bold text-gray-900 dark:text-white">QR Presensi Saya</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Lihat / tampilkan</p>
            </a>
        @endif
    </div>

    {{-- Presensi diri (hanya saat jadwal terbuka, untuk akun operasional) --}}
    @if($user->usesPamongPermissionSystem() && $attendanceScheduleOpen)
        @if($myAttendanceToday)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/30">
                <div>
                    <p class="font-bold text-emerald-900 dark:text-emerald-100">Anda sudah presensi hari ini</p>
                    <p class="mt-0.5 text-sm text-emerald-800 dark:text-emerald-100">
                        {{ ucfirst($myAttendanceToday->status) }}
                        @if($myAttendanceToday->jam_masuk) · masuk {{ \Carbon\Carbon::parse($myAttendanceToday->jam_masuk)->format('H:i') }} @endif
                        @if($myAttendanceToday->jam_keluar) · keluar {{ \Carbon\Carbon::parse($myAttendanceToday->jam_keluar)->format('H:i') }} @endif
                    </p>
                </div>
                <a href="{{ route('pamong-presensi.index') }}" class="btn-secondary text-sm">Lihat Detail</a>
            </div>
        @else
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/30">
                <div>
                    <p class="font-bold text-amber-900 dark:text-amber-100">Anda belum presensi hari ini</p>
                    <p class="mt-0.5 text-sm text-amber-800 dark:text-amber-100">Silakan scan QR untuk mencatat kehadiran.</p>
                </div>
                <a href="{{ route('public.scanner') }}" class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-amber-700">Scan Sekarang</a>
            </div>
        @endif
    @endif

    {{-- ANTREAN VERIFIKASI — blok utama --}}
    @if($queue->isNotEmpty())
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Antrean Verifikasi</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pekerjaan yang menunggu tindakan Anda hari ini.</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $totalQueue > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' }}">
                {{ $totalQueue > 0 ? $totalQueue . ' menunggu' : 'Semua beres' }}
            </span>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($queue as $item)
                <a href="{{ $item['url'] }}"
                   class="rounded-xl border p-3 transition hover:shadow-md {{ $item['count'] > 0 ? ($queueTone[$item['tone']] ?? 'border-gray-200 bg-gray-50') : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800/40' }}">
                    <p class="text-2xl font-black {{ $item['count'] > 0 ? ($queueText[$item['tone']] ?? '') : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $item['count'] }}
                    </p>
                    <p class="mt-0.5 text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $item['label'] }}</p>
                    <p class="mt-1 text-[11px] {{ $item['count'] > 0 ? 'font-semibold text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $item['count'] > 0 ? 'Kerjakan sekarang →' : 'Tidak ada antrean' }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Aksi cepat --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @if($canManualAttendance)
            <a href="{{ route('manual-attendance.helper') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Bantu Isi Presensi</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Status absen per pamong</p>
            </a>
        @endif
        @if($isAdmin || $user->hasPamongMenuAccess('tracer_bacaan_quran'))
            <a href="{{ route('quran.index') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Bacaan Qur'an</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Tracer &amp; verifikasi</p>
            </a>
        @endif
        @if($isAdmin || $user->hasPamongMenuAccess('siswa'))
            <a href="{{ route('siswa.index') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Data Generus</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $totalSiswa }} generus</p>
            </a>
        @endif
        @if($isAdmin || $user->hasPamongMenuAccess('calendar'))
            <a href="{{ route('calendar.index') }}" class="pkg-panel p-3 text-center transition hover:shadow-md">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Kalender</p>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Jadwal kegiatan</p>
            </a>
        @endif
    </div>

    {{-- Ringkasan tipis --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Ringkasan {{ $attendanceStats['scope_label'] }}</h2>
            @if($attendanceStats['has_schedule'])
                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">
                    {{ $attendanceStats['scheduled_days'] }} pertemuan terjadwal
                </span>
            @else
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Belum ada jadwal PKG bulan ini</span>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800/60">
                <p class="text-lg font-black text-gray-900 dark:text-white">{{ $totalSiswa }}</p>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Generus {{ $user->isTeacher() ? 'Binaan' : 'Aktif' }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800/60">
                <p class="text-lg font-black text-emerald-600 dark:text-emerald-300">
                    {{ $attendanceStats['has_schedule'] ? $attendanceStats['hadir'] + $attendanceStats['terlambat'] : '—' }}
                </p>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                    Kehadiran {{ $attendanceStats['percentage'] !== null ? '(' . $attendanceStats['percentage'] . '%)' : '(belum dihitung)' }}
                </p>
            </div>
            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800/60">
                <p class="text-lg font-black text-rose-600 dark:text-rose-300">{{ $attendanceStats['has_schedule'] ? $attendanceStats['alpha'] : '—' }}</p>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Kesempatan Absen Belum Hadir</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800/60">
                <p class="text-lg font-black text-indigo-600 dark:text-indigo-300">{{ $avgKarakterProgress }}%</p>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Rata-rata Tugas PKG</p>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ $todayKarakterChecks }} tugas dicek hari ini · {{ $karakterByKategori['total'] }} tugas PKG aktif
        </p>
    </div>

    {{-- Blok khusus admin: presensi pamong --}}
    @if($isAdmin && !empty($pamongAttendanceStats))
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Presensi Pamong Hari Ini</h2>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach([
                ['hadir', 'Hadir', 'text-emerald-600 dark:text-emerald-300', 'pamong_hadir'],
                ['terlambat', 'Terlambat', 'text-amber-600 dark:text-amber-300', 'pamong_terlambat'],
                ['izin', 'Izin', 'text-sky-600 dark:text-sky-300', 'pamong_izin'],
                ['sakit', 'Sakit', 'text-rose-600 dark:text-rose-300', 'pamong_sakit'],
                ['alpha', 'Belum Absen', 'text-gray-600 dark:text-gray-300', 'pamong_alpha'],
            ] as [$key, $label, $tone, $listKey])
                <button type="button"
                        @click="showPamongList('{{ $key }}', 'Pamong {{ $label }}', {{ json_encode($pamongAttendanceStats[$listKey] ?? []) }})"
                        class="rounded-xl bg-gray-50 p-3 text-center transition hover:bg-gray-100 dark:bg-gray-800/60 dark:hover:bg-gray-700">
                    <p class="text-lg font-black {{ $tone }}">{{ $pamongAttendanceStats[$key] }}</p>
                    <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ $label }}</p>
                </button>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ $pamongAttendanceStats['total'] }} akun operasional · kehadiran {{ $pamongAttendanceStats['percentage'] }}%
        </p>
    </div>
    @endif

    {{-- Panel sekunder (lazy) — riwayat & rincian --}}
    <div class="mb-4">
        <div x-show="secondaryPanelsLoading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <div class="pkg-panel p-6"><div class="h-6 w-40 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div></div>
                <div class="pkg-panel p-6"><div class="h-6 w-36 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div></div>
            </div>
            <div class="space-y-4">
                <div class="pkg-panel p-6"><div class="h-5 w-32 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div></div>
            </div>
        </div>
        <div x-show="!secondaryPanelsLoading && secondaryPanelsHtml" x-html="secondaryPanelsHtml"></div>
        <div x-show="secondaryPanelsError" class="pkg-panel p-4 text-sm text-red-600 dark:text-red-400" x-text="secondaryPanelsError"></div>
    </div>

    {{-- Modal daftar pamong (dipakai blok admin) --}}
    <div x-show="showPamongModal" x-cloak @click.self="showPamongModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
        <div class="pkg-modal max-h-[80vh] w-full max-w-2xl overflow-hidden" @click.stop>
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="pamongModalTitle"></h3>
                <button @click="showPamongModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                <input type="text" x-model="pamongSearchQuery" placeholder="Cari nama pamong..." class="pkg-field w-full px-4 py-2">
            </div>
            <div class="max-h-96 overflow-y-auto px-5 py-4">
                <template x-if="filteredPamong.length === 0">
                    <p class="py-6 text-center text-gray-500 dark:text-gray-400">Tidak ada pamong</p>
                </template>
                <div class="space-y-2">
                    <template x-for="(pamong, index) in filteredPamong" :key="pamong.id">
                        <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-500 font-bold text-white">
                                <span x-text="(pamong.name || pamong.username)?.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900 dark:text-white" x-text="pamong.name || pamong.username"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="'@' + (pamong.username || '-')"></p>
                            </div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400" x-text="'#' + (index + 1)"></span>
                        </div>
                    </template>
                </div>
            </div>
            <div class="border-t border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total: <span class="font-bold" x-text="filteredPamong.length"></span></p>
                    <button @click="showPamongModal = false" class="btn-primary text-sm">Tutup</button>
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

        showPamongModal: false,
        pamongModalTitle: '',
        pamongModalType: '',
        pamongList: [],
        pamongSearchQuery: '',

        get filteredPamong() {
            if (!this.pamongSearchQuery) return this.pamongList;
            const query = this.pamongSearchQuery.toLowerCase();
            return this.pamongList.filter((p) =>
                p.name?.toLowerCase().includes(query) || p.username?.toLowerCase().includes(query)
            );
        },

        async loadSecondaryPanels() {
            try {
                const response = await fetch(this.secondaryPanelsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('Gagal memuat panel tambahan dashboard');
                this.secondaryPanelsHtml = await response.text();
                window.pkgRefreshScrollReveal?.();
            } catch (error) {
                console.error(error);
                this.secondaryPanelsError = 'Panel tambahan dashboard gagal dimuat. Silakan refresh halaman.';
            } finally {
                this.secondaryPanelsLoading = false;
            }
        },

        showPamongList(type, title, pamongs) {
            this.pamongModalType = type;
            this.pamongModalTitle = title;
            this.pamongList = pamongs || [];
            this.pamongSearchQuery = '';
            this.showPamongModal = true;
        },
    };
}
</script>
@endpush
@endsection
