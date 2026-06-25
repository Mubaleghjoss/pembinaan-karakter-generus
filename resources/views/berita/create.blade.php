@extends('layouts.app')

@section('title', 'Tambah Berita Baru')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tambah Berita Kegiatan</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Buat berita atau kegiatan baru untuk ditampilkan di halaman utama.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <form action="{{ route('berita.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- Judul -->
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Kegiatan</label>
                <input type="text" name="judul" id="judul" value="{{ old('judul') }}" required
                    class="w-full pkg-field"
                    placeholder="Contoh: Kegiatan Perkemahan Sabtu Minggu">
                @error('judul')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" id="status" class="w-full pkg-field">
                    <option value="draft">Draft (Simpan dulu)</option>
                    <option value="published">Published (Tampilkan sekarang)</option>
                </select>
            </div>

            <!-- Cover Image -->
            <div>
                <label for="cover" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gambar Sampul (Utama)</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                            <label for="cover" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Upload file</span>
                                <input id="cover" name="cover" type="file" class="sr-only" accept="image/*">
                            </label>
                            <p class="pl-1">atau drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                    </div>
                </div>
                @error('cover')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slider Images -->
            <div>
                <label for="slider_images" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gambar Slider (Dokumentasi Tambahan)</label>
                <input type="file" name="slider_images[]" id="slider_images" multiple accept="image/*"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-500">Bisa pilih lebih dari satu gambar. Otomatis dikompres.</p>
            </div>

            <!-- PDF File -->
            <div>
                <label for="pdf_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File Pendukung (PDF)</label>
                <input type="file" name="pdf_file" id="pdf_file" accept="application/pdf"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-red-50 file:text-red-700
                    hover:file:bg-red-100">
                <p class="mt-1 text-xs text-gray-500">Hanya file PDF. Maksimal 5MB.</p>
            </div>

            <!-- Isi Berita -->
            <div>
                <label for="isi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Isi Berita</label>
                <textarea name="isi" id="isi" rows="10" required
                    class="w-full pkg-field"
                    placeholder="Tuliskan detail kegiatan di sini..."></textarea>
                @error('isi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('berita.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-500/30">
                    Simpan Berita
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

