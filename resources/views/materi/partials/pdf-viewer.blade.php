@if($materi->hasPdfFiles())
<section class="{{ $wrapperClass ?? 'p-4 sm:p-6' }}">
    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
        </svg>
        {{ $heading ?? 'Dokumen PDF' }} ({{ $materi->pdf_count }} file)
    </h2>

    <div class="space-y-3">
        @foreach($materi->pdf_files as $index => $pdf)
            @php
                $previewId = 'pdfPreview-' . $materi->id . '-' . $index;
            @endphp
            <div
                class="pkg-list-card overflow-hidden dark:bg-slate-900/70"
                data-pdf-viewer
                data-pdf-url="{{ route('public.materi.pdf.view', [$materi, $index]) }}"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/40">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="break-words font-semibold text-gray-900 dark:text-white">{{ $materi->pdfFileName($index) }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                                @if(isset($pdf['size']))
                                    {{ number_format($pdf['size'] / 1024, 1) }} KB
                                @else
                                    Dokumen PDF
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full items-center gap-2 sm:w-auto">
                        <button
                            type="button"
                            data-pdf-toggle
                            aria-expanded="false"
                            aria-controls="{{ $previewId }}"
                            class="btn-primary flex-1 !px-3 !py-2 text-sm sm:flex-none"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                            </svg>
                            <span data-pdf-toggle-label>Buka PDF</span>
                        </button>
                        <a href="{{ route('public.materi.pdf.download', [$materi, $index]) }}" class="btn-secondary flex-1 !px-3 !py-2 text-sm sm:flex-none">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/>
                            </svg>
                            Unduh
                        </a>
                    </div>
                </div>

                <div id="{{ $previewId }}" data-pdf-preview hidden class="border-t border-gray-200 dark:border-slate-800">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 p-3 dark:border-slate-800 dark:bg-slate-950/70">
                        <div class="flex items-center gap-1">
                            <button type="button" data-pdf-previous class="btn-secondary !h-9 !w-9 !p-0" aria-label="Halaman sebelumnya" title="Halaman sebelumnya">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
                                </svg>
                            </button>
                            <label class="flex items-center gap-1 text-sm text-gray-600 dark:text-slate-300">
                                <span class="sr-only">Nomor halaman</span>
                                <input type="number" data-pdf-page value="1" min="1" class="pkg-field !h-9 !w-16 !px-2 text-center">
                                <span>/ <span data-pdf-page-count>-</span></span>
                            </label>
                            <button type="button" data-pdf-next class="btn-secondary !h-9 !w-9 !p-0" aria-label="Halaman berikutnya" title="Halaman berikutnya">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" data-pdf-zoom-out class="btn-secondary !h-9 !w-9 !p-0" aria-label="Perkecil PDF" title="Perkecil PDF">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </button>
                            <span data-pdf-zoom-label class="w-12 text-center text-xs font-semibold text-gray-600 dark:text-slate-300">100%</span>
                            <button type="button" data-pdf-zoom-in class="btn-secondary !h-9 !w-9 !p-0" aria-label="Perbesar PDF" title="Perbesar PDF">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="relative min-h-80 overflow-auto bg-gray-200 p-3 dark:bg-slate-950">
                        <p data-pdf-status class="absolute inset-x-3 top-6 text-center text-sm font-medium text-gray-600 dark:text-slate-300">Memuat PDF...</p>
                        <div data-pdf-error class="hidden rounded-lg border border-red-200 bg-red-50 p-4 text-center text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                            PDF tidak dapat ditampilkan. Gunakan tombol Unduh untuk membuka dokumen di perangkat Anda.
                        </div>
                        <canvas data-pdf-canvas class="mx-auto block max-w-none bg-white shadow"></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
