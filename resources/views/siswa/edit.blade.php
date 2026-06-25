@extends('layouts.app')

@section('title', 'Edit Data Siswa')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Data Siswa</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Perbarui informasi siswa.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <form action="{{ route('siswa.update', $siswa) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NIS -->
                <div>
                    <label for="nis" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIS</label>
                    <input type="text" name="nis" id="nis" value="{{ old('nis', $siswa->nis) }}" required
                        class="w-full pkg-field">
                    @error('nis')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama', $siswa->nama) }}" required
                        class="w-full pkg-field">
                    @error('nama')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir', $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : '') }}" required
                        class="w-full pkg-field">
                    @error('tanggal_lahir')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kelas -->
                <div>
                    <label for="kelas_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelas</label>
                    <select name="kelas_id" id="kelas_id" required
                        class="w-full pkg-field">
                        <option value="">Pilih Kelas</option>
                        @foreach($kelas as $kelas)
                            @php
                                $pamongNames = $kelas->pamong->map(fn($p) => $p->name ?? $p->username)->implode(', ');
                            @endphp
                            <option value="{{ $kelas->id }}" {{ old('kelas_id', $siswa->kelas_id) == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->nama }} - Pamong: {{ $pamongNames ?: 'Belum ada' }}
                            </option>
                        @endforeach
                    </select>
                    @error('kelas_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="kelompok" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelompok</label>
                    <select name="kelompok" id="kelompok" class="w-full pkg-field">
                        <option value="">Pilih Kelompok</option>
                        @foreach($kelompokOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('kelompok', $siswa->kelompok) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kelompok')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="target_grade_override" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level Kelas PKG</label>
                    <select name="target_grade_override" id="target_grade_override" class="w-full pkg-field">
                        <option value="">Otomatis dari tanggal lahir{{ $siswa->target_grade_label ? ' (' . $siswa->target_grade_label . ')' : '' }}</option>
                        @foreach($targetGradeOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('target_grade_override', $siswa->target_grade_override) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan agar sistem menghitung dari usia pada 1 Juli.</p>
                    @error('target_grade_override')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Foto Siswa -->
            <div>
                <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto Siswa</label>
                @if($siswa->foto_path)
                    <div class="mb-4">
                        <img src="{{ asset('storage/' . $siswa->foto_path) }}" alt="Foto Siswa" class="h-32 w-32 rounded-full object-cover border-4 border-white dark:border-gray-700 shadow-md">
                        <p class="text-xs text-gray-500 mt-1">Foto saat ini</p>
                    </div>
                @endif
                <input type="file" name="foto" id="foto" accept="image/*"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100">
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('siswa.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-500/30">
                    Update Siswa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

