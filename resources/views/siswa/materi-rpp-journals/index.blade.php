@extends('layouts.siswa')

@section('title', 'Jurnal RPP')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Jurnal RPP</h1>
            <p class="pkg-page-subheading">Tugas jurnal materi yang dipercayakan kepada Anda.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="pkg-filter-bar mb-6">
        <form method="GET" class="pkg-filter-grid">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                <input type="month" name="month" value="{{ $selectedMonth }}" class="w-full pkg-field">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="workflow_status" class="w-full pkg-field">
                    <option value="">Semua Status</option>
                    @foreach($workflowOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedWorkflowStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-secondary w-full justify-center">Terapkan</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @forelse($schedules as $schedule)
            @php
                $state = $workflowService->workflowState($schedule);
                $label = $workflowService->workflowLabel($schedule);
            @endphp
            <article class="pkg-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $schedule->start_date?->format('d M Y') }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $schedule->sourceMateri?->judul ?? $schedule->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pertemuan {{ data_get($schedule->source_payload, 'number', '-') }} · {{ data_get($schedule->source_payload, 'page_range', '-') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $label }}</span>
                </div>

                <div class="mt-5 flex justify-end">
                    <a href="{{ route('siswa.materi-rpp-journals.show', $schedule) }}" class="{{ in_array($state, ['pending', 'needs_revision'], true) ? 'btn-primary' : 'btn-secondary' }}">
                        {{ $state === 'needs_revision' ? 'Perbaiki Jurnal' : ($state === 'pending' ? 'Isi Jurnal' : 'Lihat Status') }}
                    </a>
                </div>
            </article>
        @empty
            <div class="pkg-card md:col-span-2">
                <div class="pkg-empty-state">
                    <div class="pkg-empty-icon">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg>
                    </div>
                    <p class="pkg-empty-title">Belum ada tugas jurnal</p>
                    <p class="pkg-empty-copy">Tugas akan muncul setelah event materi yang ditugaskan kepada Anda selesai.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $schedules->links() }}</div>
</div>
@endsection
