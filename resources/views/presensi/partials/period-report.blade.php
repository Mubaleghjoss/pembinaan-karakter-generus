@php
    $statusFilter = request('status');
    $canonical = fn (array $extra = []) => route('presensi.index', array_merge(
        request()->except(['page']),
        ['tab' => 'rekap', 'panel' => 'laporan-periode'],
        $extra
    )).'#laporan-periode';
@endphp

<div class="space-y-4">
    <form action="{{ route('presensi.index').'#laporan-periode' }}" method="GET" class="pkg-filter-grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
        <input type="hidden" name="tab" value="rekap">
        <input type="hidden" name="panel" value="laporan-periode">
        <div>
            <label for="period_start_date" class="mb-2 block text-sm font-medium">Tanggal Mulai</label>
            <input id="period_start_date" name="start_date" type="date" value="{{ $startDate->format('Y-m-d') }}" class="pkg-field w-full">
        </div>
        <div>
            <label for="period_end_date" class="mb-2 block text-sm font-medium">Tanggal Selesai</label>
            <input id="period_end_date" name="end_date" type="date" value="{{ $endDate->format('Y-m-d') }}" class="pkg-field w-full">
        </div>
        <div>
            <label for="period_audience" class="mb-2 block text-sm font-medium">Jenis Data</label>
            <select id="period_audience" name="audience" class="pkg-field w-full">
                <option value="all" @selected($audience === 'all')>Siswa dan Pamong</option>
                <option value="siswa" @selected($audience === 'siswa')>Siswa</option>
                <option value="pamong" @selected($audience === 'pamong')>Pamong/Pengurus</option>
            </select>
        </div>
        <div>
            <label for="period_status" class="mb-2 block text-sm font-medium">Status</label>
            <select id="period_status" name="status" class="pkg-field w-full">
                <option value="">Semua Status</option>
                <option value="hadir" @selected($statusFilter === 'hadir')>Hadir</option>
                <option value="terlambat" @selected($statusFilter === 'terlambat')>Terlambat</option>
                <option value="izin_sakit" @selected($statusFilter === 'izin_sakit')>Izin/Sakit</option>
                <option value="alpha" @selected(in_array($statusFilter, ['alpha', 'tidak_hadir'], true))>Alpa (Tanpa Keterangan)</option>
            </select>
        </div>
        <div>
            <label for="period_school_grade" class="mb-2 block text-sm font-medium">Kelas Sekolah</label>
            <select id="period_school_grade" name="school_grade" class="pkg-field w-full">
                <option value="">Semua Kelas</option>
                @foreach($schoolGradeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('school_grade') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="period_pamong" class="mb-2 block text-sm font-medium">Pamong Binaan</label>
            <select id="period_pamong" name="pamong_id" class="pkg-field w-full">
                <option value="">Semua Pamong</option>
                @foreach($binaanPamongOptions as $pamong)
                    <option value="{{ $pamong->id }}" @selected(request('pamong_id') == $pamong->id)>{{ $pamong->name ?: $pamong->username }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="period_group" class="mb-2 block text-sm font-medium">Kelompok</label>
            <select id="period_group" name="kelompok" class="pkg-field w-full">
                <option value="">Semua Kelompok</option>
                @foreach($kelompokOptions as $value => $label)
                    <option value="{{ $value }}" @selected($kelompok === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="period_pamong_role" class="mb-2 block text-sm font-medium">Status Pamong</label>
            <select id="period_pamong_role" name="pamong_role" class="pkg-field w-full">
                <option value="">Semua Status</option>
                @foreach($pamongRoleOptions as $value => $label)
                    <option value="{{ $value }}" @selected($pamongRole === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="period_team" class="mb-2 block text-sm font-medium">Bidang</label>
            <select id="period_team" name="team_id" class="pkg-field w-full">
                <option value="">Semua Bidang</option>
                @foreach($teamOptions as $team)
                    <option value="{{ $team->id }}" @selected((string) $teamId === (string) $team->id)>{{ $team->short_name ?: $team->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary min-h-11 flex-1 px-4 py-2">Terapkan</button>
            <a href="{{ route('presensi.index', ['tab' => 'rekap', 'panel' => 'laporan-periode']).'#laporan-periode' }}" class="btn-secondary min-h-11 px-4 py-2">Reset</a>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['Total', $recap['total'], 'text-gray-900 dark:text-white'],
            ['Hadir', $recap['hadir'], 'text-green-600'],
            ['Terlambat', $recap['terlambat'], 'text-amber-600'],
            ['Izin', $recap['izin'], 'text-blue-600'],
            ['Sakit', $recap['sakit'], 'text-amber-600'],
            ['Alpa', $recap['alpha'], 'text-red-600'],
        ] as [$label, $value, $class])
            <div class="pkg-card-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-black {{ $class }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto pkg-mobile-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Kelas/Bidang</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Jam</th>
                </tr>
            </thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($records as $record)
                    <tr>
                        <td data-label="Tanggal" class="px-4 py-3 text-sm">{{ $record['date'] }}</td>
                        <td data-label="Nama" class="px-4 py-3 pkg-mobile-main"><div class="font-semibold">{{ $record['name'] }}</div><div class="text-xs text-gray-500">{{ $record['identifier'] }}</div></td>
                        <td data-label="Jenis" class="px-4 py-3 text-sm">{{ $record['type_label'] }}</td>
                        <td data-label="Kelas/Bidang" class="px-4 py-3 text-sm">{{ $record['unit'] }}</td>
                        <td data-label="Status" class="px-4 py-3"><span class="pkg-status-badge {{ $record['status_class'] }}">{{ $record['status_label'] }}</span></td>
                        <td data-label="Jam" class="px-4 py-3 text-sm">{{ $record['jam_masuk'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div>{{ $records->onEachSide(1)->links() }}</div>
    @endif
</div>
