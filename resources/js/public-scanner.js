import { Html5QrcodeScanner } from 'html5-qrcode';

let html5QrcodeScanner = null;
let scannerTextObserver = null;
let scannerTextUpdateQueued = false;
let currentScannerMode = 'qr';

const scannerTextReplacements = new Map([
    ['Request Camera Permissions', 'Izinkan Kamera'],
    ['Scan an Image File', 'Pindai dari Gambar'],
    ['Choose Image', 'Pilih Gambar QR'],
    ['No image choosen', 'Belum ada gambar dipilih'],
    ['No image chosen', 'Belum ada gambar dipilih'],
    ['Or drop an image to scan', 'Atau letakkan gambar QR di sini'],
    ['Scan using camera directly', 'Pindai langsung dengan kamera'],
    ['Start Scanning', 'Mulai Pindai'],
    ['Stop Scanning', 'Berhenti Pindai'],
    ['Select Camera', 'Pilih Kamera'],
    ['Camera Permission', 'Izin Kamera'],
    ['Scanning', 'Memindai'],
]);

function showById(id) {
    document.getElementById(id)?.classList.remove('hidden');
}

function hideById(id) {
    document.getElementById(id)?.classList.add('hidden');
}

window.clearScannerMessages = function clearScannerMessages() {
    hideById('success-message');
    hideById('error-message');
};

window.showScannerMode = function showScannerMode(mode) {
    currentScannerMode = mode === 'face' ? 'face' : 'qr';

    if (currentScannerMode === 'face') {
        window.stopScanning();
    } else if (typeof window.stopFaceAttendance === 'function') {
        window.stopFaceAttendance();
    }

    document.querySelectorAll('[data-scanner-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.scannerPanel !== currentScannerMode);
    });

    document.querySelectorAll('[data-scanner-mode-button]').forEach((button) => {
        button.classList.toggle('scanner-mode-button-active', button.dataset.scannerModeButton === currentScannerMode);
    });

    window.clearScannerMessages();
};

window.startScanning = function startScanning() {
    window.clearScannerMessages();
    hideById('start-section');
    showById('scanner-section');

    try {
        html5QrcodeScanner = new Html5QrcodeScanner(
            'reader',
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                disableFlip: false,
            },
            false
        );

        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        startScannerTextLocalization();
    } catch (error) {
        console.error('Error:', error);
        window.stopScanning();
        window.showError('Pemindai gagal dimuat. Muat ulang halaman lalu coba lagi.');
    }
};

function onScanSuccess(decodedText) {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
    window.stopScanning();

    fetch('/qr/scan', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ token: decodedText }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.showSuccess(data.message || 'Presensi berhasil dicatat!');
            } else {
                window.showError(data.detail || data.message || 'Gagal memproses kode QR');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            window.showError('Terjadi kesalahan saat memproses kode QR');
        });
}

function onScanFailure() {
    // Scanner continues until a QR code is detected or the user cancels.
}

window.stopScanning = function stopScanning() {
    stopScannerTextLocalization();

    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
    }

    hideById('scanner-section');
    showById('start-section');
};

window.showError = function showError(message) {
    hideById('success-message');
    const errorText = document.getElementById('error-text');
    if (errorText) {
        errorText.textContent = message;
    }
    showById('error-message');
};

window.closeError = function closeError() {
    hideById('error-message');
    if (currentScannerMode === 'face' && typeof window.resetFaceScanner === 'function') {
        window.resetFaceScanner();
    }
};

window.showSuccess = function showSuccess(message) {
    hideById('error-message');
    const successText = document.getElementById('success-text');
    if (successText) {
        successText.textContent = message;
    }
    showById('success-message');
};

window.resetScanner = function resetScanner() {
    stopScannerTextLocalization();
    window.clearScannerMessages();
    hideById('scanner-section');
    if (currentScannerMode === 'face' && typeof window.resetFaceScanner === 'function') {
        window.resetFaceScanner();
    } else {
        showById('start-section');
    }
};

function startScannerTextLocalization() {
    const reader = document.getElementById('reader');

    if (!reader) {
        return;
    }

    stopScannerTextLocalization();
    queueScannerTextLocalization();

    scannerTextObserver = new MutationObserver(queueScannerTextLocalization);
    scannerTextObserver.observe(reader, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true,
        attributeFilter: ['value', 'aria-label', 'title'],
    });
}

function stopScannerTextLocalization() {
    if (scannerTextObserver) {
        scannerTextObserver.disconnect();
        scannerTextObserver = null;
    }

    scannerTextUpdateQueued = false;
}

function queueScannerTextLocalization() {
    if (scannerTextUpdateQueued) {
        return;
    }

    scannerTextUpdateQueued = true;

    requestAnimationFrame(() => {
        scannerTextUpdateQueued = false;
        localizeScannerText();
    });
}

function localizeScannerText() {
    const reader = document.getElementById('reader');

    if (!reader) {
        return;
    }

    setElementText('html5-qrcode-button-camera-permission', 'Izinkan Kamera');
    setElementText('html5-qrcode-button-camera-start', 'Mulai Pindai');
    setElementText('html5-qrcode-button-camera-stop', 'Berhenti Pindai');
    setElementText('html5-qrcode-button-file-selection', 'Pilih Gambar QR');
    setElementText('reader__dashboard_section_swaplink', 'Pindai langsung dengan kamera');

    reader.querySelectorAll('input[type="file"]').forEach((input) => {
        input.setAttribute('aria-label', 'Pilih gambar QR untuk dipindai');
        input.setAttribute('title', 'Pilih gambar QR untuk dipindai');
    });

    const walker = document.createTreeWalker(reader, NodeFilter.SHOW_TEXT);
    const textNodes = [];

    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    textNodes.forEach((node) => {
        const translated = translateScannerText(node.nodeValue);

        if (translated !== node.nodeValue) {
            node.nodeValue = translated;
        }
    });
}

function setElementText(id, text) {
    const element = document.getElementById(id);

    if (element && element.textContent.trim() !== text) {
        element.textContent = text;
    }
}

function translateScannerText(value) {
    let translated = value;

    scannerTextReplacements.forEach((replacement, source) => {
        translated = translated.split(source).join(replacement);
    });

    return translated;
}
