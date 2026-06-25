import { Html5QrcodeScanner } from 'html5-qrcode';

let html5QrcodeScanner = null;

function showById(id) {
    document.getElementById(id)?.classList.remove('hidden');
}

function hideById(id) {
    document.getElementById(id)?.classList.add('hidden');
}

window.startScanning = function startScanning() {
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
    } catch (error) {
        console.error('Error:', error);
        window.stopScanning();
        window.showError('Scanner gagal dimuat. Muat ulang halaman lalu coba lagi.');
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
                window.showError(data.detail || data.message || 'Gagal memproses QR Code');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            window.showError('Terjadi kesalahan saat memproses QR Code');
        });
}

function onScanFailure() {
    // Scanner continues until a QR code is detected or the user cancels.
}

window.stopScanning = function stopScanning() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
    }

    hideById('scanner-section');
    showById('start-section');
};

window.showError = function showError(message) {
    const errorText = document.getElementById('error-text');
    if (errorText) {
        errorText.textContent = message;
    }
    showById('error-message');
};

window.closeError = function closeError() {
    hideById('error-message');
    window.resetScanner();
};

window.showSuccess = function showSuccess(message) {
    const successText = document.getElementById('success-text');
    if (successText) {
        successText.textContent = message;
    }
    showById('success-message');
};

window.resetScanner = function resetScanner() {
    hideById('success-message');
    hideById('error-message');
    hideById('scanner-section');
    showById('start-section');
};
