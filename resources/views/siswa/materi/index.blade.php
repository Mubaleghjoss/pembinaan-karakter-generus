@extends('layouts.siswa')

@section('title', 'Materi Pembelajaran')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Materi Pembelajaran</h1>
            <p class="pkg-page-subheading">Pilih folder karakter, lalu buka materi yang tersedia.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <section class="pkg-panel mb-6 p-5">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Target Materi Saya</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Level kelas PKG: {{ $targetGradeLabel ?? 'Belum terdeteksi' }}
                </p>
            </div>
            <a href="{{ route('siswa.profile') }}" class="btn-secondary w-fit text-sm">Atur Level</a>
        </div>

        @if(! $targetGrade)
            <div class="pkg-empty-state">
                <p class="pkg-empty-title">Level kelas belum tersedia</p>
                <p class="pkg-empty-copy">Lengkapi tanggal lahir atau pilih level manual di profil agar target materi muncul.</p>
            </div>
        @else
            <form method="GET" class="pkg-filter-grid mb-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Semester</label>
                    <select name="target_semester" class="w-full pkg-field">
                        @foreach($semesterOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedTargetSemester === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                    <select name="target_category" class="w-full pkg-field">
                        @foreach($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedTargetCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-secondary w-full justify-center">Terapkan</button>
                </div>
            </form>

            @if($materiTargets->isEmpty())
                <div class="pkg-empty-state">
                    <p class="pkg-empty-title">Belum ada target</p>
                    <p class="pkg-empty-copy">Target untuk kategori ini belum disiapkan admin.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-300">Target</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-300">Ceklis</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($materiTargets as $target)
                                @php
                                    $progress = $targetProgress->get($target->id);
                                    $completed = (bool) ($progress?->is_completed);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $target->title }}</p>
                                        @if($target->description)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $target->description }}</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">{{ $target->semester_label }} - {{ $target->category_label }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="pkg-status-badge {{ $completed ? 'pkg-status-success' : 'pkg-status-neutral' }}">
                                            {{ $completed ? 'Selesai' : 'Belum' }}
                                        </span>
                                        @if($progress?->completed_at)
                                            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">{{ $progress->completed_at->format('d M Y') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('siswa.materi-targets.toggle', $target) }}" class="inline-flex justify-end">
                                            @csrf
                                            <input type="hidden" name="completed" value="0">
                                            <input type="checkbox" name="completed" value="1" class="pkg-check rounded" @checked($completed) onchange="this.form.submit()">
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </section>

    @if($materiFolders->isEmpty())
        <div class="pkg-card pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <h3 class="pkg-empty-title">Belum Ada Materi</h3>
            <p class="pkg-empty-copy">Materi pembelajaran belum tersedia saat ini.</p>
        </div>
    @else
        @include('materi.partials.read-only-folder-tree', [
            'folders' => $materiFolders,
            'detailRouteName' => 'siswa.materi.show',
        ])
    @endif
</div>
@endsection
