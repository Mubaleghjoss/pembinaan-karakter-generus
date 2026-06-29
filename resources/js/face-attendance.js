import { Human } from '@vladmandic/human';

const instances = new Set();
let humanPromise = null;

function csrfToken(root) {
    return root.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function modelBasePath(root) {
    const basePath = root.dataset.modelBasePath || '/vendor/human/models';

    return basePath.endsWith('/') ? basePath : `${basePath}/`;
}

function setStatus(root, message) {
    const status = root.querySelector('[data-face-status]');

    if (status) {
        status.textContent = message;
    }
}

function setProgress(root, label, progress, state = 'active') {
    const safeProgress = Math.max(0, Math.min(100, Math.round(progress)));
    const phase = root.querySelector('[data-face-phase]');
    const progressText = root.querySelector('[data-face-progress]');
    const progressBar = root.querySelector('[data-face-progress-bar]');

    root.dataset.faceScanState = state;

    if (phase) {
        phase.textContent = label;
    }

    if (progressText) {
        progressText.textContent = `${safeProgress}%`;
    }

    if (progressBar) {
        progressBar.style.width = `${safeProgress}%`;
    }
}

function clearScannerMessages() {
    if (typeof window.clearScannerMessages === 'function') {
        window.clearScannerMessages();
    }
}

function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function getHuman(root) {
    if (!humanPromise) {
        const human = new Human({
            backend: 'webgl',
            modelBasePath: modelBasePath(root),
            filter: { enabled: true, equalization: true, flip: false },
            face: {
                enabled: true,
                detector: {
                    enabled: true,
                    maxDetected: 2,
                    minConfidence: 0.55,
                    rotation: false,
                },
                description: { enabled: true, minConfidence: 0.5 },
                mesh: { enabled: false },
                attention: { enabled: false },
                iris: { enabled: false },
                emotion: { enabled: false },
                antispoof: { enabled: false },
                liveness: { enabled: false },
            },
            body: { enabled: false },
            hand: { enabled: false },
            object: { enabled: false },
            segmentation: { enabled: false },
            gesture: { enabled: false },
        });

        humanPromise = human.load().then(async () => {
            await human.warmup();
            return human;
        });
    }

    return humanPromise;
}

function getLocation() {
    if (!navigator.geolocation) {
        return Promise.reject(new Error('Browser tidak mendukung lokasi GPS.'));
    }

    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy_meters: position.coords.accuracy,
                });
            },
            () => reject(new Error('Izin lokasi ditolak. Aktifkan lokasi/GPS lalu coba lagi.')),
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            }
        );
    });
}

async function startCamera(root) {
    const video = root.querySelector('[data-face-video]');

    if (!video) {
        throw new Error('Elemen kamera tidak ditemukan.');
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('Browser tidak mendukung akses kamera.');
    }

    const stream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'user',
            width: { ideal: 640 },
            height: { ideal: 480 },
        },
        audio: false,
    });

    video.srcObject = stream;
    await video.play();

    return stream;
}

function stopCamera(root) {
    const video = root.querySelector('[data-face-video]');
    const stream = video?.srcObject;

    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
    }

    if (video) {
        video.srcObject = null;
    }
}

function faceLooksCentered(face, video) {
    const box = face.box || [];

    if (box.length < 4 || !video.videoWidth || !video.videoHeight) {
        return false;
    }

    const [x, y, width, height] = box;
    const centerX = x + width / 2;
    const centerY = y + height / 2;
    const horizontalOffset = Math.abs(centerX - video.videoWidth / 2) / video.videoWidth;
    const verticalOffset = Math.abs(centerY - video.videoHeight / 2) / video.videoHeight;
    const sizeRatio = Math.min(width / video.videoWidth, height / video.videoHeight);

    return horizontalOffset < 0.22 && verticalOffset < 0.24 && sizeRatio > 0.18;
}

async function waitForStableFace(root, human) {
    const video = root.querySelector('[data-face-video]');
    let stableFrames = 0;

    for (let attempt = 0; attempt < 90; attempt += 1) {
        const result = await human.detect(video);
        const faces = result.face || [];

        if (faces.length === 0) {
            stableFrames = 0;
            setProgress(root, 'Mendeteksi wajah', 35);
            setStatus(root, 'Wajah belum terbaca. Posisikan wajah dan bahu di dalam garis panduan.');
        } else if (faces.length > 1) {
            stableFrames = 0;
            setProgress(root, 'Terlalu banyak wajah', 35);
            setStatus(root, 'Terdeteksi lebih dari satu wajah. Pastikan hanya satu orang di depan kamera.');
        } else if (!Array.isArray(faces[0].embedding) || faces[0].embedding.length === 0) {
            stableFrames = 0;
            setProgress(root, 'Membaca data wajah', 48);
            setStatus(root, 'Model wajah sedang membaca descriptor. Tetap diam sebentar.');
        } else if (!faceLooksCentered(faces[0], video)) {
            stableFrames = 0;
            setProgress(root, 'Merapikan posisi', 52);
            setStatus(root, 'Posisikan wajah dan bahu tepat di tengah garis panduan.');
        } else {
            stableFrames += 1;
            setProgress(root, 'Menstabilkan wajah', 60 + stableFrames * 10);
            setStatus(root, stableFrames >= 2 ? 'Wajah stabil. Foto otomatis sedang diambil.' : 'Wajah terbaca. Tetap diam sebentar.');

            if (stableFrames >= 3) {
                setProgress(root, 'Wajah siap', 82);
                return faces[0];
            }
        }

        await delay(450);
    }

    throw new Error('Wajah belum stabil. Perbaiki pencahayaan dan coba lagi.');
}

function captureImage(root) {
    const video = root.querySelector('[data-face-video]');
    const canvas = root.querySelector('[data-face-canvas]');

    if (!video || !canvas) {
        throw new Error('Kamera belum siap.');
    }

    const width = video.videoWidth || 640;
    const height = video.videoHeight || 480;
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d');
    context.save();
    context.translate(width, 0);
    context.scale(-1, 1);
    context.drawImage(video, 0, 0, width, height);
    context.restore();

    return canvas.toDataURL('image/jpeg', 0.82);
}

async function postJson(url, payload, token) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.success) {
        throw new Error(data.detail || data.message || 'Data wajah gagal diproses.');
    }

    return data;
}

class FaceAttendanceUi {
    constructor(root) {
        this.root = root;
        this.mode = root.hasAttribute('data-face-enrollment') ? 'enrollment' : 'scan';
        this.running = false;
        this.stream = null;
        this.bind();
        instances.add(this);
    }

    bind() {
        this.root.querySelector('[data-face-action="start-enrollment"]')?.addEventListener('click', () => this.enroll());
        this.root.querySelector('[data-face-action="start-scan"]')?.addEventListener('click', () => this.scan());
        this.root.querySelectorAll('[data-face-action="stop"]').forEach((button) => {
            button.addEventListener('click', () => this.stop());
        });
    }

    async prepare() {
        if (this.running) {
            return null;
        }

        this.running = true;
        if (this.mode === 'scan') {
            clearScannerMessages();
        }
        setProgress(this.root, 'Memuat model', 8);
        setStatus(this.root, 'Memuat model wajah. Tunggu sebentar.');
        const human = await getHuman(this.root);
        setProgress(this.root, 'Mengaktifkan kamera', 18);
        setStatus(this.root, 'Meminta izin kamera.');
        this.stream = await startCamera(this.root);
        setProgress(this.root, 'Kamera aktif', 28);

        return human;
    }

    async enroll() {
        try {
            const human = await this.prepare();

            if (!human) return;

            const face = await waitForStableFace(this.root, human);
            const referenceImage = captureImage(this.root);
            setProgress(this.root, 'Menyimpan wajah', 92);
            setStatus(this.root, 'Menyimpan data wajah awal.');

            const data = await postJson(this.root.dataset.enrollUrl, {
                descriptor: face.embedding,
                reference_image: referenceImage,
                client_captured_at: new Date().toISOString(),
            }, csrfToken(this.root));

            setStatus(this.root, data.message || 'Data wajah berhasil disimpan.');
            setProgress(this.root, 'Selesai', 100, 'success');
            this.stop(false);
            setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            this.stop(false);
            setProgress(this.root, 'Gagal', 100, 'error');
            setStatus(this.root, error.message || 'Pendaftaran wajah gagal.');
        }
    }

    async scan() {
        try {
            const human = await this.prepare();

            if (!human) return;

            setProgress(this.root, 'Mengecek lokasi', 32);
            setStatus(this.root, 'Meminta izin lokasi GPS.');
            const location = await getLocation();
            setProgress(this.root, 'Lokasi diterima', 45);
            const face = await waitForStableFace(this.root, human);
            const proofImage = captureImage(this.root);
            setProgress(this.root, 'Mengirim bukti', 92);
            setStatus(this.root, 'Mengirim bukti scan wajah.');

            const data = await postJson(this.root.dataset.scanUrl, {
                descriptor: face.embedding,
                proof_image: proofImage,
                location,
                client_captured_at: new Date().toISOString(),
            }, csrfToken(this.root));

            this.stop(false);
            setStatus(this.root, data.message || 'Presensi scan wajah berhasil.');
            setProgress(this.root, 'Selesai', 100, 'success');

            if (typeof window.showSuccess === 'function') {
                window.showSuccess(data.message || 'Presensi scan wajah berhasil.');
            }
        } catch (error) {
            this.stop(false);
            setProgress(this.root, 'Gagal', 100, 'error');
            setStatus(this.root, 'Scan wajah gagal. Baca pesan merah di atas, lalu tekan Mulai Scan Wajah untuk mencoba lagi.');

            if (this.mode === 'scan' && typeof window.showError === 'function') {
                window.showError(error.message || 'Scan wajah gagal.');
            }
        }
    }

    stop(updateStatus = true) {
        stopCamera(this.root);
        this.stream = null;
        this.running = false;

        if (updateStatus) {
            setProgress(this.root, 'Siap', 0, 'idle');
            setStatus(this.root, this.mode === 'scan'
                ? 'Klik mulai scan wajah. Sistem akan meminta izin kamera dan lokasi.'
                : 'Klik mulai kamera untuk memuat model wajah.');
        }
    }

    reset() {
        this.stop(true);
    }
}

document.querySelectorAll('[data-face-enrollment], [data-face-scanner]').forEach((root) => {
    new FaceAttendanceUi(root);
});

window.stopFaceAttendance = function stopFaceAttendance() {
    instances.forEach((instance) => instance.stop(true));
};

window.resetFaceScanner = function resetFaceScanner() {
    instances.forEach((instance) => {
        if (instance.mode === 'scan') {
            instance.reset();
        }
    });
};
