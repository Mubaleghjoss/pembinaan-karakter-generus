@extends('layouts.ortu')

@section('title', 'Materi Pembelajaran')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Materi Pembelajaran</h1>
            <p class="pkg-page-subheading">Pilih folder karakter untuk melihat materi yang dibagikan.</p>
        </div>
    </div>

    @if($materiFolders->isEmpty())
        <div class="pkg-card pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <h3 class="pkg-empty-title">Belum Ada Materi</h3>
            <p class="pkg-empty-copy">Materi pembelajaran belum tersedia saat ini.</p>
        </div>
    @else
        @include('materi.partials.read-only-folder-tree', [
            'folders' => $materiFolders,
            'detailRouteName' => 'ortu.materi.show',
        ])
    @endif
</div>
@endsection
