@extends('layouts.app')

@section('title', "Tracer Bacaan Al-Qur'an")

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="pkg-page-header">
        <div><h1 class="pkg-page-heading">Tracer Bacaan Al-Qur'an</h1><p class="pkg-page-subheading">Input, verifikasi, dan pantau riwayat bacaan Generus sesuai siswa binaan.</p></div>
    </div>
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $errors->first() }}</div>@endif

    <form method="GET" class="pkg-filter-bar">
        <div class="pkg-filter-grid">
            <label><span class="mb-1 block text-xs font-semibold">Cari siswa</span><input class="pkg-field min-h-11" name="search" value="{{ request('search') }}" placeholder="Nama atau NIS"></label>
            <label><span class="mb-1 block text-xs font-semibold">Kelas</span><select class="pkg-field min-h-11" name="kelas_id"><option value="">Semua kelas</option>@foreach($kelasOptions as $kelas)<option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama }}</option>@endforeach</select></label>
            <label><span class="mb-1 block text-xs font-semibold">Kelompok</span><select class="pkg-field min-h-11" name="kelompok"><option value="">Semua kelompok</option>@foreach($kelompokOptions as $value => $label)<option value="{{ $value }}" @selected(request('kelompok') === $value)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <div class="mt-3 flex flex-wrap gap-2"><button class="btn-primary min-h-11">Terapkan</button><a href="{{ route('quran.index') }}" class="btn-secondary min-h-11">Reset</a></div>
    </form>

    @if($pendingEntries->isNotEmpty())
        <section class="pkg-panel overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h2 class="font-bold">Antrean verifikasi <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $pendingEntries->count() }}</span></h2></div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($pendingEntries as $entry)
                    <article class="p-4 sm:p-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div><p class="font-bold">{{ $entry->siswa->nama }} <span class="font-normal text-gray-500">&middot; {{ $entry->siswa->nis }}</span></p><p class="mt-1 text-sm">{{ $entry->reading_date->isoFormat('D MMM YYYY') }} &middot; Hal. {{ $entry->page_start }}&ndash;{{ $entry->page_end }} &middot; {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }}&ndash;{{ \App\Support\QuranCatalog::name($entry->surah_end) }} {{ $entry->ayah_end }}</p>@if($entry->notes)<p class="mt-1 text-sm text-gray-500">{{ $entry->notes }}</p>@endif</div>
                            @if($capabilities['verify'])
                                <div class="grid gap-2 sm:grid-cols-2 lg:w-[440px]">
                                    <form method="POST" action="{{ route('quran.verify', $entry) }}" class="flex gap-2">@csrf @method('PATCH')<input name="verification_notes" class="pkg-field min-h-11 min-w-0 flex-1" placeholder="Catatan opsional"><button class="btn-success min-h-11">Verifikasi</button></form>
                                    <form method="POST" action="{{ route('quran.reject', $entry) }}" class="flex gap-2">@csrf @method('PATCH')<input name="verification_notes" class="pkg-field min-h-11 min-w-0 flex-1" placeholder="Alasan penolakan" required><button class="btn-danger min-h-11">Tolak</button></form>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.8fr)]">
        <section class="pkg-panel overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h2 class="font-bold">Daftar Generus</h2><p class="mt-1 text-xs text-gray-500">Pilih siswa untuk input cepat, riwayat, dan PDF{{ config('quran-reading.scan_enabled') ? ', atau scan' : '' }}.</p></div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($siswaList as $siswa)
                    <a href="{{ route('quran.index', array_merge(request()->except('page'), ['siswa_id' => $siswa->id])) }}" class="flex min-h-16 items-center justify-between gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $selectedSiswa?->id === $siswa->id ? 'bg-emerald-50 dark:bg-emerald-950/30' : '' }}">
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $siswa->nama }}</p><p class="truncate text-xs text-gray-500">{{ $siswa->nis }} &middot; {{ $siswa->kelas?->nama ?? 'Tanpa kelas' }} &middot; {{ $siswa->kelompok_label ?? $siswa->kelompok ?? '-' }}</p></div><span aria-hidden="true">&rarr;</span>
                    </a>
                @empty<div class="pkg-empty-state"><p class="pkg-empty-title">Siswa tidak ditemukan</p></div>@endforelse
            </div>
            @if($siswaList->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $siswaList->links() }}</div>@endif
        </section>

        <aside class="space-y-5">
            @if($selectedSiswa)
                <section class="pkg-panel-lg">
                    <h2 class="font-bold">{{ $capabilities['create'] ? 'Input untuk' : 'Dokumen' }} {{ $selectedSiswa->nama }}</h2>
                    @if($capabilities['create'])
                        <p class="mb-4 mt-1 text-xs text-gray-500">Input Pamong/Admin langsung berstatus terverifikasi.</p>
                        <form method="POST" action="{{ route('quran.store') }}">@csrf<input type="hidden" name="siswa_id" value="{{ $selectedSiswa->id }}">@include('quran-reading.partials.entry-fields')<button class="btn-success mt-4 min-h-11 w-full justify-center">Simpan Terverifikasi</button></form>
                    @endif
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        @if($capabilities['create'] && config('quran-reading.scan_enabled'))
                            <a class="btn-secondary min-h-11 justify-center" href="{{ route('quran.scan', $selectedSiswa) }}">Scan</a>
                        @endif
                        @if($capabilities['export'])
                            <a class="btn-secondary min-h-11 justify-center" href="{{ route('quran.sheet', $selectedSiswa) }}">Lembar PDF</a>
                            <a class="btn-primary min-h-11 justify-center" href="{{ route('quran.report', $selectedSiswa) }}">Laporan PDF</a>
                        @endif
                    </div>
                </section>
                <section class="pkg-panel overflow-hidden">
                    <div class="border-b border-gray-200 px-4 py-3 font-bold dark:border-gray-700">Riwayat terbaru</div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentEntries as $entry)
                            <article class="p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold">{{ $entry->reading_date->isoFormat('D MMM YYYY') }}</p>
                                    @include('quran-reading.partials.status', ['status' => $entry->status])
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Hal. {{ $entry->page_start }}&ndash;{{ $entry->page_end }} &middot; {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }}&ndash;{{ $entry->ayah_end }}</p>
                                @if($capabilities['edit'])
                                    <details class="mt-3">
                                        <summary class="cursor-pointer text-sm font-semibold text-emerald-700 dark:text-emerald-300">Perbaiki catatan</summary>
                                        <form method="POST" action="{{ route('quran.update', $entry) }}" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                            @csrf
                                            @method('PUT')
                                            @include('quran-reading.partials.entry-fields', ['entry' => $entry])
                                            <button class="btn-primary mt-4 min-h-11 w-full justify-center">Simpan Perbaikan</button>
                                        </form>
                                    </details>
                                @endif
                            </article>
                        @empty
                            <div class="pkg-empty-state"><p class="pkg-empty-title">Belum ada riwayat</p></div>
                        @endforelse
                    </div>
                </section>
            @else
                <div class="pkg-empty-state pkg-panel"><p class="pkg-empty-title">Pilih salah satu siswa</p><p class="pkg-empty-copy">Panel input dan riwayat akan muncul di sini.</p></div>
            @endif
        </aside>
    </div>
</div>
@endsection
