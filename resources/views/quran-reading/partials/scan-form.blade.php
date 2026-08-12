<div class="space-y-5">
    <div class="grid gap-3 text-sm sm:grid-cols-3">
        <div class="pkg-card-soft p-4">
            <strong>1. Foto rata</strong>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Pastikan cahaya cukup serta QR dan empat sudut terlihat.</p>
        </div>
        <div class="pkg-card-soft p-4">
            <strong>2. Baca QR</strong>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Sistem memastikan lembar sesuai dengan Generus yang dipilih.</p>
        </div>
        <div class="pkg-card-soft p-4">
            <strong>3. Periksa baris</strong>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Tulisan tangan tetap diperiksa dan diperbaiki sebelum disimpan.</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $layout === 'siswa' ? route('siswa.quran.scan.upload') : route('quran.scan.upload', $siswa) }}" data-quran-scan-form>
        @csrf
        <label class="block">
            <span class="mb-1.5 block text-sm font-semibold">Foto lembar (maksimal 8 MB)</span>
            <input type="file" name="scan_image" accept="image/jpeg,image/png,image/webp" capture="environment" class="pkg-field min-h-11" required data-quran-scan-file>
        </label>
        <input type="hidden" name="sheet_payload" value="{{ old('sheet_payload') }}" data-quran-sheet-payload>
        <input type="hidden" name="ocr_suggestion" value="{{ old('ocr_suggestion') }}" data-quran-ocr-suggestion>
        <div class="mt-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700" data-quran-scan-status role="status">Pilih foto. QR akan dibaca dari foto sebelum dikirim.</div>
        <div class="sr-only" aria-hidden="true" data-quran-qr-reader></div>
        <details class="mt-3">
            <summary class="cursor-pointer text-sm font-semibold">QR belum terbaca?</summary>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Foto ulang lebih dekat dan pastikan QR tidak terpotong. Untuk keamanan, kode lembar tidak dapat diketik manual.</p>
        </details>
        <button class="btn-primary mt-5 min-h-11 w-full justify-center sm:w-auto" disabled data-quran-scan-submit>Unggah dan Periksa Baris</button>
    </form>
</div>
