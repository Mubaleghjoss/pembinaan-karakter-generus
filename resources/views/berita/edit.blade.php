@extends('layouts.app')

@section('title', 'Edit Berita')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Berita</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Perbarui informasi berita atau kegiatan.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <form action="{{ route('berita.update', $berita) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Judul -->
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Kegiatan</label>
                <input type="text" name="judul" id="judul" value="{{ old('judul', $berita->judul) }}" required
                    class="w-full pkg-field">
                @error('judul')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" id="status" class="w-full pkg-field">
                    <option value="draft" {{ $berita->status == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ $berita->status == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ $berita->status == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <!-- Cover Image -->
            <div>
                <label for="cover" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gambar Sampul</label>
                @if($berita->cover_path)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $berita->cover_path) }}" alt="Current Cover" class="h-32 w-auto rounded-lg object-cover">
                        <p class="text-xs text-gray-500 mt-1">Gambar saat ini</p>
                    </div>
                @endif
                <input type="file" name="cover" id="cover" accept="image/*"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100">
            </div>

            <!-- Slider Images -->
            <div>
                <label for="slider_images" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gambar Slider (Update akan mengganti semua gambar slider lama)</label>
                @if($berita->images)
                    <div class="flex gap-2 mb-2 overflow-x-auto pb-2">
                        @foreach($berita->images as $img)
                            <img src="{{ asset('storage/' . $img) }}" class="h-20 w-20 rounded-lg object-cover flex-shrink-0">
                        @endforeach
                    </div>
                @endif
                <input type="file" name="slider_images[]" id="slider_images" multiple accept="image/*"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100">
            </div>

            <!-- PDF File -->
            <div>
                <label for="pdf_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File PDF</label>
                @if($berita->pdf_path)
                    <div class="flex items-center gap-2 mb-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span class="text-sm text-gray-600 dark:text-gray-300">File PDF tersimpan</span>
                    </div>
                @endif
                <input type="file" name="pdf_file" id="pdf_file" accept="application/pdf"
                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-red-50 file:text-red-700
                    hover:file:bg-red-100">
            </div>

            @include('berita.partials.social-links')

            <!-- Isi Berita -->
            <div>
                <label for="isi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Isi Berita</label>
                <textarea name="isi" id="isi" rows="10" required
                    class="w-full pkg-field">{{ old('isi', $berita->isi) }}</textarea>
                @error('isi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('berita.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-500/30">
                    Update Berita
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

