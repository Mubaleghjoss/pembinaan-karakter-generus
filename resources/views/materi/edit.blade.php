@extends('layouts.app')

@section('title', 'Edit Materi')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Edit Materi</h1>
            <p class="pkg-page-subheading">Perbarui informasi, PDF, dan video materi.</p>
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

    <form action="{{ route('materi.update', $materi) }}" method="POST" enctype="multipart/form-data" class="pkg-panel">
        @csrf
        @method('PUT')
        <input type="hidden" name="rpp_action" value="draft" data-rpp-action-field>
        <div class="p-6 space-y-6">
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Judul Materi <span class="text-red-500">*</span>
                </label>
                <input type="text" name="judul" id="judul" value="{{ old('judul', $materi->judul) }}" class="w-full px-3 py-2 pkg-field" required>
            </div>

            <div>
                <label for="materi_folder_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Folder Materi
                </label>
                <select name="materi_folder_id" id="materi_folder_id" class="w-full px-3 py-2 pkg-field">
                    <option value="">Tanpa Folder</option>
                    @foreach($materiFolders as $folder)
                        <option value="{{ $folder->id }}" @selected((int) old('materi_folder_id', $materi->materi_folder_id) === $folder->id)>{{ $folder->display_name ?? $folder->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Deskripsi <span class="text-red-500">*</span>
                </label>
                <textarea name="deskripsi" id="deskripsi" rows="4" class="w-full px-3 py-2 pkg-field" required>{{ old('deskripsi', $materi->deskripsi) }}</textarea>
            </div>

            <div>
                <label for="bulan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Bulan atau Periode <span class="text-red-500">*</span>
                </label>
                <input type="month" name="bulan" id="bulan" value="{{ old('bulan', $materi->bulan?->format('Y-m')) }}" class="w-full px-3 py-2 pkg-field" required>
            </div>

            <div>
                <label for="calendar_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tanggal tampil di kalender
                </label>
                <input type="date" name="calendar_date" id="calendar_date" value="{{ old('calendar_date', $materi->calendar_date?->format('Y-m-d')) }}" class="w-full px-3 py-2 pkg-field">
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Jika diisi, materi aktif akan tampil di kalender pada tanggal ini.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">File PDF</label>

                @if($materi->hasPdfFiles())
                <div class="mb-4 space-y-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">File PDF saat ini:</p>
                    @foreach($materi->pdf_files as $index => $pdf)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-3">
                            <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.9,12.35 10.92,12.31Z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $materi->pdfFileName($index) }}</p>
                                @if(isset($pdf['size']))
                                <p class="text-xs text-gray-500">{{ number_format($pdf['size'] / 1024, 1) }} KB</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ Storage::url($pdf['path']) }}" target="_blank" class="p-2 text-blue-600 hover:text-blue-800" title="Lihat PDF">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <label class="flex items-center gap-1 text-sm text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_pdfs[]" value="{{ $index }}" class="pkg-check rounded text-red-600">
                                <span>Hapus</span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

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
                    Tambah File PDF Baru
                </button>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">File baru akan ditambahkan ke daftar yang sudah ada.</p>
            </div>

            @include('materi.partials.video-links-input', [
                'videoLinks' => old('video_links', $materi->video_link_urls ?: ['']),
            ])

            @include('materi.partials.rpp-form', ['materi' => $materi])
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
