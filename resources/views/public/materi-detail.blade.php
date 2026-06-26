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
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ $materi->folder->name }}</span>
                    @endif
                    @if($materi->hasPdfFiles())
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">{{ $materi->pdf_count }} PDF</span>
                    @endif
                    @if($materi->youtube_embed_url)
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
                    <div class="pkg-list-card overflow-hidden dark:bg-slate-900/70">
                        <div class="p-4 flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.9,12.35 10.92,12.31Z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $pdf['name'] ?? basename($pdf['path']) }}</p>
                                    @if(isset($pdf['size']))
                                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ number_format($pdf['size'] / 1024, 1) }} KB</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="togglePdfPreview({{ $index }})" id="pdfToggleBtn{{ $index }}"
                                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium inline-flex items-center gap-2 transition dark:bg-blue-500 dark:hover:bg-blue-400 dark:text-slate-950">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>Buka PDF</span>
                                </button>
                                <a href="{{ Storage::url($pdf['path']) }}" download 
                                   class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium inline-flex items-center gap-2 dark:bg-red-500 dark:hover:bg-red-400 dark:text-slate-950">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                     Unduh
                                </a>
                            </div>
                        </div>
                        
                        <!-- PDF Preview (hidden by default, shown on click) -->
                        <div id="pdfPreview{{ $index }}" style="display:none;" class="border-t border-gray-200 dark:border-slate-800">
                            <iframe data-src="{{ Storage::url($pdf['path']) }}" class="w-full" style="height:600px;" frameborder="0"></iframe>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Video -->
            @if($materi->youtube_embed_url)
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10,15L15.19,12L10,9V15M21.56,7.17C21.69,7.64 21.78,8.27 21.84,9.07C21.91,9.87 21.94,10.56 21.94,11.16L22,12C22,14.19 21.84,15.8 21.56,16.83C21.31,17.73 20.73,18.31 19.83,18.56C19.36,18.69 18.5,18.78 17.18,18.84C15.88,18.91 14.69,18.94 13.59,18.94L12,19C7.81,19 5.2,18.84 4.17,18.56C3.27,18.31 2.69,17.73 2.44,16.83C2.31,16.36 2.22,15.73 2.16,14.93C2.09,14.13 2.06,13.44 2.06,12.84L2,12C2,9.81 2.16,8.2 2.44,7.17C2.69,6.27 3.27,5.69 4.17,5.44C4.64,5.31 5.5,5.22 6.82,5.16C8.12,5.09 9.31,5.06 10.41,5.06L12,5C16.19,5 18.8,5.16 19.83,5.44C20.73,5.69 21.31,6.27 21.56,7.17Z"/>
                    </svg>
                    Video Pembelajaran
                </h2>
                <div class="aspect-video rounded-xl overflow-hidden">
                    <iframe src="{{ $materi->youtube_embed_url }}" 
                        class="w-full h-full"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
function togglePdfPreview(index) {
    var preview = document.getElementById('pdfPreview' + index);
    var btn = document.getElementById('pdfToggleBtn' + index);
    if (!preview) return;
    
    var isHidden = preview.style.display === 'none';
    preview.style.display = isHidden ? 'block' : 'none';
    
    if (isHidden) {
        var iframe = preview.querySelector('iframe');
        if (iframe && !iframe.src && iframe.dataset.src) {
            iframe.src = iframe.dataset.src;
        }
        btn.querySelector('span').textContent = 'Tutup PDF';
        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        btn.classList.add('bg-gray-600', 'hover:bg-gray-700');
    } else {
        btn.querySelector('span').textContent = 'Buka PDF';
        btn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
        btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
    }
}
</script>
@endsection
