@extends('layouts.app')

@section('title', isset($attendanceSchedule) ? 'Edit Jadwal Presensi' : 'Buat Jadwal Presensi')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('attendance-schedule.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium inline-flex items-center gap-2 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ isset($attendanceSchedule) ? 'Edit Jadwal Presensi' : 'Buat Jadwal Presensi' }}
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Atur waktu scan QR code dan target peserta presensi.</p>
    </div>

    <form action="{{ isset($attendanceSchedule) ? route('attendance-schedule.update', $attendanceSchedule) : route('attendance-schedule.store') }}" 
          method="POST" class="space-y-6">
        @csrf
        @if(isset($attendanceSchedule))
            @method('PUT')
        @endif

        <!-- Card Form -->
        <div class="pkg-panel-lg p-8">
            
            <!-- Nama Jadwal -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Nama Jadwal <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $attendanceSchedule->name ?? '') }}"
                       class="w-full px-4 py-3 pkg-field"
                       placeholder="Contoh: Jadwal Reguler Senin-Sabtu"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Deskripsi -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Deskripsi (Opsional)
                </label>
                <textarea name="description" 
                          id="description" 
                          rows="3"
                          class="w-full px-4 py-3 pkg-field"
                          placeholder="Keterangan tambahan tentang jadwal ini...">{{ old('description', $attendanceSchedule->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Target Presensi -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    Target Presensi <span class="text-red-500">*</span>
                </label>
                @php
                    $selectedTarget = old('target_audience', $attendanceSchedule->target_audience ?? \App\Models\AttendanceSchedule::TARGET_ALL);
                    $targetOptions = $targetOptions ?? \App\Models\AttendanceSchedule::targetOptions();
                @endphp
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach($targetOptions as $value => $label)
                        <label class="relative flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 transition-colors {{ $selectedTarget === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 hover:border-blue-500 dark:border-gray-600' }}">
                            <input type="radio"
                                   name="target_audience"
                                   value="{{ $value }}"
                                   {{ $selectedTarget === $value ? 'checked' : '' }}
                                   class="mt-0.5 h-5 w-5 pkg-check"
                                   required>
                            <span>
                                <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ $label }}</span>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    @if($value === \App\Models\AttendanceSchedule::TARGET_ALL)
                                        Ringkasan menampilkan seluruh siswa dan pamong.
                                    @elseif($value === \App\Models\AttendanceSchedule::TARGET_PAMONG)
                                        Scan QR siswa akan ditolak untuk jadwal ini.
                                    @else
                                        Scan QR pamong akan ditolak untuk jadwal ini.
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('target_audience')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Periode Tanggal -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="start_date" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Tanggal Mulai <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           name="start_date"
                           id="start_date"
                           value="{{ old('start_date', isset($attendanceSchedule) && $attendanceSchedule->start_date ? $attendanceSchedule->start_date->format('Y-m-d') : now()->toDateString()) }}"
                           class="w-full px-4 py-3 pkg-field"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tanggal pertama presensi boleh digunakan</p>
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Tanggal Selesai <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           name="end_date"
                           id="end_date"
                           value="{{ old('end_date', isset($attendanceSchedule) && $attendanceSchedule->end_date ? $attendanceSchedule->end_date->format('Y-m-d') : (isset($attendanceSchedule) && $attendanceSchedule->start_date ? $attendanceSchedule->start_date->format('Y-m-d') : now()->toDateString())) }}"
                           class="w-full px-4 py-3 pkg-field"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Samakan dengan tanggal mulai untuk jadwal satu hari</p>
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Waktu -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Jam Buka -->
                <div>
                    <label for="open_time" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Waktu Presensi Dimulai <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           name="open_time" 
                           id="open_time" 
                           value="{{ old('open_time', isset($attendanceSchedule) ? \Carbon\Carbon::parse($attendanceSchedule->open_time)->format('H:i') : '06:00') }}"
                           class="w-full px-4 py-3 pkg-field"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Scan mulai dibuka</p>
                    @error('open_time')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batas Terlambat -->
                <div>
                    <label for="late_threshold" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Batas Tepat Waktu <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           name="late_threshold" 
                           id="late_threshold" 
                           value="{{ old('late_threshold', isset($attendanceSchedule) ? \Carbon\Carbon::parse($attendanceSchedule->late_threshold)->format('H:i') : '07:00') }}"
                           class="w-full px-4 py-3 pkg-field"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lewat waktu ini tercatat terlambat</p>
                    @error('late_threshold')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jam Tutup -->
                <div>
                    <label for="close_time" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        Waktu Presensi Ditutup <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           name="close_time" 
                           id="close_time" 
                           value="{{ old('close_time', isset($attendanceSchedule) ? \Carbon\Carbon::parse($attendanceSchedule->close_time)->format('H:i') : '23:59') }}"
                           class="w-full px-4 py-3 pkg-field"
                           required>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Scan ditutup</p>
                    @error('close_time')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Hari Aktif -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    Hari Aktif <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @php
                        $days = [
                            'monday' => 'Senin',
                            'tuesday' => 'Selasa',
                            'wednesday' => 'Rabu',
                            'thursday' => 'Kamis',
                            'friday' => 'Jumat',
                            'saturday' => 'Sabtu',
                            'sunday' => 'Minggu',
                        ];
                        $selectedDays = old('days', $attendanceSchedule->days ?? array_keys($days));
                    @endphp
                    @foreach($days as $value => $label)
                        <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 transition-colors {{ in_array($value, $selectedDays) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            <input type="checkbox" 
                                   name="days[]" 
                                   value="{{ $value }}" 
                                   {{ in_array($value, $selectedDays) ? 'checked' : '' }}
                                   class="w-5 h-5 pkg-check rounded">
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('days')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div class="mb-6">
                <label class="flex items-center gap-3 p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 transition-colors">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1" 
                           {{ old('is_active', $attendanceSchedule->is_active ?? !isset($attendanceSchedule)) ? 'checked' : '' }}
                           class="w-5 h-5 pkg-check rounded">
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white">Aktifkan jadwal ini</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Jadwal lain akan otomatis dinonaktifkan</div>
                    </div>
                </label>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-bold mb-1">Cara Kerja:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Scan QR hanya bisa dilakukan pada <strong>tanggal</strong>, <strong>waktu</strong>, dan <strong>hari</strong> yang dipilih</li>
                            <li>Scan sebelum batas tepat waktu akan tercatat sebagai <strong>Hadir</strong></li>
                            <li>Scan setelah batas tepat waktu tapi sebelum waktu tutup akan tercatat sebagai <strong>Terlambat</strong></li>
                            <li>Scan di luar jam operasional atau di luar target peserta akan ditolak</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('attendance-schedule.index') }}" 
                   class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">
                    Batal
                </a>
                <button type="submit" 
                        class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-lg hover:shadow-xl transition-all">
                    {{ isset($attendanceSchedule) ? 'Perbarui Jadwal' : 'Simpan Jadwal' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

