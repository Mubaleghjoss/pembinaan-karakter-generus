@extends('layouts.app')

@section('title', 'Tambah Materi')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Tambah Materi</h1>
            <p class="pkg-page-subheading">Tambahkan materi baru untuk siswa dan publik.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('materi.index') }}" class="btn-secondary">
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

    <form action="{{ route('materi.store') }}" method="POST" enctype="multipart/form-data" class="pkg-panel">
        @csrf
        <input type="hidden" name="rpp_action" value="draft" data-rpp-action-field>
        <div class="p-6 space-y-6">
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Judul Materi <span class="text-red-500">*</span>
                </label>
                <input type="text" name="judul" id="judul" value="{{ old('judul') }}" class="w-full px-3 py-2 pkg-field" placeholder="Masukkan judul materi" required>
            </div>

            <div>
                <label for="materi_folder_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Folder Materi
                </label>
                <select name="materi_folder_id" id="materi_folder_id" class="w-full px-3 py-2 pkg-field">
                    <option value="">Tanpa Folder</option>
                    @foreach($materiFolders as $folder)
                        <option value="{{ $folder->id }}" @selected((int) old('materi_folder_id') === $folder->id)>{{ $folder->display_name ?? $folder->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Deskripsi <span class="text-red-500">*</span>
                </label>
                <textarea name="deskripsi" id="deskripsi" rows="4" class="w-full px-3 py-2 pkg-field" placeholder="Deskripsi materi..." required>{{ old('deskripsi') }}</textarea>
            </div>

            <div>
                <label for="bulan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Bulan atau Periode <span class="text-red-500">*</span>
                </label>
                <input type="month" name="bulan" id="bulan" value="{{ old('bulan', date('Y-m')) }}" class="w-full px-3 py-2 pkg-field" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    File PDF (opsional, bisa lebih dari satu)
                </label>
                <div id="pdf-upload-container" class="space-y-3">
                    <div class="pdf-input-row flex items-center gap-2">
                        <input type="file" name="pdf_files[]" accept=".pdf" class="flex-1 px-3 py-2 pkg-field">
                        <button type="button" onclick="removePdfRow(this)" class="p-2 text-red-500 hover:text-red-700 hidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="button" onclick="addPdfRow()" class="mt-3 inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Tambah File PDF
                </button>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Maksimal 10MB per file, format PDF.</p>
            </div>

            <div>
                <label for="video_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Link Video YouTube (opsional)
                </label>
                <input type="url" name="video_url" id="video_url" value="{{ old('video_url') }}" class="w-full px-3 py-2 pkg-field" placeholder="https://www.youtube.com/watch?v=...">
            </div>

            @include('materi.partials.rpp-form')
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('materi.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-secondary" data-rpp-action-button="draft">Simpan Materi</button>
            <button type="submit" name="publish_rpp" value="1" class="btn-primary" data-rpp-action-button="publish">Simpan & Publikasikan RPP</button>
        </div>
    </form>
</div>

<script>
function addPdfRow() {
    const container = document.getElementById('pdf-upload-container');
    const newRow = document.createElement('div');
    newRow.className = 'pdf-input-row flex items-center gap-2';
    newRow.innerHTML = `
        <input type="file" name="pdf_files[]" accept=".pdf" class="flex-1 px-3 py-2 pkg-field">
        <button type="button" onclick="removePdfRow(this)" class="p-2 text-red-500 hover:text-red-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    `;
    container.appendChild(newRow);
    updateRemoveButtons();
}

function removePdfRow(button) {
    button.closest('.pdf-input-row').remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.pdf-input-row');
    rows.forEach((row) => {
        const removeBtn = row.querySelector('button');
        if (removeBtn) {
            removeBtn.classList.toggle('hidden', rows.length === 1);
        }
    });
}
</script>
@endsection
