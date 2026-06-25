@extends('layouts.app')

@section('title', 'Kelola Level')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header">
            <div>
                <h1 class="pkg-page-heading">Kelola Level</h1>
                <p class="pkg-page-subheading">Konfigurasi level, benefit, dan reward siswa dalam satu struktur yang lebih rapi.</p>
            </div>
            <div class="pkg-page-actions flex flex-wrap gap-3">
                <a href="{{ route('export.leaderboard') }}" class="pkg-btn-secondary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
                    Ekspor Total Poin
                </a>
                <a href="{{ route('export.period-collection') }}" class="pkg-btn-secondary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
                    Kumpulkan Data Periode
                </a>
                <a href="{{ route('admin.gamification.transactions') }}" class="pkg-btn-secondary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
                    Riwayat Poin
                </a>
            </div>
        </div>

        {{-- Quick Navigation --}}
        <div class="flex flex-wrap gap-3 mb-6">
            <a href="{{ route('admin.gamification.badges') }}" class="pkg-tab-link text-sm font-medium">
                Pin Penghargaan
            </a>
            <a href="{{ route('admin.gamification.levels') }}" class="pkg-btn-primary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium shadow-sm">
                Kelola Level
            </a>
            <a href="{{ route('admin.gamification.analytics') }}" class="pkg-tab-link text-sm font-medium">
                Analitik
            </a>
            <a href="{{ route('admin.gamification.transactions') }}" class="pkg-tab-link text-sm font-medium">
                Riwayat Transaksi
            </a>
        </div>

        @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ session('error') }}
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            @foreach($levels as $level)
            <div class="pkg-panel border-2 p-6 relative {{ !$level->is_active ? 'opacity-60 border-gray-200' : '' }}" 
                 style="border-color: {{ $level->warna }}">
                <div class="absolute -top-3 left-4 px-2 py-0.5 rounded text-xs font-bold text-white" style="background-color: {{ $level->warna }}">
                    Level {{ $level->level }}
                </div>
                
                <div class="text-center mt-2">
                    <div class="text-4xl mb-2">{{ $level->badge_icon_url ?? 'LVL' }}</div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ $level->nama }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $level->points_range }}</p>
                </div>
                
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Siswa di level ini:</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $level->siswa_points_count }}</p>
                </div>
                
                @if($level->benefits)
                <div class="mt-3">
                    <p class="text-xs text-gray-500 mb-1">Benefits:</p>
                    <ul class="text-xs text-gray-600 space-y-1">
                        @foreach($level->benefits as $benefit)
                        <li class="flex items-center gap-1">
                            <span class="text-green-500">OK</span> {{ $benefit }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                {{-- Auto-linked pins from Pin Penghargaan --}}
                @php
                    $linkedPins = $linkedPinsByLevel->get($level->level, collect());
                @endphp
                @if($linkedPins->count() > 0)
                <div class="mt-2">
                    <p class="text-xs text-gray-500 mb-1">Pin Otomatis:</p>
                    <ul class="text-xs space-y-1">
                        @foreach($linkedPins as $pin)
                        <li class="flex items-center gap-1 text-indigo-600">
                            <span>{{ $pin->icon_url }}</span> {{ $pin->nama }}
                            @if($pin->poin_reward > 0)
                                <span class="text-[10px] text-green-600">(+{{ $pin->poin_reward }} poin)</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                <button onclick="editLevel({{ $level->id }})" class="mt-4 w-full px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Edit Level
                </button>
            </div>
            @endforeach
        </div>

        <!-- Point Configuration -->
        <div class="pkg-panel mt-8 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Konfigurasi Poin</h2>
            <form id="pointConfigForm" method="POST" action="{{ route('admin.gamification.point-config') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Hadir Tepat Waktu</label>
                        <input type="number" name="points_hadir" value="{{ old('points_hadir', $pointConfig['points_hadir'] ?? 10) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Terlambat</label>
                        <input type="number" name="points_terlambat" value="{{ old('points_terlambat', $pointConfig['points_terlambat'] ?? 5) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Izin</label>
                        <input type="number" name="points_izin" value="{{ old('points_izin', $pointConfig['points_izin'] ?? 2) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Sakit</label>
                        <input type="number" name="points_sakit" value="{{ old('points_sakit', $pointConfig['points_sakit'] ?? 2) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Alpha</label>
                        <input type="number" name="points_alpha" value="{{ old('points_alpha', $pointConfig['points_alpha'] ?? 0) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin per Karakter</label>
                        <input type="number" name="points_karakter" value="{{ old('points_karakter', $pointConfig['points_karakter'] ?? 5) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bonus Streak 7 Hari</label>
                        <input type="number" name="points_streak_7" value="{{ old('points_streak_7', $pointConfig['points_streak_7'] ?? 20) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bonus Streak 30 Hari</label>
                        <input type="number" name="points_streak_30" value="{{ old('points_streak_30', $pointConfig['points_streak_30'] ?? 50) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bonus Bulan Sempurna</label>
                        <input type="number" name="points_perfect_month" value="{{ old('points_perfect_month', $pointConfig['points_perfect_month'] ?? 100) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="apply_to_active_period" value="1" class="rounded border-gray-300 text-indigo-600">
                    Terapkan juga ke periode aktif yang sedang berjalan
                </label>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">Perubahan default dipakai untuk periode baru. Jika dicentang, periode aktif ikut memakai nilai baru.</p>
                    <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm font-medium">Simpan Konfigurasi Poin</button>
                </div>
            </form>
        </div>

        <div class="pkg-panel mt-8 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Periode Poin</h2>
                    <p class="text-sm text-gray-500 mt-1">Gunakan periode bulanan agar poin siswa bisa dipantau per bulan tanpa kehilangan akumulasi total.</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if($activePeriod)
                    <span class="pkg-status-badge pkg-status-success">
                        Aktif: {{ $activePeriod->name }}
                    </span>
                    <a href="{{ route('export.period-collection') }}" class="pkg-btn-secondary px-4 py-2 text-sm font-medium">
                        Kumpulkan Data Periode
                    </a>
                    <form method="POST" action="{{ route('admin.gamification.periods.sync-active') }}" data-confirm="Simpan transaksi lama ke periode aktif {{ $activePeriod->name }}, lalu reset saldo poin berjalan siswa menjadi 0? Tugas PKG yang masih menunggu verifikasi pada periode aktif ini juga akan direset ke arsip." data-confirm-title="Sinkron + reset poin berjalan" data-confirm-button="Lanjutkan" data-confirm-tone="warning">
                        @csrf
                        <button type="submit" class="pkg-btn-secondary px-4 py-2 text-sm font-medium">
                            Sinkronkan Periode Aktif + Reset Poin Berjalan
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-900/40 dark:bg-blue-950/30 dark:text-blue-200">
                Total poin siswa sebenarnya sudah tersimpan otomatis di transaksi poin dan akumulasi total.
                Jika Anda ingin menutup saldo berjalan saat ini dan memulai pengumpulan poin baru dari 0, gunakan tombol <strong>Sinkronkan Periode Aktif + Reset Poin Berjalan</strong>.
                Poin lama tetap tersimpan sebagai arsip periode.
            </div>

            @if($periods->isNotEmpty())
            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                @foreach($periods as $period)
                @php($summary = $periodSummaries[$period->id] ?? ['transaction_count' => 0, 'siswa_count' => 0, 'total_points' => 0, 'incoming_points' => 0, 'outgoing_points' => 0, 'task_count' => 0, 'verified_task_count' => 0, 'pending_task_count' => 0, 'pending_task_siswa_count' => 0])
                <div class="pkg-card-soft rounded-3xl border border-slate-200/80 p-5 dark:border-slate-700/80">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-white">{{ $period->name }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $period->start_date?->format('d M Y') ?? '-' }} | {{ $period->end_date?->format('d M Y') ?? 'Berjalan' }}
                            </p>
                        </div>
                        <span class="pkg-status-badge {{ $period->status === 'active' ? 'pkg-status-success' : ($period->status === 'closed' ? 'pkg-status-neutral' : 'pkg-status-warning') }}">
                            {{ strtoupper($period->status) }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-slate-900/60">
                            <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Total Poin</p>
                            <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['total_points']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-slate-900/60">
                            <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Siswa</p>
                            <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($summary['siswa_count']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-slate-900/60">
                            <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Masuk</p>
                            <p class="mt-2 text-base font-bold text-emerald-600">{{ number_format($summary['incoming_points']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-slate-900/60">
                            <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Keluar</p>
                            <p class="mt-2 text-base font-bold text-rose-600">{{ number_format($summary['outgoing_points']) }}</p>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ number_format($summary['transaction_count']) }} transaksi tercatat di periode ini.</p>
                    @if(!empty($summary['last_reset_label']))
                    <p class="mt-1 inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
                        {{ $summary['last_reset_label'] }}
                    </p>
                    @endif
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                        {{ number_format($summary['pending_task_count']) }} tugas menunggu verifikasi historis.
                    </p>
                    @if(($summary['archived_pending_task_count'] ?? 0) > 0)
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                        {{ number_format($summary['archived_pending_task_count']) }} tugas pending sudah dikumpulkan ke arsip periode ini saat sinkron.
                    </p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('tugas-pkg.verification', array_filter([
                            'tab' => 'verification',
                            'status' => 'unverified',
                            'date_from' => optional($period->start_date)->toDateString(),
                            'date_to' => optional($period->end_date)->toDateString(),
                        ])) }}" title="Buka verifikasi tugas yang terkait dengan rentang periode ini." class="pkg-btn-secondary px-3 py-2 text-xs font-medium">
                            Verifikasi Tugas
                        </a>
                        <a href="{{ route('export.leaderboard', ['period_id' => $period->id]) }}" title="Unduh laporan Excel periode ini dengan sheet ringkasan, data siswa, rekap tugas, dan transaksi poin." class="pkg-btn-secondary px-3 py-2 text-xs font-medium">
                            Ekspor Excel
                        </a>
                        <form method="POST" action="{{ route('admin.gamification.periods.sync', $period) }}" data-confirm="Sinkronkan transaksi poin yang sudah ada ke periode {{ $period->name }}? Tugas PKG yang masih menunggu verifikasi pada periode ini juga akan direset ke arsip." data-confirm-title="Sinkronkan periode" data-confirm-button="Sinkronkan" data-confirm-tone="warning">
                            @csrf
                            <button type="submit" title="Tempelkan transaksi poin ke periode ini dan arsipkan tugas PKG pending di periode tersebut." class="pkg-btn-secondary px-3 py-2 text-xs font-medium">
                                Sinkronkan
                            </button>
                        </form>
                        @if(($summary['archived_pending_task_count'] ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.gamification.periods.restore-archived-tasks', $period) }}" data-confirm="Pulihkan semua tugas pending arsip untuk periode {{ $period->name }} ke status menunggu verifikasi? File bukti yang sudah terhapus tidak bisa ikut dipulihkan." data-confirm-title="Pulihkan tugas pending" data-confirm-button="Pulihkan" data-confirm-tone="warning">
                            @csrf
                            <button type="submit" title="Pulihkan lagi tugas pending yang sebelumnya dikumpulkan ke arsip periode ini." class="pkg-btn-secondary px-3 py-2 text-xs font-medium">
                                Pulihkan Pending
                            </button>
                        </form>
                        @endif
                        @if($period->status !== 'active')
                        <form method="POST" action="{{ route('admin.gamification.periods.activate', $period) }}">
                            @csrf
                            <button type="submit" title="Jadikan periode ini sebagai periode aktif untuk pencatatan poin baru." class="pkg-btn-primary px-3 py-2 text-xs font-medium">
                                Aktifkan
                            </button>
                        </form>
                        @elseif($period->status === 'active')
                        <form method="POST" action="{{ route('admin.gamification.periods.close', $period) }}">
                            @csrf
                            <button type="submit" class="pkg-btn-secondary px-3 py-2 text-xs font-medium">
                                Tutup
                            </button>
                        </form>
                        @endif
                    </div>

                    <details class="mt-3 md:hidden rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 text-[11px] text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                        <summary class="cursor-pointer font-medium text-slate-700 dark:text-slate-200">Info tombol</summary>
                        <div class="mt-2 space-y-1 leading-relaxed">
                            <p><strong>Verifikasi Tugas</strong>: buka verifikasi tugas pada rentang periode ini.</p>
                            <p><strong>Ekspor Excel</strong>: unduh laporan periode dengan sheet ringkasan, data siswa, rekap tugas, dan transaksi poin.</p>
                            <p><strong>Sinkronkan</strong>: tempelkan transaksi lama ke periode ini.</p>
                            @if(($summary['archived_pending_task_count'] ?? 0) > 0)
                            <p><strong>Pulihkan Pending</strong>: kembalikan tugas pending arsip ke status menunggu verifikasi.</p>
                            @endif
                            @if($period->status !== 'active')
                            <p><strong>Aktifkan</strong>: jadikan periode ini periode aktif.</p>
                            @else
                            <p><strong>Tutup</strong>: hentikan periode aktif ini.</p>
                            @endif
                        </div>
                    </details>
                </div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('admin.gamification.periods.store') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Periode</label>
                    <input type="text" name="name" placeholder="Contoh: Ramadan 1447 / April 2026" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mulai</label>
                    <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selesai</label>
                    <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Opsional: periode lomba, Ramadan, penilaian bulanan, dsb."></textarea>
                </div>
                <div class="flex flex-col justify-end gap-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="activate_now" value="1" class="rounded border-gray-300 text-indigo-600">
                        Langsung aktifkan
                    </label>
                    <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm font-medium">Buat Periode</button>
                </div>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Periode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rentang</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Ringkasan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Konfigurasi</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($periods as $period)
                        @php($periodConfig = $period->resolved_point_settings)
                        @php($summary = $periodSummaries[$period->id] ?? ['transaction_count' => 0, 'siswa_count' => 0, 'total_points' => 0, 'incoming_points' => 0, 'outgoing_points' => 0, 'task_count' => 0, 'verified_task_count' => 0, 'pending_task_count' => 0, 'pending_task_siswa_count' => 0])
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-800">{{ $period->name }}</p>
                                @if($period->notes)
                                <p class="mt-1 text-xs text-gray-500">{{ $period->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $period->start_date?->format('d M Y') ?? '-' }}
                                <span class="mx-1">|</span>
                                {{ $period->end_date?->format('d M Y') ?? 'Berjalan' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="pkg-status-badge {{ $period->status === 'active' ? 'pkg-status-success' : ($period->status === 'closed' ? 'pkg-status-neutral' : 'pkg-status-warning') }}">
                                    {{ strtoupper($period->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                <p>Total {{ number_format($summary['total_points']) }} poin</p>
                                <p>{{ number_format($summary['siswa_count']) }} siswa | {{ number_format($summary['transaction_count']) }} transaksi</p>
                                <p>{{ number_format($summary['pending_task_count']) }} tugas menunggu pamong</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                Hadir {{ $periodConfig['points_hadir'] ?? 10 }} |
                                Karakter {{ $periodConfig['points_karakter'] ?? 5 }} |
                                Streak 7 {{ $periodConfig['points_streak_7'] ?? 20 }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('tugas-pkg.verification', array_filter([
                                        'tab' => 'verification',
                                        'status' => 'unverified',
                                        'date_from' => optional($period->start_date)->toDateString(),
                                        'date_to' => optional($period->end_date)->toDateString(),
                                    ])) }}" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                                        Verifikasi
                                    </a>
                                    <a href="{{ route('export.leaderboard', ['period_id' => $period->id]) }}" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                        Ekspor
                                    </a>
                                    <form method="POST" action="{{ route('admin.gamification.periods.sync', $period) }}" data-confirm="Sinkronkan transaksi poin yang sudah ada ke periode {{ $period->name }}? Hanya transaksi dalam rentang tanggal periode yang akan ditandai." data-confirm-title="Sinkronkan periode" data-confirm-button="Sinkronkan" data-confirm-tone="warning">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                                            Sinkronkan
                                        </button>
                                    </form>
                                    @if($period->status !== 'active')
                                    <form method="POST" action="{{ route('admin.gamification.periods.activate', $period) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                            Aktifkan
                                        </button>
                                    </form>
                                    @endif
                                    @if($period->status === 'active')
                                    <form method="POST" action="{{ route('admin.gamification.periods.close', $period) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                            Tutup
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                Belum ada periode poin. Buat periode bulanan pertama untuk mulai pemantauan per bulan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Level Edit Modal -->
<div id="levelModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Level</h2>
        <form id="levelForm" onsubmit="saveLevel(event)">
            <input type="hidden" id="levelId">
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                        <input type="number" id="levelNumber" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Warna</label>
                        <input type="color" id="levelColor" class="w-full h-10 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Level</label>
                    <input type="text" id="levelName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="levelDesc" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Poin</label>
                        <input type="number" id="levelMinPoints" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Poin</label>
                        <input type="number" id="levelMaxPoints" min="0" placeholder="Kosong = unlimited" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Benefits (pisahkan dengan koma)</label>
                    <input type="text" id="levelBenefits" placeholder="Sertifikat Berkembang, Pin Khusus, dll" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[11px] text-amber-600 mt-1.5 leading-snug">
                        <strong>Tips:</strong> Agar benefit bisa didownload/dicetak siswa, pastikan mengandung salah satu kata kunci berikut:
                        <span class="inline-flex flex-wrap gap-1 mt-0.5">
                            <code class="px-1 py-0.5 bg-amber-50 rounded text-[10px]">sertifikat</code>
                            <code class="px-1 py-0.5 bg-amber-50 rounded text-[10px]">pin</code>
                            <code class="px-1 py-0.5 bg-amber-50 rounded text-[10px]">nominasi</code>
                            <code class="px-1 py-0.5 bg-amber-50 rounded text-[10px]">piagam</code>
                            <code class="px-1 py-0.5 bg-amber-50 rounded text-[10px]">apresiasi</code>
                            <code class="px-1 py-0.5 bg-amber-50 rounded text-[10px]">piala</code>
                        </span>
                    </p>
                </div>

                {{-- Auto-linked pins (read-only) --}}
                <div id="linkedPinsContainer" class="hidden">
                    <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                        <p class="text-sm font-medium text-indigo-800 mb-2">Pin Otomatis dari Pengaturan Pin</p>
                        <div id="linkedPinsList" class="space-y-1"></div>
                        <a href="{{ route('admin.gamification.badges') }}" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 mt-2 font-medium">
                            Kelola di Pin Penghargaan
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closeLevelModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const levels = @json($levels);
const levelBadges = @json($levelBadges);

function editLevel(id) {
    const level = levels.find(l => l.id === id);
    if (!level) return;
    
    document.getElementById('levelId').value = level.id;
    document.getElementById('levelNumber').value = level.level;
    document.getElementById('levelName').value = level.nama;
    document.getElementById('levelDesc').value = level.deskripsi || '';
    document.getElementById('levelColor').value = level.warna;
    document.getElementById('levelMinPoints').value = level.min_points;
    document.getElementById('levelMaxPoints').value = level.max_points || '';
    document.getElementById('levelBenefits').value = level.benefits ? level.benefits.join(', ') : '';
    
    // Show linked pins
    const container = document.getElementById('linkedPinsContainer');
    const list = document.getElementById('linkedPinsList');
    const pins = levelBadges.filter(b => b.kriteria?.type === 'level_reached' && parseInt(b.kriteria?.value) === level.level);
    
    if (pins.length > 0) {
        list.innerHTML = pins.map(p => `
            <div class="flex items-center gap-2 text-sm text-indigo-700">
                <span>${p.icon || 'PIN'}</span>
                <span class="font-medium">${p.nama}</span>
                ${p.poin_reward > 0 ? `<span class="text-xs text-green-600">(+${p.poin_reward} poin)</span>` : ''}
            </div>
        `).join('');
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
    
    document.getElementById('levelModal').classList.remove('hidden');
    document.getElementById('levelModal').classList.add('flex');
}

function closeLevelModal() {
    document.getElementById('levelModal').classList.add('hidden');
    document.getElementById('levelModal').classList.remove('flex');
}

function saveLevel(e) {
    e.preventDefault();
    
    const id = document.getElementById('levelId').value;
    const benefitsStr = document.getElementById('levelBenefits').value;
    const benefits = benefitsStr ? benefitsStr.split(',').map(b => b.trim()).filter(b => b) : [];
    
    const data = {
        level: parseInt(document.getElementById('levelNumber').value),
        nama: document.getElementById('levelName').value,
        deskripsi: document.getElementById('levelDesc').value,
        warna: document.getElementById('levelColor').value,
        min_points: parseInt(document.getElementById('levelMinPoints').value),
        max_points: document.getElementById('levelMaxPoints').value ? parseInt(document.getElementById('levelMaxPoints').value) : null,
        benefits: benefits
    };
    
    fetch(`/gamification/levels/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => {
        if (!r.ok) return r.json().then(err => { throw err; });
        return r.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            window.showNotification(data.message || 'Gagal menyimpan level', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (err.errors) {
            window.showNotification('Validasi gagal: ' + Object.values(err.errors).flat().join(', '), 'error');
        } else {
            window.showNotification(err.message || 'Terjadi kesalahan saat menyimpan', 'error');
        }
    });
}

document.getElementById('levelModal').addEventListener('click', function(e) {
    if (e.target === this) closeLevelModal();
});
</script>
@endsection
