{{-- QR Scanner Modal --}}
<div id="qr-scanner-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="qr-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeQrScanner()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Scan QR Code</h3>
                    <button onclick="closeQrScanner()" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div id="qr-reader" class="w-full"></div>
                
                <div id="qr-result" class="mt-4 hidden">
                    <div class="rounded-2xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span id="qr-result-text" class="text-green-700 dark:text-green-300"></span>
                        </div>
                    </div>
                </div>
                
                <div id="qr-error" class="mt-4 hidden">
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span id="qr-error-text" class="text-red-700 dark:text-red-300"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-end">
                <button onclick="closeQrScanner()" class="pkg-btn-secondary px-4 py-2">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let html5QrCode = null;

function openQrScanner() {
    document.getElementById('qr-scanner-modal').classList.remove('hidden');
    document.getElementById('qr-result').classList.add('hidden');
    document.getElementById('qr-error').classList.add('hidden');
    
    setTimeout(() => {
        startQrScanner();
    }, 300);
}

function closeQrScanner() {
    if (html5QrCode) {
        html5QrCode.stop().catch(err => console.log('Error stopping scanner:', err));
    }
    document.getElementById('qr-scanner-modal').classList.add('hidden');
}

async function startQrScanner() {
    try {
        const { Html5Qrcode } = await window.loadHtml5Qrcode();
        html5QrCode = new Html5Qrcode("qr-reader");

        await html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanFailure
        );
    } catch (err) {
        console.error('Error starting scanner:', err);
        document.getElementById('qr-error').classList.remove('hidden');
        document.getElementById('qr-error-text').textContent = 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.';
    }
}

async function onScanSuccess(decodedText, decodedResult) {
    // Stop scanner
    if (html5QrCode) {
        await html5QrCode.stop();
    }
    
    // Process QR code
    try {
        const response = await fetch('/presensi/scan', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ qr_data: decodedText })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            document.getElementById('qr-result').classList.remove('hidden');
            document.getElementById('qr-error').classList.add('hidden');
            document.getElementById('qr-result-text').textContent = data.message || 'Presensi berhasil dicatat!';
            
            // Reload data after 2 seconds
            setTimeout(() => {
                closeQrScanner();
                location.reload();
            }, 2000);
        } else {
            document.getElementById('qr-error').classList.remove('hidden');
            document.getElementById('qr-result').classList.add('hidden');
            document.getElementById('qr-error-text').textContent = data.message || 'QR Code tidak valid';
            
            // Restart scanner after 2 seconds
            setTimeout(() => {
                startQrScanner();
            }, 2000);
        }
    } catch (error) {
        console.error('Error processing QR:', error);
        document.getElementById('qr-error').classList.remove('hidden');
        document.getElementById('qr-error-text').textContent = 'Terjadi kesalahan saat memproses QR Code';
        
        setTimeout(() => {
            startQrScanner();
        }, 2000);
    }
}

function onScanFailure(error) {
    // Ignore scan failures (no QR detected)
}

// Expose function globally
window.openQrScanner = openQrScanner;
</script>
@endpush

