import * as pdfjs from 'pdfjs-dist';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

const viewers = new WeakMap();

class PdfViewer {
    constructor(root) {
        this.root = root;
        this.url = root.dataset.pdfUrl;
        this.preview = root.querySelector('[data-pdf-preview]');
        this.canvas = root.querySelector('[data-pdf-canvas]');
        this.status = root.querySelector('[data-pdf-status]');
        this.error = root.querySelector('[data-pdf-error]');
        this.toggleButton = root.querySelector('[data-pdf-toggle]');
        this.pageInput = root.querySelector('[data-pdf-page]');
        this.pageCount = root.querySelector('[data-pdf-page-count]');
        this.zoomLabel = root.querySelector('[data-pdf-zoom-label]');
        this.document = null;
        this.pageNumber = 1;
        this.zoom = 1;
        this.renderTask = null;
        this.resizeTimer = null;

        this.bindControls();
    }

    bindControls() {
        this.root.querySelector('[data-pdf-previous]')?.addEventListener('click', () => {
            if (this.pageNumber > 1) {
                this.pageNumber -= 1;
                this.renderPage();
            }
        });

        this.root.querySelector('[data-pdf-next]')?.addEventListener('click', () => {
            if (this.document && this.pageNumber < this.document.numPages) {
                this.pageNumber += 1;
                this.renderPage();
            }
        });

        this.root.querySelector('[data-pdf-zoom-out]')?.addEventListener('click', () => {
            this.setZoom(this.zoom - 0.25);
        });

        this.root.querySelector('[data-pdf-zoom-in]')?.addEventListener('click', () => {
            this.setZoom(this.zoom + 0.25);
        });

        this.pageInput?.addEventListener('change', () => {
            if (!this.document) {
                return;
            }

            const requestedPage = Number.parseInt(this.pageInput.value, 10);
            this.pageNumber = Math.min(Math.max(requestedPage || 1, 1), this.document.numPages);
            this.renderPage();
        });

        window.addEventListener('resize', () => {
            if (this.preview?.hidden || !this.document) {
                return;
            }

            window.clearTimeout(this.resizeTimer);
            this.resizeTimer = window.setTimeout(() => this.renderPage(), 150);
        });
    }

    async toggle() {
        if (!this.preview) {
            return;
        }

        const shouldOpen = this.preview.hidden;
        this.preview.hidden = !shouldOpen;
        this.toggleButton?.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        this.updateToggleLabel(shouldOpen);

        if (shouldOpen && !this.document) {
            await this.load();
        }
    }

    async load() {
        this.showStatus('Memuat PDF...');
        this.error?.classList.add('hidden');

        try {
            this.document = await pdfjs.getDocument({
                url: this.url,
                isEvalSupported: false,
            }).promise;
            this.pageCount.textContent = String(this.document.numPages);
            this.pageInput.max = String(this.document.numPages);
            await this.renderPage();
        } catch (error) {
            console.error('PDF gagal dimuat.', error);
            this.showStatus('');
            this.error?.classList.remove('hidden');
        }
    }

    async renderPage() {
        if (!this.document || !this.canvas || !this.preview) {
            return;
        }

        this.showStatus(`Memuat halaman ${this.pageNumber}...`);

        try {
            const page = await this.document.getPage(this.pageNumber);
            const baseViewport = page.getViewport({ scale: 1 });
            const containerWidth = Math.max(this.preview.clientWidth - 24, 280);
            const fitScale = Math.min(containerWidth / baseViewport.width, 1.5);
            const displayScale = fitScale * this.zoom;
            const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
            const renderViewport = page.getViewport({ scale: displayScale * pixelRatio });
            const displayViewport = page.getViewport({ scale: displayScale });
            const context = this.canvas.getContext('2d', { alpha: false });

            if (this.renderTask) {
                this.renderTask.cancel();
            }

            this.canvas.width = Math.floor(renderViewport.width);
            this.canvas.height = Math.floor(renderViewport.height);
            this.canvas.style.width = `${Math.floor(displayViewport.width)}px`;
            this.canvas.style.height = `${Math.floor(displayViewport.height)}px`;

            this.renderTask = page.render({
                canvasContext: context,
                viewport: renderViewport,
            });

            await this.renderTask.promise;
            this.renderTask = null;
            this.pageInput.value = String(this.pageNumber);
            this.showStatus('');
        } catch (error) {
            if (error?.name === 'RenderingCancelledException') {
                return;
            }

            console.error('Halaman PDF gagal dirender.', error);
            this.showStatus('');
            this.error?.classList.remove('hidden');
        }
    }

    setZoom(value) {
        this.zoom = Math.min(Math.max(value, 0.75), 2);
        this.zoomLabel.textContent = `${Math.round(this.zoom * 100)}%`;

        if (this.document) {
            this.renderPage();
        }
    }

    updateToggleLabel(isOpen) {
        const label = this.toggleButton?.querySelector('[data-pdf-toggle-label]');

        if (label) {
            label.textContent = isOpen ? 'Tutup PDF' : 'Buka PDF';
        }
    }

    showStatus(message) {
        if (!this.status) {
            return;
        }

        this.status.textContent = message;
        this.status.classList.toggle('hidden', message === '');
    }
}

export function togglePdfViewer(root) {
    if (!(root instanceof HTMLElement)) {
        return;
    }

    let viewer = viewers.get(root);

    if (!viewer) {
        viewer = new PdfViewer(root);
        viewers.set(root, viewer);
    }

    return viewer.toggle();
}
