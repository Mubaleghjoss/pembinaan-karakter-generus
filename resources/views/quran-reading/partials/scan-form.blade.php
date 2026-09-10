@php
    $scanLayout = $layout ?? 'operational';
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
@endphp

<section
    class="pkg-quran-scanner"
    data-quran-scan-root
    data-prefilled-payload="{{ $prefilledPayload }}"
    data-barcode-identify-url="{{ $barcodeIdentifyAction }}"
    data-barcode-store-url="{{ $barcodeStoreAction }}"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 max-w-2xl">
            <h2 class="text-lg font-bold leading-tight sm:text-xl">Scan bacaan Al-Qur'an{{ $studentName ? ' - '.$studentName : '' }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Scan QR pada lembar untuk mengenali Generus, kemudian isi surat dan ayat yang dibaca.
            </p>
        </div>
        @if($scanLayout !== 'public' && isset($siswa) && $siswa)
            <a href="{{ $scanLayout === 'siswa' ? route('siswa.quran.sheet') : route('quran.sheet', $siswa) }}" class="btn-secondary min-h-11 shrink-0 justify-center">Unduh Lembar Bulanan</a>
        @endif
    </div>

    <div class="mt-5 hidden rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100" data-quran-chrome-notice role="status">
        <p class="font-bold">Fitur pemindaian kamera hanya mendukung Google Chrome.</p>
        <p class="mt-1">Buka halaman ini dengan Chrome terbaru untuk memakai kamera. Input manual tetap dapat digunakan tanpa Chrome.</p>
        <a class="mt-3 inline-flex min-h-11 items-center font-semibold text-amber-900 underline underline-offset-4 dark:text-amber-100" href="https://www.google.com/chrome/" target="_blank" rel="noopener noreferrer">Buka atau unduh Google Chrome</a>
    </div>

    <div class="mt-5 min-w-0 max-w-full">
        <div class="pkg-card-soft min-w-0 p-4 sm:p-5">
            <div class="flex min-w-0 flex-col gap-2">
                <h3 class="font-bold leading-6">Scan QR cepat</h3>
                <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">Arahkan kamera ke QR pada lembar, pilih gambar QR, atau buka QR dengan pemindai di HP untuk mengenali Generus.</p>
            </div>

            <div class="mt-4 grid min-w-0 gap-3 min-[360px]:grid-cols-2">
                <button type="button" class="btn-primary min-h-12 w-full min-w-0 justify-center" data-quran-quick-camera-open>Scan dengan Kamera</button>
                <label class="btn-secondary min-h-12 w-full min-w-0 cursor-pointer justify-center text-center">
                    Pilih Gambar QR
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" data-quran-quick-file>
                </label>
            </div>
            <div class="mt-4 hidden min-w-0 max-w-full overflow-hidden rounded-xl bg-slate-950 p-2" data-quran-quick-camera-panel>
                <div class="min-h-56 max-w-full overflow-hidden rounded-lg" data-quran-quick-reader></div>
                <button type="button" class="btn-secondary mt-3 min-h-11 w-full justify-center" data-quran-quick-camera-close>Tutup Kamera</button>
            </div>
            <div class="mt-4 rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-700" data-quran-quick-status role="status" aria-live="polite">
                {{ $prefilledPayload ? 'Lembar sudah dikenali. Menyiapkan identitas Generus...' : 'QR belum terbaca. Scan QR terlebih dahulu.' }}
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


</section>
