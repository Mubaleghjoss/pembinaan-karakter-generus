@extends('layouts.app')

@section('title', 'Edit Jadwal Pengingat')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Edit Jadwal Pengingat</h1>
            <p class="pkg-page-subheading">Perbarui jadwal pengingat yang sudah ada.</p>
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

    <form action="{{ route('schedule-reminder.update', $scheduleReminder) }}" method="POST" class="pkg-panel p-6">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Jadwal *</label>
            <input type="text" name="title" id="title" value="{{ old('title', $scheduleReminder->title) }}" required class="w-full px-4 py-2 pkg-field">
        </div>

        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
            <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 pkg-field">{{ old('description', $scheduleReminder->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai *</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $scheduleReminder->start_date->format('Y-m-d')) }}" required class="w-full px-4 py-2 pkg-field">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $scheduleReminder->end_date?->format('Y-m-d')) }}" class="w-full px-4 py-2 pkg-field">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jam Mulai</label>
                <input type="time" name="start_time" id="start_time" value="{{ old('start_time', $scheduleReminder->start_time ? \Carbon\Carbon::parse($scheduleReminder->start_time)->format('H:i') : '') }}" class="w-full px-4 py-2 pkg-field">
            </div>
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jam Selesai</label>
                <input type="time" name="end_time" id="end_time" value="{{ old('end_time', $scheduleReminder->end_time ? \Carbon\Carbon::parse($scheduleReminder->end_time)->format('H:i') : '') }}" class="w-full px-4 py-2 pkg-field">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Audiens *</label>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="target_audience" value="all" {{ old('target_audience', $scheduleReminder->target_audience) === 'all' ? 'checked' : '' }} class="form-radio text-blue-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Semua</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="target_audience" value="siswa" {{ old('target_audience', $scheduleReminder->target_audience) === 'siswa' ? 'checked' : '' }} class="form-radio text-blue-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Siswa</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="target_audience" value="pamong" {{ old('target_audience', $scheduleReminder->target_audience) === 'pamong' ? 'checked' : '' }} class="form-radio text-blue-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Pamong</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lokasi</label>
            <input type="text" name="location" id="location" value="{{ old('location', $scheduleReminder->location) }}" class="w-full px-4 py-2 pkg-field">
        </div>

        <div class="mb-4">
            <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Warna</label>
            <input type="color" name="color" id="color" value="{{ old('color', $scheduleReminder->color) }}" class="w-16 h-10 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
        </div>

        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg" x-data="{ isRecurring: {{ old('is_recurring', $scheduleReminder->is_recurring) ? 'true' : 'false' }} }">
            <label class="inline-flex items-center mb-3">
                <input type="checkbox" name="is_recurring" value="1" x-model="isRecurring" class="pkg-check form-checkbox text-blue-600 rounded">
                <span class="ml-2 text-gray-700 dark:text-gray-300 font-medium">Jadwal Berulang</span>
            </label>

            <div x-show="isRecurring" x-transition class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pola Pengulangan</label>
                    <select name="recurrence_pattern" class="w-full pkg-field">
                        <option value="daily" {{ old('recurrence_pattern', $scheduleReminder->recurrence_pattern) === 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="weekly" {{ old('recurrence_pattern', $scheduleReminder->recurrence_pattern) === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        <option value="monthly" {{ old('recurrence_pattern', $scheduleReminder->recurrence_pattern) === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hari Aktif (untuk mingguan)</label>
                    <div class="flex flex-wrap gap-2">
                        @php $currentDays = old('recurrence_days', $scheduleReminder->recurrence_days ?? []); @endphp
                        @foreach(['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'] as $day => $label)
                        <label class="inline-flex items-center px-3 py-1 bg-white dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500">
                            <input type="checkbox" name="recurrence_days[]" value="{{ $day }}" {{ in_array($day, $currentDays) ? 'checked' : '' }} class="pkg-check form-checkbox text-blue-600 rounded">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <label class="inline-flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $scheduleReminder->is_active) ? 'checked' : '' }} class="pkg-check form-checkbox text-blue-600 rounded">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Aktifkan jadwal ini</span>
            </label>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('schedule-reminder.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
