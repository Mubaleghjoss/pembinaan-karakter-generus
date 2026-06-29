<div class="space-y-6">
    <form action="{{ route('settings.update.face-attendance') }}" method="POST" class="pkg-panel p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Scan Wajah dan Radius Lokasi</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Atur titik pusat presensi, radius lokasi yang diterima, dan minimal kemiripan wajah untuk presensi publik.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <input type="checkbox" name="enabled_siswa" value="1" class="mt-1 pkg-check" {{ ($faceAttendanceSettings['enabled_siswa'] ?? false) ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">Aktif untuk siswa</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">Siswa bisa memakai scan wajah saat jadwal presensi siswa aktif.</span>
                </span>
            </label>

            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <input type="checkbox" name="enabled_pamong" value="1" class="mt-1 pkg-check" {{ ($faceAttendanceSettings['enabled_pamong'] ?? false) ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">Aktif untuk pamong</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">Pamong, pengurus PKG, dan admin bisa memakai scan wajah saat jadwal pamong aktif.</span>
                </span>
            </label>
        </div>

        <div class="pkg-card-soft p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="face_center_lat" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Latitude titik presensi</label>
                    <input id="face_center_lat" name="center_lat" type="number" step="0.000000000000001" class="pkg-field w-full" value="{{ old('center_lat', $faceAttendanceSettings['center_lat'] ?? '') }}" required>
                    @error('center_lat')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="face_center_lng" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Longitude titik presensi</label>
                    <input id="face_center_lng" name="center_lng" type="number" step="0.000000000000001" class="pkg-field w-full" value="{{ old('center_lng', $faceAttendanceSettings['center_lng'] ?? '') }}" required>
                    @error('center_lng')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                Titik default saat ini: -6.219501040781815, 106.64336089878178.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="face_radius_value" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Radius diterima</label>
                <div class="grid grid-cols-[minmax(0,1fr)_150px] gap-3">
                    <input id="face_radius_value" name="radius_value" type="number" step="0.01" min="1" class="pkg-field w-full" value="{{ old('radius_value', $faceAttendanceSettings['radius_value'] ?? 100) }}" required>
                    <select name="radius_unit" class="pkg-field w-full" required>
                        <option value="meter" {{ old('radius_unit', $faceAttendanceSettings['radius_unit'] ?? 'meter') === 'meter' ? 'selected' : '' }}>Meter</option>
                        <option value="kilometer" {{ old('radius_unit', $faceAttendanceSettings['radius_unit'] ?? 'meter') === 'kilometer' ? 'selected' : '' }}>Kilometer</option>
                    </select>
                </div>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Radius efektif sekarang sekitar {{ number_format($faceAttendanceSettings['radius_meters'] ?? 0, 0, ',', '.') }} meter.
                </p>
                @error('radius_value')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
                @error('radius_unit')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="face_accuracy" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Maksimal akurasi GPS</label>
                <input id="face_accuracy" name="max_accuracy_meters" type="number" min="5" max="5000" class="pkg-field w-full" value="{{ old('max_accuracy_meters', $faceAttendanceSettings['max_accuracy_meters'] ?? 150) }}" required>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Jika akurasi perangkat lebih buruk dari angka ini, scan wajah ditolak.
                </p>
                @error('max_accuracy_meters')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="face_match_threshold" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Minimal kemiripan wajah (%)</label>
            <input id="face_match_threshold" name="match_threshold" type="number" min="20" max="100" step="0.1" class="pkg-field w-full max-w-xs" value="{{ old('match_threshold', $faceAttendanceSettings['match_threshold'] ?? 35.00) }}" required>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                Semakin kecil semakin mudah lolos. Rekomendasi awal 35%. Jika sering salah akun, naikkan; jika wajah benar masih gagal, turunkan sedikit.
            </p>
            @error('match_threshold')
                <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                Simpan Pengaturan Scan Wajah
            </button>
        </div>
    </form>
</div>
