import React, { useEffect, useState } from 'react';
import { Html5QrcodeScanner } from 'html5-qrcode';
import axios from 'axios';
import AttendanceSuccess from './AttendanceSuccess.jsx';

export default function QRScanner() {
    const [scanning, setScanning] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (scanning) {
            const scanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: { width: 250, height: 250 } },
                /* verbose= */ false
            );

            scanner.render(onScanSuccess, onScanFailure);

            function onScanSuccess(decodedText, decodedResult) {
                scanner.clear();
                setScanning(false);
                handleScan(decodedText);
            }

            function onScanFailure(error) {
                // handle scan failure
            }

            return () => {
                try {
                    scanner.clear();
                } catch (e) {
                    // ignore
                }
            };
        }
    }, [scanning]);

    const handleScan = async (token) => {
        try {
            const response = await axios.post('/qr/scan', { token });
            setResult(response.data);
        } catch (err) {
            setError(err.response?.data?.message || 'Terjadi kesalahan saat memproses QR Code.');
        }
    };

    const reset = () => {
        setResult(null);
        setError(null);
        setScanning(true);
    };

    if (result) {
        return (
            <AttendanceSuccess
                data={result}
                onClose={reset}
            />
        );
    }

    return (
        <div className="max-w-md mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div className="p-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center">
                <h2 className="text-2xl font-bold mb-1">Scan QR Code</h2>
                <p className="text-blue-100 text-sm">Arahkan kamera ke QR Code Peserta</p>
            </div>

            <div className="p-6">
                {error && (
                    <div className="mb-6 p-4 bg-red-50 text-red-600 rounded-xl border border-red-100 flex items-start gap-3 animate-pulse">
                        <div className="mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" x2="12" y1="8" y2="12" /><line x1="12" x2="12.01" y1="16" y2="16" /></svg>
                        </div>
                        <div className="flex-1">
                            <p className="font-bold text-sm uppercase tracking-wide mb-1">Gagal Memproses</p>
                            <p className="text-sm leading-relaxed text-red-700">{error}</p>
                        </div>
                        <button onClick={() => setError(null)} className="text-red-400 hover:text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                        </button>
                    </div>
                )}

                {!scanning && !error && (
                    <div className="text-center py-8">
                        <div className="mb-8 relative inline-block">
                            <div className="absolute inset-0 bg-blue-200 rounded-full animate-ping opacity-20"></div>
                            <div className="bg-blue-50 p-6 rounded-full relative z-10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600"><path d="M3 7V5a2 2 0 0 1 2-2h2" /><path d="M17 3h2a2 2 0 0 1 2 2v2" /><path d="M21 17v2a2 2 0 0 1-2 2h-2" /><path d="M7 21H5a2 2 0 0 1-2-2v-2" /><rect width="10" height="10" x="7" y="7" rx="2" /><path d="m16 16-.01-.01" /></svg>
                            </div>
                        </div>
                        <button
                            onClick={() => setScanning(true)}
                            className="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 flex items-center justify-center gap-3"
                        >
                            Mulai Scan Kamera
                        </button>
                        <p className="mt-4 text-sm text-gray-400">Pastikan browser diizinkan mengakses kamera</p>
                    </div>
                )}

                {scanning && (
                    <div className="relative">
                        <div id="reader" className="overflow-hidden rounded-xl border-2 border-blue-500 shadow-inner bg-black"></div>
                        <button
                            onClick={() => setScanning(false)}
                            className="mt-6 w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold transition-colors"
                        >
                            Batalkan
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
