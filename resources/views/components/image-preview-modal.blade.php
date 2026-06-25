<div id="image-preview-modal" class="fixed inset-0 z-[120] hidden overflow-y-auto bg-black/85 px-4 py-4 backdrop-blur-sm" aria-modal="true" role="dialog" aria-labelledby="image-preview-title">
    <div class="mx-auto flex min-h-full w-full max-w-5xl items-start justify-center py-2 sm:py-6">
        <div class="relative w-full">
            <div class="sticky top-2 z-20 flex justify-end">
                <button type="button" onclick="window.closeImagePreview && window.closeImagePreview()" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-black/60 text-white transition hover:bg-black/80" aria-label="Tutup preview gambar">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-3 flex w-full flex-col items-center gap-4">
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <div class="rounded-2xl border border-white/10 bg-black/30 px-4 py-2 text-sm font-medium text-white" id="image-preview-title">
                        Preview bukti foto
                    </div>
                    <a id="image-preview-download" href="#" download class="inline-flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/20">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Unduh bukti
                    </a>
                </div>
                <div id="image-preview-meta" class="rounded-2xl border border-white/10 bg-black/30 px-4 py-2 text-center text-xs text-white/80">
                    <div id="image-preview-filename">Nama file: bukti-foto.jpg</div>
                    <div id="image-preview-extra" class="mt-1">Waktu unggah: -</div>
                </div>
                <img id="image-preview-image" src="" alt="Preview bukti foto" class="max-h-[68vh] w-auto max-w-full rounded-2xl border border-white/10 bg-white object-contain shadow-2xl sm:max-h-[74vh]">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
if (!window.openImagePreviewFromLink) {
    window.openImagePreviewFromLink = function (element) {
        if (!element) return true;

        const src = element.dataset.previewSrc || element.getAttribute('href');
        if (!src) return true;

        const modal = document.getElementById('image-preview-modal');
        const image = document.getElementById('image-preview-image');
        const title = document.getElementById('image-preview-title');
        const downloadLink = document.getElementById('image-preview-download');
        const fileName = document.getElementById('image-preview-filename');
        const extra = document.getElementById('image-preview-extra');

        if (!modal || !image || !title || !downloadLink || !fileName || !extra) return true;

        image.src = src;
        image.alt = element.dataset.previewAlt || 'Preview bukti foto';
        title.textContent = element.dataset.previewTitle || 'Preview bukti foto';
        const downloadName = element.dataset.previewFilename || 'bukti-foto.jpg';
        downloadLink.href = src;
        downloadLink.setAttribute('download', downloadName);
        fileName.textContent = 'Nama file: ' + downloadName;
        extra.textContent = element.dataset.previewMeta || 'Waktu unggah: -';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        return false;
    };

    window.closeImagePreview = function () {
        const modal = document.getElementById('image-preview-modal');
        const image = document.getElementById('image-preview-image');
        const downloadLink = document.getElementById('image-preview-download');
        const fileName = document.getElementById('image-preview-filename');
        const extra = document.getElementById('image-preview-extra');

        if (!modal || !image || !downloadLink || !fileName || !extra) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        image.removeAttribute('src');
        downloadLink.setAttribute('href', '#');
        downloadLink.setAttribute('download', 'bukti-foto.jpg');
        fileName.textContent = 'Nama file: bukti-foto.jpg';
        extra.textContent = 'Waktu unggah: -';
        document.body.classList.remove('overflow-hidden');
    };

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            window.closeImagePreview();
        }
    });

    document.addEventListener('click', function (event) {
        const modal = document.getElementById('image-preview-modal');
        if (!modal || modal.classList.contains('hidden')) return;

        if (event.target === modal) {
            window.closeImagePreview();
        }
    });
}
</script>
@endpush
