import { fetchWithFreshCsrf, refreshCsrfToken } from './csrf-session';

const roots = new Set();
const QR_PATTERN = /^(?:PKGQURAN:[0-9a-f-]{36}:[A-Za-z0-9]+|PKGQ:[0-9A-F]{32}:[0-9A-F]{32}|PKGQMB:[0-9A-F]{32}:[0-9A-F]{32}|PKGQM:[0-9A-F]{32}:[0-9A-F]{32})$/i;

function uuidFromHex(value) {
    return `${value.slice(0, 8)}-${value.slice(8, 12)}-${value.slice(12, 16)}-${value.slice(16, 20)}-${value.slice(20)}`;
}

export function payloadFromPublicCode(code) {
    if (!/^[A-Za-z0-9_-]{44}$/.test(String(code || ''))) return null;
    try {
        const binary = atob(String(code).replace(/-/g, '+').replace(/_/g, '/'));
        if (binary.length !== 33) return null;
        const prefix = { 1: 'PKGQMB', 2: 'PKGQ', 3: 'PKGQM' }[binary.charCodeAt(0)];
        if (!prefix) return null;
        const bytes = [...binary].map((character) => character.charCodeAt(0).toString(16).padStart(2, '0'));
        const uuid = uuidFromHex(bytes.slice(1, 17).join(''));
        const token = bytes.slice(17, 33).join('');
        return `${prefix}:${uuid.replace(/-/g, '').toUpperCase()}:${token.toUpperCase()}`;
    } catch {
        return null;
    }
}

export function normalizeQrPayload(value) {
    const candidate = String(value || '').trim();
    if (QR_PATTERN.test(candidate)) return candidate;
    // PDF QR codes use the compact public code directly; legacy payloads and URLs remain valid.
    const publicPayload = payloadFromPublicCode(candidate);
    if (publicPayload) return publicPayload;
    try {
        const url = new URL(candidate, window.location.origin);
        const match = url.pathname.match(/^\/sq\/([A-Za-z0-9_-]{44})\/?$/);
        return match ? payloadFromPublicCode(match[1]) : null;
    } catch {
        return null;
    }
}

function isChromeBrowser(userAgent = navigator.userAgent || '') {
    // Chrome Android and desktop advertise Chrome; exclude Chromium-based competitors.
    return /(?:Chrome|CriOS)\/\d+/i.test(userAgent)
        && !/(?:Edg|EdgA|EdgiOS|OPR|Opera|SamsungBrowser|UCBrowser|YaBrowser)\//i.test(userAgent);
}

function showChromeNotices() {
    if (isChromeBrowser()) return;
    document.querySelectorAll('[data-quran-chrome-notice]').forEach((notice) => notice.classList.remove('hidden'));
}

function setQuickStatus(root, message, tone = 'neutral') {
    const status = root.querySelector('[data-quran-quick-status]');
    if (!status) return;
    const tones = {
        neutral: 'border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200',
        progress: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
        error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200',
    };
    status.className = `mt-4 rounded-xl border p-4 text-sm ${tones[tone] || tones.neutral}`;
    status.textContent = message;
}

async function responsePayload(response) {
    const payload = await response.json().catch(() => ({}));
    if (response.ok) return payload;
    const validation = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
    const error = new Error(validation || payload.message || 'Permintaan tidak dapat diproses.');
    error.status = response.status;
    throw error;
}

function barcodeStorageKey(root) {
    return `pkg-quran-barcode:${root.dataset.barcodeIdentifyUrl}`;
}

function cachedBarcode(root) {
    try {
        const cached = JSON.parse(sessionStorage.getItem(barcodeStorageKey(root)) || 'null');
        if (!cached?.payload || Number(cached.savedAt || 0) < Date.now() - 30 * 60 * 1000) return null;
        return cached.payload;
    } catch {
        return null;
    }
}

async function initQuickScanner(root, state, Html5Qrcode, index) {
    const readerElement = root.querySelector('[data-quran-quick-reader]');
    readerElement.id ||= `quran-quick-reader-${index}`;
    const quickReader = new Html5Qrcode(readerElement.id, { verbose: false });
    const cameraPanel = root.querySelector('[data-quran-quick-camera-panel]');
    const form = root.querySelector('[data-quran-quick-form]');
    const errorBox = root.querySelector('[data-quran-quick-errors]');
    let cameraRunning = false;
    let identifying = false;

    const stopQuickCamera = async () => {
        if (!cameraRunning) return;
        cameraRunning = false;
        await quickReader.stop().catch(() => {});
        cameraPanel.classList.add('hidden');
    };
    state.stopQuickCamera = stopQuickCamera;

    const identify = async (rawPayload) => {
        const payload = normalizeQrPayload(rawPayload);
        if (!payload) throw new Error('Barcode bukan lembar Tracer Bacaan Al-Qur\'an PKG.');
        if (identifying) return;
        identifying = true;
        setQuickStatus(root, 'Barcode terbaca. Memeriksa identitas Generus...', 'progress');
        try {
            const response = await fetchWithFreshCsrf(root.dataset.barcodeIdentifyUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ sheet_payload: payload }),
            }, { refreshBefore: true });
            const result = await responsePayload(response);
            sessionStorage.setItem(barcodeStorageKey(root), JSON.stringify({ payload, savedAt: Date.now() }));
            form.querySelector('[data-quran-flow-id]').value = result.flow_id;
            form.querySelector('[data-quran-student-name]').textContent = result.student.name;
            form.querySelector('[data-quran-student-nis]').textContent = result.student.masked_nis;
            form.querySelector('[data-quran-student-grade]').textContent = result.student.school_grade;
            form.querySelector('[data-quran-student-group]').textContent = result.student.group;
            form.classList.remove('hidden');
            errorBox.classList.add('hidden');
            setQuickStatus(root, 'Identitas sesuai. Isi surat dan ayat, lalu simpan.', 'success');
            return result;
        } finally {
            identifying = false;
        }
    };

    const identifySafely = async (payload) => {
        try { await identify(payload); } catch (error) { setQuickStatus(root, error.message || 'Barcode tidak dapat dikenali.', 'error'); }
    };

    root.querySelector('[data-quran-quick-camera-open]').addEventListener('click', async () => {
        await stopQuickCamera();
        cameraPanel.classList.remove('hidden');
        setQuickStatus(root, 'Kamera aktif. Arahkan ke barcode pada lembar.', 'progress');
        try {
            cameraRunning = true;
            await quickReader.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: (width, height) => ({ width: Math.min(width, height) * .72, height: Math.min(width, height) * .72 }) },
                async (decodedText) => {
                    await stopQuickCamera();
                    await identifySafely(decodedText);
                },
                () => {},
            );
        } catch {
            cameraRunning = false;
            cameraPanel.classList.add('hidden');
            setQuickStatus(root, 'Kamera tidak dapat dibuka. Izinkan kamera atau pilih gambar barcode.', 'error');
        }
    });
    root.querySelector('[data-quran-quick-camera-close]').addEventListener('click', stopQuickCamera);
    root.querySelector('[data-quran-quick-file]').addEventListener('change', async (event) => {
        const file = event.currentTarget.files?.[0];
        if (!file) return;
        await stopQuickCamera();
        setQuickStatus(root, 'Membaca barcode dari gambar...', 'progress');
        try {
            const decoded = await scanQuickFile(quickReader, file);
            await identify(decoded);
        } catch (error) {
            setQuickStatus(root, error.message?.includes('Tracer') ? error.message : 'Barcode belum terbaca dari gambar, termasuk setelah memperbesar area QR. Pastikan QR terlihat jelas lalu coba lagi.', 'error');
        } finally {
            event.currentTarget.value = '';
        }
    });

    const cross = form.querySelector('[data-quran-cross-surah]');
    const endWrap = form.querySelector('[data-quran-end-surah-wrap]');
    cross.addEventListener('change', () => {
        endWrap.classList.toggle('hidden', !cross.checked);
        endWrap.querySelector('select').required = cross.checked;
    });

    const submit = async (allowRecovery = true) => {
        const button = form.querySelector('[data-quran-quick-submit]');
        button.disabled = true;
        button.textContent = 'Menyimpan...';
        errorBox.classList.add('hidden');
        try {
            const data = new FormData(form);
            data.set('cross_surah', cross.checked ? '1' : '0');
            const response = await fetchWithFreshCsrf(root.dataset.barcodeStoreUrl, {
                method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: data,
            }, { refreshBefore: true });
            const result = await responsePayload(response);
            sessionStorage.removeItem(barcodeStorageKey(root));
            setQuickStatus(root, result.message, 'success');
            window.location.assign(result.redirect);
        } catch (error) {
            if (allowRecovery && error.status === 422 && /sesi barcode/i.test(error.message) && cachedBarcode(root)) {
                await identify(cachedBarcode(root));
                return submit(false);
            }
            errorBox.textContent = error.message || 'Catatan belum dapat disimpan.';
            errorBox.classList.remove('hidden');
            errorBox.scrollIntoView({ block: 'nearest' });
        } finally {
            button.disabled = false;
            button.textContent = 'Simpan Catatan Bacaan';
        }
    };
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        submit();
    });

    const initial = normalizeQrPayload(root.dataset.prefilledPayload || '') || cachedBarcode(root);
    if (initial) identifySafely(initial);
}

export async function scanQuickFile(reader, file, {
    loadImage = imageFromFile,
    makeCanvas = canvasFromImage,
    read = readQr,
} = {}) {
    try {
        // Keep the inexpensive full-image decoder attempt for close-up QR photos.
        return await reader.scanFile(file, true);
    } catch {
        const image = await loadImage(file);
        const qr = await read(reader, makeCanvas(image));
        return qr.payload;
    }
}

async function imageFromFile(file) {
    const url = URL.createObjectURL(file);
    try {
        const image = new Image();
        image.decoding = 'async';
        image.src = url;
        await image.decode();
        return image;
    } finally {
        URL.revokeObjectURL(url);
    }
}

export function canvasFromImage(image, maxSide = 3200) {
    const scale = Math.min(1, maxSide / Math.max(image.naturalWidth, image.naturalHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
    canvas.getContext('2d', { alpha: false }).drawImage(image, 0, 0, canvas.width, canvas.height);
    return canvas;
}

function rotateCanvas(source, degrees) {
    if (!degrees) return source;
    const canvas = document.createElement('canvas');
    const swap = Math.abs(degrees) % 180 === 90;
    canvas.width = swap ? source.height : source.width;
    canvas.height = swap ? source.width : source.height;
    const context = canvas.getContext('2d', { alpha: false });
    context.translate(canvas.width / 2, canvas.height / 2);
    context.rotate((degrees * Math.PI) / 180);
    context.drawImage(source, -source.width / 2, -source.height / 2);
    return canvas;
}

function cropCanvas(source, xRatio, yRatio, widthRatio, heightRatio, upscale = 1) {
    const sx = Math.max(0, Math.round(source.width * xRatio));
    const sy = Math.max(0, Math.round(source.height * yRatio));
    const sw = Math.max(1, Math.min(source.width - sx, Math.round(source.width * widthRatio)));
    const sh = Math.max(1, Math.min(source.height - sy, Math.round(source.height * heightRatio)));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(sw * upscale);
    canvas.height = Math.round(sh * upscale);
    const context = canvas.getContext('2d', { alpha: false });
    context.imageSmoothingEnabled = false;
    context.drawImage(source, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
    return canvas;
}

async function decodeCandidate(reader, canvas) {
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) throw new Error('Foto tidak dapat diproses.');
    return reader.scanFile(new File([blob], 'candidate.png', { type: 'image/png' }), false);
}

async function readQr(reader, source, deskewed, manualCrop = null) {
    const candidates = [];
    if (manualCrop) {
        candidates.push({ canvas: cropCanvas(source, manualCrop.x, manualCrop.y, manualCrop.width, manualCrop.height, 3), rotation: 0 });
    } else {
        [source, deskewed].filter(Boolean).forEach((canvas) => {
            candidates.push({ canvas, rotation: 0 });
            candidates.push({ canvas: cropCanvas(canvas, 0.66, 0, 0.34, 0.3, 2.5), rotation: 0 });
            candidates.push({ canvas: cropCanvas(canvas, 0.58, 0, 0.42, 0.38, 2), rotation: 0 });
            candidates.push({ canvas: cropCanvas(canvas, 0.28, 0.22, 0.44, 0.5, 2.5), rotation: 0 });
        });
        [90, -90, 180].forEach((angle) => candidates.push({ canvas: rotateCanvas(source, angle), rotation: angle }));
    }

    let lastError;
    for (const candidate of candidates) {
        try {
            const decodedValue = await decodeCandidate(reader, candidate.canvas);
            const payload = normalizeQrPayload(decodedValue);
            if (!payload) throw new Error('QR bukan lembar Tracer Bacaan Al-Qur’an PKG.');
            return { payload, rotation: candidate.rotation };
        } catch (error) {
            lastError = error;
        }
    }
    throw lastError || new Error('QR belum terbaca.');
}

function normalizeOcrText(text) {
    return String(text || '').replace(/[Oo]/g, '0').replace(/[Il|]/g, '1').replace(/[^0-9/.-]/g, '');
}

function inkRatio(canvas, x, y, width, height) {
    const safeX = Math.max(0, Math.min(canvas.width - 1, x));
    const safeY = Math.max(0, Math.min(canvas.height - 1, y));
    const safeWidth = Math.max(1, Math.min(canvas.width - safeX, width));
    const safeHeight = Math.max(1, Math.min(canvas.height - safeY, height));
    const pixels = canvas.getContext('2d').getImageData(safeX, safeY, safeWidth, safeHeight).data;
    let dark = 0;
    let sampled = 0;
    for (let index = 0; index < pixels.length; index += 16) {
        const luminance = (pixels[index] + pixels[index + 1] + pixels[index + 2]) / 3;
        if (luminance < 165) dark += 1;
        sampled += 1;
    }
    return sampled > 0 ? dark / sampled : 0;
}

function projectionLines(canvas, axis, startRatio, endRatio, threshold) {
    const image = canvas.getContext('2d', { willReadFrequently: true }).getImageData(0, 0, canvas.width, canvas.height);
    const limit = axis === 'x' ? canvas.width : canvas.height;
    const crossLimit = axis === 'x' ? canvas.height : canvas.width;
    const start = Math.round(limit * startRatio);
    const end = Math.round(limit * endRatio);
    const crossStart = Math.round(crossLimit * (axis === 'x' ? 0.19 : 0.04));
    const crossEnd = Math.round(crossLimit * (axis === 'x' ? 0.78 : 0.96));
    const groups = [];
    let active = [];

    for (let position = start; position < end; position += 1) {
        let dark = 0;
        let samples = 0;
        for (let cross = crossStart; cross < crossEnd; cross += 3) {
            const x = axis === 'x' ? position : cross;
            const y = axis === 'x' ? cross : position;
            const offset = (y * canvas.width + x) * 4;
            if ((image.data[offset] + image.data[offset + 1] + image.data[offset + 2]) / 3 < 105) dark += 1;
            samples += 1;
        }
        if (samples && dark / samples >= threshold) active.push(position);
        else if (active.length) { groups.push(active); active = []; }
    }
    if (active.length) groups.push(active);
    return groups.map((group) => Math.round(group.reduce((sum, value) => sum + value, 0) / group.length));
}

function arithmeticSequence(lines, minimumCount) {
    let best = [];
    for (let start = 0; start < lines.length - 1; start += 1) {
        for (let next = start + 1; next < Math.min(lines.length, start + 4); next += 1) {
            const gap = lines[next] - lines[start];
            if (gap < 25) continue;
            const sequence = [lines[start]];
            for (let target = lines[start] + gap; target <= lines.at(-1) + gap * 0.25; target += gap) {
                const match = lines.reduce((chosen, line) => Math.abs(line - target) < Math.abs(chosen - target) ? line : chosen, lines[0]);
                if (Math.abs(match - target) > gap * 0.25 || sequence.includes(match)) break;
                sequence.push(match);
            }
            if (sequence.length > best.length) best = sequence;
        }
    }
    return best.length >= minimumCount ? best : [];
}

function detectTableGrid(canvas, expectedRowCount = null) {
    const xLines = projectionLines(canvas, 'x', 0.025, 0.98, 0.26);
    const horizontal = arithmeticSequence(projectionLines(canvas, 'y', 0.15, 0.94, 0.42), expectedRowCount === 31 ? 32 : 8);
    const rowCount = expectedRowCount === 31
        ? (horizontal.length >= 32 ? 31 : 0)
        : (horizontal.length >= 13 ? 12 : (horizontal.length >= 8 ? 7 : 0));
    const rows = rowCount ? horizontal.slice(0, rowCount + 1) : [];
    if (xLines.length < 10 || rows.length < 8) return null;

    const left = xLines[0];
    const right = xLines.at(-1);
    const cumulative = [0, 0.04, 0.15, 0.23, 0.31, 0.41, 0.50, 0.60, 0.69, 1];
    const verticals = cumulative.map((ratio) => {
        const target = left + (right - left) * ratio;
        return xLines.reduce((chosen, line) => Math.abs(line - target) < Math.abs(chosen - target) ? line : chosen, xLines[0]);
    });
    if (new Set(verticals).size !== cumulative.length) return null;
    return { verticals, rows, rowCount, detected: true };
}

function fallbackGrid(canvas, rowCount = 7) {
    const left = canvas.width * 0.047;
    const right = canvas.width * 0.953;
    const cumulative = [0, 0.04, 0.15, 0.23, 0.31, 0.41, 0.50, 0.60, 0.69, 1];
    const verticals = cumulative.map((ratio) => Math.round(left + (right - left) * ratio));
    const first = canvas.height * (rowCount === 31 ? 0.27 : (rowCount === 7 ? 0.254 : 0.207));
    const rowHeight = canvas.height * (rowCount === 31 ? 0.0178 : (rowCount === 7 ? 0.052 : 0.0354));
    const rows = Array.from({ length: rowCount + 1 }, (_, index) => Math.round(first + rowHeight * index));
    return { verticals, rows, rowCount, detected: false };
}

function prepareCell(source, threshold = 178) {
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(120, source.width * 3);
    canvas.height = Math.max(72, source.height * 3);
    const context = canvas.getContext('2d', { alpha: false, willReadFrequently: true });
    context.fillStyle = '#fff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(source, 0, 0, canvas.width, canvas.height);
    const image = context.getImageData(0, 0, canvas.width, canvas.height);
    for (let index = 0; index < image.data.length; index += 4) {
        const gray = image.data[index] * 0.299 + image.data[index + 1] * 0.587 + image.data[index + 2] * 0.114;
        const value = gray < threshold ? 0 : 255;
        image.data[index] = value;
        image.data[index + 1] = value;
        image.data[index + 2] = value;
    }
    context.putImageData(image, 0, 0);
    return canvas;
}

function dateSuggestion(text) {
    const digits = normalizeOcrText(text).replace(/\D/g, '');
    if (digits.length !== 8) return '';
    const day = digits.slice(0, 2);
    const month = digits.slice(2, 4);
    const year = digits.slice(4, 8);
    const date = new Date(`${year}-${month}-${day}T00:00:00`);
    if (Number.isNaN(date.getTime())
        || date.getFullYear() !== Number(year)
        || date.getMonth() + 1 !== Number(month)
        || date.getDate() !== Number(day)) return '';
    return `${year}-${month}-${day}`;
}

function localDateString() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

async function createOcrWorker(root, onProgress) {
    const { createWorker, OEM } = await import('tesseract.js');
    const worker = await createWorker('eng', OEM.LSTM_ONLY, {
        workerPath: root.dataset.tesseractWorker,
        corePath: root.dataset.tesseractCore,
        langPath: root.dataset.tesseractLang,
        gzip: true,
        logger: ({ status, progress }) => onProgress(status, progress),
    });
    await worker.setParameters({
        tessedit_char_whitelist: '0123456789/',
        tessedit_pageseg_mode: '7',
        preserve_interword_spaces: '0',
    });
    return worker;
}

export async function recognizeRows(root, canvas, progressCallback = null, documentType = 'weekly') {
    if (root.dataset.ocrEnabled !== 'true') return [];
    const notify = progressCallback || (() => {});
    const worker = await createOcrWorker(root, (label, progress) => notify(`Membaca angka: ${label}`, 35 + progress * 55));
    const fields = ['reading_date', 'page_start', 'page_end', 'surah_start', 'ayah_start', 'surah_end', 'ayah_end'];
    const expectedRows = documentType === 'monthly' ? 31 : null;
    const grid = detectTableGrid(canvas, expectedRows) || fallbackGrid(canvas, expectedRows || 7);
    const rows = [];

    try {
        for (let row = 0; row < grid.rowCount; row += 1) {
            notify(`Membaca baris ${row + 1} dari ${grid.rowCount}`, 35 + (row / grid.rowCount) * 55);
            const y1 = grid.rows[row] + 5;
            const y2 = grid.rows[row + 1] - 5;
            if (inkRatio(canvas, grid.verticals[1] + 5, y1, grid.verticals[8] - grid.verticals[1] - 10, y2 - y1) < 0.006) continue;
            const suggestion = { row_number: row + 1, confidence: {}, raw: {} };
            let hasValue = false;
            for (let column = 0; column < fields.length; column += 1) {
                const field = fields[column];
                const x1 = grid.verticals[column + 1] + 5;
                const x2 = grid.verticals[column + 2] - 5;
                const crop = document.createElement('canvas');
                crop.width = Math.max(1, x2 - x1);
                crop.height = Math.max(1, y2 - y1);
                crop.getContext('2d', { alpha: false }).drawImage(canvas, x1, y1, crop.width, crop.height, 0, 0, crop.width, crop.height);
                let result = await worker.recognize(prepareCell(crop, 178));
                if (Number(result.data.confidence || 0) < 85) {
                    for (const threshold of [150, 205]) {
                        const alternative = await worker.recognize(prepareCell(crop, threshold));
                        if (Number(alternative.data.confidence || 0) > Number(result.data.confidence || 0)) result = alternative;
                    }
                }
                const raw = normalizeOcrText(result.data.text);
                const confidence = Math.round(Number(result.data.confidence || 0));
                suggestion.raw[field] = raw;
                suggestion.confidence[field] = confidence;
                let value = field === 'reading_date' ? dateSuggestion(raw) : raw.replace(/\D/g, '');
                if (confidence < 60) value = '';
                if (value) hasValue = true;
                suggestion[field] = value;
            }
            if (hasValue) rows.push({ ...suggestion, grid_detected: grid.detected });
        }
    } finally {
        await worker.terminate();
    }
    return rows;
}

async function initRoot(root, index) {
    if (root.dataset.quranScanReady === 'true') return;
    root.dataset.quranScanReady = 'true';
    roots.add(root);
    const { Html5Qrcode } = await import('html5-qrcode');
    const state = {};
    root._quranState = state;
    await initQuickScanner(root, state, Html5Qrcode, index);
}

function initPublicModes() {
    const container = document.querySelector('[data-public-scan-mode-root]');
    if (!container || container.dataset.modeReady === 'true') return;
    container.dataset.modeReady = 'true';
    const switchMode = (mode, updateUrl = true) => {
        document.querySelectorAll('[data-public-scan-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.publicScanPanel !== mode));
        container.querySelectorAll('[data-public-scan-mode]').forEach((button) => button.setAttribute('aria-selected', button.dataset.publicScanMode === mode ? 'true' : 'false'));
        if (mode !== 'quran') roots.forEach((root) => root._quranState?.stopQuickCamera?.());
        if (updateUrl) {
            const url = new URL(window.location.href);
            if (mode === 'quran') url.searchParams.set('mode', 'quran'); else url.searchParams.delete('mode');
            url.hash = mode === 'quran' ? 'quran' : '';
            history.replaceState({}, '', url);
        }
    };
    container.querySelectorAll('[data-public-scan-mode]').forEach((button) => button.addEventListener('click', (event) => {
        // Links remain usable without JavaScript, while JS switches modes without reloading the scanner.
        event.preventDefault();
        switchMode(button.dataset.publicScanMode);
    }));
    switchMode(container.dataset.initialMode || 'presence', false);
}

function safeText(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
}

function initConfirmation(root) {
    if (root.dataset.quranConfirmReady === 'true') return;
    root.dataset.quranConfirmReady = 'true';
    const rowsContainer = root.querySelector('[data-quran-confirm-rows]');
    const suggestions = JSON.parse(root.querySelector('[data-quran-confirm-suggestions]').textContent || '[]');
    const surahs = JSON.parse(root.querySelector('[data-quran-confirm-surahs]').textContent || '{}');
    const maxRows = Number(root.dataset.maxRows || 12);
    const image = root.querySelector('[data-quran-confirm-image]');
    const progress = root.querySelector('[data-quran-reread-progress]');
    let rows = suggestions.filter((row) => row && Number(row.row_number) >= 1 && Number(row.row_number) <= maxRows);

    function quality(score) {
        const number = Number(score || 0);
        if (number >= 85) return ['pkg-quran-confidence-high', 'Jelas'];
        if (number >= 60) return ['pkg-quran-confidence-medium', 'Periksa'];
        return ['', 'Isi manual'];
    }

    function firstAvailableRow() {
        const used = new Set(rows.map((row) => Number(row.row_number)));
        for (let row = 1; row <= maxRows; row += 1) if (!used.has(row)) return row;
        return null;
    }

    function selectOptions(selected) {
        return `<option value="">Pilih surat</option>${Object.entries(surahs).map(([number, label]) => `<option value="${number}" ${String(selected ?? '') === String(number) ? 'selected' : ''}>${safeText(label)}</option>`).join('')}`;
    }

    function field(label, name, type, value, confidence, attributes = '') {
        const [className, status] = quality(confidence);
        return `<label class="min-w-0 max-w-full"><span class="mb-1 flex min-w-0 flex-wrap items-center justify-between gap-1 text-xs font-semibold"><span>${label}</span><span class="font-normal text-slate-500 dark:text-slate-400">${status}</span></span><input type="${type}" inputmode="${type === 'number' ? 'numeric' : 'text'}" name="${name}" value="${safeText(value)}" class="pkg-field min-h-11 w-full min-w-0 max-w-full ${className}" required ${attributes}></label>`;
    }

    function render() {
        rows.sort((a, b) => Number(a.row_number) - Number(b.row_number));
        rowsContainer.innerHTML = rows.map((row, index) => {
            const confidence = row.confidence || {};
            return `<fieldset class="pkg-panel min-w-0 max-w-full overflow-hidden p-3 sm:p-4" data-confirm-row="${row.row_number}">
                <div class="mb-3 flex min-w-0 flex-wrap items-center justify-between gap-2"><legend class="font-bold">Baris ${row.row_number}</legend><button type="button" class="btn-danger min-h-11 max-w-full px-3" data-remove-row="${row.row_number}">Hapus Baris</button></div>
                <input type="hidden" name="rows[${index}][row_number]" value="${row.row_number}">
                <div class="pkg-quran-confirm-fields">
                    ${field('Tanggal', `rows[${index}][reading_date]`, 'date', row.reading_date, confidence.reading_date, 'max="'+localDateString()+'"')}
                    ${field('Hal. awal', `rows[${index}][page_start]`, 'number', row.page_start, confidence.page_start, 'min="1" max="1000"')}
                    ${field('Hal. akhir', `rows[${index}][page_end]`, 'number', row.page_end, confidence.page_end, 'min="1" max="1000"')}
                    <label class="min-w-0 max-w-full"><span class="mb-1 flex flex-wrap items-center justify-between gap-1 text-xs font-semibold"><span>Surat awal</span><span class="font-normal text-slate-500 dark:text-slate-400">${quality(confidence.surah_start)[1]}</span></span><select name="rows[${index}][surah_start]" class="pkg-field min-h-11 w-full min-w-0 max-w-full ${quality(confidence.surah_start)[0]}" required>${selectOptions(row.surah_start)}</select></label>
                    ${field('Ayat awal', `rows[${index}][ayah_start]`, 'number', row.ayah_start, confidence.ayah_start, 'min="1" max="286"')}
                    <label class="min-w-0 max-w-full"><span class="mb-1 flex flex-wrap items-center justify-between gap-1 text-xs font-semibold"><span>Surat akhir</span><span class="font-normal text-slate-500 dark:text-slate-400">${quality(confidence.surah_end)[1]}</span></span><select name="rows[${index}][surah_end]" class="pkg-field min-h-11 w-full min-w-0 max-w-full ${quality(confidence.surah_end)[0]}" required>${selectOptions(row.surah_end)}</select></label>
                    ${field('Ayat akhir', `rows[${index}][ayah_end]`, 'number', row.ayah_end, confidence.ayah_end, 'min="1" max="286"')}
                    <label class="min-w-0 max-w-full pkg-quran-confirm-note"><span class="mb-1 block text-xs font-semibold">Catatan</span><input name="rows[${index}][notes]" value="${safeText(row.notes)}" maxlength="1000" class="pkg-field min-h-11 w-full min-w-0 max-w-full"></label>
                </div>
            </fieldset>`;
        }).join('');
        root.querySelector('[data-quran-detected-count]').textContent = String(suggestions.length);
        const confidenceValues = suggestions.flatMap((row) => Object.values(row.confidence || {})).map(Number).filter(Number.isFinite);
        const medium = confidenceValues.filter((score) => score >= 60 && score < 85).length;
        const empty = rows.reduce((total, row) => total + ['reading_date','page_start','page_end','surah_start','ayah_start','surah_end','ayah_end'].filter((field) => !row[field]).length, 0);
        root.querySelector('[data-quran-quality-summary]').textContent = `${medium} nilai perlu diperiksa dan ${empty} kolom masih perlu diisi.`;
        root.querySelector('[data-quran-add-row]').disabled = rows.length >= maxRows;
        root.querySelector('[data-quran-no-rows]').classList.toggle('hidden', suggestions.length > 0);
        rowsContainer.querySelectorAll('[data-remove-row]').forEach((button) => button.addEventListener('click', () => {
            rows = captureRows();
            rows = rows.filter((row) => Number(row.row_number) !== Number(button.dataset.removeRow));
            if (!rows.length) rows.push({ row_number: firstAvailableRow() || 1, confidence: {} });
            render();
        }));
    }

    function captureRows() {
        return [...rowsContainer.querySelectorAll('[data-confirm-row]')].map((fieldset) => {
            const rowNumber = Number(fieldset.dataset.confirmRow);
            const previous = rows.find((row) => Number(row.row_number) === rowNumber) || {};
            const value = (field) => fieldset.querySelector(`[name$="[${field}]"]`)?.value ?? '';
            return {
                ...previous,
                row_number: rowNumber,
                reading_date: value('reading_date'),
                page_start: value('page_start'),
                page_end: value('page_end'),
                surah_start: value('surah_start'),
                ayah_start: value('ayah_start'),
                surah_end: value('surah_end'),
                ayah_end: value('ayah_end'),
                notes: value('notes'),
            };
        });
    }

    if (!rows.length) rows = [{ row_number: 1, confidence: {} }];
    render();

    root.querySelector('[data-quran-add-row]').addEventListener('click', () => {
        rows = captureRows();
        const rowNumber = firstAvailableRow();
        if (!rowNumber) return;
        rows.push({ row_number: rowNumber, confidence: {} });
        render();
        rowsContainer.lastElementChild?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
    });

    root.querySelectorAll('[data-quran-image-mode]').forEach((button) => button.addEventListener('click', () => {
        const source = button.dataset.quranImageMode === 'processed' ? root.dataset.imageProcessed : root.dataset.imageOriginal;
        if (source) image.src = source;
    }));

    root.querySelector('[data-quran-reread]').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        progress.classList.remove('hidden');
        progress.textContent = 'Menyiapkan ulang gambar...';
        try {
            if (!image.complete) await image.decode();
            const source = canvasFromImage(image);
            const reread = await recognizeRows(root, source, (label, value) => { progress.textContent = `${label} ${Math.round(value)}%`; }, root.dataset.documentType || 'weekly');
            if (!reread.length) throw new Error('Belum ada baris yang terbaca. Gunakan gambar asli atau isi satu baris secara manual.');
            rows = reread;
            suggestions.splice(0, suggestions.length, ...reread);
            root.querySelector('[data-quran-confirm-ocr]').value = JSON.stringify(reread);
            render();
            progress.textContent = `${reread.length} baris berhasil dibaca ulang. Tetap cocokkan dengan foto.`;
        } catch (error) {
            progress.textContent = error?.message || 'Pembacaan ulang gagal. Isi kolom secara manual.';
        } finally {
            button.disabled = false;
        }
    });

    root.querySelector('[data-quran-confirm-form]').addEventListener('submit', (event) => {
        const submit = event.currentTarget.querySelector('button[type="submit"], button:not([type])');
        if (!submit || submit.disabled) return;
        submit.disabled = true;
        submit.textContent = 'Menyimpan hasil...';
    });
}

showChromeNotices();

Promise.all([...document.querySelectorAll('[data-quran-scan-root]')].map((root, index) => initRoot(root, index)))
    .catch((error) => document.querySelectorAll('[data-quran-scan-root]').forEach((root) => setQuickStatus(root, error?.message || 'Pemindai tidak dapat dimuat. Muat ulang halaman.', 'error')));
initPublicModes();
document.querySelectorAll('[data-quran-confirm-root]').forEach(initConfirmation);

window.addEventListener('pagehide', () => roots.forEach((root) => root._quranState?.stopQuickCamera?.()));
window.addEventListener('pageshow', () => refreshCsrfToken().catch(() => {}));
document.addEventListener('visibilitychange', () => {
    if (document.hidden) roots.forEach((root) => root._quranState?.stopQuickCamera?.());
});
