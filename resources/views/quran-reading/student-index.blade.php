@extends('layouts.siswa')

@section('title', "Tracer Bacaan Al-Qur'an")

@section('content')
@php
    $tabs = [
        ['id' => 'rekap', 'label' => 'Riwayat'],
        ['id' => 'input', 'label' => 'Catat Bacaan'],
    ];
    if (config('quran-reading.scan_enabled')) {
        $tabs[] = ['id' => 'scan', 'label' => 'Scan Lembar'];
    }
    $tabIds = collect($tabs)->pluck('id')->all();
    $activeTab = in_array(request('tab'), $tabIds, true) ? request('tab') : 'rekap';
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Tracer Bacaan Al-Qur'an</h1>
            <p class="pkg-page-subheading">Catat bacaanmu dengan rapi. Catatan baru akan diperiksa Pamong sebelum masuk laporan resmi.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">Periksa kembali kolom yang ditandai. {{ $errors->first() }}</div>
    @endif

    <x-tabs :tabs="$tabs" :default-tab="$activeTab" :sync-query="true">
        <x-tab-panel id="rekap" class="space-y-5">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="pkg-card p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bacaan terakhir</p>
                    <p class="mt-1 font-bold">{{ $lastVerified?->reading_date?->isoFormat('D MMM YYYY') ?? 'Belum ada' }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $lastVerified ? 'Hal. '.$lastVerified->page_end : 'Mulai catatan pertama' }}</p>
                </div>
                <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Halaman bulan ini</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($monthPages) }}</p></div>
                <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Hari aktif</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($activeDays) }}</p></div>
                <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Menunggu</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($pendingCount) }}</p></div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('siswa.quran.sheet') }}" class="pkg-card flex min-h-14 items-center justify-between gap-3 p-4 font-semibold transition hover:border-emerald-400">
                    <span><span class="block">Unduh lembar lanjutan</span><span class="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">Cetak 12 baris untuk pencatatan di kertas.</span></span><span aria-hidden="true">PDF</span>
                </a>
                <a href="{{ route('siswa.quran.report') }}" class="pkg-card flex min-h-14 items-center justify-between gap-3 p-4 font-semibold transition hover:border-emerald-400">
                    <span><span class="block">Unduh laporan resmi</span><span class="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">Berisi catatan yang sudah terverifikasi.</span></span><span aria-hidden="true">PDF</span>
                </a>
            </div>

            <section class="pkg-panel overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h2 class="font-bold">Riwayat bacaan</h2></div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($entries as $entry)
                        <article class="p-4 sm:p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-bold">{{ $entry->reading_date->isoFormat('dddd, D MMMM YYYY') }}</p>
                                    <p class="mt-1 text-sm">Halaman {{ $entry->page_start }}&ndash;{{ $entry->page_end }} &middot; {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }} sampai {{ \App\Support\QuranCatalog::name($entry->surah_end) }} {{ $entry->ayah_end }}</p>
                                    @if($entry->notes)<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $entry->notes }}</p>@endif
                                    @if($entry->verification_notes)<p class="mt-2 rounded-lg bg-gray-50 p-2 text-xs dark:bg-gray-800">Catatan Pamong: {{ $entry->verification_notes }}</p>@endif
                                </div>
                                @include('quran-reading.partials.status', ['status' => $entry->status])
                            </div>
                            @if($entry->status === 'pending')
                                <details class="mt-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                                    <summary class="cursor-pointer font-semibold">Ubah catatan yang menunggu</summary>
                                    <form method="POST" action="{{ route('siswa.quran.update', $entry) }}" class="mt-4">
                                        @csrf @method('PUT')
                                        @include('quran-reading.partials.entry-fields', ['entry' => $entry])
                                        <button class="btn-primary mt-4 min-h-11">Simpan Perubahan</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @empty
                        <div class="pkg-empty-state"><p class="pkg-empty-title">Belum ada catatan bacaan</p><p class="pkg-empty-copy">Buka tab Catat Bacaan untuk memulai.</p></div>
                    @endforelse
                </div>
                @if($entries->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $entries->links() }}</div>@endif
            </section>
        </x-tab-panel>

        <x-tab-panel id="input">
            <section class="pkg-panel-lg">
                <h2 class="text-lg font-bold">Catat bacaan baru</h2>
                <p class="mb-5 mt-1 text-sm text-gray-500 dark:text-gray-400">Isi posisi awal dan akhir sesuai mushaf yang kamu gunakan.</p>
                <form method="POST" action="{{ route('siswa.quran.store') }}">
                    @csrf
                    @include('quran-reading.partials.entry-fields')
                    <button class="btn-primary mt-5 min-h-11 w-full justify-center sm:w-auto">Kirim untuk Verifikasi</button>
                </form>
            </section>
        </x-tab-panel>

        @if(config('quran-reading.scan_enabled'))
            <x-tab-panel id="scan">
                <section class="pkg-panel-lg space-y-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 class="text-lg font-bold">Scan lembar lanjutan</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Unduh dan isi lembar lebih dahulu, lalu foto untuk diperiksa.</p></div>
                        <a href="{{ route('siswa.quran.sheet') }}" class="btn-secondary min-h-11 justify-center">Unduh Lembar PDF</a>
                    </div>
                    @include('quran-reading.partials.scan-form', ['layout' => 'siswa'])
                </section>
            </x-tab-panel>
        @endif
    </x-tabs>
</div>
@endsection

@if(config('quran-reading.scan_enabled'))
    @push('scripts')
        @vite('resources/js/quran-scan.js')
    @endpush
@endif
