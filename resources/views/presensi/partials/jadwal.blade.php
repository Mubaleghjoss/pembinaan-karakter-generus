{{-- Jadwal Presensi Tab Content --}}
<div class="space-y-3 sm:space-y-4">
    <div class="pkg-card-soft flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isOpen ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Status Presensi</h3>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $isOpen ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                        {{ $isOpen ? 'Buka' : 'Tutup' }}
                    </span>
                </div>
                <p id="realtime-clock-jadwal" class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">--:--:--</p>
            </div>
        </div>

        <div class="text-sm text-gray-600 dark:text-gray-300 sm:text-right">
            @if($schedule)
                <p class="font-semibold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($schedule->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->close_time)->format('H:i') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Terlambat setelah {{ \Carbon\Carbon::parse($schedule->late_threshold)->format('H:i') }}</p>
            @else
                <p>Jadwal belum tersedia.</p>
            @endif
        </div>
    </div>

    <x-collapsible-section
        title="Pengaturan Jadwal"
        description="Atur jam operasional, hari aktif, dan batas keterlambatan."
        compact
    >
        @if($schedule)
            <div class="mb-4 flex justify-end">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $schedule->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ $schedule->is_active ? 'Jadwal aktif' : 'Jadwal nonaktif' }}
                </span>
            </div>
        @endif

        <form action="{{ route('attendance-schedule.update', $schedule->id ?? 1) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="pkg-filter-grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Buka</label>
                    <input type="time" name="open_time"
                           value="{{ $schedule ? \Carbon\Carbon::parse($schedule->open_time)->format('H:i') : '06:00' }}"
                           class="w-full pkg-field">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Batas Terlambat</label>
                    <input type="time" name="late_threshold"
                           value="{{ $schedule ? \Carbon\Carbon::parse($schedule->late_threshold)->format('H:i') : '07:00' }}"
                           class="w-full pkg-field">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Tutup</label>
                    <input type="time" name="close_time"
                           value="{{ $schedule ? \Carbon\Carbon::parse($schedule->close_time)->format('H:i') : '17:00' }}"
                           class="w-full pkg-field">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 dark:border-gray-700 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Hari Aktif</label>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @php
                            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            $activeDays = $schedule ? json_decode($schedule->active_days ?? '[]', true) : [1,2,3,4,5];
                        @endphp
                        @foreach($days as $index => $day)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="active_days[]" value="{{ $index + 1 }}"
                                       {{ in_array($index + 1, $activeDays) ? 'checked' : '' }}
                                       class="pkg-check">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Jadwal</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="is_active" value="1" {{ ($schedule && $schedule->is_active) ? 'checked' : '' }} class="pkg-check">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="is_active" value="0" {{ ($schedule && !$schedule->is_active) ? 'checked' : '' }} class="pkg-check">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nonaktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-4 py-2">Simpan Jadwal</button>
            </div>
        </form>
    </x-collapsible-section>

    <x-collapsible-section
        title="Panduan Jadwal"
        description="Ketentuan scan, terlambat, dan input manual."
        compact
    >
        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
            <li>Presensi hanya diproses pada jam operasional dan hari aktif.</li>
            <li>Scan QR setelah batas terlambat otomatis tercatat sebagai terlambat.</li>
            <li>Admin tetap dapat melakukan input manual ketika diperlukan.</li>
        </ul>
    </x-collapsible-section>
</div>

@push('scripts')
<script>
function updateJadwalClock() {
    const clockEl = document.getElementById('realtime-clock-jadwal');
    if (!clockEl) return;

    clockEl.textContent = new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });
}
setInterval(updateJadwalClock, 1000);
updateJadwalClock();
</script>
@endpush
