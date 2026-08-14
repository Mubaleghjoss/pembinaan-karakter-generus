const roots = new Set();
const QR_PATTERN = /^(?:PKGQURAN:[0-9a-f-]{36}:[A-Za-z0-9]+|PKGQ:[0-9A-F]{32}:[0-9A-F]{32}|PKGQM:[0-9A-F]{32}:[0-9A-F]{32})$/i;
const A4_WIDTH = 1654;
const A4_HEIGHT = 2339;
const A4_LANDSCAPE_WIDTH = 2339;
const A4_LANDSCAPE_HEIGHT = 1654;
let cvPromise;

function loadOpenCv() {
    if (!cvPromise) {
        cvPromise = import('@techstark/opencv-js').then((module) => module.default || module).then(async (candidate) => {
            const cv = candidate?.default || candidate;
            if (cv?.then) return cv;
            if (cv?.Mat) return cv;
            await new Promise((resolve, reject) => {
                const timeout = window.setTimeout(() => reject(new Error('OpenCV tidak siap.')), 20000);
                cv.onRuntimeInitialized = () => {
                    window.clearTimeout(timeout);
                    resolve();
                };
            });
            return cv;
        });
    }
    return cvPromise;
}

function setStatus(root, message, tone = 'neutral') {
    const status = root.querySelector('[data-quran-scan-status]');
    const tones = {
        neutral: 'border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200',
        progress: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
        error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200',
    };
    status.className = `mt-4 rounded-xl border p-4 text-sm ${tones[tone] || tones.neutral}`;
    status.textContent = message;
}

function setProgress(root, label, value) {
    const wrap = root.querySelector('[data-quran-progress-wrap]');
    const percentage = Math.max(0, Math.min(100, Math.round(value)));
    wrap.classList.remove('hidden');
    root.querySelector('[data-quran-progress-label]').textContent = label;
    root.querySelector('[data-quran-progress-value]').textContent = `${percentage}%`;
    root.querySelector('[data-quran-progress-bar]').style.width = `${percentage}%`;
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

export async function deskewDocument(source, documentType = 'weekly') {
    try {
        const cv = await loadOpenCv();
        window.cv = cv;
        const { default: Jscanify } = await import('./vendor/jscanify-client.js');
        const scanner = new Jscanify();
        const landscape = documentType === 'surah_map';
        const result = scanner.extractPaperWithMeta(source, landscape ? A4_LANDSCAPE_WIDTH : A4_WIDTH, landscape ? A4_LANDSCAPE_HEIGHT : A4_HEIGHT);
        const grid = result ? (landscape ? { detected: true, rowCount: 38 } : detectTableGrid(result.canvas)) : null;
        return result
            && grid
            ? { canvas: result.canvas, corrected: true, quality: result.areaRatio, grid }
            : { canvas: source, corrected: false, quality: 0 };
    } catch (error) {
        console.warn('Document correction unavailable:', error);
        return { canvas: source, corrected: false, quality: 0 };
    }
}

async function perspectiveFromCorners(source, points, documentType = 'weekly') {
    const cv = await loadOpenCv();
    const input = cv.imread(source);
    const output = new cv.Mat();
    const sourcePoints = cv.matFromArray(4, 1, cv.CV_32FC2, [
        points.tl.x * source.width, points.tl.y * source.height,
        points.tr.x * source.width, points.tr.y * source.height,
        points.bl.x * source.width, points.bl.y * source.height,
        points.br.x * source.width, points.br.y * source.height,
    ]);
    const width = documentType === 'surah_map' ? A4_LANDSCAPE_WIDTH : A4_WIDTH;
    const height = documentType === 'surah_map' ? A4_LANDSCAPE_HEIGHT : A4_HEIGHT;
    const destinationPoints = cv.matFromArray(4, 1, cv.CV_32FC2, [0, 0, width, 0, 0, height, width, height]);
    const transform = cv.getPerspectiveTransform(sourcePoints, destinationPoints);
    cv.warpPerspective(input, output, transform, new cv.Size(width, height), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar(255, 255, 255, 255));
    const canvas = document.createElement('canvas');
    cv.imshow(canvas, output);
    input.delete(); output.delete(); sourcePoints.delete(); destinationPoints.delete(); transform.delete();
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
        });
        [90, -90, 180].forEach((angle) => candidates.push({ canvas: rotateCanvas(source, angle), rotation: angle }));
    }

    let lastError;
    for (const candidate of candidates) {
        try {
            const payload = await decodeCandidate(reader, candidate.canvas);
            if (!QR_PATTERN.test(payload)) throw new Error('QR bukan lembar Tracer Bacaan Al-Qur’an PKG.');
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

function detectTableGrid(canvas) {
    const xLines = projectionLines(canvas, 'x', 0.025, 0.98, 0.26);
    const horizontal = arithmeticSequence(projectionLines(canvas, 'y', 0.16, 0.88, 0.42), 8);
    const rowCount = horizontal.length >= 13 ? 12 : (horizontal.length >= 8 ? 7 : 0);
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
    const first = canvas.height * (rowCount === 7 ? 0.254 : 0.207);
    const rowHeight = canvas.height * (rowCount === 7 ? 0.052 : 0.0354);
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

export async function recognizeRows(root, canvas, progressCallback = null) {
    if (root.dataset.ocrEnabled !== 'true') return [];
    const notify = progressCallback || ((label, value) => setProgress(root, label, value));
    const worker = await createOcrWorker(root, (label, progress) => notify(`Membaca angka: ${label}`, 35 + progress * 55));
    const fields = ['reading_date', 'page_start', 'page_end', 'surah_start', 'ayah_start', 'surah_end', 'ayah_end'];
    const grid = detectTableGrid(canvas) || fallbackGrid(canvas, 7);
    const rows = [];

    try {
        for (let row = 0; row < grid.rowCount; row += 1) {
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

async function recognizeKhatamMap(root, canvas, progressCallback = null) {
    const notify = progressCallback || ((label, value) => setProgress(root, label, value));
    const completedSurahs = [];
    const ambiguousSurahs = [];
    const columnStarts = [0.024, 0.346, 0.668];
    const rowTop = 0.267;
    const rowStep = 0.0129;

    for (let column = 0; column < 3; column += 1) {
        for (let row = 0; row < 38; row += 1) {
            const number = column * 38 + row + 1;
            const x = Math.round(canvas.width * (columnStarts[column] + 0.286));
            const y = Math.round(canvas.height * (rowTop + row * rowStep));
            const ratio = inkRatio(canvas, x, y, Math.max(5, canvas.width * 0.006), Math.max(5, canvas.height * 0.009));
            if (ratio >= 0.42) completedSurahs.push(number);
            else if (ratio >= 0.16) ambiguousSurahs.push(number);
        }
        notify('Membaca tanda surat', 38 + (column + 1) * 12);
    }

    const suggestion = {
        type: 'surah_map',
        completed_surahs: completedSurahs,
        ambiguous_surahs: ambiguousSurahs,
        active_surah: '',
        active_ayah: '',
        marked_on: localDateString(),
        confidence: {},
    };

    if (root.dataset.ocrEnabled !== 'true') return suggestion;
    let worker;
    try {
        worker = await createOcrWorker(root, (label, progress) => notify(`Membaca posisi aktif: ${label}`, 74 + progress * 20));
        const fields = [
            ['active_surah', 0.10, 0.225, 0.115, 0.047],
            ['active_ayah', 0.34, 0.225, 0.10, 0.047],
            ['marked_on', 0.57, 0.225, 0.17, 0.047],
        ];
        for (const [field, x, y, width, height] of fields) {
            const result = await worker.recognize(prepareCell(cropCanvas(canvas, x, y, width, height), 175));
            const confidence = Math.round(Number(result.data.confidence || 0));
            const raw = normalizeOcrText(result.data.text);
            suggestion.confidence[field] = confidence;
            if (confidence >= 60) suggestion[field] = field === 'marked_on' ? dateSuggestion(raw) : raw.replace(/\D/g, '');
        }
    } finally {
        if (worker) await worker.terminate();
    }

    return suggestion;
}

function makeFileFromCanvas(canvas, name) {
    return new Promise((resolve, reject) => canvas.toBlob((blob) => {
        if (!blob) return reject(new Error('Hasil foto tidak dapat dibuat.'));
        resolve(new File([blob], name, { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.9));
}

function updateFileInput(input, file) {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
}

function stopCamera(root) {
    root._quranStream?.getTracks().forEach((track) => track.stop());
    root._quranStream = null;
    root.querySelector('[data-quran-camera-panel]')?.classList.add('hidden');
}

function initCrop(root, state) {
    const box = root.querySelector('[data-quran-crop-box]');
    const preview = root.querySelector('.pkg-quran-preview');
    let active = null;

    box.addEventListener('pointerdown', (event) => {
        const rect = box.getBoundingClientRect();
        active = {
            resize: event.clientX > rect.right - 30 && event.clientY > rect.bottom - 30,
            startX: event.clientX,
            startY: event.clientY,
            left: box.offsetLeft,
            top: box.offsetTop,
            width: box.offsetWidth,
            height: box.offsetHeight,
        };
        box.setPointerCapture(event.pointerId);
    });
    box.addEventListener('pointermove', (event) => {
        if (!active) return;
        const dx = event.clientX - active.startX;
        const dy = event.clientY - active.startY;
        if (active.resize) {
            const size = Math.max(64, Math.min(preview.clientWidth - active.left, active.width + Math.max(dx, dy)));
            box.style.width = `${size}px`;
            box.style.height = `${size}px`;
        } else {
            box.style.left = `${Math.max(0, Math.min(preview.clientWidth - active.width, active.left + dx))}px`;
            box.style.top = `${Math.max(0, Math.min(preview.clientHeight - active.height, active.top + dy))}px`;
            box.style.right = 'auto';
        }
    });
    box.addEventListener('pointerup', () => { active = null; });

    root.querySelector('[data-quran-crop-retry]').addEventListener('click', async () => {
        if (!state.source || !state.reader) return;
        const previewRect = preview.getBoundingClientRect();
        const boxRect = box.getBoundingClientRect();
        const crop = {
            x: (boxRect.left - previewRect.left) / previewRect.width,
            y: (boxRect.top - previewRect.top) / previewRect.height,
            width: boxRect.width / previewRect.width,
            height: boxRect.height / previewRect.height,
        };
        try {
            setStatus(root, 'Membaca ulang area QR...', 'progress');
            const qr = await readQr(state.reader, state.source, state.deskewed, crop);
            state.payload = qr.payload;
            state.documentType = qr.payload.toUpperCase().startsWith('PKGQM:') ? 'surah_map' : 'weekly';
            root.querySelector('[data-quran-sheet-payload]').value = state.payload;
            root.querySelector('[data-quran-manual-crop]').classList.add('hidden');
            box.classList.add('hidden');
            if (state.documentType === 'surah_map' && state.source.height > state.source.width) state.source = rotateCanvas(state.source, 90);
            const correction = await deskewDocument(state.source, state.documentType);
            state.deskewed = correction.canvas;
            state.corrected = correction.corrected;
            if (!state.corrected) {
                state.showCornerEditor();
                setStatus(root, 'QR terbaca. Atur empat sudut kertas sebelum angka dibaca.', 'progress');
                return;
            }
            await finishDocument(root, state);
        } catch {
            setStatus(root, 'QR masih belum terbaca. Besarkan kotak tepat di sekeliling QR atau ambil foto ulang dengan cahaya lebih baik.', 'error');
        }
    });
}

function initDocumentCorners(root, state) {
    const panel = root.querySelector('[data-quran-document-corners]');
    const stage = root.querySelector('[data-quran-corners-stage]');
    const image = root.querySelector('[data-quran-corners-image]');
    const points = { tl: { x: .07, y: .07 }, tr: { x: .93, y: .07 }, bl: { x: .07, y: .93 }, br: { x: .93, y: .93 } };

    root.querySelectorAll('[data-quran-corner]').forEach((handle) => {
        const key = handle.dataset.quranCorner;
        handle.addEventListener('pointerdown', (event) => handle.setPointerCapture(event.pointerId));
        handle.addEventListener('pointermove', (event) => {
            if (!handle.hasPointerCapture(event.pointerId)) return;
            const rect = stage.getBoundingClientRect();
            points[key] = {
                x: Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width)),
                y: Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height)),
            };
            handle.style.left = `${points[key].x * 100}%`;
            handle.style.top = `${points[key].y * 100}%`;
        });
    });

    root.querySelector('[data-quran-corners-apply]').addEventListener('click', async () => {
        try {
            setStatus(root, 'Meluruskan kertas dari empat sudut...', 'progress');
            state.deskewed = await perspectiveFromCorners(state.source, points, state.documentType);
            state.corrected = true;
            panel.classList.add('hidden');
            await finishDocument(root, state);
        } catch (error) {
            setStatus(root, error?.message || 'Sudut kertas belum dapat diproses. Atur kembali penandanya.', 'error');
        }
    });

    state.showCornerEditor = () => {
        image.src = state.source.toDataURL('image/jpeg', .86);
        panel.classList.remove('hidden');
    };
}

async function finishDocument(root, state) {
    setProgress(root, 'Menyiapkan pembacaan angka', 35);
    let suggestions = [];
    try {
        suggestions = state.documentType === 'surah_map'
            ? await recognizeKhatamMap(root, state.deskewed || state.source)
            : await recognizeRows(root, state.deskewed || state.source);
    } catch (error) {
        console.warn('OCR suggestion failed:', error);
        setStatus(root, 'QR berhasil dibaca. Pembacaan angka belum optimal; semua kolom tetap dapat diisi pada layar konfirmasi.', 'success');
    }
    root.querySelector('[data-quran-ocr-suggestion]').value = JSON.stringify(suggestions);
    const processedFile = await makeFileFromCanvas(state.deskewed || state.source, 'lembar-lurus.jpg');
    updateFileInput(root.querySelector('[data-quran-processed-file]'), processedFile);
    root.querySelector('[data-quran-scan-submit]').disabled = false;
    setProgress(root, 'Siap diperiksa', 100);
    const detectedCount = state.documentType === 'surah_map'
        ? suggestions.completed_surahs?.length || 0
        : suggestions.length;
    setStatus(root, state.documentType === 'surah_map'
        ? `${detectedCount} tanda surat terbaca. Unggah untuk memeriksa perubahan Peta Khatam.`
        : (detectedCount ? `${detectedCount} baris terdeteksi. Unggah untuk memeriksa setiap angka sebelum disimpan.` : 'QR valid terbaca. Unggah dan isi atau koreksi angka pada layar konfirmasi.'), 'success');
}

async function processFile(root, state, file) {
    state.file = file;
    state.payload = '';
    state.documentType = 'weekly';
    root.querySelector('[data-quran-sheet-payload]').value = '';
    root.querySelector('[data-quran-scan-submit]').disabled = true;
    root.querySelector('[data-quran-manual-crop]').classList.add('hidden');
    root.querySelector('[data-quran-crop-box]').classList.add('hidden');
    setStatus(root, 'Menyiapkan foto dan meluruskan dokumen...', 'progress');
    setProgress(root, 'Membuka foto', 10);
    const image = await imageFromFile(file);
    state.source = canvasFromImage(image);
    const previewImage = root.querySelector('[data-quran-preview-image]');
    previewImage.src = state.source.toDataURL('image/jpeg', 0.86);
    root.querySelector('[data-quran-preview-panel]').classList.remove('hidden');
    setProgress(root, 'Meluruskan kertas', 20);
    const correction = await deskewDocument(state.source, 'weekly');
    state.deskewed = correction.canvas;
    state.corrected = correction.corrected;
    setProgress(root, 'Membaca QR', 30);

    try {
        const qr = await readQr(state.reader, state.source, state.deskewed);
        state.documentType = qr.payload.toUpperCase().startsWith('PKGQM:') ? 'surah_map' : 'weekly';
        if (qr.rotation) {
            state.source = rotateCanvas(state.source, qr.rotation);
        }
        if (state.documentType === 'surah_map' && state.source.height > state.source.width) state.source = rotateCanvas(state.source, 90);
        if (state.documentType === 'weekly' && state.source.width > state.source.height) state.source = rotateCanvas(state.source, 90);
        const orientedCorrection = await deskewDocument(state.source, state.documentType);
        state.deskewed = orientedCorrection.canvas;
        state.corrected = orientedCorrection.corrected;
        state.payload = qr.payload;
        root.querySelector('[data-quran-sheet-payload]').value = state.payload;
        if (!state.corrected) {
            state.showCornerEditor();
            setStatus(root, 'QR terbaca, tetapi batas kertas belum cukup jelas. Atur empat sudut agar pembacaan angka lebih akurat.', 'progress');
            return;
        }
        await finishDocument(root, state);
    } catch (error) {
        root.querySelector('[data-quran-manual-crop]').classList.remove('hidden');
        root.querySelector('[data-quran-crop-box]').classList.remove('hidden');
        setStatus(root, 'QR belum terbaca otomatis. Geser kotak kuning tepat ke QR lalu tekan “Coba Baca Area QR”.', 'error');
    }
}

async function initRoot(root, index) {
    if (root.dataset.quranScanReady === 'true') return;
    root.dataset.quranScanReady = 'true';
    roots.add(root);
    const { Html5Qrcode } = await import('html5-qrcode');
    const hiddenReader = document.createElement('div');
    hiddenReader.id = `quran-hidden-reader-${index}`;
    hiddenReader.className = 'sr-only';
    root.appendChild(hiddenReader);
    const state = { reader: new Html5Qrcode(hiddenReader.id, { verbose: false }), source: null, deskewed: null, file: null, documentType: 'weekly' };
    root._quranState = state;
    initCrop(root, state);
    initDocumentCorners(root, state);

    const fileInput = root.querySelector('[data-quran-scan-file]');
    const video = root.querySelector('[data-quran-camera-video]');
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) return;
        try { await processFile(root, state, file); } catch (error) { setStatus(root, error?.message || 'Foto gagal diproses.', 'error'); }
    });

    root.querySelector('[data-quran-camera-open]').addEventListener('click', async () => {
        stopCamera(root);
        try {
            root._quranStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 2560 } },
                audio: false,
            });
            video.srcObject = root._quranStream;
            root.querySelector('[data-quran-camera-panel]').classList.remove('hidden');
            setStatus(root, 'Kamera aktif. Sejajarkan seluruh lembar lalu ambil foto.', 'success');
        } catch (error) {
            setStatus(root, 'Kamera tidak dapat dibuka. Izinkan kamera pada pengaturan situs atau gunakan Pilih dari Galeri.', 'error');
        }
    });
    root.querySelector('[data-quran-camera-close]').addEventListener('click', () => stopCamera(root));
    root.querySelector('[data-quran-camera-capture]').addEventListener('click', async () => {
        if (!video.videoWidth) return setStatus(root, 'Kamera belum siap. Tunggu sebentar lalu coba lagi.', 'error');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d', { alpha: false }).drawImage(video, 0, 0);
        const file = await makeFileFromCanvas(canvas, 'foto-lembar.jpg');
        updateFileInput(fileInput, file);
        stopCamera(root);
        await processFile(root, state, file);
    });
    root.querySelector('[data-quran-retake]').addEventListener('click', () => root.querySelector('[data-quran-camera-open]').click());
    root.querySelector('[data-quran-use-photo]').addEventListener('click', async () => {
        if (state.file) await processFile(root, state, state.file);
    });
}

function initPublicModes() {
    const container = document.querySelector('[data-public-scan-mode-root]');
    if (!container || container.dataset.modeReady === 'true') return;
    container.dataset.modeReady = 'true';
    const switchMode = (mode, updateUrl = true) => {
        document.querySelectorAll('[data-public-scan-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.publicScanPanel !== mode));
        container.querySelectorAll('[data-public-scan-mode]').forEach((button) => button.setAttribute('aria-selected', button.dataset.publicScanMode === mode ? 'true' : 'false'));
        if (mode !== 'quran') roots.forEach(stopCamera);
        if (updateUrl) {
            const url = new URL(window.location.href);
            if (mode === 'quran') url.searchParams.set('mode', 'quran'); else url.searchParams.delete('mode');
            url.hash = mode === 'quran' ? 'quran' : '';
            history.replaceState({}, '', url);
        }
    };
    container.querySelectorAll('[data-public-scan-mode]').forEach((button) => button.addEventListener('click', () => switchMode(button.dataset.publicScanMode)));
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
        return `<label><span class="mb-1 flex items-center justify-between gap-2 text-xs font-semibold"><span>${label}</span><span class="font-normal text-slate-500 dark:text-slate-400">${status}</span></span><input type="${type}" inputmode="${type === 'number' ? 'numeric' : 'text'}" name="${name}" value="${safeText(value)}" class="pkg-field min-h-11 ${className}" required ${attributes}></label>`;
    }

    function render() {
        rows.sort((a, b) => Number(a.row_number) - Number(b.row_number));
        rowsContainer.innerHTML = rows.map((row, index) => {
            const confidence = row.confidence || {};
            return `<fieldset class="pkg-panel p-4" data-confirm-row="${row.row_number}">
                <div class="mb-3 flex items-center justify-between gap-3"><legend class="font-bold">Baris ${row.row_number}</legend><button type="button" class="btn-danger min-h-11 px-3" data-remove-row="${row.row_number}">Hapus Baris</button></div>
                <input type="hidden" name="rows[${index}][row_number]" value="${row.row_number}">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    ${field('Tanggal', `rows[${index}][reading_date]`, 'date', row.reading_date, confidence.reading_date, 'max="'+localDateString()+'"')}
                    ${field('Hal. awal', `rows[${index}][page_start]`, 'number', row.page_start, confidence.page_start, 'min="1" max="1000"')}
                    ${field('Hal. akhir', `rows[${index}][page_end]`, 'number', row.page_end, confidence.page_end, 'min="1" max="1000"')}
                    <label><span class="mb-1 flex items-center justify-between gap-2 text-xs font-semibold"><span>Surat awal</span><span class="font-normal text-slate-500 dark:text-slate-400">${quality(confidence.surah_start)[1]}</span></span><select name="rows[${index}][surah_start]" class="pkg-field min-h-11 ${quality(confidence.surah_start)[0]}" required>${selectOptions(row.surah_start)}</select></label>
                    ${field('Ayat awal', `rows[${index}][ayah_start]`, 'number', row.ayah_start, confidence.ayah_start, 'min="1" max="286"')}
                    <label><span class="mb-1 flex items-center justify-between gap-2 text-xs font-semibold"><span>Surat akhir</span><span class="font-normal text-slate-500 dark:text-slate-400">${quality(confidence.surah_end)[1]}</span></span><select name="rows[${index}][surah_end]" class="pkg-field min-h-11 ${quality(confidence.surah_end)[0]}" required>${selectOptions(row.surah_end)}</select></label>
                    ${field('Ayat akhir', `rows[${index}][ayah_end]`, 'number', row.ayah_end, confidence.ayah_end, 'min="1" max="286"')}
                    <label class="col-span-2 sm:col-span-4"><span class="mb-1 block text-xs font-semibold">Catatan</span><input name="rows[${index}][notes]" value="${safeText(row.notes)}" maxlength="1000" class="pkg-field min-h-11"></label>
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
            const reread = await recognizeRows(root, source, (label, value) => { progress.textContent = `${label} ${Math.round(value)}%`; });
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

Promise.all([...document.querySelectorAll('[data-quran-scan-root]')].map((root, index) => initRoot(root, index)))
    .catch((error) => document.querySelectorAll('[data-quran-scan-root]').forEach((root) => setStatus(root, error?.message || 'Pemindai tidak dapat dimuat. Muat ulang halaman.', 'error')));
initPublicModes();
document.querySelectorAll('[data-quran-confirm-root]').forEach(initConfirmation);

window.addEventListener('pagehide', () => roots.forEach(stopCamera));
document.addEventListener('visibilitychange', () => {
    if (document.hidden) roots.forEach(stopCamera);
});
