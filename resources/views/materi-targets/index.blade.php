@extends('layouts.app')

@section('title', 'Target Materi')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Target Materi</h1>
            <p class="pkg-page-subheading">Atur checklist target materi per level kelas PKG dan pantau progres siswa.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('materi.index') }}" class="btn-secondary text-sm">Kembali ke Materi</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pkg-filter-bar mb-6">
        <form method="GET" class="pkg-filter-grid">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Level Kelas</label>
                <select name="grade" class="w-full pkg-field">
                    @foreach($gradeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedGrade === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Semester</label>
                <select name="semester" class="w-full pkg-field">
                    @foreach($semesterOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedSemester === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                <select name="category" class="w-full pkg-field">
                    @foreach($categoryOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full justify-center">Terapkan</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <div class="space-y-6">
            @if($canCreate)
                <section class="pkg-panel p-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Target</h2>
                    <form method="POST" action="{{ route('materi-targets.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="target_grade" value="{{ $selectedGrade }}">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="category" value="{{ $selectedCategory }}">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Target</label>
                            <input type="text" name="title" value="{{ old('title') }}" class="w-full pkg-field" placeholder="Contoh: Makna QS Al Fatihah" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                            <textarea name="description" rows="3" class="w-full pkg-field" placeholder="Opsional">{{ old('description') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Urutan</label>
                                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', 0) }}" class="w-full pkg-field">
                            </div>
                            <label class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="is_active" value="1" class="pkg-check rounded" checked>
                                Aktif
                            </label>
                        </div>
                        <button type="submit" class="btn-primary w-full justify-center">Tambah Target</button>
                    </form>
                </section>
            @endif

            <section class="pkg-panel p-5">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Target</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $gradeOptions[$selectedGrade] }} - {{ $semesterOptions[$selectedSemester] }} - {{ $categoryOptions[$selectedCategory] }}</p>

                <div class="mt-4 space-y-3">
                    @forelse($targets as $target)
                        <div class="pkg-card-soft p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $target->title }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $target->description ?: 'Tanpa keterangan.' }}</p>
                                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Urutan: {{ $target->sort_order }} - {{ $target->semester_label }} - {{ $target->is_active ? 'Aktif' : 'Nonaktif' }} - Selesai: {{ $target->completed_count }}</p>
                                    @if($target->source_key)
                                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Sumber: {{ $target->source_key }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($canEdit || $canDelete)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if($canEdit)
                                        <details class="w-full">
                                            <summary class="cursor-pointer text-xs font-semibold text-emerald-600 dark:text-emerald-300">Edit Target</summary>
                                            <form method="POST" action="{{ route('materi-targets.update', $target) }}" class="mt-3 space-y-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="target_grade" value="{{ $target->target_grade }}">
                                                <input type="hidden" name="semester" value="{{ $target->semester }}">
                                                <input type="hidden" name="category" value="{{ $target->category }}">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Judul</label>
                                                    <input type="text" name="title" value="{{ $target->title }}" class="w-full pkg-field text-sm" required>
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Keterangan</label>
                                                    <textarea name="description" rows="2" class="w-full pkg-field text-sm">{{ $target->description }}</textarea>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Urutan</label>
                                                        <input type="number" name="sort_order" min="0" value="{{ $target->sort_order }}" class="w-full pkg-field text-sm">
                                                    </div>
                                                    <label class="mt-6 inline-flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                        <input type="checkbox" name="is_active" value="1" class="pkg-check rounded" @checked($target->is_active)>
                                                        Aktif
                                                    </label>
                                                </div>
                                                <button type="submit" class="btn-primary w-full justify-center text-xs">Simpan Target</button>
                                            </form>
                                        </details>
                                    @endif
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('materi-targets.destroy', $target) }}" data-confirm="Hapus target materi ini?" data-confirm-title="Hapus target" data-confirm-button="Hapus" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger px-3 py-2 text-xs">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="pkg-empty-state">
                            <p class="pkg-empty-title">Belum ada target</p>
                            <p class="pkg-empty-copy">Tambahkan target untuk level dan kategori ini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="pkg-panel p-5">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Progress Siswa</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Checklist seperti spreadsheet untuk {{ $gradeOptions[$selectedGrade] }} - {{ $semesterOptions[$selectedSemester] }} - {{ $categoryOptions[$selectedCategory] }}.</p>
            </div>

            @if($activeTargets->isEmpty())
                <div class="pkg-empty-state">
                    <p class="pkg-empty-title">Belum ada target aktif</p>
                    <p class="pkg-empty-copy">Aktifkan atau buat target terlebih dahulu untuk menampilkan kolom progress.</p>
                </div>
            @elseif($students->isEmpty())
                <div class="pkg-empty-state">
                    <p class="pkg-empty-title">Belum ada siswa</p>
                    <p class="pkg-empty-copy">Tidak ada siswa aktif pada level kelas ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="sticky left-0 z-10 min-w-56 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-300">Siswa</th>
                                @foreach($activeTargets as $target)
                                    <th class="min-w-36 px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-300">{{ $target->title }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($students as $student)
                                <tr>
                                    <td class="sticky left-0 z-10 bg-white px-4 py-3 dark:bg-gray-900">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $student->nama }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $student->nis }} - {{ $student->kelas?->nama ?? 'Tanpa kelas' }}</p>
                                    </td>
                                    @foreach($activeTargets as $target)
                                        @php
                                            $progress = $progressRows->get($student->id . ':' . $target->id);
                                            $completed = (bool) ($progress?->is_completed);
                                        @endphp
                                        <td class="px-4 py-3 text-center">
                                            @if($canEdit)
                                                <form method="POST" action="{{ route('materi-targets.progress.toggle', [$student, $target]) }}">
                                                    @csrf
                                                    <input type="hidden" name="completed" value="0">
                                                    <input type="checkbox" name="completed" value="1" class="pkg-check rounded" @checked($completed) onchange="this.form.submit()">
                                                </form>
                                            @else
                                                <span class="pkg-status-badge {{ $completed ? 'pkg-status-success' : 'pkg-status-neutral' }}">{{ $completed ? 'Selesai' : 'Belum' }}</span>
                                            @endif
                                            @if($progress?->completed_at)
                                                <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">{{ $progress->completed_at->format('d M Y') }}</p>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
