@extends('layouts.guru')

@section('title', 'Materi Guru')

@section('content')
<div class="space-y-5">
    <header><p class="text-sm font-bold text-emerald-600">Pustaka bahan ajar</p><h1 class="mt-1 text-2xl font-black">Materi Guru</h1><p class="mt-1 text-sm text-gray-500">Materi sesuai rombel pilihan dan materi khusus pada sesi Anda.</p></header>
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse($materials as $material)
            <article class="pkg-card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ collect($material->rombelLabels())->join(', ') ?: 'Semua Rombel' }}</span>@if($material->sessions->isNotEmpty())<span class="text-xs font-bold text-amber-600">Materi sesi</span>@endif</div>
                <h2 class="mt-4 text-lg font-black">{{ $material->title }}</h2>
                @if($material->description)<p class="mt-2 flex-1 text-sm leading-6 text-gray-500">{{ $material->description }}</p>@endif
                <a href="{{ $material->google_drive_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary mt-4 w-full justify-center">Buka Google Drive</a>
            </article>
        @empty
            <section class="pkg-empty-state sm:col-span-2"><h2 class="pkg-empty-title">Belum ada materi</h2><p class="pkg-empty-copy">Materi akan tampil sesuai rombel atau sesi yang ditugaskan kepada Anda.</p></section>
        @endforelse
    </div>
</div>
@endsection
