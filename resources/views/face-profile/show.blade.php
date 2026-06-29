@extends($subjectType === 'siswa' ? 'layouts.siswa' : 'layouts.app')

@section('title', 'Pendaftaran Wajah Presensi')

@php
    $faceAttendanceEntry = 'resources/js/face-attendance.js';
    $faceAttendanceManifestPath = public_path('build/manifest.json');
    $faceAttendanceManifest = [];

    if (is_file($faceAttendanceManifestPath)) {
        $decodedManifest = json_decode(file_get_contents($faceAttendanceManifestPath), true);
        $faceAttendanceManifest = is_array($decodedManifest) ? $decodedManifest : [];
    }

    $faceAttendanceManifestFile = $faceAttendanceManifest[$faceAttendanceEntry]['file'] ?? null;
    $faceAttendanceManifestFileExists = $faceAttendanceManifestFile
        ? is_file(public_path('build/' . $faceAttendanceManifestFile))
        : false;
    $faceAttendanceFallbackFiles = glob(public_path('build/assets/face-attendance-*.js')) ?: [];
    $faceAttendanceFallbackFile = $faceAttendanceFallbackFiles[0] ?? null;
    $faceAttendanceFallbackUrl = $faceAttendanceFallbackFile
        ? asset('build/assets/' . basename($faceAttendanceFallbackFile))
        : null;
    $faceAttendanceAssetAvailable = (bool) ($faceAttendanceManifestFileExists || $faceAttendanceFallbackUrl);
@endphp

@section('content')
<div class="w-full px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Pendaftaran Wajah Presensi</h1>
            <p class="pkg-page-subheading">Daftarkan wajah awal untuk {{ $subjectLabel }} agar scan wajah publik bisa mencatat presensi.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ $backUrl }}" class="btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section
            class="pkg-panel-lg overflow-hidden"
            data-face-enrollment
            data-enroll-url="{{ $enrollUrl }}"
            data-csrf-token="{{ csrf_token() }}"
            data-model-base-path="{{ asset('vendor/human/models') }}"
        >
            <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Ambil Foto Wajah Awal</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Posisikan wajah dan bahu di tengah frame. Sistem akan mengambil foto otomatis saat wajah stabil.</p>
            </div>

            @unless($faceAttendanceAssetAvailable)
                <div class="m-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                    <p class="text-sm font-bold uppercase tracking-wide">Asset scan wajah belum tersedia</p>
                    <p class="mt-1 text-sm leading-relaxed">Jalankan build frontend dan upload folder <span class="font-mono">public/build</span> agar pendaftaran wajah dapat berjalan.</p>
                </div>
            @endunless

            <div class="p-5">
                <div data-face-status class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-100">
                    Klik mulai kamera untuk memuat model wajah.
                </div>

                <div class="face-capture-frame">
                    <video data-face-video autoplay playsinline muted></video>
                    <canvas data-face-canvas class="hidden"></canvas>
                    <div class="face-guide" aria-hidden="true">
                        <div class="face-guide__head"></div>
                        <div class="face-guide__shoulders"></div>
                    </div>
                    <div class="face-auto-scan" aria-hidden="true"></div>
                    <div class="face-scan-hud">
                        <span data-face-phase>Siap</span>
                        <span data-face-progress>0%</span>
                    </div>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div data-face-progress-bar class="h-full w-0 rounded-full bg-emerald-500 transition-all duration-300"></div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        class="btn-primary justify-center"
                        data-face-action="start-enrollment"
                        @disabled(!$faceAttendanceAssetAvailable)
                    >
                        Mulai Kamera
                    </button>
                    <button type="button" class="btn-secondary justify-center" data-face-action="stop">
                        Hentikan Kamera
                    </button>
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="pkg-card p-5">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Status Wajah</h3>
                @if($faceProfile)
                    <div class="mt-4 overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900/70">
                        @if($faceProfile->photo_path)
                            <img src="{{ Storage::disk('public')->url($faceProfile->photo_path) }}" alt="Foto wajah terdaftar" class="h-48 w-full object-cover">
                        @endif
                        <div class="bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100">
                            Wajah sudah terdaftar pada {{ $faceProfile->created_at?->format('d M Y H:i') }}.
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Belum ada data wajah aktif untuk akun ini.</p>
                @endif
            </div>

            <div class="pkg-card-soft p-5">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Aturan Lokasi</h3>
                <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <div class="flex justify-between gap-3">
                        <dt>Radius</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ number_format($settings['radius_meters'] ?? 0, 0, ',', '.') }} m</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Akurasi GPS</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">Maks. {{ number_format($settings['max_accuracy_meters'] ?? 0, 0, ',', '.') }} m</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt>Minimal kemiripan</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $settings['match_threshold'] ?? 35.00 }}%</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</div>

<style>
    .face-capture-frame {
        position: relative;
        overflow: hidden;
        aspect-ratio: 4 / 3;
        border-radius: 18px;
        background: #020617;
        border: 1px solid rgba(148, 163, 184, 0.35);
    }

    .face-capture-frame video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }

    .face-auto-scan {
        position: absolute;
        left: 8%;
        right: 8%;
        top: 18%;
        height: 3px;
        border-radius: 999px;
        background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.95), rgba(16, 185, 129, 0.2), transparent);
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.85);
        opacity: 0;
        transform: translateY(0);
        pointer-events: none;
    }

    [data-face-scan-state="active"] .face-auto-scan {
        opacity: 1;
        animation: face-scan-line 1.7s ease-in-out infinite;
    }

    [data-face-scan-state="success"] .face-auto-scan {
        opacity: 1;
        background: linear-gradient(90deg, transparent, rgba(34, 197, 94, 0.9), transparent);
    }

    [data-face-scan-state="error"] .face-auto-scan {
        opacity: 1;
        background: linear-gradient(90deg, transparent, rgba(239, 68, 68, 0.9), transparent);
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.65);
    }

    .face-scan-hud {
        position: absolute;
        left: 12px;
        right: 12px;
        bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.78);
        color: #ecfdf5;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        backdrop-filter: blur(10px);
    }

    @keyframes face-scan-line {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(260px);
        }
    }

    .face-guide {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .face-guide__head {
        position: absolute;
        width: min(44%, 210px);
        aspect-ratio: 0.78;
        border: 3px solid rgba(16, 185, 129, 0.9);
        border-radius: 48% 48% 44% 44%;
        box-shadow: 0 0 0 999px rgba(2, 6, 23, 0.16), 0 0 28px rgba(16, 185, 129, 0.45);
        transform: translateY(-10%);
    }

    .face-guide__shoulders {
        position: absolute;
        bottom: 9%;
        width: min(72%, 340px);
        height: 26%;
        border: 3px solid rgba(16, 185, 129, 0.75);
        border-top: 0;
        border-radius: 0 0 999px 999px;
    }
</style>
@endsection

@push('scripts')
    @if($faceAttendanceManifestFileExists)
        @vite([$faceAttendanceEntry])
    @elseif($faceAttendanceFallbackUrl)
        <script type="module" src="{{ $faceAttendanceFallbackUrl }}"></script>
    @else
        <script>
            document.querySelectorAll('[data-face-action="start-enrollment"]').forEach((button) => {
                button.addEventListener('click', () => {
                    const status = document.querySelector('[data-face-status]');
                    if (status) status.textContent = 'Asset scan wajah belum tersedia. Jalankan npm run build dan upload folder public/build.';
                });
            });
        </script>
    @endif
@endpush
