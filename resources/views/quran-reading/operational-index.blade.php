@extends('layouts.app')

@section('title', "Tracer Bacaan Al-Qur'an")

@section('content')
@php
    $tabs = [
        ['id' => 'rekap', 'label' => 'Rekap & Verifikasi', 'badge' => ($pendingEntries->count() + $pendingProgressSubmissions->count()) ?: null],
        ['id' => 'khatam', 'label' => 'Peta Khatam', 'badge' => $pendingProgressSubmissions->count() ?: null],
    ];
    if ($capabilities['create']) {
        $tabs[] = ['id' => 'input', 'label' => 'Input Manual'];
        if (config('quran-reading.scan_enabled')) {
            $tabs[] = ['id' => 'scan', 'label' => 'Scan Lembar'];
        }
    }
    $tabIds = collect($tabs)->pluck('id')->all();
    $activeTab = in_array(request('tab'), $tabIds, true) ? request('tab') : 'rekap';
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="pkg-page-header">
        <div><h1 class="pkg-page-heading">Tracer Bacaan Al-Qur'an</h1><p class="pkg-page-subheading">Input, verifikasi, scan, dan pantau riwayat bacaan Generus sesuai siswa binaan.</p></div>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $errors->first() }}</div>@endif

    <form method="GET" class="pkg-filter-bar">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        @if($selectedSiswa)<input type="hidden" name="siswa_id" value="{{ $selectedSiswa->id }}">@endif
        <div class="pkg-filter-grid">
            <label><span class="mb-1 block text-xs font-semibold">Cari Generus</span><input class="pkg-field min-h-11" name="search" value="{{ request('search') }}" placeholder="Nama atau NIS"></label>
            <label><span class="mb-1 block text-xs font-semibold">Kelas Sekolah</span><select class="pkg-field min-h-11" name="school_grade"><option value="">Semua kelas sekolah</option>@foreach($schoolGradeOptions as $value => $label)<option value="{{ $value }}" @selected(request('school_grade') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label><span class="mb-1 block text-xs font-semibold">Pamong</span><select class="pkg-field min-h-11" name="pamong_id"><option value="">Semua Pamong</option>@foreach($pamongOptions as $pamong)<option value="{{ $pamong->id }}" @selected((string) request('pamong_id') === (string) $pamong->id)>{{ $pamong->name ?: $pamong->username }}</option>@endforeach</select></label>
            <label><span class="mb-1 block text-xs font-semibold">Kelompok</span><select class="pkg-field min-h-11" name="kelompok"><option value="">Semua kelompok</option>@foreach($kelompokOptions as $value => $label)<option value="{{ $value }}" @selected(request('kelompok') === $value)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <div class="mt-3 flex flex-wrap gap-2"><button class="btn-primary min-h-11">Terapkan</button><a href="{{ route('quran.index', ['tab' => $activeTab]) }}#{{ $activeTab }}" class="btn-secondary min-h-11">Reset</a></div>
    </form>

    <x-tabs :tabs="$tabs" :default-tab="$activeTab" :sync-query="true">
        <x-tab-panel id="rekap" class="space-y-5">
            <div class="space-y-5" data-quran-bulk-root data-storage-key="pkg-quran-bulk-{{ auth()->id() }}" data-filtered-count="{{ min(50, $siswaList->total()) }}">
            @if($pendingEntries->isNotEmpty())
                <section class="pkg-panel overflow-hidden">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h2 class="font-bold">Antrean verifikasi <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $pendingEntries->count() }}</span></h2></div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pendingEntries as $entry)
                            <article class="p-4 sm:p-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div><p class="font-bold">{{ $entry->siswa->nama }} <span class="font-normal text-gray-500">&middot; {{ $entry->siswa->nis }}</span> @if($entry->siswa->isGraduated())<span class="ml-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800 dark:bg-sky-950/60 dark:text-sky-200">Alumni</span>@endif</p>@if($entry->siswa->isGraduated())<p class="mt-1 text-xs font-medium text-sky-700 dark:text-sky-300">Antrean Admin{{ $entry->siswa->alumniReviewer ? ' · Penanggung jawab: '.$entry->siswa->alumniReviewer->name : ' umum' }}</p>@endif<p class="mt-1 text-sm">{{ $entry->reading_date->isoFormat('D MMM YYYY') }} &middot; {{ $entry->page_range_label }} &middot; {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }}&ndash;{{ \App\Support\QuranCatalog::name($entry->surah_end) }} {{ $entry->ayah_end }}</p>@if($entry->notes)<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $entry->notes }}</p>@endif @if($entry->scan)<a class="mt-2 inline-flex min-h-11 items-center font-semibold text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300" href="{{ route('quran.scan.image', $entry->scan) }}" target="_blank" rel="noopener">Lihat foto scan</a>@endif</div>
                                    @if($capabilities['verify'])
                                        <div class="grid gap-2 sm:grid-cols-2 lg:w-[440px]">
                                            <form method="POST" action="{{ route('quran.verify', $entry) }}" class="flex flex-col gap-2 sm:flex-row">@csrf @method('PATCH')<input name="verification_notes" class="pkg-field min-h-11 min-w-0 flex-1" placeholder="Catatan opsional"><button class="btn-success min-h-11 justify-center">Verifikasi</button></form>
                                            <form method="POST" action="{{ route('quran.reject', $entry) }}" class="flex flex-col gap-2 sm:flex-row">@csrf @method('PATCH')<input name="verification_notes" class="pkg-field min-h-11 min-w-0 flex-1" placeholder="Alasan penolakan" required><button class="btn-danger min-h-11 justify-center">Tolak</button></form>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($capabilities['export'])
                <section class="pkg-panel-lg">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="font-bold">Dokumen Kosong</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Untuk orang umum atau pencatatan kertas. Tidak berisi identitas, QR, token, dan tidak dapat dipindai ke sistem.</p>
                        </div>
                        <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">Manual</span>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <a class="btn-secondary min-h-11 justify-center" href="{{ route('quran.blank.monthly') }}">Unduh Lembar Bulanan Kosong</a>
                        <a class="btn-secondary min-h-11 justify-center" href="{{ route('quran.blank.reference') }}">Unduh Referensi 114 Surat Kosong</a>
                        <a class="btn-success min-h-11 justify-center sm:col-span-2 xl:col-span-1" href="{{ route('quran.blank.duplex') }}">Unduh Paket Kosong Bolak-Balik</a>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Paket terdiri dari halaman depan Lembar Bulanan dan halaman belakang Referensi 114 Surat. Cetak landscape, bolak-balik, lalu pilih balik sisi pendek.</p>
                </section>

                <section class="pkg-panel-lg">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div><h2 class="font-bold">Cetak Massal</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maksimal 50 Generus. PDF dirender bertahap agar aman di server.</p></div>
                        <div class="flex flex-wrap gap-2"><span class="rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"><span data-quran-bulk-count>0</span> dipilih</span><span class="rounded-full border border-gray-200 px-3 py-1.5 text-sm font-semibold dark:border-gray-700"><span data-quran-bulk-pages>0</span> halaman</span></div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2"><button type="button" class="btn-secondary min-h-11" data-quran-bulk-page>Pilih Halaman Ini</button><button type="button" class="btn-secondary min-h-11" data-quran-bulk-clear>Hapus Pilihan</button></div>
                    <form method="POST" action="{{ route('quran.bulk-sheets') }}" class="mt-4 grid gap-4 sm:grid-cols-2" data-quran-bulk-form>@csrf
                        <input type="hidden" name="search" value="{{ request('search') }}"><input type="hidden" name="school_grade" value="{{ request('school_grade') }}"><input type="hidden" name="pamong_id" value="{{ request('pamong_id') }}"><input type="hidden" name="kelompok" value="{{ request('kelompok') }}"><span data-quran-bulk-hidden></span>
                        <fieldset><legend class="mb-2 text-xs font-semibold">Jenis dokumen</legend><div class="grid gap-2">
                            <label class="flex min-h-12 items-start gap-3 rounded-xl border border-gray-200 px-3 py-3 dark:border-gray-700"><input class="pkg-check mt-0.5" type="radio" name="document_type" value="monthly" checked><span><strong class="block text-sm">Lembar Bacaan Bulanan</strong><span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Satu halaman, 31 baris, dapat dipindai.</span></span></label>
                            <label class="flex min-h-12 items-start gap-3 rounded-xl border border-gray-200 px-3 py-3 dark:border-gray-700"><input class="pkg-check mt-0.5" type="radio" name="document_type" value="surah_reference"><span><strong class="block text-sm">Peta Khatam</strong><span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Referensi 114 surat dan checklist manual.</span></span></label>
                            <label class="flex min-h-12 items-start gap-3 rounded-xl border border-emerald-300 bg-emerald-50/60 px-3 py-3 dark:border-emerald-800 dark:bg-emerald-950/20"><input class="pkg-check mt-0.5" type="radio" name="document_type" value="duplex"><span><strong class="block text-sm">Paket Bolak-Balik</strong><span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Depan bulanan, belakang Peta Khatam.</span></span></label>
                        </div></fieldset>
                        <fieldset><legend class="mb-2 text-xs font-semibold">Cakupan Generus</legend><div class="grid gap-2"><label class="flex min-h-11 items-center gap-3 rounded-xl border border-gray-200 px-3 dark:border-gray-700"><input class="pkg-check" type="radio" name="selection_mode" value="selected" data-quran-selection-mode="selected" disabled> Generus Terpilih</label><label class="flex min-h-11 items-center gap-3 rounded-xl border border-gray-200 px-3 dark:border-gray-700"><input class="pkg-check" type="radio" name="selection_mode" value="filtered" data-quran-selection-mode="filtered" checked> Semua Sesuai Filter</label></div><p class="mt-3 rounded-xl bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">Untuk paket dua sisi: cetak landscape, aktifkan bolak-balik, lalu pilih <strong>balik sisi pendek</strong>.</p></fieldset>
                        <button class="btn-primary min-h-12 justify-center sm:col-span-2" data-quran-bulk-submit>Unduh PDF Gabungan</button>
                    </form>
                </section>
            @endif

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.8fr)]">
                @include('quran-reading.partials.student-picker', ['targetTab' => 'rekap', 'bulkSelectable' => $capabilities['export']])
                <aside class="space-y-5">
                    @if($selectedSiswa)
                        <section class="pkg-panel-lg">
                            <h2 class="font-bold">Dokumen {{ $selectedSiswa->nama }}</h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laporan hanya memuat catatan yang sudah terverifikasi.</p>
                            @if($capabilities['export'])
                                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                    <a class="btn-secondary min-h-11 justify-center" href="{{ route('quran.sheet', $selectedSiswa) }}">Lembar Bulanan</a>
                                    <a class="btn-secondary min-h-11 justify-center" href="{{ route('quran.khatam-map', $selectedSiswa) }}">Peta Khatam</a>
                                    <a class="btn-success min-h-11 justify-center" href="{{ route('quran.duplex', $selectedSiswa) }}">Paket Bolak-Balik</a>
                                    <a class="btn-primary min-h-11 justify-center" href="{{ route('quran.report', $selectedSiswa) }}">Laporan PDF</a>
                                </div>
                            @else
                                <p class="mt-4 rounded-xl border border-gray-200 p-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">Akun ini tidak memiliki izin ekspor PDF.</p>
                            @endif
                        </section>
                        <section class="pkg-panel overflow-hidden">
                            <div class="border-b border-gray-200 px-4 py-3 font-bold dark:border-gray-700">Riwayat terbaru</div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($recentEntries as $entry)
                                    <article class="p-4">
                                        <div class="flex items-start justify-between gap-2"><p class="font-semibold">{{ $entry->reading_date->isoFormat('D MMM YYYY') }}</p>@include('quran-reading.partials.status', ['status' => $entry->status])</div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $entry->page_range_label }} &middot; {{ \App\Support\QuranCatalog::name($entry->surah_start) }} {{ $entry->ayah_start }}&ndash;{{ $entry->ayah_end }}</p>
                                        @if($capabilities['edit'])
                                            <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold text-emerald-700 dark:text-emerald-300">Perbaiki catatan</summary><form method="POST" action="{{ route('quran.update', $entry) }}" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">@csrf @method('PUT') @include('quran-reading.partials.entry-fields', ['entry' => $entry])<button class="btn-primary mt-4 min-h-11 w-full justify-center">Simpan Perbaikan</button></form></details>
                                        @endif
                                    </article>
                                @empty
                                    <div class="pkg-empty-state"><p class="pkg-empty-title">Belum ada riwayat</p></div>
                                @endforelse
                            </div>
                        </section>
                    @else
                        <div class="pkg-empty-state pkg-panel"><p class="pkg-empty-title">Pilih salah satu Generus</p><p class="pkg-empty-copy">Dokumen dan riwayat akan muncul di sini.</p></div>
                    @endif
                </aside>
            </div>
            </div>
        </x-tab-panel>

        <x-tab-panel id="khatam" class="space-y-5">
            @if($pendingProgressSubmissions->isNotEmpty())
                <section class="pkg-panel overflow-hidden">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700"><h2 class="font-bold">Antrean Peta Khatam <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $pendingProgressSubmissions->count() }}</span></h2></div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pendingProgressSubmissions as $submission)
                            <article class="p-4 sm:p-5"><div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"><div><p class="font-bold">{{ $submission->siswa->nama }}</p><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ count($submission->completed_surahs ?? []) }} surat baru &middot; Posisi aktif {{ $submission->active_surah ? \App\Support\QuranCatalog::name($submission->active_surah).' ayat '.$submission->active_ayah : 'tidak diisi' }}</p>@if($submission->scan)<a class="mt-2 inline-flex min-h-11 items-center font-semibold text-emerald-700 hover:underline dark:text-emerald-300" href="{{ route('quran.scan.image', $submission->scan) }}" target="_blank" rel="noopener">Lihat foto scan</a>@endif</div>
                            @if($capabilities['verify'])<div class="grid gap-2 sm:grid-cols-2 lg:w-[440px]"><form method="POST" action="{{ route('quran.progress.verify', $submission) }}" class="flex flex-col gap-2 sm:flex-row">@csrf @method('PATCH')<input name="review_notes" class="pkg-field min-h-11 min-w-0 flex-1" placeholder="Catatan opsional"><button class="btn-success min-h-11 justify-center">Verifikasi</button></form><form method="POST" action="{{ route('quran.progress.reject', $submission) }}" class="flex flex-col gap-2 sm:flex-row">@csrf @method('PATCH')<input name="review_notes" class="pkg-field min-h-11 min-w-0 flex-1" placeholder="Alasan penolakan" required><button class="btn-danger min-h-11 justify-center">Tolak</button></form></div>@endif</div></article>
                        @endforeach
                    </div>
                </section>
            @endif
            <div class="grid gap-5 lg:grid-cols-[minmax(0,0.75fr)_minmax(0,1.25fr)]">
                @include('quran-reading.partials.student-picker', ['targetTab' => 'khatam'])
                <aside>@if($selectedSiswa)@include('quran-reading.partials.khatam-card', ['downloadUrl' => $capabilities['export'] ? route('quran.khatam-map', $selectedSiswa) : null, 'correctionRoute' => $capabilities['edit'] && $khatam['cycle'] ? route('quran.progress.correct', $selectedSiswa) : null])@else<div class="pkg-empty-state pkg-panel"><p class="pkg-empty-title">Pilih Generus</p><p class="pkg-empty-copy">Progres siklus dan Peta Khatam akan muncul di sini.</p></div>@endif</aside>
            </div>
        </x-tab-panel>

        @if($capabilities['create'])
            <x-tab-panel id="input">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.8fr)]">
                    @include('quran-reading.partials.student-picker', ['targetTab' => 'input'])
                    <aside>
                        @if($selectedSiswa)
                            <section class="pkg-panel-lg">
                                <h2 class="font-bold">Input untuk {{ $selectedSiswa->nama }}</h2>
                                <p class="mb-4 mt-1 text-xs text-gray-500 dark:text-gray-400">Input Pamong/Admin langsung berstatus terverifikasi.</p>
                                <form method="POST" action="{{ route('quran.store') }}">@csrf<input type="hidden" name="siswa_id" value="{{ $selectedSiswa->id }}">@include('quran-reading.partials.entry-fields')<button class="btn-success mt-4 min-h-11 w-full justify-center">Simpan Terverifikasi</button></form>
                            </section>
                        @else
                            <div class="pkg-empty-state pkg-panel"><p class="pkg-empty-title">Pilih Generus untuk input manual</p><p class="pkg-empty-copy">Catatan dari Pamong atau Admin langsung terverifikasi.</p></div>
                        @endif
                    </aside>
                </div>
            </x-tab-panel>

            @if(config('quran-reading.scan_enabled'))
                <x-tab-panel id="scan">
                    <section class="pkg-panel-lg">
                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
                            Tidak perlu memilih Generus. QR pada lembar akan mengenali akun yang benar, lalu hasilnya ditampilkan untuk konfirmasi sebelum langsung diverifikasi.
                        </div>
                        @include('quran-reading.partials.scan-form', ['layout' => 'operational'])
                    </section>
                </x-tab-panel>
            @endif
        @endif
    </x-tabs>
</div>
@endsection

@if($capabilities['create'] && config('quran-reading.scan_enabled'))
    @push('scripts')
        @vite('resources/js/quran-scan.js')
    @endpush
@endif

@if($capabilities['export'])
    @push('scripts')
        @vite('resources/js/quran-bulk-print.js')
    @endpush
@endif
