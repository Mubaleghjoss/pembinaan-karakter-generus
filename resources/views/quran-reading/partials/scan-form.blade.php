@php
    $scanLayout = $layout ?? 'operational';
    $scanAction = match ($scanLayout) {
        'siswa' => route('siswa.quran.scan.upload'),
        'public' => route('public.quran.scan.upload'),
        default => route('quran.scan.upload'),
    };
    $studentName = isset($siswa) && $siswa ? $siswa->nama : null;
@endphp

<section
    class="pkg-quran-scanner"
    data-quran-scan-root
    data-ocr-enabled="{{ config('quran-reading.ocr_enabled') ? 'true' : 'false' }}"
    data-tesseract-worker="{{ asset('vendor/tesseract/worker.min.js') }}"
    data-tesseract-core="{{ asset('vendor/tesseract/core') }}"
    data-tesseract-lang="{{ asset('vendor/tesseract/lang') }}"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-bold">Scan lembar{{ $studentName ? ' '.$studentName : '' }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Foto seluruh lembar, maksimal 8 MB. QR dan angka akan dibaca sebagai saran sebelum Anda menyimpan.
            </p>
        </div>
        @if($scanLayout !== 'public' && isset($siswa) && $siswa)
            <a href="{{ $scanLayout === 'siswa' ? route('siswa.quran.sheet') : route('quran.sheet', $siswa) }}" class="btn-secondary min-h-11 shrink-0 justify-center">Unduh Lembar Bulanan</a>
        @endif
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="pkg-card-soft p-4"><strong class="block">1. Foto rata</strong><span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Masukkan seluruh kertas dan empat penanda sudut.</span></div>
        <div class="pkg-card-soft p-4"><strong class="block">2. Baca QR dan angka</strong><span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Sistem meluruskan foto lalu membaca kolom angka.</span></div>
        <div class="pkg-card-soft p-4"><strong class="block">3. Periksa hasil</strong><span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Tulisan tangan tetap harus dicocokkan sebelum disimpan.</span></div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $scanAction }}" class="mt-5" data-quran-scan-form>
        @csrf
        <input type="hidden" name="sheet_payload" value="{{ old('sheet_payload') }}" data-quran-sheet-payload>
        <input type="hidden" name="ocr_suggestion" value="{{ old('ocr_suggestion') }}" data-quran-ocr-suggestion>
        <input type="file" name="processed_image" accept="image/jpeg" class="hidden" tabindex="-1" data-quran-processed-file>

        <div class="grid gap-3 sm:grid-cols-2">
            <button type="button" class="btn-primary min-h-12 w-full justify-center" data-quran-camera-open>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                Buka Kamera
            </button>
            <label class="btn-secondary min-h-12 w-full cursor-pointer justify-center">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih dari Galeri
                <input type="file" name="scan_image" accept="image/jpeg,image/png,image/webp" class="sr-only" required data-quran-scan-file>
            </label>
        </div>

        <div class="pkg-quran-camera mt-4 hidden" data-quran-camera-panel>
            <div class="pkg-quran-camera__viewport">
                <video autoplay muted playsinline data-quran-camera-video></video>
                <div class="pkg-quran-camera__guide" aria-hidden="true"><span>Sejajarkan seluruh kertas di dalam bingkai</span></div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <button type="button" class="btn-success min-h-12 justify-center" data-quran-camera-capture>Ambil Foto</button>
                <button type="button" class="btn-secondary min-h-12 justify-center" data-quran-camera-close>Batalkan</button>
            </div>
        </div>

        <div class="mt-4 hidden" data-quran-preview-panel>
            <div class="pkg-quran-preview">
                <img alt="Pratinjau lembar yang akan diproses" data-quran-preview-image>
                <div class="pkg-quran-crop hidden" data-quran-crop-box aria-label="Area QR yang akan dibaca"></div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <button type="button" class="btn-secondary min-h-11 justify-center" data-quran-retake>Foto Ulang</button>
                <button type="button" class="btn-primary min-h-11 justify-center" data-quran-use-photo>Gunakan Foto</button>
            </div>
        </div>

        <div class="mt-4 hidden rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30" data-quran-manual-crop>
            <h3 class="font-bold text-amber-900 dark:text-amber-100">QR belum terbaca</h3>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">Geser kotak ke QR di kanan atas, ubah ukurannya dari sudut, lalu coba lagi.</p>
            <button type="button" class="btn-secondary mt-3 min-h-11 w-full justify-center" data-quran-crop-retry>Coba Baca Area QR</button>
        </div>

        <div class="mt-4 hidden rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30" data-quran-document-corners>
            <h3 class="font-bold text-amber-900 dark:text-amber-100">Rapikan batas kertas</h3>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">Geser empat penanda ke sudut kertas, lalu luruskan sebelum membaca angka.</p>
            <div class="pkg-quran-corners mt-3" data-quran-corners-stage>
                <img alt="Foto asli untuk mengatur empat sudut kertas" data-quran-corners-image>
                @foreach(['tl','tr','bl','br'] as $corner)<button type="button" class="pkg-quran-corner pkg-quran-corner--{{ $corner }}" aria-label="Geser sudut {{ $corner }}" data-quran-corner="{{ $corner }}"></button>@endforeach
            </div>
            <button type="button" class="btn-primary mt-3 min-h-11 w-full justify-center" data-quran-corners-apply>Luruskan dan Baca Angka</button>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-700" data-quran-scan-status role="status" aria-live="polite">
            Pilih kamera atau galeri untuk mulai memindai.
        </div>
        <div class="mt-3 hidden" data-quran-progress-wrap>
            <div class="mb-1 flex items-center justify-between text-xs font-semibold"><span data-quran-progress-label>Memproses</span><span data-quran-progress-value>0%</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"><div class="h-full w-0 rounded-full bg-emerald-500 transition-all" data-quran-progress-bar></div></div>
        </div>
        @error('sheet_payload')<p class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        @error('scan_image')<p class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror

        <button class="btn-primary mt-5 min-h-12 w-full justify-center" disabled data-quran-scan-submit>
            Unggah dan Periksa Hasil
        </button>
    </form>
</section>
