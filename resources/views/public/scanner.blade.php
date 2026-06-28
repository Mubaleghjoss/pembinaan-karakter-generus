@extends('layouts.public')

@section('title', 'Pindai Presensi - ' . ($theme->app_name ?? 'PKG Presensi'))

@php
    $publicScannerEntry = 'resources/js/public-scanner.js';
    $publicScannerManifestPath = public_path('build/manifest.json');
    $publicScannerManifest = [];

    if (is_file($publicScannerManifestPath)) {
        $decodedManifest = json_decode(file_get_contents($publicScannerManifestPath), true);
        $publicScannerManifest = is_array($decodedManifest) ? $decodedManifest : [];
    }

    $publicScannerManifestFile = $publicScannerManifest[$publicScannerEntry]['file'] ?? null;
    $publicScannerManifestFileExists = $publicScannerManifestFile
        ? is_file(public_path('build/' . $publicScannerManifestFile))
        : false;
    $publicScannerFallbackFiles = glob(public_path('build/assets/public-scanner-*.js')) ?: [];
    $publicScannerFallbackFile = $publicScannerFallbackFiles[0] ?? null;
    $publicScannerFallbackUrl = $publicScannerFallbackFile
        ? asset('build/assets/' . basename($publicScannerFallbackFile))
        : null;
    $publicScannerAssetAvailable = (bool) ($publicScannerManifestFileExists || $publicScannerFallbackUrl);
@endphp

@section('content')
<div class="py-12 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <section class="pkg-public-hero-card p-6 sm:p-8 lg:p-10 mb-8" data-reveal="zoom">
            <div class="relative z-10 grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(260px,0.7fr)] lg:items-center">
                <div>
                    <span class="pkg-glass-badge text-sm font-semibold">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.72)]"></span>
                        Presensi digital
                    </span>
                    <h1 class="pkg-page-heading mt-5 text-4xl">Pindai presensi lebih cepat dan lebih jelas.</h1>
                    <p class="pkg-page-subheading mt-3 text-base sm:text-lg">Arahkan kamera ke kode QR atau unggah gambar barcode presensi untuk mencatat kehadiran secara aman.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="pkg-hero-kpi">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Status</p>
                        <p class="text-lg font-black text-slate-950 dark:text-white">{{ $scheduleStatusLabel }}</p>
                    </div>
                    <div class="pkg-hero-kpi">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Mode</p>
                        <p class="text-lg font-black text-slate-950 dark:text-white">Kamera QR</p>
                    </div>
                    <div class="pkg-hero-kpi">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Akses</p>
                        <p class="text-lg font-black text-slate-950 dark:text-white">Instan</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Active Attendance Activities -->
        <div class="pkg-surface rounded-2xl p-6 mb-8 border-2 {{ $isOpen ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800' }}" data-reveal="up">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-300">Kegiatan Presensi Aktif</p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Presensi Untuk Kegiatan</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">
                        Pastikan kegiatan di bawah ini sesuai sebelum memindai kode QR.
                    </p>
                </div>
                <div class="flex flex-col items-start gap-1 sm:items-end">
                    <span class="inline-flex w-max rounded-full px-3 py-1 text-sm font-bold {{ $isOpen ? 'bg-green-100 text-green-700 dark:bg-green-950/60 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-200' }}">
                        {{ $scheduleStatusLabel }}
                    </span>
                    <span class="text-sm font-medium text-gray-600 dark:text-slate-300">{{ $scheduleStatusHint }}</span>
                </div>
            </div>

            @forelse($scheduleCards as $scheduleCard)
                <div class="pkg-list-card mb-4 rounded-xl p-4 last:mb-0 dark:bg-slate-950/60">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $scheduleCard['name'] }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $scheduleCard['is_open'] ? 'bg-green-100 text-green-700 dark:bg-green-950/60 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-200' }}">
                                    {{ $scheduleCard['status_label'] }}
                                </span>
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/60 dark:text-blue-200">
                                    Target: {{ $scheduleCard['target_label'] }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    Tanggal: {{ $scheduleCard['date_range'] }}
                                </span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-slate-300">{{ $scheduleCard['description'] ?: 'Belum ada deskripsi kegiatan.' }}</p>
                            @if(!$scheduleCard['is_open'])
                                <p class="mt-2 text-sm font-semibold text-yellow-700 dark:text-yellow-200">{{ $scheduleCard['next_start_text'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/40">
                            <p class="text-xs font-semibold uppercase text-green-600 dark:text-green-300">Waktu Presensi Dimulai</p>
                            <p class="text-lg font-bold text-green-700 dark:text-green-200">{{ $scheduleCard['open_time'] }}</p>
                        </div>
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-800 dark:bg-yellow-950/40">
                            <p class="text-xs font-semibold uppercase text-yellow-600 dark:text-yellow-300">Batas Presensi Tepat Waktu</p>
                            <p class="text-lg font-bold text-yellow-700 dark:text-yellow-200">{{ $scheduleCard['late_threshold'] }}</p>
                        </div>
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/40">
                            <p class="text-xs font-semibold uppercase text-red-600 dark:text-red-300">Waktu Presensi Ditutup</p>
                            <p class="text-lg font-bold text-red-700 dark:text-red-200">{{ $scheduleCard['close_time'] }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-900/70 dark:bg-blue-950/30">
                        <p class="mb-2 text-sm font-bold text-blue-800 dark:text-blue-200">Hari Presensi Aktif</p>
                        <div class="flex flex-wrap gap-2">
                            @if(count($scheduleCard['active_days']) > 0)
                                @foreach($scheduleCard['active_days'] as $day)
                                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $day === $currentDayId ? 'bg-blue-600 text-white' : 'bg-white text-blue-700 border border-blue-300 dark:bg-slate-900 dark:text-blue-200 dark:border-blue-900/60' }}">
                                        {{ $day }}{{ $day === $currentDayId ? ' (Hari Ini)' : '' }}
                                    </span>
                                @endforeach
                            @else
                                <span class="rounded-full bg-blue-600 px-3 py-1 text-sm font-semibold text-white">Setiap Hari</span>
                            @endif
                        </div>
                        @if(!$scheduleCard['is_today_active'])
                            <p class="mt-3 text-sm font-medium text-red-600 dark:text-red-300">
                                Hari ini ({{ $currentDayId }}) bukan hari presensi untuk kegiatan ini.
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="pkg-empty-state">
                    <div class="pkg-empty-icon">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="pkg-empty-title">Belum ada kegiatan aktif</h3>
                    <p class="pkg-empty-copy">Belum ada jadwal presensi yang berlaku untuk hari ini atau tanggal mendatang.</p>
                </div>
            @endforelse
        </div>

        <!-- Scanner Component -->
        <div class="max-w-md mx-auto pkg-panel-lg overflow-hidden" data-reveal="up">
            <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center">
                <h2 class="text-2xl font-bold mb-1">Pindai Kode QR</h2>
                <p class="text-blue-100 text-sm">Arahkan kamera ke barcode presensi peserta</p>
            </div>

            <div class="p-6">
                @unless($publicScannerAssetAvailable)
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                        <p class="text-sm font-bold uppercase tracking-wide">Asset pemindai belum tersedia</p>
                        <p class="mt-1 text-sm leading-relaxed">
                            File build pemindai belum ditemukan di server. Upload ulang folder <span class="font-mono">public/build</span> dari hasil build lokal agar fitur kamera dapat berjalan.
                        </p>
                    </div>
                @endunless

                <!-- Error Message -->
                <div id="error-message" class="hidden mb-6 p-4 bg-red-50 text-red-600 rounded-xl border border-red-100 flex items-start gap-3 dark:bg-red-950/30 dark:border-red-800">
                    <div class="mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" x2="12" y1="8" y2="12" /><line x1="12" x2="12.01" y1="16" y2="16" /></svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-sm uppercase tracking-wide mb-1">Gagal Memproses</p>
                        <p id="error-text" class="whitespace-pre-line text-sm leading-relaxed text-red-700 dark:text-red-100"></p>
                    </div>
                    <button onclick="closeError()" class="text-red-400 hover:text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                    </button>
                </div>

                <!-- Success Message -->
                <div id="success-message" class="hidden mb-6 p-6 bg-green-50 text-green-900 rounded-xl border-2 border-green-200 dark:bg-green-950/30 dark:border-green-800 dark:text-green-100">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Presensi Berhasil!</h3>
                        <p id="success-text" class="text-green-700 dark:text-green-100 mb-4"></p>
                        <button onclick="resetScanner()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition-colors">
                            Pindai Lagi
                        </button>
                    </div>
                </div>

                <!-- Start Button -->
                <div id="start-section" class="text-center py-8">
                    <div class="mb-8 relative inline-block">
                        <div class="absolute inset-0 bg-blue-200 rounded-full animate-ping opacity-20"></div>
                        <div class="bg-blue-50 p-6 rounded-full relative z-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M3 7V5a2 2 0 0 1 2-2h2" /><path d="M17 3h2a2 2 0 0 1 2 2v2" /><path d="M21 17v2a2 2 0 0 1-2 2h-2" /><path d="M7 21H5a2 2 0 0 1-2-2v-2" /><rect width="10" height="10" x="7" y="7" rx="2" /><path d="m16 16-.01-.01" /></svg>
                        </div>
                    </div>
                    <button
                        onclick="startScanning()"
                        @disabled(!$publicScannerAssetAvailable)
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 flex items-center justify-center gap-3"
                    >
                        Mulai Pindai Kamera
                    </button>
                    <p class="mt-4 text-sm text-gray-400 dark:text-slate-400">Pastikan browser diizinkan mengakses kamera</p>
                </div>

                <!-- Scanner Container -->
                <div id="scanner-section" class="hidden">
                    <!-- Step Indicator -->
                    <div class="mb-4 p-4 bg-blue-50 rounded-xl border border-blue-200 dark:bg-blue-950/30 dark:border-blue-900/70">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">!</div>
                            <span class="font-bold text-blue-800 dark:text-blue-200">Langkah Selanjutnya:</span>
                        </div>
                        <p class="text-sm text-blue-700 dark:text-blue-100 ml-8">
                            Klik <strong class="text-blue-900 dark:text-blue-200">Izinkan Kamera</strong> untuk memindai langsung,
                            atau pilih <strong class="text-green-700 dark:text-green-200">Pindai dari Gambar</strong> jika barcode sudah tersimpan di HP atau laptop.
                        </p>
                    </div>
                    
                    <div id="reader" class="rounded-xl border-2 border-blue-500 shadow-inner overflow-hidden"></div>
                    <button
                        onclick="stopScanning()"
                        class="mt-6 w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold transition-colors dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Batalkan
                    </button>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="pkg-surface rounded-2xl p-8 mt-8" data-reveal="up">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Cara Menggunakan
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-blue-600">1</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">Klik Mulai Pindai</h3>
                    <p class="text-gray-600 dark:text-slate-300 text-sm">Klik tombol "Mulai Pindai Kamera" untuk mengaktifkan kamera</p>
                </div>
                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-green-600">2</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">Arahkan ke Kode QR</h3>
                    <p class="text-gray-600 dark:text-slate-300 text-sm">Arahkan kamera ke barcode presensi pada kartu peserta Anda</p>
                </div>
                <div class="text-center">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-purple-600">3</span>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">Selesai!</h3>
                    <p class="text-gray-600 dark:text-slate-300 text-sm">Presensi Anda akan tercatat secara otomatis</p>
                </div>
            </div>
        </div>

        <!-- Tips -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mt-6 dark:bg-amber-950/30 dark:border-amber-700" data-reveal="up">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="font-bold text-yellow-900 dark:text-amber-200 mb-2">Tips Penting:</h3>
                    <ul class="text-yellow-800 dark:text-amber-100 text-sm space-y-1 list-disc list-inside">
                        <li>Pastikan kode QR terlihat jelas dan tidak buram</li>
                        <li>Gunakan pencahayaan yang cukup</li>
                        <li>Izinkan browser mengakses kamera Anda</li>
                        <li>Pastikan Anda berada dalam waktu presensi yang ditentukan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom styling untuk tombol html5-qrcode */
    #reader__dashboard_section_csr button,
    #reader__dashboard_section_fsr button,
    #reader button {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
        color: white !important;
        padding: 14px 28px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 16px !important;
        border: none !important;
        cursor: pointer !important;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4) !important;
        transition: all 0.3s ease !important;
        margin: 8px 4px !important;
        text-transform: none !important;
    }
    
    #reader__dashboard_section_csr button:hover,
    #reader__dashboard_section_fsr button:hover,
    #reader button:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5) !important;
    }
    
    /* Tombol Stop Scanning - warna merah */
    #reader__dashboard_section_csr button[id*="stop"],
    #reader button[id*="stop"],
    #html5-qrcode-button-camera-stop {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4) !important;
    }
    
    #reader__dashboard_section_csr button[id*="stop"]:hover,
    #reader button[id*="stop"]:hover,
    #html5-qrcode-button-camera-stop:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5) !important;
    }
    
    /* Tombol File Scan - warna hijau */
    #reader__filescan_input + button,
    #reader__dashboard_section_fsr button,
    #html5-qrcode-button-file-selection {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4) !important;
    }
    
    #reader__filescan_input + button:hover,
    #reader__dashboard_section_fsr button:hover,
    #html5-qrcode-button-file-selection:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5) !important;
    }
    
    /* Styling untuk select dropdown kamera */
    #reader select,
    #reader__camera_selection {
        padding: 12px 16px !important;
        border-radius: 10px !important;
        border: 2px solid #e5e7eb !important;
        font-size: 14px !important;
        background-color: white !important;
        cursor: pointer !important;
        margin: 8px 0 !important;
        width: 100% !important;
        max-width: 300px !important;
    }
    
    #reader select:focus,
    #reader__camera_selection:focus {
        border-color: #3b82f6 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
    }
    
    /* Styling untuk text/label */
    #reader__dashboard_section_csr span,
    #reader__dashboard_section_fsr span,
    #reader span {
        color: #374151 !important;
        font-size: 14px !important;
        font-weight: 500 !important;
    }
    
    /* Styling untuk link switch mode */
    #reader__dashboard_section_swaplink,
    #reader a {
        color: #3b82f6 !important;
        font-weight: 600 !important;
        text-decoration: underline !important;
        cursor: pointer !important;
        font-size: 14px !important;
    }
    
    #reader__dashboard_section_swaplink:hover,
    #reader a:hover {
        color: #1d4ed8 !important;
    }
    
    /* Container styling */
    #reader__dashboard_section {
        padding: 16px !important;
        background: #f9fafb !important;
        border-radius: 12px !important;
        margin-top: 12px !important;
    }
    
    /* Header text styling */
    #reader__header_message {
        color: #1f2937 !important;
        font-weight: 600 !important;
        font-size: 15px !important;
        margin-bottom: 12px !important;
    }
    
    /* File input styling */
    #reader input[type="file"] {
        padding: 10px !important;
        border: 2px dashed #d1d5db !important;
        border-radius: 10px !important;
        background: #f9fafb !important;
        cursor: pointer !important;
        width: 100% !important;
        margin: 8px 0 !important;
    }
    
    #reader input[type="file"]:hover {
        border-color: #3b82f6 !important;
        background: #eff6ff !important;
    }

    .dark #reader__dashboard_section {
        background: #0f172a !important;
    }

    .dark #reader__dashboard_section_csr span,
    .dark #reader__dashboard_section_fsr span,
    .dark #reader span,
    .dark #reader__header_message {
        color: #e2e8f0 !important;
    }

    .dark #reader select,
    .dark #reader__camera_selection {
        background-color: #020617 !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }

    .dark #reader input[type="file"] {
        background: #020617 !important;
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    .dark #reader input[type="file"]:hover {
        background: #0f172a !important;
        border-color: #2563eb !important;
    }
</style>
@endsection

@push('scripts')
    @if($publicScannerManifestFileExists)
        @vite([$publicScannerEntry])
    @elseif($publicScannerFallbackUrl)
        <script type="module" src="{{ $publicScannerFallbackUrl }}"></script>
    @else
        <script>
            window.startScanning = function () {
                const errorText = document.getElementById('error-text');
                if (errorText) {
                    errorText.textContent = 'Asset pemindai belum tersedia di server. Upload ulang folder public/build dari hasil npm run build lokal.';
                }
                document.getElementById('error-message')?.classList.remove('hidden');
            };
        </script>
    @endif
@endpush
