@extends('layouts.ortu')

@section('title', "Bacaan Al-Qur'an")

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="pkg-page-header">
        <div><h1 class="pkg-page-heading">Bacaan Al-Qur'an</h1><p class="pkg-page-subheading">Riwayat bacaan terverifikasi milik {{ $siswa->nama }}.</p></div>
        <div class="pkg-page-actions"><a href="{{ route('ortu.quran.report') }}" class="btn-primary min-h-11">Unduh PDF</a></div>
    </div>
    @include('quran-reading.partials.khatam-card', ['downloadUrl' => null])
    <section class="pkg-panel overflow-hidden">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($entries as $entry)
                <article class="p-4 sm:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-between">
                        <div><p class="font-bold">{{ $entry->reading_date->isoFormat('D MMMM YYYY') }}</p><p class="mt-1 text-sm">Hal. {{ $entry->page_start }}–{{ $entry->page_end }} · {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }} sampai {{ \App\Support\QuranCatalog::name($entry->surah_end) }} {{ $entry->ayah_end }}</p>@if($entry->notes)<p class="mt-2 text-sm text-gray-500">{{ $entry->notes }}</p>@endif</div>
                        @include('quran-reading.partials.status', ['status' => $entry->status])
                    </div>
                </article>
            @empty
                <div class="pkg-empty-state"><p class="pkg-empty-title">Belum ada bacaan terverifikasi</p><p class="pkg-empty-copy">Catatan akan tampil setelah diperiksa Pamong.</p></div>
            @endforelse
        </div>
        @if($entries->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $entries->links() }}</div>@endif
    </section>
</div>
@endsection
