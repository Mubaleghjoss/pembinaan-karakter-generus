@extends('layouts.ortu')

@section('title', $materi->judul)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('ortu.materi.index') }}" class="mb-4 flex items-center gap-1 text-teal-600 hover:text-teal-800 dark:text-teal-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
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
        @include('materi.partials.pdf-viewer')
    </div>
</div>
@endsection
