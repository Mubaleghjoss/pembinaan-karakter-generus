@extends('layouts.app')

@section('title', 'Riwayat Transaksi Poin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="pkg-page-header">
            <div>
                <h1 class="pkg-page-heading">Riwayat Transaksi Poin</h1>
                <p class="pkg-page-subheading">Kelola histori poin siswa, filter sumber transaksi, dan reset data gamifikasi per siswa.</p>
            </div>
            <div class="pkg-page-actions flex flex-wrap gap-3">
                <a href="{{ route('admin.gamification.levels') }}" class="pkg-btn-secondary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
                    Kelola Periode Poin
                </a>
                <a href="{{ route('export.period-collection') }}" class="pkg-btn-secondary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
                    Kumpulkan Data Periode
                </a>
                <a href="{{ route('export.leaderboard', array_filter(['period_id' => request('period_id')])) }}" class="pkg-btn-secondary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
                    {{ $selectedPeriod ? 'Ekspor Periode Ini' : 'Ekspor Total Poin' }}
                </a>
            </div>
        </div>

        {{-- Quick Navigation --}}
        <div class="pkg-filter-bar mb-6 flex flex-wrap gap-3">
            <a href="{{ route('admin.gamification.badges') }}" class="pkg-tab-link text-sm font-medium">
                Pin Penghargaan
            </a>
            <a href="{{ route('admin.gamification.levels') }}" class="pkg-tab-link text-sm font-medium">
                Kelola Level
            </a>
            <a href="{{ route('admin.gamification.analytics') }}" class="pkg-tab-link text-sm font-medium">
                Analitik
            </a>
            <a href="{{ route('admin.gamification.transactions') }}" class="pkg-btn-primary inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium shadow-sm">
                Riwayat Transaksi
            </a>
        </div>

        {{-- Flash Message --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-300 text-sm flex justify-between items-center"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="opacity-60 hover:opacity-100">&times;</button>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ session('error') }}
        </div>
        @endif

        @if($selectedPeriod)
        @php($selectedPeriodSummary = $periodSummaries[$selectedPeriod->id] ?? ['transaction_count' => 0, 'siswa_count' => 0, 'total_points' => 0, 'incoming_points' => 0, 'outgoing_points' => 0, 'task_count' => 0, 'verified_task_count' => 0, 'pending_task_count' => 0, 'pending_task_siswa_count' => 0])
        <div class="pkg-panel mb-6 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Periode terpilih</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $selectedPeriod->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $selectedPeriod->start_date?->format('d M Y') ?? '-' }} | {{ $selectedPeriod->end_date?->format('d M Y') ?? 'Berjalan' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('export.leaderboard', ['period_id' => $selectedPeriod->id]) }}" class="pkg-btn-secondary px-4 py-2 text-sm font-medium">
                        Ekspor Excel Periode
                    </a>
                    <a href="{{ route('tugas-pkg.verification', array_filter([
                        'tab' => 'verification',
                        'status' => 'unverified',
                        'date_from' => optional($selectedPeriod->start_date)->toDateString(),
                        'date_to' => optional($selectedPeriod->end_date)->toDateString(),
                    ])) }}" class="pkg-btn-secondary px-4 py-2 text-sm font-medium">
                        Tugas Belum Diverifikasi
                    </a>
                    <form method="POST" action="{{ route('admin.gamification.periods.sync', $selectedPeriod) }}" data-confirm="Sinkronkan transaksi yang sudah ada ke periode {{ $selectedPeriod->name }}? Tugas PKG yang masih menunggu verifikasi pada periode ini juga akan direset ke arsip." data-confirm-title="Sinkronkan periode" data-confirm-button="Sinkronkan" data-confirm-tone="warning">
                        @csrf
                        <button type="submit" class="pkg-btn-secondary px-4 py-2 text-sm font-medium">
                            Sinkronkan Periode Ini
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="pkg-card-soft rounded-2xl p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Total Tugas</p>
                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($selectedPeriodSummary['task_count']) }}</p>
                </div>
                <div class="pkg-card-soft rounded-2xl p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Sudah Diverifikasi</p>
                    <p class="mt-2 text-xl font-bold text-emerald-600">{{ number_format($selectedPeriodSummary['verified_task_count']) }}</p>
                </div>
                <div class="pkg-card-soft rounded-2xl p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Menunggu Historis</p>
                    <p class="mt-2 text-xl font-bold text-amber-600">{{ number_format($selectedPeriodSummary['pending_task_count']) }}</p>
                </div>
                <div class="pkg-card-soft rounded-2xl p-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Siswa Menunggu Historis</p>
                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($selectedPeriodSummary['pending_task_siswa_count']) }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="pkg-panel mb-6 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Pusat periode poin</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">Kelola Periode dari Satu Halaman</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat periode, aktifkan, sinkronkan poin lama, lalu audit transaksi detail tanpa pindah-pindah menu.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($activePeriod)
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
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
                Poin siswa sudah tersimpan otomatis sebagai transaksi dan histori periode.
                Tombol <strong>Sinkronkan Periode Aktif + Reset Poin Berjalan</strong> akan memindahkan saldo berjalan saat ini ke arsip periode aktif, lalu memulai lagi dari 0 tanpa menghapus riwayat lama.
            </div>

            <form method="POST" action="{{ route('admin.gamification.periods.store') }}" class="mt-5 pkg-filter-grid grid-cols-1 md:grid-cols-4 items-end">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Nama Periode</label>
                    <input type="text" name="name" placeholder="Contoh: April 2026 / Ramadan 1447" class="w-full px-3 py-2 text-sm pkg-field" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Mulai</label>
                    <input type="date" name="start_date" class="w-full px-3 py-2 text-sm pkg-field" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Selesai</label>
                    <input type="date" name="end_date" class="w-full px-3 py-2 text-sm pkg-field">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 text-sm pkg-field" placeholder="Opsional: lomba, Ramadan, penilaian bulanan, dsb."></textarea>
                </div>
                <div class="flex flex-col justify-end gap-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="activate_now" value="1" class="rounded border-gray-300 text-indigo-600">
                        Langsung aktifkan
                    </label>
                    <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm font-medium">Buat Periode</button>
                </div>
            </form>

            @if($periods->isNotEmpty())
            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
                @foreach($periods as $period)
                @php($periodSummary = $periodSummaries[$period->id] ?? ['transaction_count' => 0, 'siswa_count' => 0, 'total_points' => 0, 'incoming_points' => 0, 'outgoing_points' => 0, 'task_count' => 0, 'verified_task_count' => 0, 'pending_task_count' => 0, 'pending_task_siswa_count' => 0])
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
                            <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($periodSummary['total_points']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-slate-900/60">
                            <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Siswa</p>
                            <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($periodSummary['siswa_count']) }}</p>
                        </div>
                    </div>

                    <div class="mt-3 rounded-2xl border border-amber-200/70 bg-amber-50/80 px-4 py-3 text-xs dark:border-amber-900/40 dark:bg-amber-950/20">
                        <p class="font-semibold text-amber-700 dark:text-amber-300">
                            {{ number_format($periodSummary['pending_task_count']) }} tugas menunggu verifikasi historis
                        </p>
                        <p class="mt-1 text-amber-700/80 dark:text-amber-200/80">
                            {{ number_format($periodSummary['verified_task_count']) }} dari {{ number_format($periodSummary['task_count']) }} tugas periode ini sudah diverifikasi.
                        </p>
                        @if(($periodSummary['archived_pending_task_count'] ?? 0) > 0)
                        <p class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-200/80">
                            {{ number_format($periodSummary['archived_pending_task_count']) }} tugas pending sudah dikumpulkan ke arsip periode ini saat sinkron.
                        </p>
                        @endif
                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ number_format($periodSummary['transaction_count']) }} transaksi tercatat di periode ini.</p>
                    @if(!empty($periodSummary['last_reset_label']))
                    <p class="mt-1 inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
                        {{ $periodSummary['last_reset_label'] }}
                    </p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.gamification.transactions', ['period_id' => $period->id]) }}" title="Buka detail semua transaksi poin pada periode ini." class="pkg-btn-secondary px-3 py-2 text-xs font-medium">
                            Lihat Transaksi
                        </a>
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
                        @if(($periodSummary['archived_pending_task_count'] ?? 0) > 0)
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
                            <p><strong>Lihat Transaksi</strong>: buka detail log poin periode ini.</p>
                            <p><strong>Verifikasi Tugas</strong>: buka verifikasi tugas pada rentang periode ini.</p>
                            <p><strong>Ekspor Excel</strong>: unduh laporan periode dengan sheet ringkasan, data siswa, rekap tugas, dan transaksi poin.</p>
                            <p><strong>Sinkronkan</strong>: tempelkan transaksi lama ke periode ini.</p>
                            @if(($periodSummary['archived_pending_task_count'] ?? 0) > 0)
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
        </div>

        {{-- Reset Management Panel --}}
        <div x-data="{ showReset: false }" class="mb-6">
            <button @click="showReset = !showReset" class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                <svg class="w-4 h-4" :class="showReset ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="transition: transform 0.2s"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                Kelola Per Siswa (Reset Poin / Pin)
            </button>
            <div x-show="showReset" x-cloak x-transition class="mt-3 pkg-panel p-5">
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-4">Reset Data Gamifikasi Siswa</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Reset Character Points --}}
                    <div class="border border-orange-200 dark:border-orange-800 rounded-lg p-4 bg-orange-50 dark:bg-orange-900/10">
                        <p class="text-sm font-semibold text-orange-700 dark:text-orange-300 mb-1">Reset Poin Karakter</p>
                        <p class="text-xs text-orange-600 dark:text-orange-400 mb-3">Hapus semua transaksi karakter & reset poin karakter ke 0</p>
                        <form action="{{ route('admin.gamification.reset-character-points') }}" method="POST" data-confirm="Reset semua poin karakter siswa ini? Transaksi karakter akan dihapus permanen." data-confirm-title="Reset poin karakter" data-confirm-button="Reset" data-confirm-tone="warning">
                            @csrf
                            <select name="siswa_id" required class="w-full px-3 py-2 text-sm border border-orange-300 dark:border-orange-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-2">
                                <option value="">Pilih Siswa...</option>
                                @foreach($siswaList as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->nis }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full px-3 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition">Reset Poin Karakter</button>
                        </form>
                    </div>

                    {{-- Reset Badges --}}
                    <div class="border border-purple-200 dark:border-purple-800 rounded-lg p-4 bg-purple-50 dark:bg-purple-900/10">
                        <p class="text-sm font-semibold text-purple-700 dark:text-purple-300 mb-1">Reset Pin Penghargaan</p>
                        <p class="text-xs text-purple-600 dark:text-purple-400 mb-3">Hapus semua pin dan transaksi pin siswa</p>
                        <form action="{{ route('admin.gamification.reset-badges') }}" method="POST" data-confirm="Reset semua pin penghargaan siswa ini? Data pin akan dihapus permanen." data-confirm-title="Reset pin penghargaan" data-confirm-button="Reset" data-confirm-tone="warning">
                            @csrf
                            <select name="siswa_id" required class="w-full px-3 py-2 text-sm border border-purple-300 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-2">
                                <option value="">Pilih Siswa...</option>
                                @foreach($siswaList as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->nis }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">Reset Pin</button>
                        </form>
                    </div>

                    {{-- Full Reset --}}
                    <div class="border border-red-200 dark:border-red-800 rounded-lg p-4 bg-red-50 dark:bg-red-900/10">
                        <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-1">Reset Total</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mb-3">Hapus SEMUA transaksi, pin, poin, streak, dan level</p>
                        <form action="{{ route('admin.gamification.full-reset') }}" method="POST" data-confirm="Ini akan menghapus semua data gamifikasi siswa ini: poin, pin, streak, dan level. Lanjutkan?" data-confirm-title="Reset total gamifikasi" data-confirm-button="Reset total" data-confirm-tone="danger">
                            @csrf
                            <select name="siswa_id" required class="w-full px-3 py-2 text-sm border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white mb-2">
                                <option value="">Pilih Siswa...</option>
                                @foreach($siswaList as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->nis }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">Reset Total</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="pkg-filter-bar mb-6">
            <form method="GET" action="{{ route('admin.gamification.transactions') }}" class="pkg-filter-grid grid-cols-1 md:grid-cols-6 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Periode Poin</label>
                    <select name="period_id" class="w-full px-3 py-2 text-sm pkg-field">
                        <option value="">Semua Periode</option>
                        @foreach($periods as $period)
                        <option value="{{ $period->id }}" {{ request('period_id') == $period->id ? 'selected' : '' }}>
                            {{ $period->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Siswa</label>
                    <select name="siswa_id" class="w-full px-3 py-2 text-sm pkg-field">
                        <option value="">Semua Siswa</option>
                        @foreach($siswaList as $s)
                        <option value="{{ $s->id }}" {{ request('siswa_id') == $s->id ? 'selected' : '' }}>{{ $s->nama }} ({{ $s->nis }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Sumber</label>
                    <select name="source" class="w-full px-3 py-2 text-sm pkg-field">
                        <option value="">Semua</option>
                        <option value="attendance" {{ request('source') == 'attendance' ? 'selected' : '' }}>Kehadiran</option>
                        <option value="character" {{ request('source') == 'character' ? 'selected' : '' }}>Karakter</option>
                        <option value="manual" {{ request('source') == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="period_reset" {{ request('source') == 'period_reset' ? 'selected' : '' }}>Arsip Periode</option>
                        <option value="badge" {{ request('source') == 'badge' ? 'selected' : '' }}>Pin</option>
                        <option value="streak" {{ request('source') == 'streak' ? 'selected' : '' }}>Streak</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Tipe</label>
                    <select name="type" class="w-full px-3 py-2 text-sm pkg-field">
                        <option value="">Semua</option>
                        <option value="earned" {{ request('type') == 'earned' ? 'selected' : '' }}>+ Earned</option>
                        <option value="bonus" {{ request('type') == 'bonus' ? 'selected' : '' }}>+ Bonus</option>
                        <option value="spent" {{ request('type') == 'spent' ? 'selected' : '' }}>- Spent</option>
                        <option value="penalty" {{ request('type') == 'penalty' ? 'selected' : '' }}>- Penalty</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Cari Deskripsi</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Kata kunci..."
                        class="w-full px-3 py-2 text-sm pkg-field">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="pkg-btn-primary px-4 py-2 text-sm font-medium">Filter</button>
                    <a href="{{ route('admin.gamification.transactions') }}" class="pkg-btn-secondary px-4 py-2 text-sm font-medium">Reset</a>
                </div>
            </form>
        </div>

        {{-- Summary --}}
        <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
            <div class="pkg-card-soft rounded-2xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Transaksi</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->transaction_count ?? 0) }}</p>
            </div>
            <div class="pkg-card-soft rounded-2xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Siswa Tercatat</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->siswa_count ?? 0) }}</p>
            </div>
            <div class="pkg-card-soft rounded-2xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Poin</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->total_points ?? 0) }}</p>
            </div>
            <div class="pkg-card-soft rounded-2xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Masuk / Keluar</p>
                <p class="text-sm font-bold text-emerald-600">+{{ number_format($summary->incoming_points ?? 0) }}</p>
                <p class="text-sm font-bold text-rose-600">-{{ number_format($summary->outgoing_points ?? 0) }}</p>
            </div>
        </div>

        {{-- Transaction Table --}}
        <div class="pkg-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Deskripsi</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sumber</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Poin</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Periode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($transactions as $t)
                        <tr x-data="{ editing: false }" class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                            {{-- View Mode --}}
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-gray-400 font-mono text-xs">#{{ $t->id }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800 dark:text-white">{{ $t->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-gray-400">{{ $t->siswa->nis ?? '' }}</p>
                                </td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $t->description }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg" title="{{ $t->source }}">{{ $t->icon }}</span>
                                    <span class="block text-xs text-gray-400 capitalize">{{ $t->source_label }}</span>
                                </td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-right font-bold {{ $t->color }}">{{ $t->formatted_points }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ data_get($t->metadata, 'period_name') ?? '-' }}
                                </td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $t->created_at->format('d M Y') }}
                                    <br>{{ $t->created_at->format('H:i') }}
                                </td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="editing = true" class="rounded-lg p-1.5 transition hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Edit">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <form action="{{ route('admin.gamification.transactions.destroy', $t->id) }}" method="POST" data-confirm="Hapus transaksi #{{ $t->id }}? Poin akan dihitung ulang." data-confirm-title="Hapus transaksi" data-confirm-button="Hapus" data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rounded-lg p-1.5 transition hover:bg-red-50 dark:hover:bg-red-900/20" title="Hapus">
                                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </template>

                            {{-- Edit Mode --}}
                            <template x-if="editing">
                                <td colspan="8" class="px-4 py-3">
                                    <form action="{{ route('admin.gamification.transactions.update', $t->id) }}" method="POST" class="flex items-center gap-3 flex-wrap">
                                        @csrf @method('PUT')
                                        <span class="text-xs text-gray-400 font-mono">#{{ $t->id }}</span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t->siswa->nama ?? '-' }}</span>
                                        <input type="text" name="description" value="{{ $t->description }}" required
                                            class="flex-1 min-w-[200px] px-3 py-1.5 text-sm pkg-field">
                                        <input type="number" name="points" value="{{ $t->points }}" required
                                            class="w-24 px-3 py-1.5 text-sm pkg-field text-right">
                                        <span class="text-xs text-gray-400">poin</span>
                                        <button type="submit" class="pkg-btn-primary px-3 py-1.5 text-xs font-medium">Simpan</button>
                                        <button type="button" @click="editing = false" class="pkg-btn-secondary px-3 py-1.5 text-xs font-medium">Batal</button>
                                    </form>
                                </td>
                            </template>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-0">
                                <div class="pkg-empty-state">
                                    <h3 class="pkg-empty-title">Tidak ada transaksi ditemukan</h3>
                                    <p class="pkg-empty-copy">Ubah filter periode, siswa, sumber, tipe, atau kata kunci untuk melihat hasil lain.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>
</div>
@endsection
