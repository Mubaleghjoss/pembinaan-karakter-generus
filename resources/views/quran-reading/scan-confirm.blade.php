@extends($isPublic ? 'layouts.public' : ($isStudent ? 'layouts.siswa' : 'layouts.app'))

@section('title', 'Konfirmasi Hasil Scan')

@section('content')
@php
    $imageRoute = match (true) {
        $isPublic => route('public.quran.scan.image', $scan),
        $isStudent => route('siswa.quran.scan.image', $scan),
        default => route('quran.scan.image', $scan),
    };
    $formRoute = match (true) {
        $isPublic => route('public.quran.scan.confirm.store', $scan),
        $isStudent => route('siswa.quran.scan.confirm.store', $scan),
        default => route('quran.scan.confirm.store', $scan),
    };
    $suggestions = collect(old('rows', $scan->metadata['ocr_suggestion'] ?? []))->values()->all();
    $maxRows = max(1, min(31, (int) ($scan->sheet?->row_count ?: 12)));
@endphp
<div
    class="mx-auto max-w-6xl space-y-5 px-4 py-4 sm:px-6 sm:py-6"
    data-quran-confirm-root
    data-image-original="{{ $imageRoute }}?original=1"
    data-image-processed="{{ $scan->processed_path ? $imageRoute : '' }}"
    data-max-rows="{{ $maxRows }}"
    data-document-type="{{ $scan->sheet?->sheet_type ?: 'weekly' }}"
    data-ocr-enabled="{{ config('quran-reading.ocr_enabled') ? 'true' : 'false' }}"
    data-tesseract-worker="{{ asset('vendor/tesseract/worker.min.js') }}"
    data-tesseract-core="{{ asset('vendor/tesseract/core') }}"
    data-tesseract-lang="{{ asset('vendor/tesseract/lang') }}"
>
    <script type="application/json" data-quran-confirm-suggestions>{!! json_encode($suggestions, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
    <script type="application/json" data-quran-confirm-surahs>{!! json_encode($surahOptions, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>

    <div class="pkg-page-header">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $scan->siswa->nama }} &middot; {{ $scan->siswa->kelas?->nama ?? 'Tanpa kelas' }}</p>
            <h1 class="pkg-page-heading">Periksa hasil scan</h1>
            <p class="pkg-page-subheading">Hanya baris yang terdeteksi ditampilkan. Cocokkan angka dengan foto sebelum menyimpan.</p>
        </div>
    </div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $errors->first() }}</div>@endif

    <div class="grid gap-5 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.2fr)]">
        <aside class="pkg-panel-lg lg:sticky lg:top-24 lg:self-start">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-bold">Foto lembar</h2>
                <div class="flex gap-2" role="group" aria-label="Pilih tampilan foto">
                    <button type="button" class="pkg-tab-link min-h-11" data-quran-image-mode="original">Asli</button>
                    @if($scan->processed_path)<button type="button" class="pkg-tab-link min-h-11" data-quran-image-mode="processed">Diluruskan</button>@endif
                </div>
            </div>
            <img src="{{ $scan->processed_path ? $imageRoute : $imageRoute.'?original=1' }}" alt="Foto lembar bacaan yang diunggah" class="mt-3 max-h-[70vh] w-full rounded-xl border border-gray-200 object-contain dark:border-gray-700" data-quran-confirm-image crossorigin="same-origin">
            <button type="button" class="btn-secondary mt-3 min-h-11 w-full justify-center" data-quran-reread>Baca Ulang Angka</button>
            <div class="mt-3 hidden" data-quran-reread-progress role="status" aria-live="polite"></div>
        </aside>

        <form method="POST" action="{{ $formRoute }}" class="space-y-4" data-quran-confirm-form>@csrf
            <input type="hidden" name="ocr_suggestion" value="{{ json_encode($suggestions) }}" data-quran-confirm-ocr>
            <section class="pkg-card-soft p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-bold"><span data-quran-detected-count>0</span> baris ditemukan</p><p class="mt-1 text-sm text-slate-600 dark:text-slate-300" data-quran-quality-summary>Periksa nilai berwarna kuning atau yang masih kosong.</p></div>
                    <button type="button" class="btn-secondary min-h-11 justify-center" data-quran-add-row>Tambah Baris</button>
                </div>
            </section>
            <div class="space-y-3" data-quran-confirm-rows></div>
            <div class="pkg-empty-state pkg-panel hidden" data-quran-no-rows><p class="pkg-empty-title">Tidak ada angka yang terbaca</p><p class="pkg-empty-copy">Satu baris kosong sudah disiapkan. Isi sesuai foto atau tekan Baca Ulang Angka.</p></div>
            <button class="btn-primary min-h-12 w-full justify-center">{{ ($isStudent || $isPublic) ? 'Kirim untuk Verifikasi Pamong' : 'Simpan sebagai Terverifikasi' }}</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/quran-scan.js')
@endpush
