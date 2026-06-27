@if(!empty($dashboardQrData['qr_image_base64']))
    <section
        x-data="{ expanded: true }"
        class="pkg-panel mx-auto w-full max-w-xl overflow-hidden"
        data-dashboard-qr
    >
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">QR Presensi Saya</h2>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $dashboardQrIdentity }}</p>
            </div>
            <button
                type="button"
                class="btn-secondary !h-9 !w-9 shrink-0 !p-0"
                @click="expanded = !expanded"
                :aria-expanded="expanded.toString()"
                aria-label="Tampilkan atau minimalkan QR presensi"
                title="Tampilkan atau minimalkan QR"
            >
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': !expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
            </button>
        </div>

        <div x-show="expanded" class="p-4 sm:p-5">
            <div class="mx-auto flex w-full max-w-[18rem] items-center justify-center rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700">
                <img
                    src="{{ $dashboardQrData['qr_image_base64'] }}"
                    alt="QR presensi {{ $dashboardQrIdentity }}"
                    width="280"
                    height="280"
                    class="aspect-square h-auto w-full object-contain"
                >
            </div>
            <p class="mx-auto mt-3 max-w-sm text-center text-xs leading-5 text-slate-500 dark:text-slate-400">
                Tunjukkan QR ini kepada petugas scan presensi.
            </p>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <a
                    href="{{ $dashboardQrData['qr_image_base64'] }}"
                    download="{{ $dashboardQrDownloadName }}"
                    class="btn-primary justify-center text-sm"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v10m0 0l-4-4m4 4l4-4M5 20h14"/>
                    </svg>
                    Unduh QR
                </a>
                <a href="{{ $dashboardIdCardUrl }}?download=1" class="btn-secondary justify-center text-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16v12H4zM8 10h3m-3 3h5m3-3h.01"/>
                    </svg>
                    Unduh ID Card
                </a>
            </div>
        </div>
    </section>
@endif
