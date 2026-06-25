@extends('layouts.app')

@section('title', 'Kartu Pamong - ' . $user->name)

@section('content')
@php
    $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
    $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
    $rawCardTitle = trim($cardSettings['card_title'] ?? 'KARTU IDENTITAS');
    $displayCardTitle = strcasecmp($rawCardTitle, 'KARTU PESERTA') === 0 ? 'KARTU IDENTITAS' : $rawCardTitle;
@endphp
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kartu Pamong</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $user->name ?? $user->username }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('pamong.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                Kembali
            </a>
            <button onclick="refreshQr()" id="refreshBtn" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh QR
            </button>
            <a href="{{ route('pamong-presensi.card.print', $user) }}" target="_blank" rel="noopener" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Mode Print KTP
            </a>
        </div>
    </div>

    <!-- Card Preview -->
    <div class="flex justify-center" id="card-wrapper">
        <div class="id-card">
            <div class="card-header">
                <div class="card-logo">
                    @if(!empty($cardSettings['card_logo']))
                        <img src="{{ Storage::url($cardSettings['card_logo']) }}" alt="Logo">
                    @elseif(!empty($siteSettings['site_logo']))
                        <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo">
                    @else
                        {{ substr($siteSettings['site_title'] ?? 'PKG', 0, 3) }}
                    @endif
                </div>
                <div class="card-title">
                    <h3>{{ $displayCardTitle }}</h3>
                    <p>{{ $cardSettings['card_subtitle'] ?? ($siteSettings['site_name'] ?? 'Pembinaan Karakter Generus') }}</p>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Left Side: Photo + Info (40%) -->
                <div class="left-section">
                    <div class="pamong-photo">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}">
                        @else
                            <svg fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                    </div>
                    
                    <div class="pamong-info">
                        <div class="pamong-name">{{ $user->name ?? $user->username }}</div>
                        <div class="info-row">
                            <span class="info-label">Username</span>
                            <span class="info-value">: {{ $user->username }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Role</span>
                            <span class="info-value">: {{ $user->operationalRoleLabel() }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: QR Code (60%) - BESAR -->
                <div class="qr-section" id="qr-container">
                    <img src="{{ $qrData['qr_image_base64'] }}" alt="QR Code" id="qr-image">
                </div>
            </div>
            
            <div class="card-footer">
                {{ $cardFooterText }}
            </div>
        </div>
    </div>

    <!-- Instructions -->
    @include('components.id-card-print-help')

    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Petunjuk Penggunaan</h3>
        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>Tunjukkan QR Code pada scanner untuk melakukan presensi</li>
            <li>QR Code pamong tidak memiliki masa kadaluarsa</li>
            <li>Klik "Refresh QR" jika ingin membuat QR Code baru</li>
            <li>Simpan kartu ini dengan baik</li>
        </ul>
    </div>
</div>

<style>
    /* Professional ID Card Design - Ukuran KTP Indonesia: 85.6mm x 54mm */
    .id-card {
        width: 85.6mm;
        height: 54mm;
        background: linear-gradient(135deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 84%, #0f172a) 100%);
        border-radius: 3mm;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    /* Decorative Elements */
    .id-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 80%;
        height: 120%;
        background: radial-gradient(ellipse, rgba(255,255,255,0.12) 0%, transparent 60%);
        pointer-events: none;
    }
    
    .id-card::after {
        content: '';
        position: absolute;
        bottom: -20%;
        left: -20%;
        width: 50%;
        height: 60%;
        background: radial-gradient(ellipse, rgba(0,0,0,0.08) 0%, transparent 60%);
        pointer-events: none;
    }
    
    .card-header {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        padding: 2mm 3mm;
        background: rgba(0,0,0,0.15);
        border-bottom: 0.3mm solid rgba(255,255,255,0.2);
    }
    
    .card-logo {
        width: 6mm;
        height: 6mm;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #065f46;
        font-size: 2.5mm;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .card-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 9999px;
    }
    
    .card-title {
        margin-left: 2mm;
        color: white;
    }
    
    .card-title h3 {
        font-size: 2.8mm;
        font-weight: 700;
        letter-spacing: 0.3mm;
        margin: 0;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    .card-title p {
        font-size: 1.6mm;
        opacity: 0.9;
        margin: 0.3mm 0 0 0;
    }
    
    .card-body {
        position: relative;
        z-index: 1;
        display: flex;
        padding: 2mm 3mm;
        height: calc(100% - 14mm);
        color: white;
    }
    
    /* Left Section: 38% - Photo + Info */
    .left-section {
        width: 38%;
        display: flex;
        flex-direction: column;
        gap: 1.5mm;
    }
    
    .pamong-photo {
        width: 100%;
        height: 18mm;
        background: rgba(255,255,255,0.95);
        border-radius: 1.5mm;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .pamong-photo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .pamong-photo svg {
        width: 10mm;
        height: 10mm;
        color: #9ca3af;
    }
    
    .pamong-info {
        flex: 1;
    }
    
    .pamong-name {
        font-size: 2.8mm;
        font-weight: 700;
        margin-bottom: 1mm;
        line-height: 1.2;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .info-row {
        display: flex;
        font-size: 1.8mm;
        margin-bottom: 0.5mm;
        line-height: 1.3;
    }
    
    .info-label {
        width: 12mm;
        opacity: 0.85;
        flex-shrink: 0;
    }
    
    .info-value {
        font-weight: 600;
    }
    
    /* Right Section: 62% - QR Code BESAR */
    .qr-section {
        width: 62%;
        margin-left: 2mm;
        background: white;
        border-radius: 2mm;
        padding: 1.5mm;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    .qr-section img {
        width: 100% !important;
        height: auto !important;
        max-height: 100%;
        object-fit: contain;
    }
    
    .card-footer {
        position: absolute;
        bottom: 1mm;
        left: 3mm;
        right: 3mm;
        font-size: 1.4mm;
        color: rgba(255,255,255,0.8);
        z-index: 1;
        opacity: 0.7;
        text-align: center;
    }
    
    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #card-wrapper, #card-wrapper * {
            visibility: visible;
        }
        #card-wrapper {
            position: absolute;
            left: 0;
            top: 0;
        }
        .id-card {
            box-shadow: none;
        }
    }
</style>

@push('scripts')
<script>
function refreshQr() {
    const btn = document.getElementById('refreshBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Loading...';
    
    fetch('{{ route("pamong-presensi.refresh-qr", $user) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('qr-image').src = data.data.qr_image_base64;
            window.showNotification('QR Code berhasil di-refresh', 'success');
        } else {
            window.showNotification(data.message || 'Gagal refresh QR Code', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.showNotification('Terjadi kesalahan saat refresh QR Code', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Refresh QR';
    });
}
</script>
@endpush
@endsection
