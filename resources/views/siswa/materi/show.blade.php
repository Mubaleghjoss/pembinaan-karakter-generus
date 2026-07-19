@extends('layouts.siswa')

@section('title', $materi->judul)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('siswa.materi.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Materi
        </a>
    </div>

    <div class="pkg-card">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="text-sm text-blue-600 dark:text-blue-400 mb-2">{{ $materi->bulan?->format('F Y') }}</div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $materi->judul }}</h1>
        </div>

        <!-- Description -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="prose dark:prose-invert max-w-none">
                {!! nl2br(e($materi->deskripsi)) !!}
            </div>
        </div>

        @if($materi->isRppPublished())
            @include('materi.partials.rpp-summary', ['materi' => $materi])
        @endif

        @include('materi.partials.video-list', ['materi' => $materi, 'withBorder' => true])

        <!-- PDF Files -->
        @if($materi->hasPdfFiles())
        <div class="p-6" x-data="{ pdfModal: false, pdfUrl: '', pdfName: '' }">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.9,12.35 10.92,12.31Z"/>
                </svg>
                Dokumen PDF ({{ $materi->pdf_count }} file)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($materi->pdf_files as $index => $pdf)
                <div class="relative group rounded-xl border border-gray-200 dark:border-gray-600 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-750 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/20">
                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.9,12.35 10.92,12.31Z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $materi->pdfFileName($index) }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    @if(isset($pdf['size']))
                                        {{ number_format($pdf['size'] / 1024, 1) }} KB |
                                    @endif
                                    Dokumen PDF
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4">
                            <button @click="pdfUrl = '{{ route('public.materi.pdf.view', [$materi, $index]) }}'; pdfName = '{{ addslashes($materi->pdfFileName($index)) }}'; pdfModal = true"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg text-sm font-medium transition-all shadow-sm hover:shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Lihat
                            </button>
                            <a href="{{ route('public.materi.pdf.download', [$materi, $index]) }}"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Unduh
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- PDF Modal Viewer -->
            <div x-show="pdfModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                 @keydown.escape.window="pdfModal = false" @click.self="pdfModal = false" style="display: none;">
                <div x-show="pdfModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                     class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden w-full max-w-5xl" style="height: 85vh;">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                            </div>
                            <p class="font-semibold text-gray-900 dark:text-white truncate" x-text="pdfName"></p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a :href="pdfUrl" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                <span class="hidden sm:inline">Tab Baru</span>
                            </a>
                            <button @click="pdfModal = false"
                                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors text-gray-500 dark:text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <!-- PDF Content -->
                    <div class="h-full" style="height: calc(85vh - 56px);">
                        <iframe :src="pdfUrl" class="w-full h-full border-0" x-show="pdfModal"></iframe>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

