import '../css/presentation.css';
import {
    applyPresentationCamera,
    clampPresentationNumber,
    decodePresentationPayload,
    findPresentationElement,
    findPresentationFrame,
    presentationPinchFactor,
    presentationId,
    renderPresentationStage,
    zoomPresentationAtPoint,
} from './presentation-canvas';

const root = document.getElementById('presentation-editor');

if (root) {
    const payload = decodePresentationPayload(root.dataset.presentationPayload);
    const state = {
        presentation: payload,
        selectedFrameId: payload.canvas.frames[0]?.id || null,
        selectedElementId: null,
        mode: 'overview',
        dirty: false,
        saving: false,
        changeVersion: 0,
        saveTimer: null,
        activeSave: null,
        cameraScale: 1,
        manualCamera: false,
        drag: null,
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
    };

    elements.title.value = payload.title || '';
    elements.description.value = payload.description || '';
    elements.background.value = payload.backgroundColor || '#0f172a';
    elements.pathMode.value = payload.pathMode || 'overview_between';

    const scheduleAutomaticSave = () => {
        window.clearTimeout(state.saveTimer);
        state.saveTimer = window.setTimeout(() => save(), 1200);
    };

    const markDirty = () => {
        state.dirty = true;
        state.changeVersion += 1;
        elements.saveStatus.textContent = 'Belum disimpan · akan disimpan otomatis';
        elements.saveStatus.classList.add('text-amber-600', 'dark:text-amber-300');
        scheduleAutomaticSave();
    };

    const selectedFrame = () => findPresentationFrame(state.presentation, state.selectedFrameId);
    const selectedElement = () => findPresentationElement(selectedFrame(), state.selectedElementId);

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
        state.presentation.canvas.width = clampPresentationNumber(requiredWidth, 1200, 7000, 2400);
        state.presentation.canvas.height = clampPresentationNumber(requiredHeight, 800, 12500, 1400);
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

    const updateCameraHint = () => {
        const frame = state.mode === 'focus' ? selectedFrame() : null;
        const label = frame ? `Fokus: ${frame.title}` : 'Mode Overview';
        elements.hint.textContent = `${label} · ${Math.round(state.cameraScale * 100)}%`;
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
        state.manualCamera = false;
        updateCameraHint();
    };

    const render = (animate = true) => {
        renderPresentationStage({
            stage: elements.stage,
            presentation: state.presentation,
            selectedFrameId: state.selectedFrameId,
            selectedElementId: state.selectedElementId,
            overview: state.mode === 'overview',
        });
        renderFrameList();
        renderInspector();
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

    const commonPositionFields = (item, isFrame = false) => `
        <div class="grid grid-cols-2 gap-3">
            ${numberField('X', 'x', item.x, 0, 5000)}
            ${numberField('Y', 'y', item.y, 0, isFrame ? 11000 : 1100)}
            ${numberField('Lebar', 'width', item.width, isFrame ? 320 : 40, 1600)}
            ${numberField('Tinggi', 'height', item.height, isFrame ? 180 : 30, 900)}
        </div>
    `;

    const renderInspector = () => {
        const frame = selectedFrame();
        const element = selectedElement();

        if (!frame) {
            elements.inspector.innerHTML = '<p class="pkg-empty-copy">Tambahkan atau pilih frame untuk mulai menyunting.</p>';
            return;
        }

        if (!element) {
            elements.inspector.innerHTML = `
                <div class="space-y-4" data-inspector-scope="frame">
                    <div>
                        <label class="form-label">Judul frame</label>
                        <input class="pkg-field w-full" maxlength="120" data-inspector-prop="title" value="${escapeAttribute(frame.title)}">
                    </div>
                    <div>
                        <label class="form-label">Warna frame</label>
                        <input type="color" class="pkg-field h-11 w-full p-1" data-inspector-prop="backgroundColor" value="${frame.backgroundColor || '#ffffff'}">
                    </div>
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
        } else if (element.type === 'image') {
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
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${colorField('Warna diagram', 'color', element.color || '#0f172a')}
                    ${colorField('Latar', 'backgroundColor', normalizeColorInput(element.backgroundColor, '#ffffff'))}
                </div>
            `;
        }

        elements.inspector.innerHTML = `
            <div class="space-y-4" data-inspector-scope="element">
                <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    ${elementTypeLabel(element.type)}
                </div>
                ${typeFields}
                ${commonPositionFields(element)}
                <button type="button" class="btn-danger w-full justify-center" data-delete-selected-element>Hapus Elemen</button>
            </div>
        `;
    };

    const focusFrame = (frameId) => {
        state.selectedFrameId = frameId;
        state.selectedElementId = null;
        state.mode = 'focus';
        state.manualCamera = false;
        render();
    };

    root.querySelector('[data-editor-overview]').addEventListener('click', () => {
        state.mode = 'overview';
        state.selectedElementId = null;
        state.manualCamera = false;
        render();
    });
    root.querySelector('[data-editor-fit]').addEventListener('click', () => {
        state.manualCamera = false;
        applyCamera();
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
            elements: [],
        };
        state.presentation.canvas.frames.push(frame);
        ensureCanvasContainsFrames();
        state.selectedFrameId = frame.id;
        state.selectedElementId = null;
        state.mode = 'overview';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-arrange-frames]').addEventListener('click', () => {
        const frames = state.presentation.canvas.frames;
        const maximumWidth = Math.max(...frames.map((frame) => Number(frame.width || 800)));
        const maximumHeight = Math.max(...frames.map((frame) => Number(frame.height || 450)));
        const columnGap = 160;
        const rowGap = 140;

        frames.forEach((frame, index) => {
            frame.x = 120 + ((index % 2) * (maximumWidth + columnGap));
            frame.y = 120 + (Math.floor(index / 2) * (maximumHeight + rowGap));
        });
        state.mode = 'overview';
        state.selectedElementId = null;
        state.manualCamera = false;
        ensureCanvasContainsFrames();
        markDirty();
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
        state.mode = 'focus';
        state.manualCamera = false;
        markDirty();
        render();
    });

    root.querySelector('[data-add-image]').addEventListener('click', () => {
        if (selectedFrame()) elements.imageInput.click();
    });

    elements.imageInput.addEventListener('change', async () => {
        const file = elements.imageInput.files?.[0];
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
                type: 'image',
                assetId: data.asset.id,
                x: 90,
                y: 80,
                width: Math.min(420, frame.width - 180),
                height: Math.min(280, frame.height - 140),
                rotation: 0,
                alt: data.asset.name,
                fit: 'cover',
                color: '#0f172a',
                backgroundColor: 'transparent',
            };
            frame.elements.push(element);
            state.selectedElementId = element.id;
            state.mode = 'focus';
            state.manualCamera = false;
            markDirty();
            render();
        } catch (error) {
            elements.saveStatus.textContent = error.message;
            elements.saveStatus.classList.add('text-red-600');
        } finally {
            elements.imageInput.value = '';
        }
    });

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
        const target = scope === 'frame' ? selectedFrame() : selectedElement();
        if (!target) return;
        if (scope === 'frame' && ['width', 'height'].includes(input.dataset.inspectorProp)) return;

        let value = input.type === 'checkbox' ? input.checked : input.value;
        if (['x', 'y', 'width', 'height', 'fontSize'].includes(input.dataset.inspectorProp)) {
            value = Number(value);
        }
        if (input.dataset.inspectorProp === 'items') {
            value = String(value).split('\n').map((item) => item.trim()).filter(Boolean).slice(0, 8);
        }
        target[input.dataset.inspectorProp] = value;
        if (scope === 'frame') ensureCanvasContainsFrames();
        markDirty();
        renderPresentationStage({
            stage: elements.stage,
            presentation: state.presentation,
            selectedFrameId: state.selectedFrameId,
            selectedElementId: state.selectedElementId,
            overview: state.mode === 'overview',
        });
        if (state.manualCamera) updateCameraHint();
        else applyCamera(false);
        if (scope === 'frame' && input.dataset.inspectorProp === 'title') renderFrameList();
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
            const frame = selectedFrame();
            frame.elements = frame.elements.filter((element) => element.id !== state.selectedElementId);
            state.selectedElementId = null;
            markDirty();
            render();
            return;
        }
        if (event.target.closest('[data-delete-selected-frame]') && state.presentation.canvas.frames.length > 1) {
            const frames = state.presentation.canvas.frames;
            const index = frames.findIndex((frame) => frame.id === state.selectedFrameId);
            frames.splice(index, 1);
            state.selectedFrameId = frames[Math.max(0, index - 1)]?.id || frames[0]?.id;
            state.selectedElementId = null;
            state.mode = 'overview';
            state.manualCamera = false;
            ensureCanvasContainsFrames();
            markDirty();
            render();
        }
    });

    const metadataChanged = () => {
        state.presentation.title = elements.title.value;
        state.presentation.description = elements.description.value;
        state.presentation.backgroundColor = elements.background.value;
        state.presentation.pathMode = elements.pathMode.value;
        markDirty();
        if (document.activeElement === elements.background) render(false);
    };
    [elements.title, elements.description, elements.background, elements.pathMode].forEach((input) => {
        input.addEventListener('input', metadataChanged);
        input.addEventListener('change', metadataChanged);
    });

    elements.viewport.addEventListener('wheel', (event) => {
        if (!event.ctrlKey && !event.metaKey) return;

        event.preventDefault();
        state.cameraScale = zoomPresentationAtPoint(
            elements.viewport,
            elements.stage,
            presentationPinchFactor(event.deltaY),
            event.clientX,
            event.clientY,
            { minimumScale: 0.03, maximumScale: 4 }
        );
        state.manualCamera = true;
        updateCameraHint();
    }, { passive: false });

    elements.viewport.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        const frameNode = event.target.closest('[data-frame-id]');
        const elementNode = event.target.closest('[data-element-id]');
        const resizeHandle = event.target.closest('[data-frame-resize]');
        if (!frameNode) return;

        const frame = findPresentationFrame(state.presentation, frameNode.dataset.frameId);
        const element = findPresentationElement(frame, elementNode?.dataset.elementId);
        state.selectedFrameId = frame.id;
        state.selectedElementId = state.mode === 'focus' && element ? element.id : null;
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
                ? (resizeHandle ? 'frame-resize' : 'frame')
                : (element ? 'element' : null),
            target: state.mode === 'overview' ? frame : element,
            node: state.mode === 'overview' ? frameNode : elementNode,
            startX: event.clientX,
            startY: event.clientY,
            originX: state.mode === 'overview' ? frame.x : element?.x,
            originY: state.mode === 'overview' ? frame.y : element?.y,
            originWidth: frame.width,
            originHeight: frame.height,
            frameElements,
            moved: false,
        };
        elements.viewport.setPointerCapture(event.pointerId);
        renderInspector();
    });

    elements.viewport.addEventListener('pointermove', (event) => {
        if (!state.drag?.kind || !state.drag.target) return;
        const deltaX = (event.clientX - state.drag.startX) / state.cameraScale;
        const deltaY = (event.clientY - state.drag.startY) / state.cameraScale;
        if (Math.abs(deltaX) + Math.abs(deltaY) > 2) state.drag.moved = true;
        if (state.drag.kind === 'frame-resize') {
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
        } else {
            state.drag.target.x = Math.max(0, state.drag.originX + deltaX);
            state.drag.target.y = Math.max(0, state.drag.originY + deltaY);
            state.drag.node.style.left = `${state.drag.target.x}px`;
            state.drag.node.style.top = `${state.drag.target.y}px`;
        }
    });

    const finishPointer = (event) => {
        if (!state.drag) return;
        const drag = state.drag;
        state.drag = null;
        if (elements.viewport.hasPointerCapture(event.pointerId)) elements.viewport.releasePointerCapture(event.pointerId);
        if (drag.moved) {
            ensureCanvasContainsFrames();
            markDirty();
            render(false);
        } else if (state.mode === 'overview' && drag.kind === 'frame') {
            state.mode = 'focus';
            state.manualCamera = false;
            render();
            return;
        }
        renderFrameList();
    };
    elements.viewport.addEventListener('pointerup', finishPointer);
    elements.viewport.addEventListener('pointercancel', finishPointer);

    async function save() {
        window.clearTimeout(state.saveTimer);
        if (state.activeSave) {
            const activeSaved = await state.activeSave;
            if (activeSaved && state.dirty) return save();
            return activeSaved;
        }
        if (!state.dirty) return true;

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
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Presentasi gagal disimpan.');
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

    root.querySelector('[data-editor-save]').addEventListener('click', save);
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
    return ({ text: 'Elemen Teks', image: 'Elemen Gambar', diagram: 'Elemen Diagram' })[type] || 'Elemen';
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
