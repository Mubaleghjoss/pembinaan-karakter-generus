@extends('layouts.public')

@section('title', $berita->judul . ' - ' . ($theme->app_name ?? 'PKG Presensi'))

@section('content')
<div class="py-12 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6" data-reveal="left">
            <a href="{{ route('public.index') }}" class="inline-flex items-center font-medium pkg-link-accent">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Beranda
            </a>
        </div>

        <!-- Article -->
        <article class="pkg-surface rounded-2xl overflow-hidden" data-reveal="zoom">
            <!-- Cover Image -->
            @if($berita->cover_path)
                <div class="relative h-96 overflow-hidden">
                    <img src="{{ asset('storage/' . $berita->cover_path) }}" alt="{{ $berita->judul }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
            @endif

            <!-- Content -->
            <div class="p-8 md:p-12">
                <!-- Meta -->
                <div class="flex items-center gap-4 mb-6 text-sm text-gray-600 dark:text-slate-400">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{ $berita->published_at ? $berita->published_at->format('d F Y') : 'Draft' }}
                    </span>
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        {{ $berita->view_count }} dilihat
                    </span>
                </div>

                <!-- Title -->
                <h1 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-6">{{ $berita->judul }}</h1>

                <!-- Content -->
                <div class="prose prose-lg pkg-prose max-w-none mb-8">
                    {!! nl2br(e($berita->isi)) !!}
                </div>

                <!-- Image Gallery -->
                @if($berita->images && count($berita->images) > 0)
                    <div class="mb-8" data-reveal="up">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Dokumentasi</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($berita->images as $index => $image)
                                <div class="relative aspect-square overflow-hidden rounded-lg cursor-pointer group" onclick="openLightbox({{ $index }})">
                                    <img src="{{ asset('storage/' . $image) }}" alt="Dokumentasi" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Fullscreen Lightbox Modal -->
                    <div id="lightbox-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black">
                        <!-- Close Button (X) -->
                        <button onclick="closeLightbox()" id="lightbox-close-btn" class="absolute top-4 right-4 z-[10001] w-12 h-12 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/70 text-white transition-colors duration-200 backdrop-blur-sm border border-white/10" title="Tutup (Esc)">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <!-- Image Counter -->
                        <div class="absolute top-5 left-1/2 -translate-x-1/2 z-[10001] text-white/70 text-sm font-medium bg-black/40 px-4 py-1.5 rounded-full backdrop-blur-sm">
                            <span id="lightbox-counter">1 / {{ count($berita->images) }}</span>
                        </div>

                        <!-- Prev Button -->
                        @if(count($berita->images) > 1)
                        <button onclick="lightboxPrev()" class="absolute left-0 top-0 bottom-0 z-[10000] w-24 flex items-center justify-center group outline-none focus:outline-none" title="Sebelumnya">
                            <div class="w-14 h-14 flex items-center justify-center rounded-full bg-black/50 group-hover:bg-black/70 text-white/70 group-hover:text-white transition-all duration-200 backdrop-blur-sm border border-white/10">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </div>
                        </button>

                        <!-- Next Button -->
                        <button onclick="lightboxNext()" class="absolute right-0 top-0 bottom-0 z-[10000] w-24 flex items-center justify-center group outline-none focus:outline-none" title="Selanjutnya">
                            <div class="w-14 h-14 flex items-center justify-center rounded-full bg-black/50 group-hover:bg-black/70 text-white/70 group-hover:text-white transition-all duration-200 backdrop-blur-sm border border-white/10">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>
                        @endif

                        <!-- Lightbox Image -->
                        <div class="flex items-center justify-center w-full h-full" onclick="closeLightbox()">
                            <img id="lightbox-image" src="" alt="Preview" class="max-w-screen max-h-screen object-contain transition-opacity duration-300" onclick="event.stopPropagation()">
                        </div>
                    </div>

                    <style>
                        #lightbox-modal.lightbox-active {
                            display: flex !important;
                            animation: lightbox-fade-in 0.25s ease-out;
                        }
                        #lightbox-modal.lightbox-closing {
                            animation: lightbox-fade-out 0.2s ease-in forwards;
                        }
                        @keyframes lightbox-fade-in {
                            from { opacity: 0; }
                            to { opacity: 1; }
                        }
                        @keyframes lightbox-fade-out {
                            from { opacity: 1; }
                            to { opacity: 0; }
                        }
                        #lightbox-image {
                            max-height: 100vh;
                            max-width: 100vw;
                        }
                        body.lightbox-open {
                            overflow: hidden;
                        }
                    </style>

                    <script>
                        const lightboxImages = @json(collect($berita->images)->map(fn($img) => asset('storage/' . $img))->values());
                        let lightboxCurrentIndex = 0;

                        function openLightbox(index) {
                            lightboxCurrentIndex = index;
                            const modal = document.getElementById('lightbox-modal');
                            const img = document.getElementById('lightbox-image');
                            img.src = lightboxImages[index];
                            updateCounter();
                            modal.classList.remove('hidden', 'lightbox-closing');
                            modal.classList.add('lightbox-active');
                            document.body.classList.add('lightbox-open');
                        }

                        function closeLightbox() {
                            const modal = document.getElementById('lightbox-modal');
                            modal.classList.add('lightbox-closing');
                            setTimeout(() => {
                                modal.classList.remove('lightbox-active', 'lightbox-closing');
                                modal.classList.add('hidden');
                                document.body.classList.remove('lightbox-open');
                            }, 200);
                        }

                        function lightboxNext() {
                            lightboxCurrentIndex = (lightboxCurrentIndex + 1) % lightboxImages.length;
                            document.getElementById('lightbox-image').src = lightboxImages[lightboxCurrentIndex];
                            updateCounter();
                        }

                        function lightboxPrev() {
                            lightboxCurrentIndex = (lightboxCurrentIndex - 1 + lightboxImages.length) % lightboxImages.length;
                            document.getElementById('lightbox-image').src = lightboxImages[lightboxCurrentIndex];
                            updateCounter();
                        }

                        function updateCounter() {
                            document.getElementById('lightbox-counter').textContent = (lightboxCurrentIndex + 1) + ' / ' + lightboxImages.length;
                        }

                        // Keyboard: ESC to close, Arrow keys to navigate
                        document.addEventListener('keydown', function(e) {
                            const modal = document.getElementById('lightbox-modal');
                            if (!modal.classList.contains('lightbox-active')) return;

                            if (e.key === 'Escape') {
                                closeLightbox();
                            } else if (e.key === 'ArrowRight') {
                                lightboxNext();
                            } else if (e.key === 'ArrowLeft') {
                                lightboxPrev();
                            }
                        });
                    </script>
                @endif

                <!-- PDF Download -->
                @if($berita->pdf_path)
                    <div class="pkg-panel-gradient rounded-xl p-6 border-2 border-blue-200 dark:border-slate-700" data-reveal="up">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="bg-red-500 w-12 h-12 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">File PDF Tersedia</h3>
                                    <p class="text-sm text-gray-600 dark:text-slate-300">Unduh untuk informasi lengkap</p>
                                    @if($berita->download_count > 0)
                                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">{{ $berita->download_count }} kali diunduh</p>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('berita.download', $berita) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold inline-flex items-center transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Unduh PDF
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </article>

        <!-- Related News -->
        <div class="mt-12" data-reveal="up">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Berita Lainnya</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($relatedNews as $item)
                    <a href="{{ route('public.berita', $item->slug) }}" class="pkg-list-card block overflow-hidden dark:bg-slate-900 dark:shadow-black/30">
                        <div class="relative h-40 overflow-hidden">
                            @if($item->cover_path)
                                <img src="{{ asset('storage/' . $item->cover_path) }}" alt="{{ $item->judul }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-blue-400 to-purple-500"></div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white line-clamp-2 mb-2">{{ $item->judul }}</h3>
                            <p class="text-sm text-gray-600 dark:text-slate-400">{{ $item->published_at->format('d M Y') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
