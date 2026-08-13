const roots = new Set();
const QR_PATTERN = /^(?:PKGQURAN:[0-9a-f-]{36}:[A-Za-z0-9]+|PKGQ:[0-9A-F]{32}:[0-9A-F]{32})$/i;
const A4_WIDTH = 1654;
const A4_HEIGHT = 2339;
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

function canvasFromImage(image, maxSide = 3200) {
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

async function deskewDocument(source) {
    try {
        const cv = await loadOpenCv();
        window.cv = cv;
        const { default: Jscanify } = await import('./vendor/jscanify-client.js');
        const scanner = new Jscanify();
        return scanner.extractPaper(source, A4_WIDTH, A4_HEIGHT) || source;
    } catch (error) {
        console.warn('Document correction unavailable:', error);
        return source;
    }
}

async function decodeCandidate(reader, canvas) {
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) throw new Error('Foto tidak dapat diproses.');
    return reader.scanFile(new File([blob], 'candidate.png', { type: 'image/png' }), false);
}

async function readQr(reader, source, deskewed, manualCrop = null) {
    const candidates = [];
    if (manualCrop) {
        candidates.push(cropCanvas(source, manualCrop.x, manualCrop.y, manualCrop.width, manualCrop.height, 3));
    } else {
        [source, deskewed].filter(Boolean).forEach((canvas) => {
            candidates.push(canvas);
            candidates.push(cropCanvas(canvas, 0.66, 0, 0.34, 0.3, 2.5));
            candidates.push(cropCanvas(canvas, 0.58, 0, 0.42, 0.38, 2));
        });
        [90, -90, 180].forEach((angle) => candidates.push(rotateCanvas(source, angle)));
    }

    let lastError;
    for (const candidate of candidates) {
        try {
            const payload = await decodeCandidate(reader, candidate);
            if (!QR_PATTERN.test(payload)) throw new Error('QR bukan lembar Tracer Bacaan Al-Qur’an PKG.');
            return payload;
        } catch (error) {
            lastError = error;
        }
    }
    throw lastError || new Error('QR belum terbaca.');
}

function normalizeOcrText(text) {
    return String(text || '').replace(/[Oo]/g, '0').replace(/[Il|]/g, '1').replace(/[^0-9/.-]/g, '');
}

function rowHasInk(canvas, rowIndex) {
    const x = Math.round(canvas.width * 0.083);
    const y = Math.round(canvas.height * (0.209 + rowIndex * 0.0354));
    const width = Math.round(canvas.width * 0.615);
    const height = Math.max(8, Math.round(canvas.height * 0.030));
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
    return sampled > 0 && dark / sampled > 0.006;
}

function dateSuggestion(text) {
    const digits = normalizeOcrText(text).replace(/\D/g, '');
    if (digits.length !== 8) return '';
    const day = digits.slice(0, 2);
    const month = digits.slice(2, 4);
    const year = digits.slice(4, 8);
    const date = new Date(`${year}-${month}-${day}T00:00:00`);
    return Number.isNaN(date.getTime()) ? '' : `${year}-${month}-${day}`;
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

async function recognizeRows(root, canvas) {
    if (root.dataset.ocrEnabled !== 'true') return [];
    const worker = await createOcrWorker(root, (label, progress) => setProgress(root, `Membaca angka: ${label}`, 35 + progress * 55));
    const columns = [
        ['reading_date', 0.08, 0.176, 0.105],
        ['page_start', 0.185, 0.176, 0.077],
        ['page_end', 0.262, 0.176, 0.077],
        ['surah_start', 0.339, 0.176, 0.096],
        ['ayah_start', 0.435, 0.176, 0.086],
        ['surah_end', 0.521, 0.176, 0.096],
        ['ayah_end', 0.617, 0.176, 0.086],
    ];
    const rowStart = 0.207;
    const rowHeight = 0.0354;
    const rows = [];

    try {
        for (let row = 0; row < 12; row += 1) {
            if (!rowHasInk(canvas, row)) continue;
            const suggestion = { row_number: row + 1, confidence: {}, raw: {} };
            let hasValue = false;
            for (const [field, x, width] of columns) {
                const crop = cropCanvas(canvas, x, rowStart + row * rowHeight, width, rowHeight, 2);
                const result = await worker.recognize(crop);
                const raw = normalizeOcrText(result.data.text);
                const confidence = Math.round(Number(result.data.confidence || 0));
                suggestion.raw[field] = raw;
                suggestion.confidence[field] = confidence;
                let value = field === 'reading_date' ? dateSuggestion(raw) : raw.replace(/\D/g, '');
                if (confidence < 60) value = '';
                if (value) hasValue = true;
                suggestion[field] = value;
            }
            if (hasValue) rows.push(suggestion);
        }
    } finally {
        await worker.terminate();
    }
    return rows;
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
            state.payload = await readQr(state.reader, state.source, state.deskewed, crop);
            root.querySelector('[data-quran-sheet-payload]').value = state.payload;
            root.querySelector('[data-quran-manual-crop]').classList.add('hidden');
            box.classList.add('hidden');
            await finishDocument(root, state);
        } catch {
            setStatus(root, 'QR masih belum terbaca. Besarkan kotak tepat di sekeliling QR atau ambil foto ulang dengan cahaya lebih baik.', 'error');
        }
    });
}

async function finishDocument(root, state) {
    setProgress(root, 'Menyiapkan pembacaan angka', 35);
    let suggestions = [];
    try {
        suggestions = await recognizeRows(root, state.deskewed || state.source);
    } catch (error) {
        console.warn('OCR suggestion failed:', error);
        setStatus(root, 'QR berhasil dibaca. Pembacaan angka belum optimal; semua kolom tetap dapat diisi pada layar konfirmasi.', 'success');
    }
    root.querySelector('[data-quran-ocr-suggestion]').value = JSON.stringify(suggestions);
    const processedFile = await makeFileFromCanvas(state.deskewed || state.source, 'lembar-lurus.jpg');
    updateFileInput(root.querySelector('[data-quran-processed-file]'), processedFile);
    root.querySelector('[data-quran-scan-submit]').disabled = false;
    setProgress(root, 'Siap diperiksa', 100);
    setStatus(root, suggestions.length
        ? `${suggestions.length} baris terdeteksi. Unggah untuk memeriksa setiap angka sebelum disimpan.`
        : 'QR valid terbaca. Unggah dan isi atau koreksi angka pada layar konfirmasi.', 'success');
}

async function processFile(root, state, file) {
    state.file = file;
    state.payload = '';
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
    state.deskewed = await deskewDocument(state.source);
    setProgress(root, 'Membaca QR', 30);

    try {
        state.payload = await readQr(state.reader, state.source, state.deskewed);
        root.querySelector('[data-quran-sheet-payload]').value = state.payload;
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
    const state = { reader: new Html5Qrcode(hiddenReader.id, { verbose: false }), source: null, deskewed: null, file: null };
    root._quranState = state;
    initCrop(root, state);

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

Promise.all([...document.querySelectorAll('[data-quran-scan-root]')].map((root, index) => initRoot(root, index)))
    .catch((error) => document.querySelectorAll('[data-quran-scan-root]').forEach((root) => setStatus(root, error?.message || 'Pemindai tidak dapat dimuat. Muat ulang halaman.', 'error')));
initPublicModes();

window.addEventListener('pagehide', () => roots.forEach(stopCamera));
document.addEventListener('visibilitychange', () => {
    if (document.hidden) roots.forEach(stopCamera);
});
