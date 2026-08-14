document.querySelectorAll('[data-quran-bulk-root]').forEach((root) => {
    const storageKey = root.dataset.storageKey || 'pkg-quran-bulk-selection';
    let selected;
    try {
        selected = new Set(JSON.parse(sessionStorage.getItem(storageKey) || '[]').map(Number));
    } catch {
        selected = new Set();
    }

    const checkboxes = [...root.querySelectorAll('[data-quran-bulk-student]')];
    const count = root.querySelector('[data-quran-bulk-count]');
    const hidden = root.querySelector('[data-quran-bulk-hidden]');
    const selectedMode = root.querySelector('[data-quran-selection-mode="selected"]');

    const render = () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = selected.has(Number(checkbox.value)); });
        count.textContent = String(selected.size);
        hidden.innerHTML = [...selected].map((id) => `<input type="hidden" name="selected_ids[]" value="${id}">`).join('');
        root.querySelector('[data-quran-bulk-clear]').disabled = selected.size === 0;
        if (selectedMode) selectedMode.disabled = selected.size === 0;
        if (!selected.size && selectedMode?.checked) root.querySelector('[data-quran-selection-mode="filtered"]').checked = true;
        sessionStorage.setItem(storageKey, JSON.stringify([...selected]));
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', () => {
        const id = Number(checkbox.value);
        if (checkbox.checked) selected.add(id); else selected.delete(id);
        render();
    }));
    root.querySelector('[data-quran-bulk-page]').addEventListener('click', () => {
        checkboxes.forEach((checkbox) => selected.add(Number(checkbox.value)));
        render();
    });
    root.querySelector('[data-quran-bulk-clear]').addEventListener('click', () => { selected.clear(); render(); });
    render();
});
