@extends('layouts.app')

@section('title', 'Jadwal Pengingat')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Jadwal Pengingat</h1>
            <p class="pkg-page-subheading">Kelola jadwal pengingat untuk kalender siswa dan pamong.</p>
        </div>
        <a href="{{ route('schedule-reminder.create') }}" class="btn-primary text-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Buat Jadwal Baru
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @php
        $dayLabels = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];
        $canEditAttendanceSchedule = auth()->user()->hasPamongCrudPermission('jadwal', 'edit');
    @endphp

    <div class="pkg-card overflow-hidden mb-6">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Jadwal Presensi Terintegrasi</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Data dari halaman Jadwal Presensi. Jadwal aktif otomatis tampil di kalender.</p>
                </div>
                <a href="{{ route('attendance-schedule.index') }}" class="btn-secondary text-sm">Kelola Presensi</a>
            </div>
        </div>
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Jam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Hari</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($attendanceSchedules as $attendanceSchedule)
                    <tr>
                        <td class="px-6 py-4 pkg-mobile-main" data-label="Nama">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $attendanceSchedule->name }}</div>
                            @if($attendanceSchedule->description)
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($attendanceSchedule->description, 70) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Target">
                            <span class="rounded-full bg-teal-100 px-2 py-1 text-xs font-medium text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                                {{ $attendanceSchedule->targetLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300" data-label="Jam">
                            {{ \Carbon\Carbon::parse($attendanceSchedule->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($attendanceSchedule->close_time)->format('H:i') }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">Tepat waktu sampai {{ \Carbon\Carbon::parse($attendanceSchedule->late_threshold)->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300" data-label="Hari">
                            {{ collect($attendanceSchedule->days ?? [])->map(fn ($day) => $dayLabels[$day] ?? ucfirst($day))->join(', ') ?: '-' }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">Tanggal: {{ $attendanceSchedule->dateRangeLabel() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Status">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $attendanceSchedule->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                {{ $attendanceSchedule->is_active ? 'Aktif di Kalender' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium pkg-mobile-actions" data-label="Aksi">
                            @if($canEditAttendanceSchedule)
                                <a href="{{ route('attendance-schedule.edit', $attendanceSchedule) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Edit Presensi</a>
                            @else
                                <a href="{{ route('attendance-schedule.index') }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Lihat Presensi</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 pkg-mobile-empty">
                            <div class="pkg-empty-state">
                                <p class="pkg-empty-title">Belum ada jadwal presensi</p>
                                <p class="pkg-empty-copy">Buat jadwal presensi agar muncul sebagai jadwal aktif di kalender.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schedule List -->
    <div class="pkg-card overflow-hidden">
        <div class="overflow-x-auto pkg-mobile-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jadwal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Target</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($schedules as $schedule)
                <tr>
                    <td class="px-6 py-4 pkg-mobile-main" data-label="Jadwal">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $schedule->color }}"></div>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $schedule->title }}</div>
                                @if($schedule->location)
                                <div class="text-sm text-gray-500 dark:text-gray-400">Lokasi: {{ $schedule->location }}</div>
                                @endif
                                @if($schedule->is_recurring)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Ulang {{ ucfirst($schedule->recurrence_pattern) }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Tanggal">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $schedule->start_date->format('d M Y') }}
                            @if($schedule->end_date)
                            - {{ $schedule->end_date->format('d M Y') }}
                            @endif
                        </div>
                        @if($schedule->time_range)
                        <div class="text-sm text-gray-500 dark:text-gray-400">Jam: {{ $schedule->time_range }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Target">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            {{ $schedule->target_audience === 'siswa' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                            {{ $schedule->target_audience === 'pamong' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $schedule->target_audience === 'all' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                        ">
                            {{ $schedule->target_audience === 'siswa' ? 'Siswa' : '' }}
                            {{ $schedule->target_audience === 'pamong' ? 'Pamong' : '' }}
                            {{ $schedule->target_audience === 'all' ? 'Semua' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Status">
                        <form action="{{ route('schedule-reminder.toggle', $schedule) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-2 py-1 text-xs font-medium rounded-full {{ $schedule->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium pkg-mobile-actions" data-label="Aksi">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('schedule-reminder.edit', $schedule) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Edit</a>
                            <form action="{{ route('schedule-reminder.destroy', $schedule) }}" method="POST" class="inline" data-confirm="Yakin ingin menghapus jadwal ini?" data-confirm-title="Hapus jadwal" data-confirm-button="Hapus" data-confirm-tone="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 pkg-mobile-empty" data-label="">
                        <div class="pkg-empty-state">
                            <svg class="pkg-empty-icon !w-12 !h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="pkg-empty-title">Belum ada jadwal pengingat</p>
                            <p class="pkg-empty-copy">Buat jadwal pertama untuk mulai mengirim pengingat ke siswa atau pamong.</p>
                            <a href="{{ route('schedule-reminder.create') }}" class="mt-3 inline-flex text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">Buat jadwal pertama</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $schedules->links() }}
    </div>
</div>
@endsection

