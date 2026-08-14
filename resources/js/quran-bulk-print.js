document.querySelectorAll('[data-quran-bulk-root]').forEach((root) => {
    const storageKey = root.dataset.storageKey || 'pkg-quran-bulk-selection';
    let selected;
    try {
        selected = new Set(JSON.parse(sessionStorage.getItem(storageKey) || '[]').map(Number).filter(Boolean).slice(0, 50));
    } catch {
        selected = new Set();
    }

    const checkboxes = [...root.querySelectorAll('[data-quran-bulk-student]')];
    const count = root.querySelector('[data-quran-bulk-count]');
    const pageCount = root.querySelector('[data-quran-bulk-pages]');
    const hidden = root.querySelector('[data-quran-bulk-hidden]');
    const selectedMode = root.querySelector('[data-quran-selection-mode="selected"]');
    const filteredMode = root.querySelector('[data-quran-selection-mode="filtered"]');
    const form = root.querySelector('[data-quran-bulk-form]');
    const submit = root.querySelector('[data-quran-bulk-submit]');
    const filteredCount = Math.min(50, Number(root.dataset.filteredCount || 0));

    const documentFactor = () => form?.querySelector('[name="document_type"]:checked')?.value === 'duplex' ? 2 : 1;
    const activeStudentCount = () => selectedMode?.checked ? selected.size : filteredCount;

    const render = () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = selected.has(Number(checkbox.value)); });
        count.textContent = String(selected.size);
        pageCount.textContent = String(activeStudentCount() * documentFactor());
        hidden.innerHTML = [...selected].map((id) => `<input type="hidden" name="selected_ids[]" value="${id}">`).join('');
        root.querySelector('[data-quran-bulk-clear]').disabled = selected.size === 0;
        if (selectedMode) selectedMode.disabled = selected.size === 0;
        if (!selected.size && selectedMode?.checked) filteredMode.checked = true;
        sessionStorage.setItem(storageKey, JSON.stringify([...selected]));
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', () => {
        const id = Number(checkbox.value);
        if (checkbox.checked && selected.size >= 50 && !selected.has(id)) {
            checkbox.checked = false;
            window.alert('Maksimal 50 Generus dalam satu PDF.');
            return;
        }
        if (checkbox.checked) selected.add(id); else selected.delete(id);
        if (selected.size && selectedMode) selectedMode.checked = true;
        render();
    }));

    root.querySelector('[data-quran-bulk-page]').addEventListener('click', () => {
        checkboxes.forEach((checkbox) => {
            if (selected.size < 50) selected.add(Number(checkbox.value));
        });
        if (selected.size && selectedMode) selectedMode.checked = true;
        render();
    });
    root.querySelector('[data-quran-bulk-clear]').addEventListener('click', () => {
        selected.clear();
        filteredMode.checked = true;
        render();
    });
    form?.querySelectorAll('[name="document_type"], [name="selection_mode"]').forEach((input) => input.addEventListener('change', render));
    form?.addEventListener('submit', () => {
        submit.disabled = true;
        submit.textContent = 'Menyiapkan PDF...';
        window.setTimeout(() => {
            submit.disabled = false;
            submit.textContent = 'Unduh PDF Gabungan';
        }, 15000);
    });
    window.addEventListener('pageshow', () => {
        if (!submit) return;
        submit.disabled = false;
        submit.textContent = 'Unduh PDF Gabungan';
    });
    render();
});
