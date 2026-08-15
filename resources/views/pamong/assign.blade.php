@extends('layouts.app')

@section('title', 'Papan Plotting Binaan Pamong')

@section('content')
<div
    class="mx-auto max-w-screen-2xl space-y-5"
    data-pamong-assignment-board
    data-save-url="{{ route('pamong.assignments.board') }}"
    data-reload-url="{{ route('pamong.assign.form', $pamong) }}"
>
    <script type="application/json" data-board-payload>@json($boardData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>

    <header class="pkg-page-header">
        <div class="min-w-0">
            <p class="pkg-page-subheading">Data Utama</p>
            <h1 class="pkg-page-heading text-balance">Papan Plotting Generus–Pamong</h1>
            <p class="pkg-page-subheading text-pretty">
                Geser Generus antarkolom untuk memperbarui binaan. Kolom
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $pamong->display_name }}</span>
                otomatis difokuskan saat halaman dibuka.
            </p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('kelas.index') }}" class="btn-secondary">Kembali ke Binaan Pamong</a>
            <a href="{{ route('pamong.show', $pamong) }}" class="btn-secondary">Profil Pamong</a>
        </div>
    </header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan pembagian">
        @foreach([
            ['label' => 'Pamong Aktif', 'value' => $totalPamong],
            ['label' => 'Generus Aktif', 'value' => $totalStudents],
            ['label' => 'Sudah Dibina', 'value' => $totalAssigned],
            ['label' => 'Belum Ada Pamong', 'value' => $totalUnassigned],
        ] as $stat)
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="pkg-filter-bar" aria-label="Filter papan plotting">
        <div class="pkg-filter-grid md:grid-cols-2 xl:grid-cols-5">
            <label class="block xl:col-span-2">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Cari Generus</span>
                <input type="search" class="pkg-field w-full" placeholder="Nama atau NIS" autocomplete="off" data-board-search>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Kelas Sekolah</span>
                <select class="pkg-field w-full" data-board-grade>
                    <option value="">Semua Kelas</option>
                    @foreach($gradeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                    <option value="unconfirmed">Belum dikonfirmasi</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Kelompok</span>
                <select class="pkg-field w-full" data-board-group>
                    <option value="">Semua Kelompok</option>
                    @foreach($kelompokOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Status Binaan</span>
                <select class="pkg-field w-full" data-board-status-filter>
                    <option value="">Semua Status</option>
                    <option value="assigned">Sudah Dibina</option>
                    <option value="unassigned">Belum Ada Pamong</option>
                </select>
            </label>
        </div>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <label class="block w-full sm:max-w-xs">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Fokus Pamong</span>
                <select class="pkg-field w-full" data-board-pamong-filter>
                    <option value="">Semua Pamong</option>
                    @foreach($boardData['pamongs'] as $item)
                        <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm text-slate-500 dark:text-slate-400" data-board-filter-summary aria-live="polite">Memuat data papan...</p>
                <button type="button" class="btn-secondary" data-board-reset-filters>Reset Filter</button>
            </div>
        </div>
    </section>

    <div class="hidden rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100" data-board-conflict role="alert">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-pretty">Data Binaan berubah di perangkat lain. Draft Anda masih tersimpan, tetapi muat ulang diperlukan sebelum menyimpan.</p>
            <button type="button" class="btn-secondary shrink-0" data-board-reload>Muat Ulang Data</button>
        </div>
    </div>

    <section class="pkg-card overflow-hidden" aria-labelledby="board-heading">
        <div class="flex flex-col gap-2 border-b border-slate-200 p-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="board-heading" class="text-base font-bold text-slate-900 dark:text-white">Pembagian Generus Aktif</h2>
                <p class="text-sm text-pretty text-slate-500 dark:text-slate-400">Pada HP, geser papan ke samping atau ketuk kartu untuk memilih tujuan Pamong.</p>
            </div>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Perubahan belum tersimpan sampai tombol Simpan ditekan.</p>
        </div>

        <div class="space-y-3 p-3 sm:p-4" data-board-loading aria-hidden="true">
            <div class="h-16 rounded-xl bg-slate-100 dark:bg-slate-800"></div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach(range(1, 3) as $unused)
                    <div class="h-72 rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                @endforeach
            </div>
        </div>

        <div class="pkg-mentorship-board hidden" data-board-scroll tabindex="0" aria-label="Papan pembagian Generus per Pamong">
            <div class="pkg-mentorship-columns" data-board-columns></div>
        </div>
    </section>

    <div class="pkg-mentorship-draft-bar hidden" data-board-draft-bar role="status">
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 dark:text-white"><span class="tabular-nums" data-board-dirty-count>0</span> Generus berubah</p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400" data-board-change-summary>Belum ada perubahan.</p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <button type="button" class="btn-secondary" data-board-undo disabled>Urungkan</button>
            <button type="button" class="btn-secondary" data-board-reset-draft>Reset</button>
            <button type="button" class="btn-primary" data-board-open-save>Simpan Perubahan</button>
        </div>
    </div>

    <dialog class="pkg-modal pkg-mentorship-dialog" data-board-action-dialog aria-labelledby="assignment-action-title">
        <form method="dialog" class="flex max-h-[85dvh] flex-col">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 dark:border-slate-700">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-300">Atur Binaan</p>
                    <h2 id="assignment-action-title" class="truncate text-lg font-bold text-slate-900 dark:text-white" data-action-student-name>Generus</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400" data-action-student-meta></p>
                </div>
                <button type="submit" value="cancel" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Tutup pengaturan binaan">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="min-h-0 space-y-4 overflow-y-auto p-5">
                <div class="pkg-card-soft p-3">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pamong aktif saat ini</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white" data-action-current-pamongs></p>
                </div>
                <label class="block">
                    <span class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Pilih Pamong tujuan</span>
                    <select class="pkg-field w-full" data-action-target></select>
                </label>
                <p class="text-sm text-pretty text-slate-500 dark:text-slate-400" data-action-help></p>
            </div>
            <div class="grid gap-2 border-t border-slate-200 p-5 dark:border-slate-700 sm:grid-cols-2">
                <button type="button" class="btn-primary min-h-11" data-action-move>Pindahkan</button>
                <button type="button" class="btn-secondary min-h-11" data-action-add>Tambahkan Pamong</button>
                <button type="button" class="btn-danger min-h-11 sm:col-span-2" data-action-end>Akhiri Binaan Ini</button>
            </div>
        </form>
    </dialog>

    <dialog class="pkg-modal pkg-mentorship-dialog" data-board-save-dialog aria-labelledby="assignment-save-title">
        <div class="flex max-h-[85dvh] flex-col">
            <div class="border-b border-slate-200 p-5 dark:border-slate-700">
                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-300">Konfirmasi Perubahan</p>
                <h2 id="assignment-save-title" class="text-lg font-bold text-balance text-slate-900 dark:text-white">Simpan pembagian Generus dan Pamong?</h2>
                <p class="mt-1 text-sm text-pretty text-slate-500 dark:text-slate-400" data-save-summary></p>
            </div>
            <div class="min-h-0 overflow-y-auto p-5">
                <ul class="space-y-2" data-save-list></ul>
            </div>
            <div class="border-t border-slate-200 p-5 dark:border-slate-700">
                <div
                    class="mb-3 hidden rounded-xl border border-red-300 bg-red-50 p-3 text-sm font-semibold text-red-800 dark:border-red-700 dark:bg-red-950/40 dark:text-red-200"
                    data-save-error
                    role="alert"
                    tabindex="-1"
                ></div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="btn-secondary min-h-11" data-save-cancel>Batal</button>
                    <button type="button" class="btn-primary min-h-11" data-save-confirm>Simpan</button>
                </div>
            </div>
        </div>
    </dialog>
</div>
@endsection
