@extends('layouts.app')

@section('title', 'Binaan Pamong')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="pkg-page-header">
        <div>
            <p class="pkg-page-subheading">Data Utama</p>
            <h1 class="pkg-page-heading text-balance">Binaan Pamong</h1>
            <p class="pkg-page-subheading text-pretty">Generus dikelompokkan berdasarkan Pamong aktif dan dapat berasal dari kelas sekolah yang berbeda.</p>
        </div>
    </header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-5" aria-label="Ringkasan binaan">
        @foreach([
            ['label' => 'Pamong', 'value' => $totalPamong],
            ['label' => 'Generus Aktif', 'value' => $totalSiswa],
            ['label' => 'Sudah Dibina', 'value' => $totalAssigned],
            ['label' => 'Belum Ada Pamong', 'value' => $totalUnassigned],
            ['label' => 'Kelas Belum Diisi', 'value' => $totalUnconfirmedGrade],
        ] as $stat)
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <form method="GET" class="pkg-filter-bar">
        <div class="pkg-filter-grid md:grid-cols-4">
            <label class="block"><span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Cari</span><input name="search" value="{{ request('search') }}" class="pkg-field w-full" placeholder="Nama atau NIS"></label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Pamong</span>
                <select name="pamong_id" class="pkg-field w-full"><option value="">Semua Pamong</option>@foreach($pamongOptions as $pamong)<option value="{{ $pamong->id }}" @selected((int) request('pamong_id') === $pamong->id)>{{ $pamong->name ?: $pamong->username }}</option>@endforeach</select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Kelas Sekolah</span>
                <select name="school_grade" class="pkg-field w-full"><option value="">Semua Kelas Sekolah</option>@foreach($schoolGradeOptions as $value => $label)<option value="{{ $value }}" @selected(request('school_grade') === $value)>{{ $label }}</option>@endforeach</select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Kelompok</span>
                <select name="kelompok" class="pkg-field w-full"><option value="">Semua Kelompok</option>@foreach($kelompokOptions as $value => $label)<option value="{{ $value }}" @selected(request('kelompok') === $value)>{{ $label }}</option>@endforeach</select>
            </label>
        </div>
        <div class="mt-3 flex flex-wrap gap-2"><button class="btn-primary" type="submit">Terapkan Filter</button><a class="btn-secondary" href="{{ route('kelas.index') }}">Reset</a></div>
    </form>

    <div class="grid gap-4 xl:grid-cols-2">
        @forelse($pamongList as $pamong)
            <article class="pkg-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                    <div class="min-w-0"><h2 class="truncate text-base font-bold text-slate-900 dark:text-white">{{ $pamong->name ?: $pamong->username }}</h2><p class="text-sm text-slate-500 dark:text-slate-400">{{ $pamong->assignedStudents->count() }} Generus ditampilkan</p></div>
                    @if(auth()->user()->hasRole('admin'))<a href="{{ route('pamong.assign.form', $pamong) }}" class="btn-secondary shrink-0">Atur Binaan</a>@endif
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($pamong->assignedStudents as $assignment)
                        @php($student = $assignment->siswa)
                        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $student->nama }}</p><p class="text-xs text-slate-500 dark:text-slate-400">NIS {{ $student->nis }} &middot; {{ $student->kelompok_label ?? 'Kelompok belum diisi' }}</p></div>
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">{{ $student->school_grade_label ?? 'Kelas belum dikonfirmasi' }}</span>
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">Level {{ $student->target_grade_label ?? 'belum tersedia' }}</span>
                                @if($student->pamongAssignments->count() > 1)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ $student->pamongAssignments->count() }} Pamong</span>@endif
                            </div>
                        </div>
                    @empty
                        <div class="pkg-empty-state p-6"><h3 class="pkg-empty-title">Belum ada Generus sesuai filter</h3><p class="pkg-empty-copy">Ubah filter atau atur binaan Pamong ini.</p></div>
                    @endforelse
                </div>
            </article>
        @empty
            <div class="pkg-empty-state pkg-card p-8 xl:col-span-2"><h2 class="pkg-empty-title">Tidak ada Pamong yang cocok</h2><p class="pkg-empty-copy">Periksa kembali kata pencarian atau filter yang digunakan.</p><a href="{{ route('kelas.index') }}" class="btn-primary mt-4">Reset Filter</a></div>
        @endforelse
    </div>

    <section class="pkg-panel-lg p-4 sm:p-6">
        <div class="mb-4"><h2 class="text-lg font-bold text-slate-900 dark:text-white">Belum Memiliki Pamong</h2><p class="text-sm text-slate-500 dark:text-slate-400">Generus aktif yang belum masuk binaan Pamong mana pun.</p></div>
        @if($unassignedStudents->isEmpty())
            <div class="pkg-empty-state"><h3 class="pkg-empty-title">Semua Generus sudah memiliki Pamong</h3><p class="pkg-empty-copy">Tidak ada penugasan yang perlu dilengkapi.</p></div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($unassignedStudents as $student)<div class="pkg-card-soft p-3"><p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $student->nama }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $student->nis }} &middot; {{ $student->school_grade_label ?? 'Kelas belum dikonfirmasi' }}</p></div>@endforeach</div>
        @endif
    </section>
</div>
@endsection
