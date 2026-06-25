@extends('layouts.app')

@section('title', 'Jurnal RPP')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Jurnal RPP</h1>
            <p class="pkg-page-subheading">Pantau penugasan, kiriman siswa, dan realisasi setiap event materi.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('materi-rpp-journals.export', request()->only(['month', 'materi_id', 'workflow_status'])) }}" class="btn-success text-sm">Ekspor Excel</a>
            <a href="{{ route('calendar.index') }}" class="btn-secondary text-sm">Buka Kalender</a>
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
                <input type="month" name="month" value="{{ $selectedMonth }}" class="w-full pkg-field text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Materi</label>
                <select name="materi_id" class="w-full pkg-field text-sm">
                    <option value="">Semua Materi</option>
                    @foreach($materiOptions as $materiOption)
                        <option value="{{ $materiOption->id }}" @selected((int) $selectedMateriId === $materiOption->id)>{{ $materiOption->judul }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Jurnal</label>
                <select name="workflow_status" class="w-full pkg-field text-sm">
                    <option value="">Semua Status</option>
                    @foreach($workflowOptions as $statusValue => $statusLabel)
                        <option value="{{ $statusValue }}" @selected($selectedWorkflowStatus === $statusValue)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-secondary w-full justify-center text-sm">Terapkan</button>
            </div>
        </form>
    </div>

    <div class="pkg-card">
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Materi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Petugas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Realisasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($schedules as $schedule)
                        @php
                            $journal = $schedule->rppJournal;
                            $workflowState = $workflowService->workflowState($schedule);
                            $workflowLabel = $workflowService->workflowLabel($schedule);
                            $workflowTone = match ($workflowState) {
                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                'pending_review' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                'needs_revision' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300" data-label="Tanggal">
                                {{ $schedule->start_date?->format('d M Y') }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $schedule->start_time ? substr($schedule->getAttributes()['start_time'], 0, 5) : 'Sepanjang hari' }}
                                    {{ $schedule->end_time ? ' - ' . substr($schedule->getAttributes()['end_time'], 0, 5) : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 pkg-mobile-main" data-label="Materi">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $schedule->sourceMateri?->judul ?? $schedule->title }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Pertemuan {{ data_get($schedule->source_payload, 'number', '-') }} · {{ data_get($schedule->source_payload, 'page_range', '-') }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300" data-label="Petugas">
                                {{ $schedule->journal_assignee_label }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300" data-label="Realisasi">
                                {{ $journal?->actual_page_range ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm" data-label="Status">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $workflowTone }}">{{ $workflowLabel }}</span>
                            </td>
                            <td class="px-6 py-4 text-right pkg-mobile-actions" data-label="Aksi">
                                <a href="{{ route('materi-rpp-journals.schedule', $schedule) }}" class="btn-secondary px-3 py-2 text-xs">
                                    {{ $workflowState === 'pending_review' ? 'Tinjau Jurnal' : ($journal ? 'Lihat Jurnal' : 'Buka Tugas') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12">
                                <div class="pkg-empty-state">
                                    <p class="pkg-empty-title">Belum ada event jurnal</p>
                                    <p class="pkg-empty-copy">Event RPP yang sudah selesai akan muncul di halaman ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 p-4 dark:border-gray-700">
            {{ $schedules->links() }}
        </div>
    </div>
</div>
@endsection
