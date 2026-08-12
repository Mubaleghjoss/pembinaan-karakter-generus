@extends($layout === 'siswa' ? 'layouts.siswa' : 'layouts.app')

@section('title', "Scan Lembar Bacaan Al-Qur'an")

@section('content')
<div class="mx-auto max-w-3xl space-y-5 {{ $layout === 'operational' ? 'px-4 py-4 sm:px-6 sm:py-6' : '' }}">
    <div class="pkg-page-header"><div><h1 class="pkg-page-heading">Scan lembar bacaan</h1><p class="pkg-page-subheading">{{ $siswa->nama }} · Foto lembar harus menampilkan QR dan seluruh baris dengan jelas.</p></div></div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif
    <div class="pkg-panel-lg">
        <ol class="mb-5 grid gap-3 text-sm sm:grid-cols-3">
            <li class="pkg-card-soft p-3"><strong>1. Foto rata</strong><br><span class="text-gray-500">Cahaya cukup dan empat sudut terlihat.</span></li>
            <li class="pkg-card-soft p-3"><strong>2. Baca QR</strong><br><span class="text-gray-500">Sistem memastikan lembar milik siswa.</span></li>
            <li class="pkg-card-soft p-3"><strong>3. Periksa hasil</strong><br><span class="text-gray-500">Semua angka wajib dikoreksi sebelum disimpan.</span></li>
        </ol>
        <form method="POST" enctype="multipart/form-data" action="{{ $layout === 'siswa' ? route('siswa.quran.scan.upload') : route('quran.scan.upload', $siswa) }}" data-quran-scan-form>
            @csrf
            <label class="block"><span class="mb-1.5 block text-sm font-semibold">Foto lembar (maksimal 8 MB)</span><input type="file" name="scan_image" accept="image/jpeg,image/png,image/webp" capture="environment" class="pkg-field min-h-11" required data-quran-scan-file></label>
            <input type="hidden" name="sheet_payload" value="{{ old('sheet_payload') }}" data-quran-sheet-payload>
            <input type="hidden" name="ocr_suggestion" value="{{ old('ocr_suggestion') }}" data-quran-ocr-suggestion>
            <div class="mt-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700" data-quran-scan-status role="status">Pilih foto. QR akan dibaca dari foto sebelum dikirim.</div>
            <div id="quran-qr-reader-hidden" class="sr-only" aria-hidden="true"></div>
            <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">QR belum terbaca?</summary><p class="mt-2 text-xs text-gray-500">Coba foto ulang lebih dekat dan pastikan QR tidak terpotong. Untuk keamanan, kode lembar tidak dapat diketik manual.</p></details>
            <button class="btn-primary mt-5 min-h-11 w-full justify-center" disabled data-quran-scan-submit>Unggah dan Periksa Baris</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/quran-scan.js')
@endpush
