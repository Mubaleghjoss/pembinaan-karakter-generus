@extends('layouts.public')

@section('title', $materi->judul . ' - ' . ($theme->app_name ?? 'PKG Presensi'))

@section('content')
<div class="min-h-screen py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6" data-reveal="left">
            <a href="{{ route('materi.index') }}" class="pkg-link-accent inline-flex items-center font-medium">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
                </svg>
                Kembali ke Materi
            </a>
        </div>

        <div class="pkg-surface overflow-hidden rounded-2xl" data-reveal="zoom">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
                <div class="mb-2 flex items-center gap-2 text-sm text-blue-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
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

            <div class="border-b border-gray-200 p-6 dark:border-slate-800">
                <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Deskripsi</h2>
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
                @include('materi.partials.pdf-viewer', [
                    'wrapperClass' => 'border-b border-gray-200 p-6 dark:border-slate-800',
                    'heading' => 'File PDF',
                ])
                @include('materi.partials.video-list', ['materi' => $materi])
            @endif
        </div>
    </div>
</div>
@endsection
