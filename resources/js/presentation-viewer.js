import '../css/presentation.css';
import {
    applyPresentationCamera,
    bindPresentationTouchGestures,
    decodePresentationPayload,
    findPresentationFrame,
    panPresentationCamera,
    presentationPinchFactor,
    renderPresentationStage,
    zoomPresentationAtPoint,
} from './presentation-canvas';

const root = document.getElementById('presentation-viewer');

if (root) {
    const presentation = decodePresentationPayload(root.dataset.presentationPayload);
    const viewport = root.querySelector('[data-viewer-viewport]');
    const stage = root.querySelector('[data-viewer-stage]');
    const progress = root.querySelector('[data-viewer-progress]');
    const state = {
        mode: 'overview',
        currentIndex: -1,
        pendingIndex: 0,
        cameraScale: 1,
        focusScale: 1,
        overviewCandidate: false,
        manualCamera: false,
    };

    renderPresentationStage({ stage, presentation, overview: true, interactive: true });

    const updateCamera = (animate = true) => {
        const frame = state.mode === 'frame' ? presentation.canvas.frames[state.currentIndex] : null;
        state.cameraScale = applyPresentationCamera(viewport, stage, presentation.canvas, frame, animate);
        if (frame) state.focusScale = state.cameraScale;
        state.manualCamera = false;
        stage.querySelectorAll('[data-frame-id]').forEach((node) => {
            node.classList.toggle('is-active-view', frame?.id === node.dataset.frameId);
            node.classList.toggle('is-overview', !frame);
        });
        const locationLabel = frame
            ? `${state.currentIndex + 1} / ${presentation.canvas.frames.length} · ${frame.title}`
            : 'Overview';
        progress.textContent = `${locationLabel} · ${Math.round(state.cameraScale * 100)}%`;
    };

    const updateZoomProgress = () => {
        const frame = state.mode === 'frame' ? presentation.canvas.frames[state.currentIndex] : null;
        const locationLabel = frame
            ? `${state.currentIndex + 1} / ${presentation.canvas.frames.length} · ${frame.title}`
            : 'Overview';
        progress.textContent = `${locationLabel} · ${Math.round(state.cameraScale * 100)}%`;
    };

    const showOverview = (pendingIndex = state.currentIndex < 0 ? 0 : state.currentIndex + 1) => {
        state.mode = 'overview';
        state.pendingIndex = Math.max(0, Math.min(presentation.canvas.frames.length - 1, pendingIndex));
        updateCamera();
    };

    const showFrame = (index) => {
        if (!presentation.canvas.frames.length) return;
        state.currentIndex = Math.max(0, Math.min(presentation.canvas.frames.length - 1, index));
        state.mode = 'frame';
        state.pendingIndex = state.currentIndex + 1;
        updateCamera();
    };

    const next = () => {
        if (state.mode === 'overview') {
            showFrame(state.pendingIndex);
            return;
        }
        if (state.currentIndex >= presentation.canvas.frames.length - 1) {
            showOverview(0);
            return;
        }
        if (presentation.pathMode === 'overview_between') {
            showOverview(state.currentIndex + 1);
        } else {
            showFrame(state.currentIndex + 1);
        }
    };

    const previous = () => {
        if (state.mode === 'overview') {
            showFrame(Math.max(0, state.pendingIndex - 1));
            return;
        }
        if (state.currentIndex <= 0) {
            showOverview(0);
            return;
        }
        if (presentation.pathMode === 'overview_between') {
            showOverview(state.currentIndex - 1);
        } else {
            showFrame(state.currentIndex - 1);
        }
    };

    const touchGesture = bindPresentationTouchGestures(viewport, stage, {
        minimumScale: 0.03,
        maximumScale: 4,
        onStart: () => {
            state.overviewCandidate = false;
        },
        onUpdate: (scale) => {
            state.cameraScale = scale;
            state.manualCamera = true;
            if (state.mode === 'frame' && scale <= state.focusScale * 0.72) {
                state.overviewCandidate = true;
            }
            updateZoomProgress();
        },
        onEnd: () => {
            if (!state.overviewCandidate || state.mode !== 'frame') return;
            state.mode = 'overview';
            state.pendingIndex = Math.max(0, state.currentIndex);
            state.manualCamera = true;
            state.overviewCandidate = false;
            stage.querySelectorAll('[data-frame-id]').forEach((node) => {
                node.classList.remove('is-active-view');
                node.classList.add('is-overview');
            });
            updateZoomProgress();
        },
        onTap: (tap) => {
            if (state.mode !== 'overview') return;
            const frameNode = tap.target.closest?.('[data-frame-id]');
            if (!frameNode) return;
            const frame = findPresentationFrame(presentation, frameNode.dataset.frameId);
            if (frame) showFrame(presentation.canvas.frames.indexOf(frame));
        },
    });

    stage.addEventListener('click', (event) => {
        if (touchGesture.shouldSuppressTap()) return;
        if (state.mode !== 'overview') return;
        const frameNode = event.target.closest('[data-frame-id]');
        if (!frameNode) return;
        const frame = findPresentationFrame(presentation, frameNode.dataset.frameId);
        showFrame(presentation.canvas.frames.indexOf(frame));
    });

    root.querySelector('[data-viewer-next]').addEventListener('click', next);
    root.querySelector('[data-viewer-prev]').addEventListener('click', previous);
    root.querySelector('[data-viewer-home]').addEventListener('click', () => showOverview(0));
    root.querySelector('[data-viewer-fit]').addEventListener('click', () => updateCamera());
    root.querySelector('[data-viewer-fullscreen]').addEventListener('click', async () => {
        if (document.fullscreenElement) {
            await document.exitFullscreen();
        } else {
            await root.requestFullscreen();
        }
    });

    viewport.addEventListener('wheel', (event) => {
        event.preventDefault();
        if (event.ctrlKey || event.metaKey) {
            state.cameraScale = zoomPresentationAtPoint(
                viewport,
                stage,
                presentationPinchFactor(event.deltaY),
                event.clientX,
                event.clientY,
                { minimumScale: 0.03, maximumScale: 4 }
            );
        } else {
            state.cameraScale = panPresentationCamera(stage, -event.deltaX, -event.deltaY);
        }
        state.manualCamera = true;
        if ((event.ctrlKey || event.metaKey)
            && state.mode === 'frame'
            && state.cameraScale <= state.focusScale * 0.72) {
            state.mode = 'overview';
            state.pendingIndex = Math.max(0, state.currentIndex);
            stage.querySelectorAll('[data-frame-id]').forEach((node) => {
                node.classList.remove('is-active-view');
                node.classList.add('is-overview');
            });
        }
        updateZoomProgress();
    }, { passive: false });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight' || event.key === ' ') {
            event.preventDefault();
            next();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            previous();
        } else if (event.key === 'Home' || event.key.toLowerCase() === 'o') {
            event.preventDefault();
            showOverview(0);
        } else if (event.key === '0') {
            event.preventDefault();
            updateCamera();
        }
    });

    new ResizeObserver(() => {
        state.manualCamera = false;
        updateCamera(false);
    }).observe(viewport);
    requestAnimationFrame(() => updateCamera(false));
}
