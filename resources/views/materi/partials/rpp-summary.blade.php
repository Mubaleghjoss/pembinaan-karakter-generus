@php
    $showStatus = $showStatus ?? false;
@endphp

@if($materi->hasRpp())
<div class="p-6 border-b border-gray-200 dark:border-gray-700">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">RPP Kalender</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Target {{ $materi->rpp_pages_per_session }} halaman per pertemuan dari halaman {{ $materi->rpp_start_page ?? 1 }} sampai {{ $materi->rpp_total_pages }}.
            </p>
        </div>
        @if($showStatus)
            <span class="pkg-status-badge {{ $materi->isRppPublished() ? 'pkg-status-success' : 'pkg-status-neutral' }}">
                {{ $materi->isRppPublished() ? 'Terpublikasi' : 'Draft' }}
            </span>
        @endif
    </div>
    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="pkg-card-soft rounded-xl p-4">
            <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Mulai</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $materi->rpp_start_date?->format('d M Y') }}</p>
        </div>
        <div class="pkg-card-soft rounded-xl p-4">
            <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Selesai Estimasi</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $materi->rpp_end_date?->format('d M Y') }}</p>
        </div>
        <div class="pkg-card-soft rounded-xl p-4">
            <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Waktu</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                @if($materi->rpp_start_time)
                    {{ substr($materi->rpp_start_time, 0, 5) }}{{ $materi->rpp_end_time ? ' - ' . substr($materi->rpp_end_time, 0, 5) : '' }}
                @else
                    Sepanjang hari
                @endif
            </p>
        </div>
        <div class="pkg-card-soft rounded-xl p-4">
            <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Hari Tambahan Mingguan</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ count($materi->rpp_extra_sessions ?? []) }} hari</p>
        </div>
        <div class="pkg-card-soft rounded-xl p-4">
            <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Kejar Target</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ count($materi->rpp_catch_up_ranges ?? []) }} rentang</p>
        </div>
        <div class="pkg-card-soft rounded-xl p-4">
            <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Pengajar</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ count($materi->rpp_teacher_pool ?? []) }} orang</p>
        </div>
    </div>
</div>
@endif
