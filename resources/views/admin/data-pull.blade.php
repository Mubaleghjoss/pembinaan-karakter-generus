@extends('layouts.app')

@section('title', 'Tarik Data dari Server Online')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8" x-data="dataPull()">
    <div
        x-show="loading"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-md rounded-xl border border-white/10 bg-white p-6 text-center shadow-2xl dark:bg-slate-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                <span class="h-8 w-8 animate-spin rounded-full border-4 border-current border-t-transparent"></span>
            </div>
            <h2 class="mt-4 text-base font-semibold text-slate-900 dark:text-white" x-text="loadingTitle"></h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300" x-text="loadingCopy"></p>
            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                Jangan tutup halaman sampai proses selesai.
            </p>
        </div>
    </div>

    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Tarik Data dari Server Online</h1>
            <p class="pkg-page-subheading">Tarik semua data dari server online untuk menimpa data lokal.</p>
        </div>
    </div>

    @if(session('success') || (!session('error') && $lastPullNotice))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
        {{ session('success') ?? $lastPullNotice }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        {{ session('error') }}
    </div>
    @endif

    <div class="pkg-panel p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">API Key Ekspor</h2>
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
            Atur API key yang dipakai untuk melindungi data server ini saat ditarik oleh server lain.
            Server lain harus memakai key yang sama agar bisa menarik data dari sini.
        </p>

        <form method="POST" action="{{ route('admin.data-pull.save-export-key') }}" class="flex flex-col gap-3 sm:flex-row">
            @csrf
            <input
                type="text"
                name="export_key"
                value="{{ $exportKey }}"
                required
                placeholder="buat-key-rahasia-disini"
                class="flex-1 pkg-field text-sm font-mono"
            >
            <button type="submit" class="btn-primary whitespace-nowrap !px-4 !py-2 text-sm">
                Simpan Key
            </button>
        </form>

        @if($exportKey)
        <p class="mt-2 text-xs text-green-600 dark:text-green-400">API key sudah diset. Server lain bisa menarik data dari server ini.</p>
        @else
        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">API key belum diset. Server lain belum bisa menarik data dari sini.</p>
        @endif
    </div>

    <div class="pkg-panel p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Konfigurasi Tarik Data</h2>

        <form method="POST" action="{{ route('admin.data-pull.save-settings') }}" class="space-y-4" x-ref="settingsForm">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        URL Server Online <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="url"
                        name="server_url"
                        value="{{ $serverUrl }}"
                        required
                        placeholder="https://pkg.example.com"
                        class="w-full pkg-field text-sm"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contoh: https://pkg.jariyah.com</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="api_key"
                        value="{{ $apiKey }}"
                        required
                        placeholder="masukkan-api-key-server"
                        class="w-full pkg-field text-sm font-mono"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Harus sama dengan API Key Ekspor yang diset pada server online.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200">
                Isi <strong>URL server online</strong> dan <strong>API Key yang sama</strong> di halaman ini juga.
                Key ekspor yang disimpan di server online tidak otomatis ikut terisi di server lokal.
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary !px-4 !py-2 text-sm">Simpan Konfigurasi</button>
                <button
                    type="button"
                    @click="testConnection()"
                    :disabled="testing || saving"
                    class="btn-secondary !px-4 !py-2 text-sm disabled:opacity-50"
                >
                    <span x-show="!testing && !saving">Tes Koneksi</span>
                    <span x-show="testing">Mengecek...</span>
                    <span x-show="!testing && saving">Menyimpan...</span>
                </button>
            </div>
        </form>

        <div
            x-show="testResult"
            x-cloak
            class="mt-4 rounded-lg border p-3 text-sm"
            :class="testResult?.success
                ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-200'
                : 'border-red-200 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-900/20 dark:text-red-200'"
        >
            <p class="font-semibold" x-text="testResult?.success ? 'Koneksi berhasil' : 'Koneksi gagal'"></p>
            <p class="mt-1" x-text="testResult?.message"></p>
            <template x-if="testResult?.success">
                <div class="mt-1 text-xs">
                    <span>Server: <strong x-text="testResult?.server"></strong></span>
                    <span class="mx-1">|</span>
                    <span>Tabel tersedia: <strong x-text="testResult?.tables_available"></strong></span>
                </div>
            </template>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 rounded-xl bg-amber-100 p-2 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86l-7.19 12.47A2 2 0 004.81 19h14.38a2 2 0 001.71-2.67L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-amber-800 dark:text-amber-200">Peringatan</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Proses ini akan <strong>menimpa semua data lokal</strong> dengan data dari server online.
                    Pastikan Anda sudah membackup data lokal jika diperlukan. Proses ini tidak bisa dibatalkan.
                </p>
            </div>
        </div>
    </div>

    <div class="pkg-panel p-6 mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tarik Data Sekarang</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($lastPull)
                        Terakhir ditarik: <strong>{{ \Carbon\Carbon::parse($lastPull)->isoFormat('D MMM YYYY HH:mm') }}</strong>
                    @else
                        Belum pernah menarik data
                    @endif
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.data-pull.pull') }}"
                @submit.prevent="confirmAndSubmit($event, 'Menarik data dan mengunduh semua file media', 'Data lokal sedang ditimpa dengan data server. File media yang sudah ada juga akan diunduh ulang.')"
                data-confirm="Semua data lokal akan ditimpa dengan data dari server online. Lanjutkan?"
                data-confirm-title="Tarik data dari server"
                data-confirm-button="Tarik data"
                data-confirm-tone="danger"
            >
                @csrf
                <input type="hidden" name="limit" value="12">
                <button
                    type="submit"
                    :disabled="!configReady"
                    class="inline-flex items-center rounded-lg bg-red-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    {{ !$serverUrl || !$apiKey ? 'disabled' : '' }}
                >
                    Tarik Data Sekarang
                </button>
            </form>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <form
                method="POST"
                action="{{ route('admin.data-pull.sync-media') }}"
                @submit.prevent="confirmAndSubmit($event, 'Mengunduh file media dari server', 'Sistem sedang mengambil file media yang belum lengkap di lokal.')"
                data-confirm="Sinkron ulang hanya file media dari server online tanpa menimpa tabel data lokal. Lanjutkan?"
                data-confirm-title="Sinkron ulang media"
                data-confirm-button="Sinkron media"
                data-confirm-tone="primary"
            >
                @csrf
                <input type="hidden" name="limit_mode" value="half">
                <button
                    type="submit"
                    :disabled="!configReady"
                    class="btn-secondary px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                    {{ !$serverUrl || !$apiKey ? 'disabled' : '' }}
                >
                    Sinkron Ulang Media Saja
                </button>
            </form>
            <form
                method="POST"
                action="{{ route('admin.data-pull.sync-media') }}"
                @submit.prevent="confirmAndSubmit($event, 'Mencoba ulang file tidak tersedia', 'Sistem sedang mengecek ulang dan mengunduh file media dari server.')"
                data-confirm="Daftar file yang sebelumnya tidak tersedia akan dicoba ulang. Pakai ini setelah file bukti dipulihkan di cPanel. Lanjutkan?"
                data-confirm-title="Coba ulang file tidak tersedia"
                data-confirm-button="Coba ulang"
                data-confirm-tone="primary"
            >
                @csrf
                <input type="hidden" name="limit_mode" value="all">
                <input type="hidden" name="retry_unavailable" value="1">
                <button
                    type="submit"
                    :disabled="!configReady"
                    class="btn-secondary px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                    {{ !$serverUrl || !$apiKey ? 'disabled' : '' }}
                >
                    Coba Ulang File Tidak Tersedia
                </button>
            </form>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Gunakan ini setelah kode sync di server online diperbarui atau setelah file bukti dipulihkan di cPanel.
            </p>
            <a
                href="{{ route('admin.data-pull.unavailable-media-report') }}"
                class="btn-secondary px-4 py-2 text-sm"
            >
                Unduh Daftar Media Tidak Tersedia
            </a>
        </div>
        <p x-show="!configReady" x-cloak class="mt-3 text-xs text-amber-600 dark:text-amber-400">
            Tes koneksi sukses akan otomatis menyimpan konfigurasi lokal dan mengaktifkan tombol tarik data.
        </p>
    </div>

    @php
        $pullResult = session('pullResult') ?? ($lastPullResult ? json_decode($lastPullResult, true) : null);
    @endphp

    @if($pullResult && isset($pullResult['details']))
    <div class="pkg-panel overflow-hidden">
        <div class="border-b bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Hasil Tarik Data</h2>
            <div class="mt-2 flex flex-wrap gap-4 text-sm">
                <span class="text-green-600 dark:text-green-400">{{ $pullResult['total_success'] ?? 0 }} berhasil</span>
                <span class="text-red-600 dark:text-red-400">{{ $pullResult['total_failed'] ?? 0 }} gagal</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $pullResult['total_skipped'] ?? 0 }} dilewati</span>
                @if(!empty($pullResult['media']))
                <span class="text-sky-600 dark:text-sky-400">{{ $pullResult['media']['downloaded'] ?? 0 }} file media tersalin</span>
                <span class="text-rose-600 dark:text-rose-400">{{ $pullResult['media']['failed'] ?? 0 }} file media gagal</span>
                @if(($pullResult['media']['unavailable'] ?? 0) > 0)
                    <span class="text-amber-600 dark:text-amber-300">{{ $pullResult['media']['unavailable'] }} media tidak tersedia</span>
                @endif
                @if(($pullResult['media']['remaining'] ?? 0) > 0)
                    <span class="text-amber-600 dark:text-amber-300">{{ $pullResult['media']['remaining'] }} media belum tersalin</span>
                @endif
                @endif
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $pullResult['pulled_at'] ?? '' }}</span>
            </div>
        </div>

        <div class="overflow-x-auto pkg-mobile-table">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Tabel</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Jumlah Record</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($pullResult['details'] as $table => $detail)
                    <tr x-data="{ detailOpen: false }" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td data-label="#" class="px-4 py-3 text-sm text-gray-500">{{ $loop->iteration }}</td>
                        <td data-label="Tabel" class="px-4 py-3 text-sm font-mono text-gray-800 dark:text-white pkg-mobile-main">{{ $table }}</td>
                        <td data-label="Status" class="px-4 py-3 text-center">
                            @if($detail['status'] === 'success')
                                <span class="pkg-status-badge pkg-status-success">Berhasil</span>
                            @elseif($detail['status'] === 'skipped')
                                <span class="pkg-status-badge pkg-status-neutral">Dilewati</span>
                            @else
                                <span class="pkg-status-badge pkg-status-danger">Gagal</span>
                            @endif
                        </td>
                        <td data-label="Jumlah Record" class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            {{ number_format($detail['count'] ?? 0) }}
                        </td>
                        <td data-label="Keterangan" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex items-center justify-between gap-3">
                                <span class="block min-w-0 truncate">{{ $detail['message'] ?? '-' }}</span>
                                <button
                                    type="button"
                                    @click="detailOpen = !detailOpen"
                                    class="pkg-btn-secondary shrink-0 px-3 py-1 text-xs"
                                >
                                    <span x-text="detailOpen ? 'Tutup' : 'Detail'"></span>
                                </button>
                            </div>
                            <div x-show="detailOpen" x-cloak class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300">
                                <p class="font-semibold text-slate-900 dark:text-white">Detail {{ $table }}</p>
                                <pre class="mt-2 whitespace-pre-wrap break-words font-mono text-[11px] leading-5">{{ $detail['message'] ?? '-' }}</pre>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(!empty($pullResult['media']))
    <div class="pkg-panel mt-6 overflow-hidden">
        <div class="border-b bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Hasil Sinkron File Media</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                File yang direferensikan data hasil tarik akan dicoba disalin dari server online ke `storage/app/public` lokal.
            </p>
            @if(!empty($pullResult['media']['details_note']))
                <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">{{ $pullResult['media']['details_note'] }}</p>
            @endif
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs sm:grid-cols-7">
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Total referensi</div>
                    <div class="font-semibold text-slate-900 dark:text-white">{{ number_format($pullResult['media']['total'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Sudah lokal</div>
                    <div class="font-semibold text-slate-900 dark:text-white">{{ number_format($pullResult['media']['already_local'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Diproses batch</div>
                    <div class="font-semibold text-slate-900 dark:text-white">
                        {{ number_format($pullResult['media']['attempted'] ?? 0) }}/{{ number_format($pullResult['media']['batch_limit'] ?? 0) }}
                    </div>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Berhasil batch</div>
                    <div class="font-semibold text-emerald-600">{{ number_format($pullResult['media']['downloaded'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Belum tersalin</div>
                    <div class="font-semibold text-amber-600">{{ number_format($pullResult['media']['remaining'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Tidak tersedia</div>
                    <div class="font-semibold text-rose-600">{{ number_format($pullResult['media']['unavailable'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <div class="text-slate-500 dark:text-slate-400">Batch berikutnya</div>
                    <div class="font-semibold text-slate-900 dark:text-white">
                        {{ number_format($pullResult['media']['next_cursor'] ?? 0) }}/{{ number_format($pullResult['media']['total'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Path</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse(($pullResult['media']['details'] ?? []) as $mediaDetail)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td data-label="#" class="px-4 py-3 text-sm text-gray-500">{{ $loop->iteration }}</td>
                        <td data-label="Path" class="px-4 py-3 text-sm font-mono text-gray-800 dark:text-white break-all pkg-mobile-main">{{ $mediaDetail['path'] ?? '-' }}</td>
                        <td data-label="Status" class="px-4 py-3 text-center">
                            @if(($mediaDetail['status'] ?? null) === 'success')
                                <span class="pkg-status-badge pkg-status-success">Tersalin</span>
                            @elseif(($mediaDetail['status'] ?? null) === 'missing')
                                <span class="pkg-status-badge pkg-status-neutral">Tidak tersedia</span>
                            @else
                                <span class="pkg-status-badge pkg-status-danger">Gagal</span>
                            @endif
                        </td>
                        <td data-label="Keterangan" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $mediaDetail['message'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 pkg-mobile-empty">
                            Tidak ada file media yang perlu disalin.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif
</div>

<script>
function dataPull() {
    return {
        testing: false,
        saving: false,
        loading: false,
        loadingTitle: 'Memproses',
        loadingCopy: 'Mohon tunggu sampai proses selesai.',
        configReady: {{ ($serverUrl && $apiKey) ? 'true' : 'false' }},
        testResult: null,

        confirmAndSubmit(event, title, copy) {
            const form = event.target;
            const message = form?.dataset?.confirm;

            if (message && !window.confirm(message)) {
                return;
            }

            this.loadingTitle = title || 'Memproses';
            this.loadingCopy = copy || 'Mohon tunggu sampai proses selesai.';
            this.loading = true;

            this.$nextTick(() => {
                form.submit();
            });
        },

        async saveSettings(payload) {
            this.saving = true;

            try {
                const response = await fetch('{{ route("admin.data-pull.save-settings") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result?.success) {
                    throw new Error(result?.message || 'Konfigurasi lokal gagal disimpan.');
                }

                this.configReady = true;

                if (this.testResult?.success) {
                    this.testResult.message = `${this.testResult.message} Konfigurasi lokal juga sudah disimpan.`;
                }
            } finally {
                this.saving = false;
            }
        },

        async testConnection() {
            this.testing = true;
            this.testResult = null;

            try {
                const form = this.$refs.settingsForm;
                const payload = {
                    server_url: form?.querySelector('[name="server_url"]')?.value?.trim() || '',
                    api_key: form?.querySelector('[name="api_key"]')?.value?.trim() || '',
                };

                const response = await fetch('{{ route("admin.data-pull.test") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                this.testResult = await response.json();

                if (response.ok && this.testResult?.success) {
                    await this.saveSettings(payload);
                }
            } catch (error) {
                this.testResult = {
                    success: false,
                    message: 'Gagal menghubungi server: ' + error.message
                };
            } finally {
                this.testing = false;
            }
        }
    };
}
</script>
@endsection
