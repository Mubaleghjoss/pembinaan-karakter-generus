@extends('layouts.app')

@section('title', 'Buat Jadwal Pengingat')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Buat Jadwal Pengingat</h1>
            <p class="pkg-page-subheading">Buat jadwal baru untuk ditampilkan di kalender.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('schedule-reminder.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('schedule-reminder.store') }}" method="POST" class="pkg-panel p-6">
        @csrf

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Jadwal *</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-4 py-2 pkg-field" placeholder="Contoh: Rapat Bulanan">
        </div>

        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
            <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 pkg-field" placeholder="Deskripsi jadwal (opsional)">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai *</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required class="w-full px-4 py-2 pkg-field">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" class="w-full px-4 py-2 pkg-field">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jam Mulai</label>
                <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" class="w-full px-4 py-2 pkg-field">
            </div>
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jam Selesai</label>
                <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" class="w-full px-4 py-2 pkg-field">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Audiens *</label>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="target_audience" value="all" {{ old('target_audience', 'all') === 'all' ? 'checked' : '' }} class="form-radio text-blue-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Semua</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="target_audience" value="siswa" {{ old('target_audience') === 'siswa' ? 'checked' : '' }} class="form-radio text-blue-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Siswa</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="target_audience" value="pamong" {{ old('target_audience') === 'pamong' ? 'checked' : '' }} class="form-radio text-blue-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Pamong</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lokasi</label>
            <input type="text" name="location" id="location" value="{{ old('location') }}" class="w-full px-4 py-2 pkg-field" placeholder="Contoh: Aula Utama">
        </div>

        <div class="mb-4">
            <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Warna</label>
            <input type="color" name="color" id="color" value="{{ old('color', '#3B82F6') }}" class="w-16 h-10 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
        </div>

        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg" x-data="{ isRecurring: {{ old('is_recurring') ? 'true' : 'false' }} }">
            <label class="inline-flex items-center mb-3">
                <input type="checkbox" name="is_recurring" value="1" x-model="isRecurring" class="pkg-check form-checkbox text-blue-600 rounded">
                <span class="ml-2 text-gray-700 dark:text-gray-300 font-medium">Jadwal Berulang</span>
            </label>

            <div x-show="isRecurring" x-transition class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pola Pengulangan</label>
                    <select name="recurrence_pattern" class="w-full pkg-field">
                        <option value="daily" {{ old('recurrence_pattern') === 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="weekly" {{ old('recurrence_pattern') === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        <option value="monthly" {{ old('recurrence_pattern') === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hari Aktif (untuk mingguan)</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'] as $day => $label)
                        <label class="inline-flex items-center px-3 py-1 bg-white dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500">
                            <input type="checkbox" name="recurrence_days[]" value="{{ $day }}" {{ in_array($day, old('recurrence_days', [])) ? 'checked' : '' }} class="pkg-check form-checkbox text-blue-600 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700" x-data="{ createAttendance: {{ old('create_attendance_schedule') ? 'true' : 'false' }} }">
            <label class="inline-flex items-center">
                <input type="checkbox" name="create_attendance_schedule" value="1" x-model="createAttendance" class="pkg-check form-checkbox text-blue-600 rounded">
                <span class="ml-2 text-gray-700 dark:text-gray-300 font-medium">Buat juga jadwal presensi</span>
            </label>

            <div x-show="createAttendance" x-transition class="mt-4 space-y-4">
                <div>
                    <label for="attendance_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Jadwal Presensi *</label>
                    <input type="text" name="attendance_name" id="attendance_name" value="{{ old('attendance_name') }}" x-bind:required="createAttendance" class="w-full px-4 py-2 pkg-field" placeholder="Contoh: Presensi Rapat Bulanan">
                </div>

                <div>
                    <label for="attendance_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi Presensi</label>
                    <textarea name="attendance_description" id="attendance_description" rows="2" class="w-full px-4 py-2 pkg-field" placeholder="Keterangan presensi (opsional)">{{ old('attendance_description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Presensi *</label>
                    <div class="flex flex-wrap gap-4">
                        @foreach(\App\Models\AttendanceSchedule::targetOptions() as $value => $label)
                            <label class="inline-flex items-center">
                                <input type="radio" name="attendance_target_audience" value="{{ $value }}" {{ old('attendance_target_audience', old('target_audience', 'all')) === $value ? 'checked' : '' }} class="form-radio text-blue-600">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="attendance_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai Presensi *</label>
                        <input type="date" name="attendance_start_date" id="attendance_start_date" value="{{ old('attendance_start_date', old('start_date')) }}" x-bind:required="createAttendance" class="w-full px-4 py-2 pkg-field">
                    </div>
                    <div>
                        <label for="attendance_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai Presensi</label>
                        <input type="date" name="attendance_end_date" id="attendance_end_date" value="{{ old('attendance_end_date', old('end_date', old('start_date'))) }}" class="w-full px-4 py-2 pkg-field">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="attendance_open_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presensi Dibuka *</label>
                        <input type="time" name="attendance_open_time" id="attendance_open_time" value="{{ old('attendance_open_time', old('start_time', '06:00')) }}" x-bind:required="createAttendance" class="w-full px-4 py-2 pkg-field">
                    </div>
                    <div>
                        <label for="attendance_late_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Batas Tepat Waktu *</label>
                        <input type="time" name="attendance_late_threshold" id="attendance_late_threshold" value="{{ old('attendance_late_threshold', old('start_time', '07:00')) }}" x-bind:required="createAttendance" class="w-full px-4 py-2 pkg-field">
                    </div>
                    <div>
                        <label for="attendance_close_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presensi Ditutup *</label>
                        <input type="time" name="attendance_close_time" id="attendance_close_time" value="{{ old('attendance_close_time', old('end_time', '23:59')) }}" x-bind:required="createAttendance" class="w-full px-4 py-2 pkg-field">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hari Presensi *</label>
                    @php
                        $defaultAttendanceDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        $selectedAttendanceDays = old('attendance_days', old('recurrence_days', $defaultAttendanceDays));
                    @endphp
                    <div class="flex flex-wrap gap-2">
                        @foreach(['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'] as $day => $label)
                        <label class="inline-flex items-center px-3 py-1 bg-white dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500">
                            <input type="checkbox" name="attendance_days[]" value="{{ $day }}" {{ in_array($day, $selectedAttendanceDays) ? 'checked' : '' }} class="pkg-check form-checkbox text-blue-600 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <label class="inline-flex items-center">
                    <input type="checkbox" name="attendance_is_active" value="1" {{ old('attendance_is_active', true) ? 'checked' : '' }} class="pkg-check form-checkbox text-blue-600 rounded">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Aktifkan jadwal presensi ini</span>
                </label>
            </div>
        </div>

        <div class="mb-6">
            <label class="inline-flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="pkg-check form-checkbox text-blue-600 rounded">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Aktifkan jadwal ini</span>
            </label>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('schedule-reminder.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan Jadwal</button>
        </div>
    </form>
</div>
@endsection
