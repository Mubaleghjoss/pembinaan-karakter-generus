@extends('layouts.app')

@section('title', 'Tambah Siswa Baru')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tambah Siswa</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Masukkan data siswa baru ke dalam sistem.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <form action="{{ route('siswa.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NIS -->
                <div>
                    <label for="nis" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIS (Nomor Induk Siswa)</label>
                    <input type="text" name="nis" id="nis" value="{{ old('nis') }}" required
                        class="w-full pkg-field"
                        placeholder="Contoh: 2023001">
                    @error('nis')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required
                        class="w-full pkg-field"
                        placeholder="Nama Lengkap Siswa">
                    @error('nama')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required
                        class="w-full pkg-field">
                    @error('tanggal_lahir')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="school_grade" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelas Sekolah</label>
                    <select name="school_grade" id="school_grade" required class="w-full pkg-field">
                        <option value="">Pilih Kelas Sekolah</option>
                        @foreach($targetGradeOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('school_grade') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('school_grade')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="kelompok" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelompok</label>
                    <select name="kelompok" id="kelompok" class="w-full pkg-field">
                        <option value="">Pilih Kelompok</option>
                        @foreach($kelompokOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('kelompok') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kelompok')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <!-- Foto Siswa -->
            <div>
                <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foto Siswa</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                            <label for="foto" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Upload foto</span>
                                <input id="foto" name="foto" type="file" class="sr-only" accept="image/*">
                            </label>
                            <p class="pl-1">atau drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                    </div>
                </div>
                @error('foto')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('siswa.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-500/30">
                    Simpan Siswa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

