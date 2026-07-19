@extends('layouts.ortu')

@section('title', $materi->judul)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('ortu.materi.index') }}" class="mb-4 flex items-center gap-1 text-teal-600 hover:text-teal-800 dark:text-teal-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Materi
        </a>
    </div>

    <div class="pkg-card">
        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
            <div class="mb-2 text-sm text-teal-600 dark:text-teal-400">{{ $materi->bulan?->format('F Y') }}</div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $materi->judul }}</h1>
        </div>

        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
            <div class="prose max-w-none dark:prose-invert">
                {!! nl2br(e($materi->deskripsi)) !!}
            </div>
        </div>

        @if($materi->isRppPublished())
            @include('materi.partials.rpp-summary', ['materi' => $materi])
        @endif

        @include('materi.partials.video-list', ['materi' => $materi, 'withBorder' => true])

        @if($materi->hasPdfFiles())
        <div class="p-6" x-data="{ pdfModal: false, pdfUrl: '', pdfName: '' }">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                </svg>
                Dokumen PDF ({{ $materi->pdf_count }} file)
            </h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($materi->pdf_files as $index => $pdf)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-600 dark:bg-gray-800">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-500 text-white">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $materi->pdfFileName($index) }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if(isset($pdf['size']))
                                    {{ number_format($pdf['size'] / 1024, 1) }} KB |
                                @endif
                                Dokumen PDF
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button type="button"
                            @click="pdfUrl = '{{ route('public.materi.pdf.view', [$materi, $index]) }}'; pdfName = '{{ addslashes($materi->pdfFileName($index)) }}'; pdfModal = true"
                            class="btn-primary flex-1 text-sm !px-3 !py-2">
                            Lihat
                        </button>
                        <a href="{{ route('public.materi.pdf.download', [$materi, $index]) }}" class="btn-secondary text-sm !px-3 !py-2">
                            Unduh
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            <div x-show="pdfModal"
                 x-transition
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                 @keydown.escape.window="pdfModal = false"
                 @click.self="pdfModal = false"
                 style="display: none;">
                <div class="flex h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800">
                        <p class="truncate font-semibold text-gray-900 dark:text-white" x-text="pdfName"></p>
                        <div class="flex items-center gap-2">
                            <a :href="pdfUrl" target="_blank" class="btn-secondary text-sm !px-3 !py-1.5">Tab Baru</a>
                            <button type="button" @click="pdfModal = false" class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1">
                        <iframe :src="pdfUrl" class="h-full w-full border-0" x-show="pdfModal"></iframe>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
