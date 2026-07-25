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

export function renderPresentationStage({
    stage,
    presentation,
    selectedFrameId = null,
    selectedElementId = null,
    overview = true,
}) {
    const canvas = presentation.canvas;
    stage.replaceChildren();
    stage.style.width = `${canvas.width}px`;
    stage.style.height = `${canvas.height}px`;
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
        frameElement.classList.toggle('is-selected', frame.id === selectedFrameId);
        frameElement.classList.toggle('is-overview', overview);

        const pathBadge = document.createElement('span');
        pathBadge.className = 'pkg-presentation-path-badge';
        pathBadge.textContent = String(frameIndex + 1);
        frameElement.appendChild(pathBadge);

        const frameLabel = document.createElement('span');
        frameLabel.className = 'pkg-presentation-frame-label';
        frameLabel.textContent = frame.title || `Frame ${frameIndex + 1}`;
        frameElement.appendChild(frameLabel);

        (frame.elements || []).forEach((element) => {
            const elementNode = renderPresentationElement(element, presentation.assets || {});
            elementNode.dataset.elementId = element.id;
            elementNode.classList.toggle('is-selected', frame.id === selectedFrameId && element.id === selectedElementId);
            frameElement.appendChild(elementNode);
        });

        stage.appendChild(frameElement);
    });
}

export function applyPresentationCamera(viewport, stage, canvas, frame = null, animate = true) {
    const viewportWidth = Math.max(320, viewport.clientWidth);
    const viewportHeight = Math.max(320, viewport.clientHeight);
    const bounds = frame || { x: 0, y: 0, width: canvas.width, height: canvas.height };
    const padding = frame ? 54 : 30;
    const scale = Math.min(
        (viewportWidth - (padding * 2)) / bounds.width,
        (viewportHeight - (padding * 2)) / bounds.height
    );
    const safeScale = clampPresentationNumber(scale, 0.08, frame ? 2.4 : 0.9, 0.3);
    const translateX = ((viewportWidth - (bounds.width * safeScale)) / 2) - (bounds.x * safeScale);
    const translateY = ((viewportHeight - (bounds.height * safeScale)) / 2) - (bounds.y * safeScale);

    stage.style.transition = animate ? 'transform 560ms cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
    stage.style.transform = `translate3d(${translateX}px, ${translateY}px, 0) scale(${safeScale})`;

    return safeScale;
}

function renderPresentationElement(element, assets) {
    const node = document.createElement('div');
    node.className = `pkg-presentation-element is-${element.type}`;
    node.style.left = `${element.x}px`;
    node.style.top = `${element.y}px`;
    node.style.width = `${element.width}px`;
    node.style.height = `${element.height}px`;
    node.style.transform = `rotate(${element.rotation || 0}deg)`;
    node.style.color = element.color || '#0f172a';
    node.style.backgroundColor = element.backgroundColor || 'transparent';

    if (element.type === 'text') {
        node.classList.add('pkg-presentation-text');
        node.style.fontSize = `${element.fontSize || 32}px`;
        node.style.textAlign = element.align || 'left';
        node.style.fontWeight = element.bold ? '700' : '400';
        node.textContent = element.text || 'Teks';
        return node;
    }

    if (element.type === 'image') {
        const image = document.createElement('img');
        image.src = assets[String(element.assetId)]?.url || '';
        image.alt = element.alt || 'Gambar presentasi';
        image.draggable = false;
        image.style.objectFit = element.fit || 'cover';
        node.appendChild(image);
        return node;
    }

    node.classList.add('pkg-presentation-diagram', `is-${element.diagramType || 'process'}`);
    const items = Array.isArray(element.items) && element.items.length ? element.items : ['Ide 1', 'Ide 2', 'Ide 3'];
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
