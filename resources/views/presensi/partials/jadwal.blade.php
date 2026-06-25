{{-- Jadwal Presensi Tab Content --}}
<div class="space-y-6">
    <!-- Current Schedule Status -->
    <div class="pkg-panel overflow-hidden p-0">
        <div class="bg-gradient-to-r {{ $isOpen ? 'from-green-500 to-emerald-600' : 'from-gray-500 to-gray-600' }} rounded-3xl p-6 text-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white/80">Status Presensi Saat Ini</p>
                    <p class="text-2xl font-bold">{{ $isOpen ? 'BUKA' : 'TUTUP' }}</p>
                    <p id="realtime-clock-jadwal" class="text-lg font-mono">--:--:--</p>
                </div>
            </div>
            
            @if($schedule)
            <div class="text-right">
                <p class="text-sm text-white/80">Jam Operasional</p>
                <p class="text-xl font-bold">{{ \Carbon\Carbon::parse($schedule->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->close_time)->format('H:i') }}</p>
                <p class="text-sm text-white/80">Batas Terlambat: {{ \Carbon\Carbon::parse($schedule->late_threshold)->format('H:i') }}</p>
            </div>
            @endif
        </div>
        </div>
    </div>

    <!-- Schedule Management -->
    <div class="pkg-panel p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pengaturan Jadwal Presensi</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Atur jam buka, tutup, dan batas keterlambatan</p>
            </div>
            @if($schedule)
            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{ $schedule->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
            @endif
        </div>

        <form action="{{ route('attendance-schedule.update', $schedule->id ?? 1) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="pkg-filter-grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jam Buka</label>
                    <input type="time" name="open_time" 
                           value="{{ $schedule ? \Carbon\Carbon::parse($schedule->open_time)->format('H:i') : '06:00' }}"
                           class="w-full pkg-field">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Waktu presensi mulai dibuka</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Batas Terlambat</label>
                    <input type="time" name="late_threshold" 
                           value="{{ $schedule ? \Carbon\Carbon::parse($schedule->late_threshold)->format('H:i') : '07:00' }}"
                           class="w-full pkg-field">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Setelah jam ini dianggap terlambat</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jam Tutup</label>
                    <input type="time" name="close_time" 
                           value="{{ $schedule ? \Carbon\Carbon::parse($schedule->close_time)->format('H:i') : '17:00' }}"
                           class="w-full pkg-field">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Waktu presensi ditutup</p>
                </div>
            </div>

            <div class="pkg-filter-grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hari Aktif</label>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            $activeDays = $schedule ? json_decode($schedule->active_days ?? '[]', true) : [1,2,3,4,5];
                        @endphp
                        @foreach($days as $index => $day)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="active_days[]" value="{{ $index + 1 }}"
                                   {{ in_array($index + 1, $activeDays) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $day }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Jadwal</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="is_active" value="1" 
                                   {{ ($schedule && $schedule->is_active) ? 'checked' : '' }}
                                   class="border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="is_active" value="0"
                                   {{ ($schedule && !$schedule->is_active) ? 'checked' : '' }}
                                   class="border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nonaktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="pkg-btn-primary px-4 py-2">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    <!-- Schedule Info -->
    <div class="pkg-card-soft rounded-2xl border border-blue-200 p-4 dark:border-blue-800">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Informasi Jadwal</h4>
                <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                    <li>Presensi hanya dapat dilakukan pada jam operasional yang ditentukan</li>
                    <li>Siswa yang scan QR setelah batas terlambat akan otomatis dicatat sebagai "Terlambat"</li>
                    <li>Presensi di luar hari aktif tidak akan diproses</li>
                    <li>Admin dapat melakukan input manual kapan saja</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Realtime clock for jadwal tab
function updateJadwalClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false 
    });
    const clockEl = document.getElementById('realtime-clock-jadwal');
    if (clockEl) {
        clockEl.textContent = timeStr;
    }
}
setInterval(updateJadwalClock, 1000);
updateJadwalClock();
</script>
@endpush

