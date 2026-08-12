@extends('layouts.siswa')

@section('title', "Tracer Bacaan Al-Qur'an")

@section('content')
<div class="mx-auto max-w-6xl space-y-5" x-data="{ formOpen: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Tracer Bacaan Al-Qur'an</h1>
            <p class="pkg-page-subheading">Catat bacaanmu dengan rapi. Catatan baru akan diperiksa Pamong sebelum masuk laporan resmi.</p>
        </div>
        <div class="pkg-page-actions">
            <button type="button" class="btn-primary min-h-11" @click="formOpen = !formOpen" x-text="formOpen ? 'Tutup Form' : 'Catat Bacaan'"></button>
        </div>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">Periksa kembali kolom yang ditandai. {{ $errors->first() }}</div>@endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Bacaan terakhir</p><p class="mt-1 font-bold">{{ $lastVerified?->reading_date?->isoFormat('D MMM YYYY') ?? 'Belum ada' }}</p><p class="mt-1 text-xs text-gray-500">{{ $lastVerified ? 'Hal. '.$lastVerified->page_end : 'Mulai catatan pertama' }}</p></div>
        <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Halaman bulan ini</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($monthPages) }}</p></div>
        <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Hari aktif</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($activeDays) }}</p></div>
        <div class="pkg-card p-4"><p class="text-xs text-gray-500 dark:text-gray-400">Menunggu</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format($pendingCount) }}</p></div>
    </div>

    <div x-show="formOpen" x-collapse class="pkg-panel-lg">
        <h2 class="text-lg font-bold">Catat bacaan baru</h2>
        <p class="mb-5 mt-1 text-sm text-gray-500 dark:text-gray-400">Isi posisi awal dan akhir sesuai mushaf yang kamu gunakan.</p>
        <form method="POST" action="{{ route('siswa.quran.store') }}">
            @csrf
            @include('quran-reading.partials.entry-fields')
            <button class="btn-primary mt-5 min-h-11 w-full justify-center sm:w-auto">Kirim untuk Verifikasi</button>
        </form>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        @if(config('quran-reading.scan_enabled'))
            <a href="{{ route('siswa.quran.scan') }}" class="pkg-card flex min-h-14 items-center justify-between p-4 font-semibold transition hover:border-emerald-400"><span>Scan lembar kertas</span><span aria-hidden="true">&rarr;</span></a>
        @endif
        <a href="{{ route('siswa.quran.sheet') }}" class="pkg-card flex min-h-14 items-center justify-between p-4 font-semibold transition hover:border-emerald-400"><span>Unduh lembar lanjutan</span><span aria-hidden="true">PDF</span></a>
        <a href="{{ route('siswa.quran.report') }}" class="pkg-card flex min-h-14 items-center justify-between p-4 font-semibold transition hover:border-emerald-400"><span>Unduh laporan resmi</span><span aria-hidden="true">PDF</span></a>
    </div>

    <section class="pkg-panel overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h2 class="font-bold">Riwayat bacaan</h2></div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($entries as $entry)
                <article class="p-4 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-bold">{{ $entry->reading_date->isoFormat('dddd, D MMMM YYYY') }}</p>
                            <p class="mt-1 text-sm">Halaman {{ $entry->page_start }}–{{ $entry->page_end }} · {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }} sampai {{ \App\Support\QuranCatalog::name($entry->surah_end) }} {{ $entry->ayah_end }}</p>
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
                <div class="pkg-empty-state"><p class="pkg-empty-title">Belum ada catatan bacaan</p><p class="pkg-empty-copy">Gunakan tombol Catat Bacaan untuk memulai.</p></div>
            @endforelse
        </div>
        @if($entries->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $entries->links() }}</div>@endif
    </section>
</div>
@endsection
