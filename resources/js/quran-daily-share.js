function filterParams(root) {
    const params = new URLSearchParams();
    const form = root.closest('[data-quran-bulk-root]')?.parentElement?.querySelector('form[method="GET"]')
        || document.querySelector('form[method="GET"]');

    ['search', 'school_grade', 'pamong_id', 'kelompok'].forEach((name) => {
        const value = form?.elements?.namedItem(name)?.value?.trim();
        if (value) params.set(name, value);
    });
    params.set('reading_date', root.querySelector('[data-quran-share-date]').value);

    return params;
}

async function loadText(root) {
    const error = root.querySelector('[data-quran-share-error]');
    const preview = root.querySelector('[data-quran-share-preview]');
    error.classList.add('hidden');

    const response = await fetch(`${root.dataset.endpoint}?${filterParams(root)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !payload?.data?.text) {
        throw new Error(payload.message || 'Ringkasan bacaan belum dapat dibuat.');
    }

    preview.textContent = payload.data.text;
    preview.classList.remove('hidden');
    return payload.data.text;
}

async function copyText(text) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
}

document.querySelectorAll('[data-quran-daily-share]').forEach((root) => {
    if (root.dataset.bound === '1') return;
    root.dataset.bound = '1';
    const error = root.querySelector('[data-quran-share-error]');
    const copyButton = root.querySelector('[data-quran-share-copy]');
    const whatsappButton = root.querySelector('[data-quran-share-whatsapp]');

    const run = async (mode) => {
        const button = mode === 'copy' ? copyButton : whatsappButton;
        const original = button.textContent;
        const target = mode === 'whatsapp' ? window.open('about:blank', '_blank') : null;
        button.disabled = true;
        button.textContent = 'Menyiapkan...';
        try {
            const text = await loadText(root);
            if (mode === 'copy') {
                await copyText(text);
                window.showNotification?.('Teks ringkasan bacaan berhasil disalin', 'success');
            } else {
                const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
                if (target) target.location.href = url;
                else window.location.href = url;
            }
        } catch (exception) {
            target?.close();
            error.textContent = exception.message || 'Ringkasan bacaan belum dapat dibuat.';
            error.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = original;
        }
    };

    copyButton.addEventListener('click', () => run('copy'));
    whatsappButton.addEventListener('click', () => run('whatsapp'));
});
