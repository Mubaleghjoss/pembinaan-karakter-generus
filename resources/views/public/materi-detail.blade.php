@extends('layouts.public')

@section('title', $materi->judul . ' - ' . ($theme->app_name ?? 'PKG Presensi'))

@section('content')
<div class="py-12 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6" data-reveal="left">
            <a href="{{ route('materi.index') }}" class="inline-flex items-center font-medium pkg-link-accent">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Materi
            </a>
        </div>

        <!-- Materi Card -->
        <div class="pkg-surface rounded-2xl overflow-hidden" data-reveal="zoom">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
                <div class="flex items-center gap-2 text-blue-200 text-sm mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $materi->bulan ? $materi->bulan->format('F Y') : '-' }}
                </div>
                <h1 class="text-3xl font-bold">{{ $materi->judul }}</h1>
                <div class="mt-5 flex flex-wrap gap-2">
                    @if($materi->folder)
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ $materi->folder->display_name }}</span>
                    @endif
                    @if($materi->hasPdfFiles())
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ $materi->pdf_count }} PDF</span>
                    @endif
                    @if($materi->has_video_links)
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">Video</span>
                    @endif
                </div>
            </div>

            <!-- Description -->
            <div class="p-6 border-b border-gray-200 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Deskripsi</h2>
                <div class="prose pkg-prose max-w-none">
                    {!! nl2br(e($materi->deskripsi)) !!}
                </div>
            </div>

            @if($materi->isRppPublished())
                @include('materi.partials.rpp-summary', ['materi' => $materi])
            @endif

            @if(! $canAccessContent && ($materi->hasPdfFiles() || $materi->has_video_links))
                @include('public.partials.materi-login-required')
            @else
            <!-- PDF Files -->
            @if($materi->hasPdfFiles())
            <div class="p-6 border-b border-gray-200 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    File PDF ({{ $materi->pdf_count }} file)
                </h2>
                
                <div class="space-y-3">
                    @foreach($materi->pdf_files as $index => $pdf)
                    <div class="pkg-list-card overflow-hidden dark:bg-slate-900/70"
                         data-pdf-viewer
                         data-pdf-url="{{ route('public.materi.pdf.view', [$materi, $index]) }}">
                        <div class="p-4 flex items-center justify-between flex-wrap gap-3">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.9,12.35 10.92,12.31Z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="break-words font-semibold text-gray-900 dark:text-white">{{ $materi->pdfFileName($index) }}</p>
                                    @if(isset($pdf['size']))
                                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ number_format($pdf['size'] / 1024, 1) }} KB</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex w-full items-center gap-2 sm:w-auto">
                                <button type="button"
                                   data-pdf-toggle
                                   aria-expanded="false"
                                   aria-controls="pdfPreview{{ $index }}"
                                   class="btn-primary flex-1 !px-3 !py-2 text-sm sm:flex-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span data-pdf-toggle-label>Buka PDF</span>
                                </button>
                                <a href="{{ route('public.materi.pdf.download', [$materi, $index]) }}"
                                   class="btn-danger flex-1 !px-3 !py-2 text-sm sm:flex-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                     Unduh
                                </a>
                            </div>
                        </div>
                        
                        <div id="pdfPreview{{ $index }}" data-pdf-preview hidden class="border-t border-gray-200 dark:border-slate-800">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 p-3 dark:border-slate-800 dark:bg-slate-950/70">
                                <div class="flex items-center gap-1">
                                    <button type="button" data-pdf-previous class="btn-secondary !h-9 !w-9 !p-0" aria-label="Halaman sebelumnya" title="Halaman sebelumnya">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <label class="flex items-center gap-1 text-sm text-gray-600 dark:text-slate-300">
                                        <span class="sr-only">Nomor halaman</span>
                                        <input type="number" data-pdf-page value="1" min="1" class="pkg-field !h-9 !w-16 !px-2 text-center">
                                        <span>/ <span data-pdf-page-count>-</span></span>
                                    </label>
                                    <button type="button" data-pdf-next class="btn-secondary !h-9 !w-9 !p-0" aria-label="Halaman berikutnya" title="Halaman berikutnya">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" data-pdf-zoom-out class="btn-secondary !h-9 !w-9 !p-0" aria-label="Perkecil PDF" title="Perkecil PDF">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <span data-pdf-zoom-label class="w-12 text-center text-xs font-semibold text-gray-600 dark:text-slate-300">100%</span>
                                    <button type="button" data-pdf-zoom-in class="btn-secondary !h-9 !w-9 !p-0" aria-label="Perbesar PDF" title="Perbesar PDF">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </div>
            @endif

            @include('materi.partials.video-list', ['materi' => $materi])
            @endif
        </div>
    </div>
</div>
@endsection
