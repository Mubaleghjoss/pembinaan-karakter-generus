export function decodePresentationPayload(value) {
    const bytes = Uint8Array.from(atob(value || ''), (character) => character.charCodeAt(0));
    return JSON.parse(new TextDecoder().decode(bytes));
}

export function presentationId(prefix) {
    return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
}

export function clampPresentationNumber(value, min, max, fallback = min) {
    const number = Number(value);
    return Math.max(min, Math.min(max, Number.isFinite(number) ? number : fallback));
}

export function findPresentationFrame(presentation, frameId) {
    return presentation.canvas.frames.find((frame) => frame.id === frameId) || null;
}

export function findPresentationElement(frame, elementId) {
    return frame?.elements?.find((element) => element.id === elementId) || null;
}

export function presentationCanvasBounds(canvas, padding = 120) {
    const frames = Array.isArray(canvas.frames) ? canvas.frames : [];
    const maximumX = frames.reduce(
        (maximum, frame) => Math.max(maximum, Number(frame.x || 0) + Number(frame.width || 0)),
        Number(canvas.width || 2400)
    );
    const maximumY = frames.reduce(
        (maximum, frame) => Math.max(maximum, Number(frame.y || 0) + Number(frame.height || 0)),
        Number(canvas.height || 1400)
    );

    return {
        x: 0,
        y: 0,
        width: Math.max(1200, maximumX + padding),
        height: Math.max(800, maximumY + padding),
    };
}

export function renderPresentationStage({
    stage,
    presentation,
    selectedFrameId = null,
    selectedElementId = null,
    overview = true,
    interactive = false,
}) {
    const canvas = presentation.canvas;
    const canvasBounds = presentationCanvasBounds(canvas);
    stage.replaceChildren();
    stage.style.width = `${canvasBounds.width}px`;
    stage.style.height = `${canvasBounds.height}px`;
    stage.style.backgroundColor = presentation.backgroundColor || '#0f172a';

    canvas.frames.forEach((frame, frameIndex) => {
        const frameElement = document.createElement('section');
        frameElement.className = 'pkg-presentation-frame';
        frameElement.dataset.frameId = frame.id;
        frameElement.style.left = `${frame.x}px`;
        frameElement.style.top = `${frame.y}px`;
        frameElement.style.width = `${frame.width}px`;
        frameElement.style.height = `${frame.height}px`;
        frameElement.style.backgroundColor = frame.backgroundColor || '#ffffff';
        applyPresentationShape(frameElement, frame.shape || 'rounded', frame.borderRadius || 22);
        frameElement.classList.toggle('is-selected', frame.id === selectedFrameId);
        frameElement.classList.toggle('is-overview', overview);

        if (overview && frame.id === selectedFrameId) {
            const resizeHandle = document.createElement('button');
            resizeHandle.type = 'button';
            resizeHandle.className = 'pkg-presentation-frame-resize';
            resizeHandle.dataset.frameResize = frame.id;
            resizeHandle.setAttribute('aria-label', 'Ubah ukuran frame');
            frameElement.appendChild(resizeHandle);
        }

        const pathBadge = document.createElement('span');
        pathBadge.className = 'pkg-presentation-path-badge';
        pathBadge.textContent = String(frameIndex + 1);
        frameElement.appendChild(pathBadge);

        const frameLabel = document.createElement('span');
        frameLabel.className = 'pkg-presentation-frame-label';
        frameLabel.textContent = frame.title || `Frame ${frameIndex + 1}`;
        frameElement.appendChild(frameLabel);

        (frame.elements || []).forEach((element) => {
            const elementNode = renderPresentationElement(element, presentation.assets || {}, interactive);
            elementNode.dataset.elementId = element.id;
            const isSelected = frame.id === selectedFrameId && element.id === selectedElementId;
            elementNode.classList.toggle('is-selected', isSelected);
            frameElement.appendChild(elementNode);
            if (!overview && isSelected) {
                frameElement.appendChild(createPresentationElementControls(element));
            }
        });

        stage.appendChild(frameElement);
    });
}

export function applyPresentationCamera(viewport, stage, canvas, frame = null, animate = true) {
    const viewportWidth = Math.max(320, viewport.clientWidth);
    const viewportHeight = Math.max(320, viewport.clientHeight);
    const bounds = frame || presentationCanvasBounds(canvas);
    const padding = frame ? 54 : 30;
    const scale = Math.min(
        (viewportWidth - (padding * 2)) / bounds.width,
        (viewportHeight - (padding * 2)) / bounds.height
    );
    const safeScale = clampPresentationNumber(scale, frame ? 0.08 : 0.03, frame ? 2.4 : 0.9, 0.3);
    const translateX = ((viewportWidth - (bounds.width * safeScale)) / 2) - (bounds.x * safeScale);
    const translateY = ((viewportHeight - (bounds.height * safeScale)) / 2) - (bounds.y * safeScale);

    setPresentationCameraTransform(stage, translateX, translateY, safeScale, animate);

    return safeScale;
}

export function zoomPresentationAtPoint(viewport, stage, factor, clientX, clientY, options = {}) {
    const viewportBounds = viewport.getBoundingClientRect();
    const currentScale = cameraNumber(stage.dataset.cameraScale, 1);
    const currentX = cameraNumber(stage.dataset.cameraX, 0);
    const currentY = cameraNumber(stage.dataset.cameraY, 0);
    const minimumScale = Number.isFinite(options.minimumScale) ? options.minimumScale : 0.03;
    const maximumScale = Number.isFinite(options.maximumScale) ? options.maximumScale : 4;
    const requestedScale = currentScale * clampPresentationNumber(factor, 0.72, 1.38, 1);
    const nextScale = clampPresentationNumber(requestedScale, minimumScale, maximumScale, currentScale);
    const pointerX = clientX - viewportBounds.left;
    const pointerY = clientY - viewportBounds.top;
    const contentX = (pointerX - currentX) / currentScale;
    const contentY = (pointerY - currentY) / currentScale;
    const nextX = pointerX - (contentX * nextScale);
    const nextY = pointerY - (contentY * nextScale);

    setPresentationCameraTransform(stage, nextX, nextY, nextScale, false);

    return nextScale;
}

export function panPresentationCamera(stage, deltaX, deltaY) {
    const currentScale = cameraNumber(stage.dataset.cameraScale, 1);
    const nextX = cameraNumber(stage.dataset.cameraX, 0) + cameraNumber(deltaX, 0);
    const nextY = cameraNumber(stage.dataset.cameraY, 0) + cameraNumber(deltaY, 0);

    setPresentationCameraTransform(stage, nextX, nextY, currentScale, false);

    return currentScale;
}

export function syncPresentationCamera(stage) {
    const transform = window.getComputedStyle(stage).transform;
    if (!transform || transform === 'none') {
        return cameraNumber(stage.dataset.cameraScale, 1);
    }

    const matrix = new DOMMatrixReadOnly(transform);
    const scale = Math.max(0.001, Math.hypot(matrix.a, matrix.b));
    setPresentationCameraTransform(stage, matrix.e, matrix.f, scale, false);

    return scale;
}

export function presentationPinchFactor(deltaY) {
    return Math.exp(-clampPresentationNumber(deltaY, -40, 40, 0) * 0.012);
}

export function bindPresentationTouchGestures(viewport, stage, options = {}) {
    const pointers = new Map();
    const tapStarts = new Map();
    let active = false;
    let previousGesture = null;
    let suppressTapUntil = 0;

    const gestureMetrics = () => {
        const [first, second] = Array.from(pointers.values()).slice(0, 2);
        if (!first || !second) return null;
        const deltaX = second.x - first.x;
        const deltaY = second.y - first.y;

        return {
            x: (first.x + second.x) / 2,
            y: (first.y + second.y) / 2,
            distance: Math.max(1, Math.hypot(deltaX, deltaY)),
        };
    };

    const pointerDown = (event) => {
        if (event.pointerType !== 'touch') return;
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        tapStarts.set(event.pointerId, {
            x: event.clientX,
            y: event.clientY,
            time: performance.now(),
            target: event.target,
        });

        if (pointers.size >= 2 && !active) {
            active = true;
            tapStarts.clear();
            previousGesture = gestureMetrics();
            options.onStart?.();
            event.preventDefault();
        }
    };

    const pointerMove = (event) => {
        if (!pointers.has(event.pointerId)) return;
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        if (!active || pointers.size < 2) return;

        const currentGesture = gestureMetrics();
        if (!currentGesture || !previousGesture) return;
        const factor = currentGesture.distance / previousGesture.distance;
        const scale = zoomPresentationAtPoint(
            viewport,
            stage,
            factor,
            previousGesture.x,
            previousGesture.y,
            {
                minimumScale: options.minimumScale ?? 0.03,
                maximumScale: options.maximumScale ?? 4,
            }
        );
        panPresentationCamera(
            stage,
            currentGesture.x - previousGesture.x,
            currentGesture.y - previousGesture.y
        );
        previousGesture = currentGesture;
        options.onUpdate?.(scale);
        event.preventDefault();
    };

    const pointerEnd = (event) => {
        if (!pointers.has(event.pointerId)) return;
        const tapStart = tapStarts.get(event.pointerId);
        const wasGesture = active;
        pointers.delete(event.pointerId);
        if (active && pointers.size < 2) {
            active = false;
            previousGesture = null;
            suppressTapUntil = performance.now() + 320;
            options.onEnd?.();
            event.preventDefault();
        }
        if (!wasGesture && tapStart && event.type === 'pointerup') {
            const movement = Math.hypot(event.clientX - tapStart.x, event.clientY - tapStart.y);
            const duration = performance.now() - tapStart.time;
            if (movement <= 14 && duration <= 650) {
                const handled = options.onTap?.({
                    target: tapStart.target,
                    clientX: event.clientX,
                    clientY: event.clientY,
                    originalEvent: event,
                });
                if (handled !== false) {
                    suppressTapUntil = performance.now() + 360;
                    event.preventDefault();
                }
            }
        }
        tapStarts.delete(event.pointerId);
    };

    viewport.addEventListener('pointerdown', pointerDown, { capture: true });
    viewport.addEventListener('pointermove', pointerMove, { capture: true });
    viewport.addEventListener('pointerup', pointerEnd, { capture: true });
    viewport.addEventListener('pointercancel', pointerEnd, { capture: true });

    return {
        isActive: () => active,
        shouldSuppressTap: () => performance.now() < suppressTapUntil,
        destroy: () => {
            viewport.removeEventListener('pointerdown', pointerDown, { capture: true });
            viewport.removeEventListener('pointermove', pointerMove, { capture: true });
            viewport.removeEventListener('pointerup', pointerEnd, { capture: true });
            viewport.removeEventListener('pointercancel', pointerEnd, { capture: true });
        },
    };
}

function setPresentationCameraTransform(stage, translateX, translateY, scale, animate) {
    stage.dataset.cameraX = String(translateX);
    stage.dataset.cameraY = String(translateY);
    stage.dataset.cameraScale = String(scale);
    stage.style.transition = animate ? 'transform 560ms cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
    stage.style.transform = `translate3d(${translateX}px, ${translateY}px, 0) scale(${scale})`;
}

function cameraNumber(value, fallback) {
    const number = Number(value);
    return Number.isFinite(number) ? number : fallback;
}

function renderPresentationElement(element, assets, interactive = false) {
    const node = createPresentationElementShell(element);

    if (element.type === 'text') {
        node.classList.add('pkg-presentation-text');
        node.style.fontSize = `${element.fontSize || 32}px`;
        node.style.textAlign = element.align || 'left';
        node.style.fontWeight = element.bold ? '700' : '400';
        node.textContent = element.text || 'Teks';
        return node;
    }

    if (element.type === 'image' || element.type === 'logo') {
        const image = document.createElement('img');
        image.src = assets[String(element.assetId)]?.url || '';
        image.alt = element.alt || (element.type === 'logo' ? 'Logo presentasi' : 'Gambar presentasi');
        image.draggable = false;
        image.style.objectFit = element.fit || 'cover';
        applyPresentationShape(node, element.shape || (element.type === 'logo' ? 'circle' : 'rounded'), 18);
        node.appendChild(image);
        return node;
    }

    if (element.type === 'youtube') {
        node.classList.add('pkg-presentation-youtube');
        const placeholder = document.createElement('div');
        placeholder.className = 'pkg-presentation-youtube-placeholder';
        placeholder.innerHTML = '<span aria-hidden="true"></span><strong>Video YouTube</strong>';
        node.appendChild(placeholder);
        if (interactive && element.youtubeId) {
            node.classList.add('is-interactive');
            const iframe = document.createElement('iframe');
            iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(element.youtubeId)}?rel=0`;
            iframe.title = element.title || 'Video YouTube';
            iframe.loading = 'lazy';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.allowFullscreen = true;
            iframe.referrerPolicy = 'strict-origin-when-cross-origin';
            node.appendChild(iframe);
        }
        return node;
    }

    if (element.type === 'link') {
        const link = document.createElement(interactive ? 'a' : 'div');
        link.className = `pkg-presentation-link is-${element.linkStyle || 'button'}`;
        link.textContent = element.text || 'Buka tautan';
        if (interactive) {
            link.href = element.url || '#';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        }
        node.appendChild(link);
        return node;
    }

    if (element.type === 'shape') {
        node.classList.add('pkg-presentation-shape');
        applyPresentationShape(node, element.shapeType || 'rounded', element.borderRadius || 24);
        node.style.fontSize = `${element.fontSize || 28}px`;
        node.textContent = element.text || '';
        return node;
    }

    if (element.type === 'line') {
        node.classList.add('pkg-presentation-line');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 100 20');
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('aria-hidden', 'true');

        const definitions = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const markerId = `presentation-arrow-${String(element.id || '').replace(/[^a-z0-9_-]/gi, '')}`;
        const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', markerId);
        marker.setAttribute('viewBox', '0 0 10 10');
        marker.setAttribute('refX', '8');
        marker.setAttribute('refY', '5');
        marker.setAttribute('markerWidth', '6');
        marker.setAttribute('markerHeight', '6');
        marker.setAttribute('orient', 'auto-start-reverse');
        const arrowPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        arrowPath.setAttribute('d', 'M 0 0 L 10 5 L 0 10 z');
        arrowPath.setAttribute('fill', element.color || '#0f172a');
        marker.appendChild(arrowPath);
        definitions.appendChild(marker);
        svg.appendChild(definitions);

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', '4');
        line.setAttribute('y1', '10');
        line.setAttribute('x2', '96');
        line.setAttribute('y2', '10');
        line.setAttribute('stroke', element.color || '#0f172a');
        line.setAttribute('stroke-width', String(element.strokeWidth || 4));
        line.setAttribute('vector-effect', 'non-scaling-stroke');
        line.setAttribute('stroke-linecap', 'round');
        if (element.lineStyle === 'dashed') line.setAttribute('stroke-dasharray', '12 8');
        if (element.lineStyle === 'dotted') line.setAttribute('stroke-dasharray', '2 7');
        if (['start', 'both'].includes(element.arrow)) line.setAttribute('marker-start', `url(#${markerId})`);
        if (['end', 'both'].includes(element.arrow)) line.setAttribute('marker-end', `url(#${markerId})`);
        svg.appendChild(line);
        node.appendChild(svg);
        return node;
    }

    node.classList.add('pkg-presentation-diagram', `is-${element.diagramType || 'process'}`);
    const items = Array.isArray(element.items) && element.items.length ? element.items : ['Ide 1', 'Ide 2', 'Ide 3'];
    if (element.diagramType === 'radial') {
        const centerNode = document.createElement('div');
        centerNode.className = 'pkg-presentation-diagram-center';
        centerNode.textContent = element.centerText || 'Logo / Tema';
        applyPresentationShape(centerNode, element.nodeShape || 'circle', 22);
        node.appendChild(centerNode);
        items.forEach((item, index) => {
            const angle = ((Math.PI * 2) / items.length) * index - (Math.PI / 2);
            const connector = document.createElement('span');
            connector.className = 'pkg-presentation-radial-connector';
            connector.style.setProperty('--connector-angle', `${angle}rad`);
            node.appendChild(connector);

            const itemNode = document.createElement('div');
            itemNode.className = 'pkg-presentation-diagram-node';
            itemNode.textContent = item;
            itemNode.style.setProperty('--node-x', `${50 + (Math.cos(angle) * 40)}%`);
            itemNode.style.setProperty('--node-y', `${50 + (Math.sin(angle) * 38)}%`);
            applyPresentationShape(itemNode, element.nodeShape || 'circle', 18);
            node.appendChild(itemNode);
        });
        return node;
    }

    items.forEach((item, index) => {
        const itemNode = document.createElement('div');
        itemNode.className = 'pkg-presentation-diagram-node';
        itemNode.textContent = item;
        node.appendChild(itemNode);
        if (index < items.length - 1) {
            const connector = document.createElement('span');
            connector.className = 'pkg-presentation-diagram-connector';
            connector.textContent = '→';
            node.appendChild(connector);
        }
    });
    return node;
}

function createPresentationElementShell(element) {
    const node = document.createElement('div');
    node.className = `pkg-presentation-element is-${element.type}`;
    node.style.left = `${element.x}px`;
    node.style.top = `${element.y}px`;
    node.style.width = `${element.width}px`;
    node.style.height = `${element.height}px`;
    node.style.transform = `rotate(${element.rotation || 0}deg)`;
    node.style.color = element.color || '#0f172a';
    node.style.backgroundColor = element.backgroundColor || 'transparent';
    return node;
}

function createPresentationElementControls(element) {
    const controls = document.createElement('div');
    controls.className = 'pkg-presentation-element-controls';
    controls.dataset.elementId = element.id;
    controls.style.left = `${element.x}px`;
    controls.style.top = `${element.y}px`;
    controls.style.width = `${element.width}px`;
    controls.style.height = `${element.height}px`;
    controls.style.transform = `rotate(${element.rotation || 0}deg)`;

    ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'].forEach((direction) => {
        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = `pkg-presentation-element-resize-handle is-${direction}`;
        handle.dataset.elementResize = direction;
        handle.dataset.elementId = element.id;
        handle.setAttribute('aria-label', `Ubah ukuran elemen dari sisi ${direction}`);
        controls.appendChild(handle);
    });

    return controls;
}

function applyPresentationShape(node, shape, borderRadius = 22) {
    node.classList.add(`is-shape-${shape}`);
    if (shape === 'custom') node.style.borderRadius = `${borderRadius}px`;
    if (shape === 'hexagon') {
        node.style.clipPath = 'polygon(25% 0, 75% 0, 100% 50%, 75% 100%, 25% 100%, 0 50%)';
    }
}
