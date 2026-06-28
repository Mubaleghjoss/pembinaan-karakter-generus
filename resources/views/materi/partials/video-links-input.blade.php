@php
    $videoLinks = $videoLinks ?? [''];

    if (! is_array($videoLinks)) {
        $videoLinks = [$videoLinks];
    }

    $videoLinks = collect($videoLinks)
        ->map(fn ($link) => is_scalar($link) ? (string) $link : '')
        ->values()
        ->all();

    if (empty($videoLinks)) {
        $videoLinks = [''];
    }
@endphp

<div data-video-link-field>
    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
        Link Video (opsional)
    </label>
    <div class="space-y-3" data-video-link-container>
        @foreach($videoLinks as $link)
            <div class="video-link-row flex items-start gap-2">
                <input
                    type="url"
                    name="video_links[]"
                    value="{{ $link }}"
                    class="flex-1 px-3 py-2 pkg-field"
                    placeholder="https://www.youtube.com/watch?v=... atau https://drive.google.com/file/d/..."
                >
                <button type="button"
                        class="btn-secondary !h-10 !w-10 !p-0 text-red-600 dark:text-red-300"
                        data-video-link-remove
                        onclick="removeVideoLinkRow(this)"
                        aria-label="Hapus link video"
                        title="Hapus link video">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
    <button type="button"
            class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400"
            onclick="addVideoLinkRow(this)">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/>
        </svg>
        Tambah Link Video
    </button>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Bisa diisi YouTube, Google Drive, atau link video lain. YouTube dan Google Drive akan ditampilkan sebagai frame video.
    </p>
</div>

@once
    @push('scripts')
    <script>
    function addVideoLinkRow(button) {
        const field = button.closest('[data-video-link-field]');
        const container = field?.querySelector('[data-video-link-container]');

        if (!container) return;

        const row = document.createElement('div');
        row.className = 'video-link-row flex items-start gap-2';
        row.innerHTML = `
            <input type="url" name="video_links[]" class="flex-1 px-3 py-2 pkg-field" placeholder="https://www.youtube.com/watch?v=... atau https://drive.google.com/file/d/...">
            <button type="button"
                    class="btn-secondary !h-10 !w-10 !p-0 text-red-600 dark:text-red-300"
                    data-video-link-remove
                    onclick="removeVideoLinkRow(this)"
                    aria-label="Hapus link video"
                    title="Hapus link video">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        container.appendChild(row);
        updateVideoLinkRemoveButtons(container);
        row.querySelector('input')?.focus();
    }

    function removeVideoLinkRow(button) {
        const container = button.closest('[data-video-link-container]');
        button.closest('.video-link-row')?.remove();
        updateVideoLinkRemoveButtons(container);
    }

    function updateVideoLinkRemoveButtons(container) {
        if (!container) return;

        const rows = container.querySelectorAll('.video-link-row');
        rows.forEach((row) => {
            const removeButton = row.querySelector('[data-video-link-remove]');
            removeButton?.classList.toggle('hidden', rows.length === 1);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-video-link-container]').forEach(updateVideoLinkRemoveButtons);
    });
    </script>
    @endpush
@endonce
