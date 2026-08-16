<div class="space-y-4">
    <p class="pkg-card-soft p-3 text-sm text-gray-600 dark:text-gray-300">Cakupan data: {{ $scopeLabel }}.</p>
    <form action="{{ route('presensi.index').'#rekap-generus' }}" method="GET" class="pkg-filter-grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5">
        <input type="hidden" name="tab" value="rekap">
        <input type="hidden" name="panel" value="rekap-generus">
        <div><label for="generus_start" class="mb-2 block text-sm font-medium">Tanggal Mulai</label><input id="generus_start" name="start_date" type="date" value="{{ $startDate->format('Y-m-d') }}" class="pkg-field w-full"></div>
        <div><label for="generus_end" class="mb-2 block text-sm font-medium">Tanggal Selesai</label><input id="generus_end" name="end_date" type="date" value="{{ $endDate->format('Y-m-d') }}" class="pkg-field w-full"></div>
        <div><label for="generus_semester" class="mb-2 block text-sm font-medium">Semester RPP</label><select id="generus_semester" name="semester" class="pkg-field w-full">@foreach($semesterOptions as $value => $label)<option value="{{ $value }}" @selected($semester === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="generus_group" class="mb-2 block text-sm font-medium">Kelompok</label><select id="generus_group" name="kelompok" class="pkg-field w-full"><option value="">Semua Kelompok</option>@foreach($kelompokOptions as $value => $label)<option value="{{ $value }}" @selected($selectedKelompok === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="flex items-end gap-2"><button class="btn-primary min-h-11 flex-1 px-4 py-2">Terapkan</button><a href="{{ route('presensi.index', ['tab' => 'rekap', 'panel' => 'rekap-generus']).'#rekap-generus' }}" class="btn-secondary min-h-11 px-4 py-2">Reset</a></div>
    </form>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="pkg-card-soft p-4"><p class="text-sm text-gray-500">Total Generus</p><p class="mt-1 text-2xl font-black">{{ $totals['total_students'] }}</p></div>
        <div class="pkg-card-soft p-4"><p class="text-sm text-gray-500">Tugas Terverifikasi</p><p class="mt-1 text-2xl font-black text-emerald-600">{{ $totals['task']['verified'] }}</p></div>
        <div class="pkg-card-soft p-4"><p class="text-sm text-gray-500">Catatan Hadir</p><p class="mt-1 text-2xl font-black text-blue-600">{{ $totals['attendance']['present'] }}</p></div>
        <div class="pkg-card-soft p-4"><p class="text-sm text-gray-500">Target RPP Selesai</p><p class="mt-1 text-2xl font-black text-violet-600">{{ $totals['rpp']['completed'] }}</p></div>
    </div>

    <div class="overflow-x-auto pkg-mobile-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase">Kelompok</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase">Tugas PKG</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase">Kehadiran</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase">Target RPP</th></tr></thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($rows as $row)
                    <tr>
                        <td data-label="Kelompok" class="px-4 py-3 pkg-mobile-main"><div class="font-semibold">{{ $row['label'] }}</div><div class="text-xs text-gray-500">{{ $row['total_students'] }} Generus</div></td>
                        <td data-label="Tugas PKG" class="px-4 py-3 text-sm">{{ $row['task']['verified'] }} / {{ $row['task']['submitted'] }} <span class="pkg-status-badge pkg-status-success">{{ $row['task']['percentage'] }}%</span></td>
                        <td data-label="Kehadiran" class="px-4 py-3 text-sm">{{ $row['attendance']['present'] }} / {{ $row['attendance']['records'] }} <span class="pkg-status-badge pkg-status-info">{{ $row['attendance']['percentage'] }}%</span></td>
                        <td data-label="Target RPP" class="px-4 py-3 text-sm">{{ $row['rpp']['completed'] }} / {{ $row['rpp']['expected'] }} <span class="pkg-status-badge pkg-status-warning">{{ $row['rpp']['percentage'] }}%</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada Generus dalam cakupan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
