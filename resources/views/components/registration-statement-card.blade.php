@props([
    'registration' => null,
    'previewRoute',
    'downloadRoute',
])

<section class="pkg-panel-lg mb-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">Dokumen PKG</p>
            <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Surat Pernyataan PKG</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Surat terbaru yang ditandatangani Orang Tua dan Generus.</p>
        </div>
        @if($registration)
            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">Sudah lengkap</span>
        @else
            <span class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Belum tersedia</span>
        @endif
    </div>

    @if($registration)
        <dl class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-900/50 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-gray-500 dark:text-gray-400">Nama Generus</dt><dd class="mt-1 font-bold text-gray-900 dark:text-white">{{ $registration->student_name }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Orang Tua</dt><dd class="mt-1 font-bold text-gray-900 dark:text-white">{{ $registration->parent_name }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Kelompok</dt><dd class="mt-1 font-bold text-gray-900 dark:text-white">{{ \App\Models\Siswa::kelompokOptions()[$registration->kelompok] ?? $registration->kelompok }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Ditandatangani</dt><dd class="mt-1 font-bold text-gray-900 dark:text-white">{{ $registration->submitted_at?->translatedFormat('d M Y H:i') }}</dd></div>
        </dl>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <a href="{{ $previewRoute }}" target="_blank" rel="noopener" class="btn-secondary justify-center px-4 py-2.5">Lihat Surat</a>
            <a href="{{ $downloadRoute }}" class="btn-success justify-center px-4 py-2.5">Unduh PDF</a>
        </div>
    @else
        <div class="pkg-empty-state mt-5 py-6">
            <div class="pkg-empty-icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l3.414 3.414A1 1 0 0 1 17 7.414V19a2 2 0 0 1-2 2Z"/></svg>
            </div>
            <h3 class="pkg-empty-title">Surat belum dibuat</h3>
            <p class="pkg-empty-copy">Lengkapi formulir melalui tautan privat dan kode akses dari pengurus.</p>
        </div>
    @endif
</section>
