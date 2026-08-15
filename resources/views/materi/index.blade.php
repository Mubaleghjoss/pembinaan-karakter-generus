@extends('layouts.app')

@section('title', 'Materi Pembelajaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Materi Pembelajaran</h1>
            <p class="pkg-page-subheading">{{ ($canManageMateri ?? false) ? 'Kelola materi bulanan untuk siswa.' : 'Lihat materi pembelajaran yang tersedia.' }}</p>
        </div>
        @if($canManageMateri ?? false)
        <a href="{{ route('materi-targets.index') }}" class="btn-secondary text-sm">
            Target Materi
        </a>
        @endif
        @if($canCreateMateri ?? false)
        <a href="{{ route('materi.create') }}" class="btn-primary text-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Materi
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <section
        id="target-analytics"
        class="pkg-panel mb-6 p-5 scroll-mt-6"
        x-data="{ analyticsOpen: @js($targetAnalytics['show_completed_details'] || request()->filled('analytics_grade') || request()->filled('analytics_semester')) }"
    >
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Analitik Target Materi</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Scope: {{ $targetAnalytics['scope_label'] }}. Progress dihitung dari target aktif pada semester yang dipilih.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="analyticsOpen = !analyticsOpen" class="btn-secondary w-fit text-sm">
                    <span x-show="!analyticsOpen">Tampilkan Analitik</span>
                    <span x-show="analyticsOpen">Sembunyikan Analitik</span>
                </button>
                @if($canManageMateri ?? false)
                <a href="{{ route('materi-targets.index') }}" class="btn-secondary w-fit text-sm">
                    Kelola Target
                </a>
                @endif
            </div>
        </div>

        <div x-show="analyticsOpen" x-transition>
        <form method="GET" action="{{ route('materi.index') }}" class="pkg-filter-grid mb-5">
            @foreach(['search', 'folder_id', 'bulan', 'status'] as $preservedFilter)
                @if(request()->filled($preservedFilter))
                    <input type="hidden" name="{{ $preservedFilter }}" value="{{ request($preservedFilter) }}">
                @endif
            @endforeach
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Level</label>
                <select name="analytics_grade" class="w-full pkg-field text-sm">
                    <option value="">Semua Level</option>
                    @foreach($targetAnalytics['grade_options'] as $gradeValue => $gradeLabel)
                        <option value="{{ $gradeValue }}" @selected($targetAnalytics['selected_grade'] === $gradeValue)>{{ $gradeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Semester</label>
                <select name="analytics_semester" class="w-full pkg-field text-sm">
                    @foreach($targetAnalytics['semester_options'] as $semesterValue => $semesterLabel)
                        <option value="{{ $semesterValue }}" @selected((int) $targetAnalytics['selected_semester'] === (int) $semesterValue)>{{ $semesterLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-secondary w-full justify-center text-sm">Terapkan</button>
            </div>
        </form>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scope Siswa</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $targetAnalytics['student_count'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">dari {{ $targetAnalytics['scope_student_count'] }} siswa aktif</p>
            </div>
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Target</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $targetAnalytics['target_total'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">target yang seharusnya dikerjakan</p>
            </div>
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selesai</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ $targetAnalytics['completed_total'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ceklis selesai</p>
                @if($targetAnalytics['completed_total'] > 0)
                    <a href="{{ $targetAnalytics['completed_detail_url'] }}" class="mt-3 inline-flex text-xs font-semibold text-emerald-700 underline underline-offset-4 hover:text-emerald-800 dark:text-emerald-300 dark:hover:text-emerald-200">
                        Lihat siapa
                    </a>
                @endif
            </div>
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Persentase</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $targetAnalytics['percentage'] }}%</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $targetAnalytics['completed_total'] }} / {{ $targetAnalytics['target_total'] }}</p>
            </div>
        </div>

        @if($targetAnalytics['show_completed_details'])
        <section id="analytics-completed-details" class="pkg-card mt-5 scroll-mt-6 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Siswa yang Menyelesaikan Target</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $targetAnalytics['completed_student_count'] }} siswa menyelesaikan {{ $targetAnalytics['completed_total'] }} ceklis pada {{ $targetAnalytics['scope_label'] }}.
                    </p>
                </div>
                <a href="{{ $targetAnalytics['completed_detail_close_url'] }}" class="btn-secondary w-fit px-3 py-2 text-xs">Tutup detail</a>
            </div>

            @if($targetAnalytics['completed_students']->isEmpty())
                <div class="mt-4 pkg-empty-state">
                    <p class="pkg-empty-title">Belum ada ceklis selesai</p>
                    <p class="pkg-empty-copy">Belum ada siswa yang menyelesaikan target sesuai filter aktif.</p>
                </div>
            @else
                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach($targetAnalytics['completed_students'] as $student)
                        <article class="pkg-card-soft p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $student['nama'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">NIS {{ $student['nis'] }} · {{ $student['kelas'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                    {{ $student['completed_count'] }} ceklis
                                </span>
                            </div>

                            <div class="mt-4 divide-y divide-gray-200 border-t border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                                @foreach($student['targets'] as $target)
                                    <div class="py-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $target['title'] }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $target['category'] }} · selesai {{ $target['completed_at'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
        @endif

        @if($targetAnalytics['unleveled_count'] > 0)
        <details class="group mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
            <summary class="flex cursor-pointer list-none flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span>{{ $targetAnalytics['unleveled_count'] }} siswa belum punya level kelas PKG, sehingga tidak masuk perhitungan target.</span>
                <span class="font-semibold text-amber-900 underline underline-offset-4 dark:text-amber-100">
                    <span class="group-open:hidden">Lihat daftar</span>
                    <span class="hidden group-open:inline">Tutup daftar</span>
                </span>
            </summary>
            <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach($targetAnalytics['unleveled_students'] as $student)
                    <div class="rounded-lg border border-amber-200 bg-white/70 p-3 dark:border-amber-800 dark:bg-gray-900/30">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $student['nama'] }}</p>
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-200">
                                    NIS {{ $student['nis'] }} - Kelas Sekolah {{ $student['kelas'] }} - Lahir {{ $student['tanggal_lahir'] }}
                                </p>
                            </div>
                            @if($student['edit_url'])
                                <a href="{{ $student['edit_url'] }}" class="btn-secondary w-fit px-3 py-2 text-xs">Edit</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
        @endif

        @if($targetAnalytics['student_count'] < 1 || $targetAnalytics['target_total'] < 1)
        <div class="mt-5 pkg-empty-state">
            <p class="pkg-empty-title">Analitik belum tersedia</p>
            <p class="pkg-empty-copy">Belum ada siswa dengan level sesuai filter atau belum ada target aktif pada semester ini.</p>
        </div>
        @else
        <div class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
            @foreach($targetAnalytics['categories'] as $categoryProgress)
                <div class="pkg-card-soft p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $categoryProgress['label'] }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $categoryProgress['completed'] }} / {{ $categoryProgress['expected'] }} selesai</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                            {{ $categoryProgress['percentage'] }}%
                        </span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, max(0, $categoryProgress['percentage'])) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_320px] mb-6">
        <details class="pkg-panel group overflow-hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Folder Materi</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Materi dikelompokkan seperti folder agar mudah dieksplorasi. Klik untuk membuka daftar folder.</p>
                </div>
                <span class="btn-secondary px-3 py-2 text-xs">
                    <span class="group-open:hidden">Buka</span>
                    <span class="hidden group-open:inline">Tutup</span>
                </span>
            </summary>
            <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                <div class="mb-4 flex justify-end">
                    <a href="{{ route('materi.index') }}" class="btn-secondary px-3 py-2 text-xs">Semua Folder</a>
                </div>
                <div class="space-y-4">
                @forelse($folderCards as $folder)
                    @php
                        $children = $folder->childrenTree ?? collect();
                        $totalCount = (int) ($folder->total_materi_count ?? $folder->materi_count ?? 0);
                    @endphp
                    <details class="pkg-card-soft group overflow-hidden {{ (int) request('folder_id') === $folder->id ? 'ring-2 ring-emerald-500' : '' }}">
                        <summary class="flex cursor-pointer list-none flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $folder->name }}</p>
                                @if($folder->description)
                                    <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $folder->description }}</p>
                                @else
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Belum ada keterangan.</p>
                                @endif
                                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Urutan: {{ $folder->sort_order }} - {{ $folder->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $totalCount }}</span>
                                <span class="btn-secondary px-3 py-2 text-xs">
                                    <span class="group-open:hidden">Buka</span>
                                    <span class="hidden group-open:inline">Tutup</span>
                                </span>
                            </div>
                        </summary>

                        <div class="space-y-3 border-t border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($folder->exists)
                                    <a href="{{ route('materi.index', ['folder_id' => $folder->id]) }}" class="btn-secondary px-3 py-2 text-xs">Buka Folder Ini</a>
                                @endif
                                @if(($canEditMateri ?? false) && $folder->exists)
                                    <details class="group w-full">
                                        <summary class="mt-2 cursor-pointer text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-300">
                                            Edit Folder
                                        </summary>
                                        @include('materi.partials.folder-edit-form', ['currentFolder' => $folder, 'folderOptions' => $folderOptions])
                                    </details>
                                @endif
                            </div>

                            @if($children->isNotEmpty())
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($children as $child)
                                        <div class="rounded-xl border border-gray-200 bg-white/70 p-3 dark:border-gray-700 dark:bg-gray-900/40 {{ (int) request('folder_id') === $child->id ? 'ring-2 ring-emerald-500' : '' }}">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $child->name }}</p>
                                                    @if($child->description)
                                                        <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $child->description }}</p>
                                                    @else
                                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Belum ada keterangan.</p>
                                                    @endif
                                                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Urutan: {{ $child->sort_order }} - {{ $child->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                                                </div>
                                                <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ $child->total_materi_count ?? $child->materi_count }}</span>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @if($child->exists)
                                                    <a href="{{ route('materi.index', ['folder_id' => $child->id]) }}" class="btn-secondary px-3 py-2 text-xs">Buka</a>
                                                @endif
                                                @if(($canEditMateri ?? false) && $child->exists)
                                                    <details class="group w-full">
                                                        <summary class="mt-2 cursor-pointer text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-300">
                                                            Edit Folder
                                                        </summary>
                                                        @include('materi.partials.folder-edit-form', ['currentFolder' => $child, 'folderOptions' => $folderOptions])
                                                    </details>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="pkg-empty-state">
                        <p class="pkg-empty-title">Belum ada folder</p>
                        <p class="pkg-empty-copy">Buat folder pertama untuk mengelompokkan materi.</p>
                    </div>
                @endforelse
                </div>
            </div>
        </details>

        @if($canCreateMateri ?? false)
        <div class="pkg-panel p-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Buat Folder</h2>
            <form method="POST" action="{{ route('materi.folders.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Folder</label>
                    <input type="text" name="name" class="w-full pkg-field" placeholder="Contoh: Jujur" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Folder Induk</label>
                    <select name="parent_id" class="w-full pkg-field">
                        <option value="">Folder Utama</option>
                        @foreach($folderOptions as $folder)
                            <option value="{{ $folder->id }}" @selected((int) old('parent_id') === (int) $folder->id)>
                                {{ $folder->display_name ?? $folder->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <textarea name="description" rows="3" class="w-full pkg-field" placeholder="Opsional"></textarea>
                </div>
                <button type="submit" class="btn-primary w-full justify-center text-sm">Tambah Folder</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Filters -->
    <div class="pkg-filter-bar mb-6">
        <form method="GET" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari materi..."
                class="flex-1 min-w-[200px] px-3 py-2 pkg-field text-sm">
            <select name="folder_id" class="px-3 py-2 pkg-field text-sm">
                <option value="">Semua Folder</option>
                @foreach($materiFolders as $folder)
                    <option value="{{ $folder->id }}" @selected((int) request('folder_id') === $folder->id)>{{ $folder->display_name ?? $folder->name }}</option>
                @endforeach
            </select>
            <input type="month" name="bulan" value="{{ request('bulan') }}"
                class="px-3 py-2 pkg-field text-sm">
            @if($canManageMateri ?? false)
            <select name="status" class="px-3 py-2 pkg-field text-sm">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            @endif
            <button type="submit" class="btn-primary text-sm !px-4 !py-2">Filter</button>
            <a href="{{ route('materi.index') }}" class="btn-secondary text-sm !px-4 !py-2">Reset</a>
        </form>
    </div>

    <!-- Materi List -->
    <div class="pkg-card">
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Judul</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Folder</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bulan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Media</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($materi as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 pkg-mobile-main" data-label="Judul">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->judul }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($item->deskripsi, 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="Folder">
                            {{ $item->folder?->display_name ?? $item->folder?->name ?? 'Tanpa Folder' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="Bulan">
                            {{ $item->bulan ? $item->bulan->format('F Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Media">
                            <div class="flex items-center justify-center gap-2">
                                @if($item->pdf_path)
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded">PDF</span>
                                @endif
                                @if($item->has_video_links)
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">Video</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center" data-label="Status">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $item->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right pkg-mobile-actions" data-label="Aksi">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('materi.show', $item) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 text-sm">Lihat</a>
                                @if($canEditMateri ?? false)
                                <a href="{{ route('materi.edit', $item) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 text-sm">Edit</a>
                                <form action="{{ route('materi.toggle-status', $item) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 text-sm">
                                        {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 pkg-mobile-empty" data-label="">
                            <div class="pkg-empty-state">
                                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <p class="pkg-empty-title">Belum ada materi</p>
                                <p class="pkg-empty-copy">Tambahkan materi pertama untuk mulai membagikan pembelajaran ke siswa.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $materi->links() }}
        </div>
    </div>
</div>
@endsection

