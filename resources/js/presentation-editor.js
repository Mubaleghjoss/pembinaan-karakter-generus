import '../css/presentation.css';
import {
    applyPresentationCamera,
    bindPresentationTouchGestures,
    clampPresentationNumber,
    decodePresentationPayload,
    findPresentationElement,
    findPresentationFrame,
    panPresentationCamera,
    presentationPinchFactor,
    presentationId,
    renderPresentationStage,
    syncPresentationCamera,
    zoomPresentationAtPoint,
} from './presentation-canvas';

const root = document.getElementById('presentation-editor');

if (root) {
    const payload = decodePresentationPayload(root.dataset.presentationPayload);
    payload.canvas.elements = Array.isArray(payload.canvas.elements) ? payload.canvas.elements : [];
    payload.canvas.layoutSnapshot = payload.canvas.layoutSnapshot && typeof payload.canvas.layoutSnapshot === 'object'
        ? payload.canvas.layoutSnapshot
        : null;
    const state = {
        presentation: payload,
        selectedFrameId: payload.canvas.frames[0]?.id || null,
        selectedElementId: null,
        selectedCanvasElementId: null,
        mode: 'overview',
        dirty: false,
        saving: false,
        changeVersion: 0,
        saveTimer: null,
        activeSave: null,
        cameraScale: 1,
        focusScale: 1,
        overviewCandidate: false,
        manualCamera: false,
        drag: null,
        blockMode: false,
        blockSelection: new Set(),
        history: {
            current: null,
            undo: [],
            redo: [],
            lastGroup: null,
            lastRecordedAt: 0,
        },
    };
    const elements = {
        viewport: root.querySelector('[data-editor-viewport]'),
        stage: root.querySelector('[data-editor-stage]'),
        frameList: root.querySelector('[data-frame-list]'),
        inspector: root.querySelector('[data-editor-inspector]'),
        hint: root.querySelector('[data-editor-hint]'),
        saveStatus: root.querySelector('[data-save-status]'),
        title: root.querySelector('[data-editor-title]'),
        description: root.querySelector('[data-editor-description]'),
        background: root.querySelector('[data-editor-background]'),
        pathMode: root.querySelector('[data-editor-path-mode]'),
        imageInput: root.querySelector('[data-image-input]'),
        logoInput: root.querySelector('[data-logo-input]'),
        undo: root.querySelector('[data-editor-undo]'),
        redo: root.querySelector('[data-editor-redo]'),
        layoutSave: root.querySelector('[data-save-layout]'),
        blockToggle: root.querySelector('[data-editor-block]'),
        blockMenu: root.querySelector('[data-block-context-menu]'),
        blockMenuTitle: root.querySelector('[data-block-menu-title]'),
    };
    let touchGesture = null;

    elements.title.value = payload.title || '';
    elements.description.value = payload.description || '';
    elements.background.value = payload.backgroundColor || '#0f172a';
    elements.pathMode.value = payload.pathMode || 'overview_between';

    const scheduleAutomaticSave = () => {
        window.clearTimeout(state.saveTimer);
        state.saveTimer = window.setTimeout(() => save(), 1200);
    };

    const captureHistorySnapshot = () => JSON.stringify({
        canvas: state.presentation.canvas,
        title: state.presentation.title,
        description: state.presentation.description,
        backgroundColor: state.presentation.backgroundColor,
        pathMode: state.presentation.pathMode,
    });

    const updateHistoryButtons = () => {
        elements.undo.disabled = state.history.undo.length === 0;
        elements.redo.disabled = state.history.redo.length === 0;
    };

    const recordHistory = (group = null) => {
        const nextSnapshot = captureHistorySnapshot();
        if (state.history.current === null) {
            state.history.current = nextSnapshot;
            updateHistoryButtons();
            return;
        }
        if (nextSnapshot === state.history.current) return;

        const now = performance.now();
        const shouldCoalesce = group
            && state.history.lastGroup === group
            && now - state.history.lastRecordedAt < 700;
        if (!shouldCoalesce) {
            state.history.undo.push(state.history.current);
            if (state.history.undo.length > 80) state.history.undo.shift();
        }
        state.history.current = nextSnapshot;
        state.history.redo = [];
        state.history.lastGroup = group;
        state.history.lastRecordedAt = now;
        updateHistoryButtons();
    };

    const markDirty = (historyGroup = null) => {
        recordHistory(historyGroup);
        state.dirty = true;
        state.changeVersion += 1;
        if (elements.layoutSave) {
            elements.layoutSave.disabled = false;
            elements.layoutSave.textContent = 'Simpan Tata Letak Rapi';
        }
        elements.saveStatus.textContent = 'Belum disimpan · akan disimpan otomatis';
        elements.saveStatus.classList.add('text-amber-600', 'dark:text-amber-300');
        scheduleAutomaticSave();
    };

    const selectedFrame = () => findPresentationFrame(state.presentation, state.selectedFrameId);
    const selectedElement = () => findPresentationElement(selectedFrame(), state.selectedElementId);
    const selectedCanvasElement = () => state.presentation.canvas.elements
        .find((element) => element.id === state.selectedCanvasElementId) || null;

    const blockKeyFromTarget = (target) => {
        const canvasElementNode = target.closest?.('[data-canvas-element-id]');
        if (canvasElementNode?.dataset.canvasElementId) {
            return `canvas:${canvasElementNode.dataset.canvasElementId}`;
        }
        const frameNode = target.closest?.('[data-frame-id]');
        return frameNode?.dataset.frameId ? `frame:${frameNode.dataset.frameId}` : null;
    };

    const selectedBlockItems = () => {
        const items = [];
        state.presentation.canvas.frames.forEach((frame) => {
            const key = `frame:${frame.id}`;
            if (state.blockSelection.has(key)) items.push({ key, item: frame, type: 'frame' });
        });
        state.presentation.canvas.elements.forEach((element) => {
            const key = `canvas:${element.id}`;
            if (state.blockSelection.has(key)) items.push({ key, item: element, type: 'canvas' });
        });
        return items;
    };

    const updateBlockUi = () => {
        const count = state.blockSelection.size;
        elements.blockToggle?.classList.toggle('is-active', state.blockMode);
        if (elements.blockToggle) {
            elements.blockToggle.textContent = state.blockMode
                ? `Blok Aktif (${count})`
                : 'Blok Pilihan';
            elements.blockToggle.setAttribute('aria-pressed', state.blockMode ? 'true' : 'false');
        }
        updateCameraHint();
    };

    const applyBlockSelectionStyles = () => {
        elements.stage.querySelectorAll('.is-block-selected').forEach((node) => {
            node.classList.remove('is-block-selected');
        });
        selectedBlockItems().forEach(({ key }) => {
            const [type, id] = key.split(':');
            const selector = type === 'frame'
                ? `[data-frame-id="${CSS.escape(id)}"]`
                : `.pkg-presentation-element[data-canvas-element-id="${CSS.escape(id)}"]`;
            elements.stage.querySelector(selector)?.classList.add('is-block-selected');
        });
        updateBlockUi();
    };

    const stagePointFromEvent = (event) => {
        const bounds = elements.viewport.getBoundingClientRect();
        const scale = Number(elements.stage.dataset.cameraScale || state.cameraScale || 1);
        const cameraX = Number(elements.stage.dataset.cameraX || 0);
        const cameraY = Number(elements.stage.dataset.cameraY || 0);
        return {
            x: (event.clientX - bounds.left - cameraX) / scale,
            y: (event.clientY - bounds.top - cameraY) / scale,
        };
    };

    const hideBlockMenu = () => {
        elements.blockMenu?.classList.add('hidden');
    };

    const ensureCanvasContainsFrames = () => {
        const frames = state.presentation.canvas.frames;
        const requiredWidth = frames.reduce(
            (maximum, frame) => Math.max(maximum, Number(frame.x || 0) + Number(frame.width || 0) + 120),
            1200
        );
        const requiredHeight = frames.reduce(
            (maximum, frame) => Math.max(maximum, Number(frame.y || 0) + Number(frame.height || 0) + 120),
            800
        );
        const elementRequiredWidth = state.presentation.canvas.elements.reduce(
            (maximum, element) => Math.max(maximum, Number(element.x || 0) + Number(element.width || 0) + 120),
            requiredWidth
        );
        const elementRequiredHeight = state.presentation.canvas.elements.reduce(
            (maximum, element) => Math.max(maximum, Number(element.y || 0) + Number(element.height || 0) + 120),
            requiredHeight
        );
        state.presentation.canvas.width = clampPresentationNumber(elementRequiredWidth, 1200, 7000, 2400);
        state.presentation.canvas.height = clampPresentationNumber(elementRequiredHeight, 800, 12500, 1400);
    };

    const resizeFrame = (frame, width, height) => {
        const previousWidth = Math.max(1, Number(frame.width || 800));
        const previousHeight = Math.max(1, Number(frame.height || 450));
        const nextWidth = clampPresentationNumber(width, 320, 1600, previousWidth);
        const nextHeight = clampPresentationNumber(height, 180, 900, previousHeight);
        const scaleX = nextWidth / previousWidth;
        const scaleY = nextHeight / previousHeight;
        const fontScale = Math.min(scaleX, scaleY);

        (frame.elements || []).forEach((element) => {
            element.x = Number(element.x || 0) * scaleX;
            element.y = Number(element.y || 0) * scaleY;
            element.width = Math.max(40, Number(element.width || 100) * scaleX);
            element.height = Math.max(30, Number(element.height || 80) * scaleY);
            if (element.type === 'text') {
                element.fontSize = clampPresentationNumber(
                    Number(element.fontSize || 32) * fontScale,
                    10,
                    160,
                    32
                );
            }
        });

        frame.width = nextWidth;
        frame.height = nextHeight;
        ensureCanvasContainsFrames();
    };

    ensureCanvasContainsFrames();
    state.history.current = captureHistorySnapshot();
    updateHistoryButtons();

    const updateCameraHint = () => {
        const frame = state.mode === 'focus' ? selectedFrame() : null;
        const label = frame ? `Fokus: ${frame.title}` : 'Mode Overview';
        elements.hint.textContent = `${label} · ${Math.round(state.cameraScale * 100)}%`;
        if (state.blockMode) {
            elements.hint.textContent += ` · Blok ${state.blockSelection.size} objek`;
        }
    };

    const applyCamera = (animate = true) => {
        const frame = state.mode === 'focus' ? selectedFrame() : null;
        state.cameraScale = applyPresentationCamera(
            elements.viewport,
            elements.stage,
            state.presentation.canvas,
            frame,
            animate
        );
        if (frame) state.focusScale = state.cameraScale;
        state.manualCamera = false;
        updateCameraHint();
    };

    const render = (animate = true) => {
        renderPresentationStage({
            stage: elements.stage,
            presentation: state.presentation,
            selectedFrameId: state.selectedFrameId,
            selectedElementId: state.selectedElementId,
            selectedCanvasElementId: state.selectedCanvasElementId,
            overview: state.mode === 'overview',
            editable: true,
        });
        renderFrameList();
        renderInspector();
        applyBlockSelectionStyles();
        requestAnimationFrame(() => {
            if (state.manualCamera) {
                updateCameraHint();
                return;
            }
            applyCamera(animate);
        });
    };

    const renderFrameList = () => {
        elements.frameList.replaceChildren();
        state.presentation.canvas.frames.forEach((frame, index) => {
            const item = document.createElement('div');
            item.className = `pkg-presentation-frame-list-item${frame.id === state.selectedFrameId ? ' is-selected' : ''}`;

            const focusButton = document.createElement('button');
            focusButton.type = 'button';
            focusButton.className = 'min-w-0 flex-1 text-left';
            focusButton.dataset.frameFocus = frame.id;
            focusButton.innerHTML = `<span class="block text-xs font-bold text-emerald-600">${index + 1}</span>`;
            const title = document.createElement('span');
            title.className = 'block truncate text-sm font-semibold text-gray-900 dark:text-white';
            title.textContent = frame.title;
            focusButton.appendChild(title);
            item.appendChild(focusButton);

            const controls = document.createElement('div');
            controls.className = 'flex gap-1';
            controls.innerHTML = `
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="up" data-frame-id="${frame.id}" aria-label="Naik">↑</button>
                <button type="button" class="pkg-presentation-mini-button" data-frame-move="down" data-frame-id="${frame.id}" aria-label="Turun">↓</button>
            `;
            item.appendChild(controls);
            elements.frameList.appendChild(item);
        });
    };

    const commonPositionFields = (item, isFrame = false, isCanvasElement = false) => `
        <div class="grid grid-cols-2 gap-3">
            ${numberField('X', 'x', item.x, 0, isCanvasElement ? 6800 : 5000)}
            ${numberField('Y', 'y', item.y, 0, isCanvasElement || isFrame ? 12300 : 1100)}
            ${numberField(
                'Lebar',
                'width',
                item.width,
                isFrame ? 320 : 40,
                isCanvasElement
                    ? Math.max(40, Number(state.presentation.canvas.width || 7000) - Number(item.x || 0))
                    : 1600
            )}
            ${numberField('Tinggi', 'height', item.height, isFrame ? 180 : 30, 900)}
        </div>
    `;

    const renderInspector = () => {
        const frame = selectedFrame();
        const canvasElement = selectedCanvasElement();
        const element = canvasElement || selectedElement();
        const isCanvasElement = Boolean(canvasElement);

        if (!frame && !canvasElement) {
            elements.inspector.innerHTML = '<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';
            return;
        }

        if (!element && frame) {
            elements.inspector.innerHTML = `
                <div class="space-y-4" data-inspector-scope="frame">
                    <div>
                        <label class="form-label">Judul frame</label>
                        <input class="pkg-field w-full" maxlength="120" data-inspector-prop="title" value="${escapeAttribute(frame.title)}">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        ${numberField('Ukuran judul', 'titleFontSize', frame.titleFontSize || 14, 10, 72)}
                        ${colorField('Warna judul', 'titleColor', frame.titleColor || '#334155')}
                    </div>
                    <div>
                        <label class="form-label">Warna frame</label>
                        <input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="backgroundColor" value="${frame.backgroundColor || '#ffffff'}">
                    </div>
                    <div>
                        <label class="form-label">Bentuk frame</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${option('rounded', 'Sudut membulat', frame.shape || 'rounded')}
                            ${option('rectangle', 'Kotak', frame.shape)}
                            ${option('circle', 'Lingkaran / oval', frame.shape)}
                            ${option('hexagon', 'Segi enam', frame.shape)}
                            ${option('custom', 'Radius buatan sendiri', frame.shape)}
                        </select>
                    </div>
                    ${frame.shape === 'custom' ? numberField('Radius sudut', 'borderRadius', frame.borderRadius || 22, 0, 240) : ''}
                    <div>
                        <label class="form-label">Ukuran frame</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="pkg-frame-size-button" data-frame-size="560x315">Kecil</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="800x450">Sedang</button>
                            <button type="button" class="pkg-frame-size-button" data-frame-size="1120x630">Besar</button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Gunakan preset, isi ukuran manual, atau seret pegangan hijau di sudut kanan bawah frame.</p>
                    </div>
                    ${commonPositionFields(frame, true)}
                    <button type="button" class="btn-primary w-full justify-center" data-focus-selected-frame>Fokuskan Frame</button>
                    <button type="button" class="btn-danger w-full justify-center" data-delete-selected-frame ${state.presentation.canvas.frames.length <= 1 ? 'disabled' : ''}>Hapus Frame</button>
                </div>
            `;
            return;
        }

        let typeFields = '';
        if (element.type === 'text') {
            typeFields = `
                <div>
                    <label class="form-label">Isi teks</label>
                    <textarea class="pkg-field w-full" rows="5" maxlength="5000" data-inspector-prop="text">${escapeHtml(element.text || '')}</textarea>
                </div>
                ${numberField('Ukuran huruf', 'fontSize', element.fontSize || 32, 10, 160)}
                <div class="grid grid-cols-2 gap-3">
                    ${colorField('Warna teks', 'color', element.color || '#0f172a')}
                    ${colorField('Latar', 'backgroundColor', normalizeColorInput(element.backgroundColor, '#ffffff'))}
                </div>
                <div>
                    <label class="form-label">Perataan</label>
                    <select class="pkg-field w-full" data-inspector-prop="align">
                        ${option('left', 'Kiri', element.align)}
                        ${option('center', 'Tengah', element.align)}
                        ${option('right', 'Kanan', element.align)}
                    </select>
                </div>
                <label class="pkg-check"><input type="checkbox" data-inspector-prop="bold" ${element.bold ? 'checked' : ''}><span>Teks tebal</span></label>
            `;
        } else if (element.type === 'image' || element.type === 'logo') {
            typeFields = `
                <div>
                    <label class="form-label">Teks alternatif</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="alt" value="${escapeAttribute(element.alt || '')}">
                </div>
                <div>
                    <label class="form-label">Penyesuaian gambar</label>
                    <select class="pkg-field w-full" data-inspector-prop="fit">
                        ${option('cover', 'Penuhi area', element.fit)}
                        ${option('contain', 'Tampilkan utuh', element.fit)}
                    </select>
                </div>
                ${element.type === 'logo' ? `
                    <div>
                        <label class="form-label">Bentuk logo</label>
                        <select class="pkg-field w-full" data-inspector-prop="shape">
                            ${option('circle', 'Lingkaran', element.shape || 'circle')}
                            ${option('rounded', 'Sudut membulat', element.shape)}
                            ${option('square', 'Kotak', element.shape)}
                            ${option('hexagon', 'Segi enam', element.shape)}
                        </select>
                    </div>
                ` : ''}
            `;
        } else if (element.type === 'youtube') {
            typeFields = `
                <div>
                    <label class="form-label">Link YouTube</label>
                    <input type="url" class="pkg-field w-full" maxlength="500" data-inspector-prop="youtubeUrl" value="${escapeAttribute(element.youtubeUrl || '')}" placeholder="https://youtu.be/...">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Video dapat diputar dan dibuka layar penuh pada Pratinjau atau Tautan Publik.</p>
                </div>
                <div>
                    <label class="form-label">Judul video</label>
                    <input class="pkg-field w-full" maxlength="160" data-inspector-prop="title" value="${escapeAttribute(element.title || '')}">
                </div>
            `;
        } else if (element.type === 'link') {
            typeFields = `
                <div><label class="form-label">Label tautan</label><input class="pkg-field w-full" maxlength="160" data-inspector-prop="text" value="${escapeAttribute(element.text || '')}"></div>
                <div><label class="form-label">Alamat tautan</label><input type="url" class="pkg-field w-full" maxlength="1000" data-inspector-prop="url" value="${escapeAttribute(element.url || '')}" placeholder="https://..."></div>
                <div>
                    <label class="form-label">Tampilan tautan</label>
                    <select class="pkg-field w-full" data-inspector-prop="linkStyle">
                        ${option('button', 'Tombol', element.linkStyle)}
                        ${option('card', 'Kartu', element.linkStyle)}
                        ${option('text', 'Teks', element.linkStyle)}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${colorField('Warna teks', 'color', element.color || '#ffffff')}
                    ${colorField('Latar', 'backgroundColor', normalizeColorInput(element.backgroundColor, '#047857'))}
                </div>
            `;
        } else if (element.type === 'shape') {
            typeFields = `
                <div><label class="form-label">Teks di bentuk</label><textarea class="pkg-field w-full" rows="3" maxlength="1000" data-inspector-prop="text">${escapeHtml(element.text || '')}</textarea></div>
                <div>
                    <label class="form-label">Bentuk</label>
                    <select class="pkg-field w-full" data-inspector-prop="shapeType">
                        ${option('circle', 'Lingkaran / oval', element.shapeType)}
                        ${option('rounded', 'Sudut membulat', element.shapeType)}
                        ${option('rectangle', 'Kotak', element.shapeType)}
                        ${option('hexagon', 'Segi enam', element.shapeType)}
                        ${option('custom', 'Radius buatan sendiri', element.shapeType)}
                    </select>
                </div>
                ${element.shapeType === 'custom' ? numberField('Radius sudut', 'borderRadius', element.borderRadius || 24, 0, 240) : ''}
                ${numberField('Ukuran huruf', 'fontSize', element.fontSize || 28, 10, 160)}
                <div class="grid grid-cols-2 gap-3">
                    ${colorField('Warna teks', 'color', element.color || '#ffffff')}
                    ${colorField('Warna bentuk', 'backgroundColor', normalizeColorInput(element.backgroundColor, '#0f766e'))}
                </div>
            `;
        } else if (element.type === 'line') {
            typeFields = `
                <div class="grid grid-cols-2 gap-3">
                    ${colorField('Warna garis', 'color', element.color || '#0f172a')}
                    ${numberField('Ketebalan', 'strokeWidth', element.strokeWidth || 4, 1, 20)}
                </div>
                <div>
                    <label class="form-label">Pola garis</label>
                    <select class="pkg-field w-full" data-inspector-prop="lineStyle">
                        ${option('solid', 'Penuh', element.lineStyle)}
                        ${option('dashed', 'Putus-putus', element.lineStyle)}
                        ${option('dotted', 'Titik-titik', element.lineStyle)}
                    </select>
                </div>
                <div>
                    <label class="form-label">Ujung panah</label>
                    <select class="pkg-field w-full" data-inspector-prop="arrow">
                        ${option('none', 'Tanpa panah', element.arrow)}
                        ${option('end', 'Panah di akhir', element.arrow)}
                        ${option('start', 'Panah di awal', element.arrow)}
                        ${option('both', 'Panah dua arah', element.arrow)}
                    </select>
                </div>
                ${numberField('Rotasi garis', 'rotation', element.rotation || 0, -180, 180)}
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Atur panjang melalui penanda kiri/kanan. Gunakan rotasi untuk membuat garis miring atau tegak.</p>
            `;
        } else {
            typeFields = `
                <div>
                    <label class="form-label">Isi diagram (satu baris per kotak)</label>
                    <textarea class="pkg-field w-full" rows="6" data-inspector-prop="items">${escapeHtml((element.items || []).join('\n'))}</textarea>
                </div>
                <div>
                    <label class="form-label">Bentuk alur</label>
                    <select class="pkg-field w-full" data-inspector-prop="diagramType">
                        ${option('process', 'Proses mendatar', element.diagramType)}
                        ${option('cycle', 'Siklus', element.diagramType)}
                        ${option('hierarchy', 'Hierarki', element.diagramType)}
                        ${option('radial', 'Radial dengan pusat', element.diagramType)}
                    </select>
                </div>
                ${element.diagramType === 'radial' ? `
                    <div><label class="form-label">Teks pusat / logo</label><input class="pkg-field w-full" maxlength="120" data-inspector-prop="centerText" value="${escapeAttribute(element.centerText || '')}"></div>
                    <div>
                        <label class="form-label">Bentuk node</label>
                        <select class="pkg-field w-full" data-inspector-prop="nodeShape">
                            ${option('circle', 'Lingkaran', element.nodeShape || 'circle')}
                            ${option('rounded', 'Sudut membulat', element.nodeShape)}
                            ${option('square', 'Kotak', element.nodeShape)}
                            ${option('hexagon', 'Segi enam', element.nodeShape)}
                        </select>
                    </div>
                ` : ''}
                <div class="grid grid-cols-2 gap-3">
                    ${colorField('Warna diagram', 'color', element.color || '#0f172a')}
                    ${colorField('Latar', 'backgroundColor', normalizeColorInput(element.backgroundColor, '#ffffff'))}
                </div>
            `;
        }

        elements.inspector.innerHTML = `
            <div class="space-y-4" data-inspector-scope="${isCanvasElement ? 'canvas-element' : 'element'}">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${elementTypeLabel(element.type)}${isCanvasElement ? ' di Luar Frame' : ''}
                </div>
                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Seret bagian tengah elemen untuk memindahkan. Gunakan penanda hijau pada sisi dan sudut untuk mengubah ukuran langsung.${isCanvasElement ? ' Elemen ini tampil pada Overview dan berada di luar isi frame.' : ''}</p>
                ${typeFields}
                ${commonPositionFields(element, false, isCanvasElement)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `;
    };

    const restoreHistorySnapshot = (snapshot) => {
        const restored = JSON.parse(snapshot);
        state.presentation.canvas = restored.canvas;
        state.presentation.canvas.elements = Array.isArray(state.presentation.canvas.elements)
            ? state.presentation.canvas.elements
            : [];
        state.presentation.title = restored.title;
        state.presentation.description = restored.description;
        state.presentation.backgroundColor = restored.backgroundColor;
        state.presentation.pathMode = restored.pathMode;
        elements.title.value = restored.title || '';
        elements.description.value = restored.description || '';
        elements.background.value = restored.backgroundColor || '#0f172a';
        elements.pathMode.value = restored.pathMode || 'overview_between';

        if (!findPresentationFrame(state.presentation, state.selectedFrameId)) {
            state.selectedFrameId = state.presentation.canvas.frames[0]?.id || null;
        }
        if (!findPresentationElement(selectedFrame(), state.selectedElementId)) {
            state.selectedElementId = null;
        }
        if (!selectedCanvasElement()) {
            state.selectedCanvasElementId = null;
        }
        state.blockSelection.clear();
        hideBlockMenu();
        ensureCanvasContainsFrames();
        state.dirty = true;
        state.changeVersion += 1;
        state.history.lastGroup = null;
        state.history.lastRecordedAt = 0;
        state.manualCamera = false;
        elements.saveStatus.textContent = 'Perubahan dipulihkan · akan disimpan otomatis';
        elements.saveStatus.classList.add('text-amber-600', 'dark:text-amber-300');
        updateHistoryButtons();
        scheduleAutomaticSave();
        render(false);
    };

    const undoHistory = () => {
        if (!state.history.undo.length) return;
        state.history.redo.push(state.history.current);
        state.history.current = state.history.undo.pop();
        restoreHistorySnapshot(state.history.current);
    };

    const redoHistory = () => {
        if (!state.history.redo.length) return;
        state.history.undo.push(state.history.current);
        state.history.current = state.history.redo.pop();
        restoreHistorySnapshot(state.history.current);
    };

    const focusFrame = (frameId) => {
        state.selectedFrameId = frameId;
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.blockMode = false;
        state.blockSelection.clear();
        hideBlockMenu();
        state.mode = 'focus';
        state.manualCamera = false;
        render();
    };

    root.querySelector('[data-editor-overview]').addEventListener('click', () => {
        state.mode = 'overview';
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.manualCamera = false;
        render();
    });
    root.querySelector('[data-editor-fit]').addEventListener('click', () => {
        state.manualCamera = false;
        applyCamera();
    });
    elements.blockToggle?.addEventListener('click', () => {
        hideBlockMenu();
        state.blockMode = !state.blockMode;
        state.blockSelection.clear();
        state.mode = 'overview';
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.manualCamera = true;
        render(false);
    });

    root.querySelector('[data-add-frame]').addEventListener('click', () => {
        const index = state.presentation.canvas.frames.length;
        const frameX = 180 + ((index % 2) * 1100);
        const frameY = 180 + (Math.floor(index / 2) * 560);
        const frame = {
            id: presentationId('frame'),
            title: `Frame ${index + 1}`,
            x: frameX,
            y: frameY,
            width: 800,
            height: 450,
            backgroundColor: '#ffffff',
            titleColor: '#334155',
            titleFontSize: 14,
            shape: 'rounded',
            borderRadius: 22,
            elements: [],
        };
        state.presentation.canvas.frames.push(frame);
        ensureCanvasContainsFrames();
        state.selectedFrameId = frame.id;
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.mode = 'overview';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-arrange-frames]').addEventListener('click', () => {
        const frames = state.presentation.canvas.frames;
        const snapshot = state.presentation.canvas.layoutSnapshot;
        const hasSavedLayout = Boolean(snapshot?.frames?.length || snapshot?.elements?.length);
        if (hasSavedLayout) {
            const frameLayouts = new Map((snapshot.frames || []).map((layout) => [layout.id, layout]));
            const elementLayouts = new Map((snapshot.elements || []).map((layout) => [layout.id, layout]));
            frames.forEach((frame) => {
                const layout = frameLayouts.get(frame.id);
                if (!layout) return;
                if (Number(frame.width) !== Number(layout.width)
                    || Number(frame.height) !== Number(layout.height)) {
                    resizeFrame(frame, Number(layout.width), Number(layout.height));
                }
                frame.x = Number(layout.x);
                frame.y = Number(layout.y);
            });
            state.presentation.canvas.elements.forEach((element) => {
                const layout = elementLayouts.get(element.id);
                if (!layout) return;
                Object.assign(element, {
                    x: Number(layout.x),
                    y: Number(layout.y),
                    width: Number(layout.width),
                    height: Number(layout.height),
                    rotation: Number(layout.rotation || 0),
                });
            });
        } else {
            const maximumWidth = Math.max(...frames.map((frame) => Number(frame.width || 800)));
            const maximumHeight = Math.max(...frames.map((frame) => Number(frame.height || 450)));
            const columnGap = 160;
            const rowGap = 140;
            frames.forEach((frame, index) => {
                frame.x = 120 + ((index % 2) * (maximumWidth + columnGap));
                frame.y = 120 + (Math.floor(index / 2) * (maximumHeight + rowGap));
            });
        }
        state.mode = 'overview';
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.manualCamera = false;
        ensureCanvasContainsFrames();
        markDirty();
        elements.saveStatus.textContent = hasSavedLayout
            ? 'Tata letak rapi tersimpan sudah dipulihkan dan akan disimpan otomatis.'
            : 'Belum ada patokan tersimpan. Frame dirapikan otomatis dan akan disimpan.';
        render();
    });

    root.querySelector('[data-add-text]').addEventListener('click', () => {
        const frame = selectedFrame();
        if (!frame) return;
        const element = {
            id: presentationId('element'),
            type: 'text',
            x: 70,
            y: 80,
            width: Math.max(240, frame.width - 140),
            height: 130,
            rotation: 0,
            text: 'Tulis materi di sini',
            fontSize: 36,
            color: '#0f172a',
            backgroundColor: 'transparent',
            align: 'left',
            bold: false,
        };
        frame.elements.push(element);
        state.selectedElementId = element.id;
        state.selectedCanvasElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-add-diagram]').addEventListener('click', () => {
        const frame = selectedFrame();
        if (!frame) return;
        const element = {
            id: presentationId('element'),
            type: 'diagram',
            x: 70,
            y: 130,
            width: Math.max(360, frame.width - 140),
            height: 180,
            rotation: 0,
            color: '#047857',
            backgroundColor: 'transparent',
            diagramType: 'process',
            items: ['Pembuka', 'Pembahasan', 'Kesimpulan'],
        };
        frame.elements.push(element);
        state.selectedElementId = element.id;
        state.selectedCanvasElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-add-youtube]').addEventListener('click', () => {
        const frame = selectedFrame();
        if (!frame) return;
        const element = {
            id: presentationId('element'),
            type: 'youtube',
            x: 90,
            y: 90,
            width: Math.min(560, frame.width - 180),
            height: Math.min(315, frame.height - 150),
            rotation: 0,
            youtubeUrl: '',
            youtubeId: '',
            title: 'Video YouTube',
            color: '#ffffff',
            backgroundColor: '#0f172a',
        };
        frame.elements.push(element);
        state.selectedElementId = element.id;
        state.selectedCanvasElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-add-link]').addEventListener('click', () => {
        const frame = selectedFrame();
        if (!frame) return;
        const element = {
            id: presentationId('element'),
            type: 'link',
            x: 90,
            y: 300,
            width: 260,
            height: 70,
            rotation: 0,
            text: 'Buka tautan',
            url: 'https://',
            linkStyle: 'button',
            color: '#ffffff',
            backgroundColor: '#047857',
        };
        frame.elements.push(element);
        state.selectedElementId = element.id;
        state.selectedCanvasElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-add-shape]').addEventListener('click', () => {
        const frame = selectedFrame();
        if (!frame) return;
        const element = {
            id: presentationId('element'),
            type: 'shape',
            x: 110,
            y: 140,
            width: 220,
            height: 150,
            rotation: 0,
            text: 'Isi bentuk',
            shapeType: 'rounded',
            borderRadius: 24,
            fontSize: 28,
            color: '#ffffff',
            backgroundColor: '#0f766e',
        };
        frame.elements.push(element);
        state.selectedElementId = element.id;
        state.selectedCanvasElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-add-line]').addEventListener('click', () => {
        const frame = selectedFrame();
        if (!frame) return;
        const element = {
            id: presentationId('element'),
            type: 'line',
            x: 100,
            y: Math.max(70, Math.round(frame.height / 2) - 20),
            width: Math.max(220, frame.width - 200),
            height: 40,
            rotation: 0,
            color: '#0f766e',
            backgroundColor: 'transparent',
            strokeWidth: 4,
            lineStyle: 'solid',
            arrow: 'none',
        };
        frame.elements.push(element);
        state.selectedElementId = element.id;
        state.selectedCanvasElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    const canvasDecorationPosition = (width, height, offsetY = 0) => {
        const frame = selectedFrame();
        const canvas = state.presentation.canvas;
        const preferredX = frame
            ? Number(frame.x || 0) + ((Number(frame.width || 800) - width) / 2)
            : 160;
        const preferredY = frame
            ? Number(frame.y || 0) - height - 70 + offsetY
            : 120 + offsetY;

        return {
            x: clampPresentationNumber(preferredX, 30, Math.max(30, Number(canvas.width || 2400) - width - 30), 160),
            y: clampPresentationNumber(preferredY, 30, Math.max(30, Number(canvas.height || 1400) - height - 30), 120),
        };
    };

    root.querySelector('[data-add-canvas-text]').addEventListener('click', () => {
        const position = canvasDecorationPosition(520, 90);
        const element = {
            id: presentationId('canvas-element'),
            type: 'text',
            ...position,
            width: 520,
            height: 90,
            rotation: 0,
            text: 'Tulis judul atau keterangan Overview',
            fontSize: 36,
            color: '#ffffff',
            backgroundColor: 'transparent',
            align: 'center',
            bold: true,
        };
        state.presentation.canvas.elements.push(element);
        state.selectedCanvasElementId = element.id;
        state.selectedElementId = null;
        state.mode = 'overview';
        state.manualCamera = false;
        ensureCanvasContainsFrames();
        markDirty();
        render();
    });

    root.querySelector('[data-add-canvas-line]').addEventListener('click', () => {
        const position = canvasDecorationPosition(440, 40, 125);
        const element = {
            id: presentationId('canvas-element'),
            type: 'line',
            ...position,
            width: 440,
            height: 40,
            rotation: 0,
            color: '#34d399',
            backgroundColor: 'transparent',
            strokeWidth: 5,
            lineStyle: 'solid',
            arrow: 'none',
        };
        state.presentation.canvas.elements.push(element);
        state.selectedCanvasElementId = element.id;
        state.selectedElementId = null;
        state.mode = 'overview';
        state.manualCamera = false;
        ensureCanvasContainsFrames();
        markDirty();
        render();
    });

    root.querySelector('[data-add-image]').addEventListener('click', () => {
        if (selectedFrame()) elements.imageInput.click();
    });
    root.querySelector('[data-add-logo]').addEventListener('click', () => {
        if (selectedFrame()) elements.logoInput.click();
    });

    const uploadVisual = async (input, type) => {
        const file = input.files?.[0];
        const frame = selectedFrame();
        if (!file || !frame) return;

        const formData = new FormData();
        formData.append('image', file);
        elements.saveStatus.textContent = 'Mengunggah gambar...';

        try {
            const response = await fetch(root.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/json',
                },
                body: formData,
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Gambar gagal diunggah.');

            state.presentation.assets[String(data.asset.id)] = data.asset;
            const element = {
                id: presentationId('element'),
                type,
                assetId: data.asset.id,
                x: 90,
                y: 80,
                width: type === 'logo' ? Math.min(220, frame.width - 180) : Math.min(420, frame.width - 180),
                height: type === 'logo' ? Math.min(220, frame.height - 140) : Math.min(280, frame.height - 140),
                rotation: 0,
                alt: data.asset.name,
                fit: type === 'logo' ? 'contain' : 'cover',
                shape: type === 'logo' ? 'circle' : 'rounded',
                color: '#0f172a',
                backgroundColor: 'transparent',
            };
            frame.elements.push(element);
            state.selectedElementId = element.id;
            state.selectedCanvasElementId = null;
            state.mode = 'focus';
            state.manualCamera = false;
            markDirty();
            render();
        } catch (error) {
            elements.saveStatus.textContent = error.message;
            elements.saveStatus.classList.add('text-red-600');
        } finally {
            input.value = '';
        }
    };
    elements.imageInput.addEventListener('change', () => uploadVisual(elements.imageInput, 'image'));
    elements.logoInput.addEventListener('change', () => uploadVisual(elements.logoInput, 'logo'));

    elements.frameList.addEventListener('click', (event) => {
        const focusButton = event.target.closest('[data-frame-focus]');
        if (focusButton) {
            focusFrame(focusButton.dataset.frameFocus);
            return;
        }

        const moveButton = event.target.closest('[data-frame-move]');
        if (!moveButton) return;
        const frames = state.presentation.canvas.frames;
        const index = frames.findIndex((frame) => frame.id === moveButton.dataset.frameId);
        const targetIndex = moveButton.dataset.frameMove === 'up' ? index - 1 : index + 1;
        if (index < 0 || targetIndex < 0 || targetIndex >= frames.length) return;
        [frames[index], frames[targetIndex]] = [frames[targetIndex], frames[index]];
        markDirty();
        renderFrameList();
    });

    elements.inspector.addEventListener('input', (event) => {
        const input = event.target.closest('[data-inspector-prop]');
        if (!input) return;
        const scope = input.closest('[data-inspector-scope]')?.dataset.inspectorScope;
        const target = scope === 'frame'
            ? selectedFrame()
            : scope === 'canvas-element'
                ? selectedCanvasElement()
                : selectedElement();
        if (!target) return;
        if (scope === 'frame' && ['width', 'height'].includes(input.dataset.inspectorProp)) return;

        let value = input.type === 'checkbox' ? input.checked : input.value;
        if (['x', 'y', 'width', 'height', 'fontSize', 'borderRadius', 'strokeWidth', 'rotation'].includes(input.dataset.inspectorProp)) {
            value = Number(value);
        }
        if (input.dataset.inspectorProp === 'items') {
            value = String(value).split('\n').map((item) => item.trim()).filter(Boolean).slice(0, 8);
        }
        target[input.dataset.inspectorProp] = value;
        if (input.dataset.inspectorProp === 'youtubeUrl') {
            target.youtubeId = extractYouTubeId(value);
        }
        if (scope === 'frame' || scope === 'canvas-element') ensureCanvasContainsFrames();
        markDirty(`inspector:${scope}:${target.id}:${input.dataset.inspectorProp}`);
        renderPresentationStage({
            stage: elements.stage,
            presentation: state.presentation,
            selectedFrameId: state.selectedFrameId,
            selectedElementId: state.selectedElementId,
            selectedCanvasElementId: state.selectedCanvasElementId,
            overview: state.mode === 'overview',
            editable: true,
        });
        applyBlockSelectionStyles();
        if (state.manualCamera) updateCameraHint();
        else applyCamera(false);
        if (scope === 'frame' && input.dataset.inspectorProp === 'title') renderFrameList();
        if (['shape', 'shapeType', 'diagramType'].includes(input.dataset.inspectorProp)) renderInspector();
    });

    elements.inspector.addEventListener('change', (event) => {
        const input = event.target.closest('[data-inspector-prop]');
        const scope = input?.closest('[data-inspector-scope]')?.dataset.inspectorScope;
        if (!input || scope !== 'frame' || !['width', 'height'].includes(input.dataset.inspectorProp)) return;
        const frame = selectedFrame();
        if (!frame) return;

        resizeFrame(
            frame,
            input.dataset.inspectorProp === 'width' ? Number(input.value) : frame.width,
            input.dataset.inspectorProp === 'height' ? Number(input.value) : frame.height
        );
        markDirty();
        render(false);
    });

    elements.inspector.addEventListener('click', (event) => {
        const sizeButton = event.target.closest('[data-frame-size]');
        if (sizeButton) {
            const frame = selectedFrame();
            const [width, height] = sizeButton.dataset.frameSize.split('x').map(Number);
            resizeFrame(frame, width, height);
            markDirty();
            render(false);
            return;
        }
        if (event.target.closest('[data-focus-selected-frame]')) {
            state.mode = 'focus';
            state.manualCamera = false;
            render();
            return;
        }
        if (event.target.closest('[data-delete-selected-element]')) {
            const scope = event.target.closest('[data-inspector-scope]')?.dataset.inspectorScope;
            if (scope === 'canvas-element') {
                state.presentation.canvas.elements = state.presentation.canvas.elements
                    .filter((element) => element.id !== state.selectedCanvasElementId);
                state.selectedCanvasElementId = null;
            } else {
                const frame = selectedFrame();
                frame.elements = frame.elements.filter((element) => element.id !== state.selectedElementId);
                state.selectedElementId = null;
            }
            ensureCanvasContainsFrames();
            markDirty();
            render(false);
            return;
        }
        if (event.target.closest('[data-delete-selected-frame]') && state.presentation.canvas.frames.length > 1) {
            const frames = state.presentation.canvas.frames;
            const index = frames.findIndex((frame) => frame.id === state.selectedFrameId);
            frames.splice(index, 1);
            state.selectedFrameId = frames[Math.max(0, index - 1)]?.id || frames[0]?.id;
            state.selectedElementId = null;
            state.selectedCanvasElementId = null;
            state.mode = 'overview';
            state.manualCamera = false;
            ensureCanvasContainsFrames();
            markDirty();
            render();
        }
    });

    const metadataChanged = (event) => {
        state.presentation.title = elements.title.value;
        state.presentation.description = elements.description.value;
        state.presentation.backgroundColor = elements.background.value;
        state.presentation.pathMode = elements.pathMode.value;
        markDirty(`metadata:${event.target.dataset.editorTitle !== undefined
            ? 'title'
            : event.target.dataset.editorDescription !== undefined
                ? 'description'
                : event.target.dataset.editorBackground !== undefined
                    ? 'background'
                    : 'path'}`);
        if (document.activeElement === elements.background) render(false);
    };
    [elements.title, elements.description, elements.background, elements.pathMode].forEach((input) => {
        input.addEventListener('input', metadataChanged);
        input.addEventListener('change', metadataChanged);
    });

    elements.viewport.addEventListener('wheel', (event) => {
        event.preventDefault();
        if (event.ctrlKey || event.metaKey) {
            state.cameraScale = zoomPresentationAtPoint(
                elements.viewport,
                elements.stage,
                presentationPinchFactor(event.deltaY),
                event.clientX,
                event.clientY,
                { minimumScale: 0.03, maximumScale: 4 }
            );
        } else {
            state.cameraScale = panPresentationCamera(elements.stage, -event.deltaX, -event.deltaY);
        }
        state.manualCamera = true;
        if ((event.ctrlKey || event.metaKey)
            && state.mode === 'focus'
            && state.cameraScale <= state.focusScale * 0.72) {
            state.mode = 'overview';
            state.selectedElementId = null;
            render(false);
            return;
        }
        updateCameraHint();
    }, { passive: false });

    const cancelActiveDragForGesture = () => {
        const drag = state.drag;
        if (!drag) return;

        if (drag.kind === 'marquee') {
            drag.marqueeNode?.remove();
        } else if (drag.kind === 'block') {
            drag.snapshots.forEach((snapshot) => {
                snapshot.item.x = snapshot.x;
                snapshot.item.y = snapshot.y;
            });
        } else if (drag.kind === 'frame-resize') {
            drag.target.width = drag.originWidth;
            drag.target.height = drag.originHeight;
            drag.frameElements.forEach((snapshot) => {
                snapshot.item.x = snapshot.x;
                snapshot.item.y = snapshot.y;
                snapshot.item.width = snapshot.width;
                snapshot.item.height = snapshot.height;
                if (snapshot.item.type === 'text') snapshot.item.fontSize = snapshot.fontSize;
            });
        } else if (['element-resize', 'canvas-element-resize'].includes(drag.kind) && drag.target) {
            drag.target.x = drag.originX;
            drag.target.y = drag.originY;
            drag.target.width = drag.originWidth;
            drag.target.height = drag.originHeight;
        } else if (drag.target) {
            drag.target.x = drag.originX;
            drag.target.y = drag.originY;
        }

        state.drag = null;
        state.manualCamera = true;
        render(false);
    };

    touchGesture = bindPresentationTouchGestures(elements.viewport, elements.stage, {
        minimumScale: 0.03,
        maximumScale: 4,
        onStart: () => {
            state.overviewCandidate = false;
            cancelActiveDragForGesture();
        },
        onUpdate: (scale) => {
            state.cameraScale = scale;
            state.manualCamera = true;
            if (state.mode === 'focus' && scale <= state.focusScale * 0.72) {
                state.overviewCandidate = true;
            }
            updateCameraHint();
        },
        onEnd: () => {
            if (!state.overviewCandidate || state.mode !== 'focus') return;
            state.mode = 'overview';
            state.selectedElementId = null;
            state.manualCamera = true;
            state.overviewCandidate = false;
            render(false);
        },
        onTap: (tap) => {
            if (state.mode !== 'overview' || state.drag?.moved) return false;
            if (state.blockMode) {
                const key = blockKeyFromTarget(tap.target);
                if (!key) return false;
                state.blockSelection = new Set([key]);
                applyBlockSelectionStyles();
                return true;
            }
            const canvasElementNode = tap.target.closest?.('[data-canvas-element-id]');
            if (canvasElementNode) {
                state.selectedCanvasElementId = canvasElementNode.dataset.canvasElementId;
                state.selectedElementId = null;
                state.manualCamera = true;
                render(false);
                return true;
            }
            const frameNode = tap.target.closest?.('[data-frame-id]');
            if (!frameNode) return false;
            focusFrame(frameNode.dataset.frameId);
            return true;
        },
    });

    const blockNodeForItem = ({ key }) => {
        const [type, id] = key.split(':');
        return type === 'frame'
            ? elements.stage.querySelector(`[data-frame-id="${CSS.escape(id)}"]`)
            : elements.stage.querySelector(
                `.pkg-presentation-element[data-canvas-element-id="${CSS.escape(id)}"]`
            );
    };

    const startBlockDrag = (event, clickedKey) => {
        if (!state.blockSelection.has(clickedKey)) {
            if (!event.shiftKey && !event.ctrlKey && !event.metaKey) state.blockSelection.clear();
            state.blockSelection.add(clickedKey);
        }
        const snapshots = selectedBlockItems().map((entry) => ({
            ...entry,
            x: Number(entry.item.x || 0),
            y: Number(entry.item.y || 0),
            width: Number(entry.item.width || 100),
            height: Number(entry.item.height || 80),
            node: blockNodeForItem(entry),
        }));
        if (!snapshots.length) return false;

        applyBlockSelectionStyles();
        state.drag = {
            kind: 'block',
            target: snapshots[0].item,
            snapshots,
            startX: event.clientX,
            startY: event.clientY,
            moved: false,
        };
        elements.viewport.setPointerCapture(event.pointerId);
        return true;
    };

    const startBlockMarquee = (event) => {
        const startPoint = stagePointFromEvent(event);
        const marqueeNode = document.createElement('div');
        marqueeNode.className = 'pkg-presentation-selection-marquee';
        marqueeNode.style.left = `${startPoint.x}px`;
        marqueeNode.style.top = `${startPoint.y}px`;
        marqueeNode.style.width = '0px';
        marqueeNode.style.height = '0px';
        elements.stage.appendChild(marqueeNode);

        state.drag = {
            kind: 'marquee',
            target: state.presentation.canvas,
            startPoint,
            baseSelection: event.shiftKey || event.ctrlKey || event.metaKey
                ? new Set(state.blockSelection)
                : new Set(),
            marqueeNode,
            moved: false,
        };
        if (!event.shiftKey && !event.ctrlKey && !event.metaKey) state.blockSelection.clear();
        applyBlockSelectionStyles();
        elements.viewport.setPointerCapture(event.pointerId);
    };

    elements.viewport.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        if (touchGesture?.isActive()) return;
        hideBlockMenu();
        state.cameraScale = syncPresentationCamera(elements.stage);
        const blockKey = state.mode === 'overview' ? blockKeyFromTarget(event.target) : null;
        const isResizeHandle = event.target.closest(
            '[data-frame-resize], [data-canvas-element-resize], [data-element-resize]'
        );
        if (state.blockMode && state.mode === 'overview' && blockKey && !isResizeHandle) {
            if (startBlockDrag(event, blockKey)) return;
        }
        if (state.blockMode && state.mode === 'overview' && !blockKey) {
            startBlockMarquee(event);
            return;
        }
        if (isResizeHandle && state.blockSelection.size) {
            state.blockSelection.clear();
            applyBlockSelectionStyles();
        }

        const canvasResizeHandle = event.target.closest('[data-canvas-element-resize]');
        const canvasElementNode = event.target.closest('.pkg-presentation-element[data-canvas-element-id]');
        const canvasElementId = canvasResizeHandle?.dataset.canvasElementId
            || canvasElementNode?.dataset.canvasElementId;
        const canvasElement = state.mode === 'overview'
            ? state.presentation.canvas.elements.find((element) => element.id === canvasElementId)
            : null;
        if (canvasElement) {
            const elementNode = elements.stage.querySelector(
                `.pkg-presentation-element[data-canvas-element-id="${CSS.escape(canvasElement.id)}"]`
            );
            const controlsNode = canvasResizeHandle?.closest('.pkg-presentation-element-controls')
                || elements.stage.querySelector(
                    `.pkg-presentation-element-controls[data-canvas-element-id="${CSS.escape(canvasElement.id)}"]`
                );
            state.selectedCanvasElementId = canvasElement.id;
            state.selectedElementId = null;
            state.drag = {
                kind: canvasResizeHandle ? 'canvas-element-resize' : 'canvas-element',
                target: canvasElement,
                node: elementNode,
                controlsNode,
                frame: state.presentation.canvas,
                resizeDirection: canvasResizeHandle?.dataset.canvasElementResize || null,
                startX: event.clientX,
                startY: event.clientY,
                originX: Number(canvasElement.x || 0),
                originY: Number(canvasElement.y || 0),
                originWidth: Number(canvasElement.width || 100),
                originHeight: Number(canvasElement.height || 80),
                frameElements: [],
                moved: false,
            };
            elements.viewport.setPointerCapture(event.pointerId);
            renderInspector();
            return;
        }

        const frameNode = event.target.closest('[data-frame-id]');
        const frameResizeHandle = event.target.closest('[data-frame-resize]');
        const frameDragHandle = event.target.closest('[data-frame-drag]');
        const elementResizeHandle = event.target.closest('[data-element-resize]');
        if (!frameNode) return;

        const frame = findPresentationFrame(state.presentation, frameNode.dataset.frameId);
        const clickedElementNode = event.target.closest('.pkg-presentation-element[data-element-id]');
        const elementId = elementResizeHandle?.dataset.elementId || clickedElementNode?.dataset.elementId;
        const element = findPresentationElement(frame, elementId);
        const elementNode = element
            ? frameNode.querySelector(`.pkg-presentation-element[data-element-id="${CSS.escape(element.id)}"]`)
            : null;
        const controlsNode = elementResizeHandle?.closest('.pkg-presentation-element-controls')
            || (element
                ? frameNode.querySelector(`.pkg-presentation-element-controls[data-element-id="${CSS.escape(element.id)}"]`)
                : null);
        state.selectedFrameId = frame.id;
        state.selectedElementId = state.mode === 'focus' && element ? element.id : null;
        state.selectedCanvasElementId = null;
        const frameElements = (frame.elements || []).map((item) => ({
            item,
            x: Number(item.x || 0),
            y: Number(item.y || 0),
            width: Number(item.width || 100),
            height: Number(item.height || 80),
            fontSize: Number(item.fontSize || 32),
        }));
        state.drag = {
            kind: state.mode === 'overview'
                ? (frameResizeHandle ? 'frame-resize' : 'frame')
                : (elementResizeHandle ? 'element-resize' : (element ? 'element' : null)),
            target: state.mode === 'overview' ? frame : element,
            node: state.mode === 'overview' ? frameNode : elementNode,
            controlsNode,
            frame: state.mode === 'overview' ? state.presentation.canvas : frame,
            explicitFrameDrag: Boolean(frameDragHandle),
            resizeDirection: elementResizeHandle?.dataset.elementResize || null,
            startX: event.clientX,
            startY: event.clientY,
            originX: state.mode === 'overview' ? frame.x : element?.x,
            originY: state.mode === 'overview' ? frame.y : element?.y,
            originWidth: state.mode === 'overview' ? frame.width : element?.width,
            originHeight: state.mode === 'overview' ? frame.height : element?.height,
            frameElements,
            moved: false,
        };
        elements.viewport.setPointerCapture(event.pointerId);
        renderInspector();
    });

    elements.viewport.addEventListener('pointermove', (event) => {
        if (touchGesture?.isActive()) return;
        if (!state.drag?.kind || !state.drag.target) return;
        if (state.drag.kind === 'marquee') {
            const currentPoint = stagePointFromEvent(event);
            const x = Math.min(state.drag.startPoint.x, currentPoint.x);
            const y = Math.min(state.drag.startPoint.y, currentPoint.y);
            const width = Math.abs(currentPoint.x - state.drag.startPoint.x);
            const height = Math.abs(currentPoint.y - state.drag.startPoint.y);
            state.drag.moved = width > 5 || height > 5;
            Object.assign(state.drag.marqueeNode.style, {
                left: `${x}px`,
                top: `${y}px`,
                width: `${width}px`,
                height: `${height}px`,
            });

            const selection = new Set(state.drag.baseSelection);
            const intersects = (item) => (
                x < Number(item.x || 0) + Number(item.width || 0)
                && x + width > Number(item.x || 0)
                && y < Number(item.y || 0) + Number(item.height || 0)
                && y + height > Number(item.y || 0)
            );
            state.presentation.canvas.frames.forEach((frame) => {
                if (intersects(frame)) selection.add(`frame:${frame.id}`);
            });
            state.presentation.canvas.elements.forEach((element) => {
                if (intersects(element)) selection.add(`canvas:${element.id}`);
            });
            state.blockSelection = selection;
            applyBlockSelectionStyles();
            return;
        }

        const screenDeltaX = event.clientX - state.drag.startX;
        const screenDeltaY = event.clientY - state.drag.startY;
        const movementThreshold = state.drag.kind.includes('resize') ? 3 : 7;
        if (!state.drag.moved && Math.hypot(screenDeltaX, screenDeltaY) < movementThreshold) return;
        state.drag.moved = true;
        const deltaX = screenDeltaX / state.cameraScale;
        const deltaY = screenDeltaY / state.cameraScale;
        if (state.drag.kind === 'block') {
            const minimumDeltaX = Math.max(...state.drag.snapshots.map((snapshot) => -snapshot.x));
            const maximumDeltaX = Math.min(...state.drag.snapshots.map(
                (snapshot) => state.presentation.canvas.width - snapshot.x - snapshot.width
            ));
            const minimumDeltaY = Math.max(...state.drag.snapshots.map((snapshot) => -snapshot.y));
            const maximumDeltaY = Math.min(...state.drag.snapshots.map(
                (snapshot) => state.presentation.canvas.height - snapshot.y - snapshot.height
            ));
            const safeDeltaX = clampPresentationNumber(deltaX, minimumDeltaX, maximumDeltaX, 0);
            const safeDeltaY = clampPresentationNumber(deltaY, minimumDeltaY, maximumDeltaY, 0);
            state.drag.snapshots.forEach((snapshot) => {
                snapshot.item.x = snapshot.x + safeDeltaX;
                snapshot.item.y = snapshot.y + safeDeltaY;
                if (snapshot.node) {
                    snapshot.node.style.left = `${snapshot.item.x}px`;
                    snapshot.node.style.top = `${snapshot.item.y}px`;
                }
            });
        } else if (state.drag.kind === 'frame-resize') {
            const width = clampPresentationNumber(state.drag.originWidth + deltaX, 320, 1600, state.drag.originWidth);
            const height = clampPresentationNumber(state.drag.originHeight + deltaY, 180, 900, state.drag.originHeight);
            const scaleX = width / state.drag.originWidth;
            const scaleY = height / state.drag.originHeight;
            const fontScale = Math.min(scaleX, scaleY);
            state.drag.target.width = width;
            state.drag.target.height = height;
            state.drag.node.style.width = `${width}px`;
            state.drag.node.style.height = `${height}px`;
            state.drag.frameElements.forEach((snapshot) => {
                snapshot.item.x = snapshot.x * scaleX;
                snapshot.item.y = snapshot.y * scaleY;
                snapshot.item.width = Math.max(40, snapshot.width * scaleX);
                snapshot.item.height = Math.max(30, snapshot.height * scaleY);
                if (snapshot.item.type === 'text') {
                    snapshot.item.fontSize = clampPresentationNumber(snapshot.fontSize * fontScale, 10, 160, snapshot.fontSize);
                }
                const node = state.drag.node.querySelector(`[data-element-id="${CSS.escape(snapshot.item.id)}"]`);
                if (!node) return;
                node.style.left = `${snapshot.item.x}px`;
                node.style.top = `${snapshot.item.y}px`;
                node.style.width = `${snapshot.item.width}px`;
                node.style.height = `${snapshot.item.height}px`;
                if (snapshot.item.type === 'text') node.style.fontSize = `${snapshot.item.fontSize}px`;
            });
        } else if (['element-resize', 'canvas-element-resize'].includes(state.drag.kind)) {
            const direction = state.drag.resizeDirection || 'se';
            const minimumWidth = 40;
            const minimumHeight = 30;
            let x = state.drag.originX;
            let y = state.drag.originY;
            let width = state.drag.originWidth;
            let height = state.drag.originHeight;

            if (direction.includes('e')) {
                width = clampPresentationNumber(
                    state.drag.originWidth + deltaX,
                    minimumWidth,
                    Math.max(minimumWidth, state.drag.frame.width - state.drag.originX),
                    state.drag.originWidth
                );
            }
            if (direction.includes('s')) {
                height = clampPresentationNumber(
                    state.drag.originHeight + deltaY,
                    minimumHeight,
                    Math.max(minimumHeight, state.drag.frame.height - state.drag.originY),
                    state.drag.originHeight
                );
            }
            if (direction.includes('w')) {
                x = clampPresentationNumber(
                    state.drag.originX + deltaX,
                    0,
                    state.drag.originX + state.drag.originWidth - minimumWidth,
                    state.drag.originX
                );
                width = state.drag.originWidth + (state.drag.originX - x);
            }
            if (direction.includes('n')) {
                y = clampPresentationNumber(
                    state.drag.originY + deltaY,
                    0,
                    state.drag.originY + state.drag.originHeight - minimumHeight,
                    state.drag.originY
                );
                height = state.drag.originHeight + (state.drag.originY - y);
            }

            Object.assign(state.drag.target, { x, y, width, height });
            state.drag.node.style.left = `${x}px`;
            state.drag.node.style.top = `${y}px`;
            state.drag.node.style.width = `${width}px`;
            state.drag.node.style.height = `${height}px`;
            if (state.drag.controlsNode) {
                state.drag.controlsNode.style.left = `${x}px`;
                state.drag.controlsNode.style.top = `${y}px`;
                state.drag.controlsNode.style.width = `${width}px`;
                state.drag.controlsNode.style.height = `${height}px`;
            }
        } else {
            const maximumX = Math.max(0, state.drag.frame.width - state.drag.target.width);
            const maximumY = Math.max(0, state.drag.frame.height - state.drag.target.height);
            state.drag.target.x = clampPresentationNumber(state.drag.originX + deltaX, 0, maximumX, state.drag.originX);
            state.drag.target.y = clampPresentationNumber(state.drag.originY + deltaY, 0, maximumY, state.drag.originY);
            state.drag.node.style.left = `${state.drag.target.x}px`;
            state.drag.node.style.top = `${state.drag.target.y}px`;
            if (state.drag.controlsNode) {
                state.drag.controlsNode.style.left = `${state.drag.target.x}px`;
                state.drag.controlsNode.style.top = `${state.drag.target.y}px`;
            }
        }
    });

    const finishPointer = (event) => {
        if (touchGesture?.shouldSuppressTap()) {
            state.drag = null;
            return;
        }
        if (!state.drag) return;
        const drag = state.drag;
        state.drag = null;
        if (elements.viewport.hasPointerCapture(event.pointerId)) elements.viewport.releasePointerCapture(event.pointerId);
        if (drag.kind === 'marquee') {
            drag.marqueeNode?.remove();
            applyBlockSelectionStyles();
            return;
        }
        if (drag.moved) {
            ensureCanvasContainsFrames();
            markDirty();
            render(false);
        } else if (state.mode === 'overview' && drag.kind === 'frame' && !drag.explicitFrameDrag) {
            state.mode = 'focus';
            state.manualCamera = false;
            render();
            return;
        } else if (state.mode === 'overview' && ['frame', 'canvas-element'].includes(drag.kind)) {
            render(false);
            return;
        } else if (state.mode === 'focus' && drag.kind === 'element') {
            render(false);
            return;
        }
        renderFrameList();
    };
    elements.viewport.addEventListener('pointerup', finishPointer);
    elements.viewport.addEventListener('pointercancel', finishPointer);

    const duplicateBlockSelection = () => {
        const selectedItems = selectedBlockItems();
        if (!selectedItems.length) return;

        const nextSelection = new Set();
        let lastFrameId = null;
        let remainingFrameSlots = Math.max(0, 40 - state.presentation.canvas.frames.length);
        let remainingCanvasSlots = Math.max(0, 80 - state.presentation.canvas.elements.length);

        selectedItems.forEach(({ item, type }) => {
            if (type === 'frame' && remainingFrameSlots > 0) {
                const clone = JSON.parse(JSON.stringify(item));
                clone.id = presentationId('frame');
                clone.title = `Salinan ${item.title || 'Frame'}`.slice(0, 120);
                clone.x = clampPresentationNumber(
                    Number(item.x || 0) + 60,
                    0,
                    Math.max(0, state.presentation.canvas.width - Number(item.width || 0)),
                    Number(item.x || 0)
                );
                clone.y = clampPresentationNumber(
                    Number(item.y || 0) + 60,
                    0,
                    Math.max(0, state.presentation.canvas.height - Number(item.height || 0)),
                    Number(item.y || 0)
                );
                clone.elements = (clone.elements || []).map((element) => ({
                    ...element,
                    id: presentationId('element'),
                }));
                state.presentation.canvas.frames.push(clone);
                nextSelection.add(`frame:${clone.id}`);
                lastFrameId = clone.id;
                remainingFrameSlots -= 1;
            } else if (type === 'canvas' && remainingCanvasSlots > 0) {
                const clone = JSON.parse(JSON.stringify(item));
                clone.id = presentationId('canvas-element');
                clone.x = clampPresentationNumber(
                    Number(item.x || 0) + 45,
                    0,
                    Math.max(0, state.presentation.canvas.width - Number(item.width || 0)),
                    Number(item.x || 0)
                );
                clone.y = clampPresentationNumber(
                    Number(item.y || 0) + 45,
                    0,
                    Math.max(0, state.presentation.canvas.height - Number(item.height || 0)),
                    Number(item.y || 0)
                );
                state.presentation.canvas.elements.push(clone);
                nextSelection.add(`canvas:${clone.id}`);
                remainingCanvasSlots -= 1;
            }
        });

        if (!nextSelection.size) return;
        state.blockSelection = nextSelection;
        if (lastFrameId) state.selectedFrameId = lastFrameId;
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.manualCamera = true;
        ensureCanvasContainsFrames();
        hideBlockMenu();
        markDirty();
        render(false);
    };

    const deleteBlockSelection = () => {
        if (!state.blockSelection.size) return;
        const selectedFrameIds = new Set(
            [...state.blockSelection]
                .filter((key) => key.startsWith('frame:'))
                .map((key) => key.slice(6))
        );
        const selectedCanvasIds = new Set(
            [...state.blockSelection]
                .filter((key) => key.startsWith('canvas:'))
                .map((key) => key.slice(7))
        );
        const frames = state.presentation.canvas.frames;
        if (selectedFrameIds.size >= frames.length && frames[0]) {
            selectedFrameIds.delete(frames[0].id);
        }

        const previousFrameCount = frames.length;
        const previousCanvasCount = state.presentation.canvas.elements.length;
        state.presentation.canvas.frames = frames.filter((frame) => !selectedFrameIds.has(frame.id));
        state.presentation.canvas.elements = state.presentation.canvas.elements
            .filter((element) => !selectedCanvasIds.has(element.id));
        const changed = previousFrameCount !== state.presentation.canvas.frames.length
            || previousCanvasCount !== state.presentation.canvas.elements.length;

        hideBlockMenu();
        if (!changed) {
            elements.saveStatus.textContent = 'Minimal satu frame harus tetap tersedia.';
            return;
        }

        if (!findPresentationFrame(state.presentation, state.selectedFrameId)) {
            state.selectedFrameId = state.presentation.canvas.frames[0]?.id || null;
        }
        state.blockSelection.clear();
        state.selectedElementId = null;
        state.selectedCanvasElementId = null;
        state.manualCamera = true;
        ensureCanvasContainsFrames();
        markDirty();
        render(false);
    };

    elements.viewport.addEventListener('contextmenu', (event) => {
        if (state.mode !== 'overview') return;
        const key = blockKeyFromTarget(event.target);
        if (!key && !state.blockSelection.size) return;
        event.preventDefault();

        if (key && !state.blockSelection.has(key)) {
            state.blockSelection = new Set([key]);
        }
        if (!state.blockSelection.size) return;
        state.blockMode = true;
        applyBlockSelectionStyles();
        elements.blockMenuTitle.textContent = `${state.blockSelection.size} objek terpilih`;
        elements.blockMenu.classList.remove('hidden');
        const menuWidth = 220;
        const menuHeight = 120;
        elements.blockMenu.style.left = `${Math.max(8, Math.min(event.clientX, window.innerWidth - menuWidth - 12))}px`;
        elements.blockMenu.style.top = `${Math.max(8, Math.min(event.clientY, window.innerHeight - menuHeight - 12))}px`;
    });
    elements.blockMenu?.querySelector('[data-block-duplicate]')
        ?.addEventListener('click', duplicateBlockSelection);
    elements.blockMenu?.querySelector('[data-block-delete]')
        ?.addEventListener('click', deleteBlockSelection);
    document.addEventListener('pointerdown', (event) => {
        if (!elements.blockMenu?.contains(event.target)) hideBlockMenu();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideBlockMenu();
    });

    async function save(options = {}) {
        const force = options.force === true;
        window.clearTimeout(state.saveTimer);
        if (state.activeSave) {
            const activeSaved = await state.activeSave;
            if (activeSaved && (state.dirty || force)) return save({ force });
            return activeSaved;
        }
        if (!state.dirty && !force) return true;

        const saveVersion = state.changeVersion;
        state.saving = true;
        elements.saveStatus.textContent = 'Menyimpan...';
        state.activeSave = (async () => {
            try {
                ensureCanvasContainsFrames();
                const response = await fetch(root.dataset.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        title: elements.title.value,
                        description: elements.description.value,
                        background_color: elements.background.value,
                        path_mode: elements.pathMode.value,
                        canvas_data: state.presentation.canvas,
                    }),
                });
                const responseText = await response.text();
                let data = {};
                try {
                    data = responseText ? JSON.parse(responseText) : {};
                } catch {
                    data = {};
                }
                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error('Sesi halaman sudah berakhir. Muat ulang halaman lalu simpan kembali.');
                    }
                    if ([401, 403].includes(response.status)) {
                        throw new Error('Akses penyimpanan berakhir. Silakan login kembali.');
                    }
                    throw new Error(
                        data.message
                        || Object.values(data.errors || {})[0]?.[0]
                        || `Presentasi gagal disimpan (${response.status}).`
                    );
                }
                if (state.changeVersion === saveVersion) {
                    state.dirty = false;
                    elements.saveStatus.textContent = 'Semua perubahan tersimpan';
                    elements.saveStatus.classList.remove('text-amber-600', 'dark:text-amber-300', 'text-red-600');
                }
                return true;
            } catch (error) {
                elements.saveStatus.textContent = error.message;
                elements.saveStatus.classList.add('text-red-600');
                return false;
            } finally {
                state.saving = false;
            }
        })();

        const saved = await state.activeSave;
        state.activeSave = null;
        if (saved && state.dirty) return save();
        return saved;
    }

    elements.undo.addEventListener('click', undoHistory);
    elements.redo.addEventListener('click', redoHistory);
    document.addEventListener('keydown', (event) => {
        if (!(event.ctrlKey || event.metaKey) || event.altKey) return;
        const key = event.key.toLowerCase();
        if (key === 'z') {
            event.preventDefault();
            if (event.shiftKey) redoHistory();
            else undoHistory();
        } else if (key === 'y') {
            event.preventDefault();
            redoHistory();
        }
    });

    root.querySelector('[data-editor-save]').addEventListener('click', save);
    elements.layoutSave?.addEventListener('click', async () => {
        const savedAt = new Date();
        state.presentation.canvas.layoutSnapshot = {
            savedAt: savedAt.toISOString(),
            frames: state.presentation.canvas.frames.map((frame) => ({
                id: frame.id,
                x: Number(frame.x || 0),
                y: Number(frame.y || 0),
                width: Number(frame.width || 800),
                height: Number(frame.height || 450),
            })),
            elements: state.presentation.canvas.elements.map((element) => ({
                id: element.id,
                x: Number(element.x || 0),
                y: Number(element.y || 0),
                width: Number(element.width || 320),
                height: Number(element.height || 80),
                rotation: Number(element.rotation || 0),
            })),
        };
        markDirty('layout-snapshot');
        elements.layoutSave.disabled = true;
        elements.layoutSave.textContent = 'Menyimpan Patokan...';
        const saved = await save({ force: true });
        const savedTime = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).format(savedAt);
        elements.layoutSave.textContent = saved
            ? `Patokan Tersimpan ${savedTime}`
            : 'Gagal Menyimpan';
        elements.layoutSave.disabled = false;
        if (saved) {
            elements.saveStatus.textContent = `Patokan tata letak rapi tersimpan pada ${savedTime}`;
            elements.saveStatus.classList.remove('text-amber-600', 'dark:text-amber-300', 'text-red-600');
        }
    });
    root.querySelectorAll('[data-export-link]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            if (!state.dirty) return;

            event.preventDefault();
            const saved = await save();
            if (saved) window.location.assign(link.href);
        });
    });
    root.querySelectorAll('[data-save-before-open]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            if (!state.dirty) return;

            event.preventDefault();
            const popup = link.target === '_blank' ? window.open('about:blank', '_blank') : null;
            const saved = await save();
            if (saved) {
                if (popup) popup.location.href = link.href;
                else window.location.assign(link.href);
            } else if (popup) {
                popup.close();
            }
        });
    });
    root.querySelectorAll('[data-publish-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (!state.dirty) return;
            event.preventDefault();
            if (await save()) form.submit();
        });
    });
    window.addEventListener('beforeunload', (event) => {
        if (!state.dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
    new ResizeObserver(() => {
        state.manualCamera = false;
        applyCamera(false);
    }).observe(elements.viewport);

    render(false);
}

function numberField(label, property, value, min, max) {
    return `<div><label class="form-label">${label}</label><input type="number" class="pkg-field w-full" min="${min}" max="${max}" data-inspector-prop="${property}" value="${Math.round(value)}"></div>`;
}

function colorField(label, property, value) {
    return `<div><label class="form-label">${label}</label><input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="${property}" value="${value}"></div>`;
}

function option(value, label, selected) {
    return `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`;
}

function normalizeColorInput(value, fallback) {
    return /^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback;
}

function elementTypeLabel(type) {
    return ({
        text: 'Elemen Teks',
        image: 'Elemen Gambar',
        logo: 'Elemen Logo',
        youtube: 'Elemen YouTube',
        link: 'Elemen Tautan',
        shape: 'Elemen Bentuk',
        line: 'Elemen Garis',
        diagram: 'Elemen Diagram',
    })[type] || 'Elemen';
}

function extractYouTubeId(value) {
    const match = String(value || '').trim().match(
        /(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/i
    );
    return match?.[1] || '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/\n/g, '&#10;');
}
