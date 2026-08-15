@php
    $scanLayout = $layout ?? 'operational';
    $scanAction = match ($scanLayout) {
        'siswa' => route('siswa.quran.scan.upload'),
        'public' => route('public.quran.scan.upload'),
        default => route('quran.scan.upload'),
    };
    $barcodeIdentifyAction = match ($scanLayout) {
        'siswa' => route('siswa.quran.barcode.identify'),
        'public' => route('public.quran.barcode.identify'),
        default => route('quran.barcode.identify'),
    };
    $barcodeStoreAction = match ($scanLayout) {
        'siswa' => route('siswa.quran.barcode.store'),
        'public' => route('public.quran.barcode.store'),
        default => route('quran.barcode.store'),
    };
    $studentName = isset($siswa) && $siswa ? $siswa->nama : null;
    $prefilledPayload = $prefilledPayload ?? null;
    $maxUploadBytes = (int) config('quran-reading.max_upload_kilobytes', 8192) * 1024;
@endphp

<section
    class="pkg-quran-scanner"
    data-quran-scan-root
    data-ocr-enabled="{{ config('quran-reading.ocr_enabled') ? 'true' : 'false' }}"
    data-tesseract-worker="{{ asset('vendor/tesseract/worker.min.js') }}"
    data-tesseract-core="{{ asset('vendor/tesseract/core') }}"
    data-tesseract-lang="{{ asset('vendor/tesseract/lang') }}"
    data-prefilled-payload="{{ $prefilledPayload }}"
    data-auto-submit="false"
    data-max-upload-bytes="{{ $maxUploadBytes }}"
    data-barcode-identify-url="{{ $barcodeIdentifyAction }}"
    data-barcode-store-url="{{ $barcodeStoreAction }}"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-bold">Scan bacaan Al-Qur'an{{ $studentName ? ' '.$studentName : '' }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Scan barcode untuk mengenali Generus, lalu isi surat dan ayat secara manual.
            </p>
        </div>
        @if($scanLayout !== 'public' && isset($siswa) && $siswa)
            <a href="{{ $scanLayout === 'siswa' ? route('siswa.quran.sheet') : route('quran.sheet', $siswa) }}" class="btn-secondary min-h-11 shrink-0 justify-center">Unduh Lembar Bulanan</a>
        @endif
    </div>

    <div class="pkg-quran-mode-switch mt-5" role="tablist" aria-label="Pilih cara pemindaian">
        <button type="button" class="pkg-quran-mode-button" role="tab" aria-selected="true" data-quran-mode="quick">Scan Barcode Cepat</button>
        <button type="button" class="pkg-quran-mode-button" role="tab" aria-selected="false" data-quran-mode="advanced">Scan Lembar Lengkap</button>
    </div>

    <div class="mt-5 min-w-0 max-w-full" data-quran-mode-panel="quick">
        <div class="pkg-card-soft min-w-0 p-4 sm:p-5">
            <div class="flex min-w-0 flex-col gap-1">
                <h3 class="font-bold">Kenali Generus dari barcode</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">Arahkan kamera ke QR lembar, pilih gambar barcode, atau buka QR memakai pemindai HP.</p>
            </div>

            <div class="mt-4 grid min-w-0 gap-3 min-[360px]:grid-cols-2">
                <button type="button" class="btn-primary min-h-12 w-full min-w-0 justify-center" data-quran-quick-camera-open>Scan dengan Kamera</button>
                <label class="btn-secondary min-h-12 w-full min-w-0 cursor-pointer justify-center text-center">
                    Pilih Gambar Barcode
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" data-quran-quick-file>
                </label>
            </div>
            <div class="mt-4 hidden min-w-0 max-w-full overflow-hidden rounded-xl bg-slate-950 p-2" data-quran-quick-camera-panel>
                <div class="min-h-56 max-w-full overflow-hidden rounded-lg" data-quran-quick-reader></div>
                <button type="button" class="btn-secondary mt-3 min-h-11 w-full justify-center" data-quran-quick-camera-close>Tutup Kamera</button>
            </div>
            <div class="mt-4 rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-700" data-quran-quick-status role="status" aria-live="polite">
                {{ $prefilledPayload ? 'Lembar sudah dikenali. Menyiapkan identitas Generus...' : 'Barcode belum terbaca. Scan barcode terlebih dahulu.' }}
            </div>
        </div>

        <form class="mt-4 hidden min-w-0 max-w-full pkg-panel-lg" data-quran-quick-form novalidate>
            <input type="hidden" name="flow_id" data-quran-flow-id>
            <div class="pkg-quran-identity">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Generus dikenali</p>
                <p class="mt-1 break-words text-lg font-bold" data-quran-student-name></p>
                <dl class="mt-3 grid min-w-0 gap-3 min-[360px]:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">NIS</dt><dd class="mt-1 break-words font-semibold" data-quran-student-nis></dd></div>
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">Kelas sekolah</dt><dd class="mt-1 break-words font-semibold" data-quran-student-grade></dd></div>
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">Kelompok</dt><dd class="mt-1 break-words font-semibold" data-quran-student-group></dd></div>
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">Tanggal</dt><dd class="mt-1 font-semibold">{{ now()->isoFormat('D MMMM YYYY') }}</dd></div>
                </dl>
            </div>

            <div class="pkg-quran-quick-fields mt-5">
                <label class="min-w-0"><span class="mb-1 block text-sm font-semibold">Surat</span><select name="surah_start" class="pkg-field min-h-11 w-full min-w-0 max-w-full" required><option value="">Pilih surat</option>@foreach(\App\Support\QuranCatalog::options() as $number => $label)<option value="{{ $number }}">{{ $number }}. {{ $label }}</option>@endforeach</select></label>
                <label class="min-w-0"><span class="mb-1 block text-sm font-semibold">Ayat awal</span><input type="number" inputmode="numeric" name="ayah_start" min="1" max="286" class="pkg-field min-h-11 w-full min-w-0 max-w-full" required></label>
                <label class="min-w-0"><span class="mb-1 block text-sm font-semibold">Ayat akhir</span><input type="number" inputmode="numeric" name="ayah_end" min="1" max="286" class="pkg-field min-h-11 w-full min-w-0 max-w-full" required></label>
            </div>
            <label class="mt-4 flex min-h-11 min-w-0 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700"><input type="checkbox" name="cross_surah" value="1" class="pkg-check shrink-0" data-quran-cross-surah><span class="min-w-0 text-sm font-semibold">Bacaan berlanjut ke surat lain</span></label>
            <div class="mt-3 hidden min-w-0" data-quran-end-surah-wrap><label class="min-w-0"><span class="mb-1 block text-sm font-semibold">Surat akhir</span><select name="surah_end" class="pkg-field min-h-11 w-full min-w-0 max-w-full"><option value="">Pilih surat akhir</option>@foreach(\App\Support\QuranCatalog::options() as $number => $label)<option value="{{ $number }}">{{ $number }}. {{ $label }}</option>@endforeach</select></label></div>

            <details class="mt-4 min-w-0 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                <summary class="min-h-11 cursor-pointer font-semibold">Detail Tambahan <span class="font-normal text-slate-500">(opsional)</span></summary>
                <div class="mt-3 grid min-w-0 gap-3 min-[360px]:grid-cols-2">
                    <label class="min-w-0"><span class="mb-1 block text-sm font-semibold">Halaman awal</span><input type="number" inputmode="numeric" name="page_start" min="1" max="1000" class="pkg-field min-h-11 w-full min-w-0 max-w-full"></label>
                    <label class="min-w-0"><span class="mb-1 block text-sm font-semibold">Halaman akhir</span><input type="number" inputmode="numeric" name="page_end" min="1" max="1000" class="pkg-field min-h-11 w-full min-w-0 max-w-full"></label>
                    <label class="min-w-0 min-[360px]:col-span-2"><span class="mb-1 block text-sm font-semibold">Catatan</span><textarea name="notes" rows="3" maxlength="1000" class="pkg-field w-full min-w-0 max-w-full"></textarea></label>
                </div>
            </details>
            <div class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200" data-quran-quick-errors role="alert"></div>
            <button type="submit" class="btn-success mt-5 min-h-12 w-full min-w-0 justify-center" data-quran-quick-submit>Simpan Catatan Bacaan</button>
        </form>
    </div>

    <div class="mt-5 hidden min-w-0 max-w-full" data-quran-mode-panel="advanced">
        <div class="pkg-card-soft p-4"><h3 class="font-bold">Scan Lembar Lengkap</h3><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Gunakan foto atau PDF seluruh lembar maksimal 8 MB. Pelurusan dan OCR dimuat hanya dalam mode ini.</p></div>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="pkg-card-soft p-4"><strong class="block">1. Foto rata</strong><span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Masukkan seluruh kertas dan empat penanda sudut.</span></div>
        <div class="pkg-card-soft p-4"><strong class="block">2. Baca QR dan angka</strong><span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Sistem meluruskan foto lalu membaca kolom angka.</span></div>
        <div class="pkg-card-soft p-4"><strong class="block">3. Periksa hasil</strong><span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Tulisan tangan tetap harus dicocokkan sebelum disimpan.</span></div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $scanAction }}" class="mt-5" data-quran-scan-form>
        @csrf
        <input type="hidden" name="sheet_payload" value="{{ old('sheet_payload', $prefilledPayload) }}" data-quran-sheet-payload>
        <input type="hidden" name="ocr_suggestion" value="{{ old('ocr_suggestion') }}" data-quran-ocr-suggestion>
        <input type="file" name="processed_image" accept="image/jpeg" class="hidden" tabindex="-1" data-quran-processed-file>

        <div class="grid gap-3 sm:grid-cols-3">
            <button type="button" class="btn-primary min-h-12 w-full justify-center" data-quran-camera-open>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                Buka Kamera
            </button>
            <label class="btn-secondary min-h-12 w-full cursor-pointer justify-center">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Pilih dari Galeri
                <input type="file" name="scan_image" accept="image/jpeg,image/png,image/webp" class="sr-only" required data-quran-scan-file>
            </label>
            <label class="btn-secondary min-h-12 w-full cursor-pointer justify-center">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v6h6M9 14h6M9 17h4" /></svg>
                Pilih PDF
                <input type="file" accept="application/pdf,.pdf" class="sr-only" data-quran-pdf-file>
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
            {{ $prefilledPayload ? 'Lembar sudah dikenali. Pilih foto atau PDF untuk diperiksa.' : 'Pilih kamera, galeri, atau PDF untuk mulai memindai.' }}
        </div>
        <div class="mt-3 hidden" data-quran-progress-wrap>
            <div class="mb-1 flex items-center justify-between text-xs font-semibold"><span data-quran-progress-label>Memproses</span><span data-quran-progress-value>0%</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"><div class="h-full w-0 rounded-full bg-emerald-500 transition-[width] duration-200" data-quran-progress-bar></div></div>
        </div>
        @error('sheet_payload')<p class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        @error('scan_image')<p class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror

        <button class="btn-primary mt-5 min-h-12 w-full justify-center" disabled data-quran-scan-submit>
            Unggah dan Periksa Hasil
        </button>
    </form>
    </div>
</section>
