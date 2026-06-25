<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
        $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Kartu Pamong - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 15px;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .print-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
            background: white;
            padding: 10px;
            border-radius: 8px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Card Grid */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            gap: 8mm;
            justify-content: center;
            max-width: 210mm;
            margin: 0 auto;
        }
        
        /* Professional ID Card Design */
        .id-card {
            width: 85.6mm;
            height: 54mm;
            border-radius: 3mm;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            page-break-inside: avoid;
            background: linear-gradient(135deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 84%, #0f172a) 100%);
        }
        
        /* Decorative Elements */
        .card-decoration {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        
        .card-decoration::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 120%;
            background: radial-gradient(ellipse, rgba(255,255,255,0.1) 0%, transparent 60%);
        }
        
        .card-decoration::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 50%;
            height: 60%;
            background: radial-gradient(ellipse, rgba(0,0,0,0.1) 0%, transparent 60%);
        }
        
        /* Header */
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
        
        .card-title {
            margin-left: 2mm;
            color: white;
        }
        
        .card-title h3 {
            font-size: 2.8mm;
            font-weight: 700;
            letter-spacing: 0.3mm;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .card-title p {
            font-size: 1.6mm;
            opacity: 0.9;
            margin-top: 0.3mm;
        }
        
        /* Body */
        .card-body {
            position: relative;
            z-index: 1;
            display: flex;
            padding: 2mm 3mm;
            height: calc(100% - 14mm);
        }
        
        /* Left Section - 38% */
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
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
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
            color: white;
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
            width: 10mm;
            opacity: 0.85;
            flex-shrink: 0;
        }
        
        .info-value {
            font-weight: 600;
        }
        
        /* Right Section - 62% QR Code */
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
        
        /* Footer */
        .card-footer {
            position: absolute;
            bottom: 1mm;
            left: 3mm;
            right: 3mm;
            font-size: 1.4mm;
            color: rgba(255,255,255,0.8);
            text-align: center;
            z-index: 1;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
            background: white;
            border-radius: 10px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .no-print, .print-info {
                display: none !important;
            }
            
            .card-grid {
                gap: 5mm;
            }
            
            .id-card {
                box-shadow: none;
                border: 0.2mm solid #ccc;
            }
            
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
        
        @media screen and (max-width: 600px) {
            .card-grid {
                grid-template-columns: 1fr;
                padding: 0 10px;
            }
            
            .id-card {
                width: 100%;
                height: auto;
                aspect-ratio: 85.6/54;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h2 style="margin-bottom: 10px; color: #333;">{{ $cardSettings['card_title'] ?? 'Kartu Identitas Pamong' }}</h2>
        <p style="margin-bottom: 15px; color: #666; font-size: 13px;">
            Ukuran kartu: 85.6mm x 54mm (Standar KTP Indonesia) | 2 kartu per baris
        </p>
        <button class="btn btn-primary" onclick="window.print()">
            Print Kartu Pamong
        </button>
        <a href="{{ route('pamong.qr.generate') }}" class="btn btn-secondary">
            Kembali
        </a>
    </div>
        <h2 style="margin-bottom: 10px; color: #333;">Kartu Identitas Pamong</h2>
    <div class="print-info">
        Total: {{ count($pamongList) }} pamong | 
        Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
            Print Kartu Pamong
    @if(count($pamongList) > 0)
    <div class="card-grid">
            Kembali
        @php $pamong = $item['user']; $qrData = $item['qr_data']; @endphp
        <div class="id-card">
            <div class="card-decoration"></div>
            
            <div class="card-header">
                <div class="card-logo">
                    @if(!empty($cardSettings['card_logo']))
                        <img src="{{ Storage::url($cardSettings['card_logo']) }}" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:9999px;">
                    @else
                        {{ substr($siteSettings['site_title'] ?? 'PKG', 0, 3) }}
                    @endif
                </div>
                <div class="card-title">
                    <h3>{{ $cardSettings['card_title'] ?? 'KARTU IDENTITAS PAMONG' }}</h3>
                    <p>{{ $cardSettings['card_subtitle'] ?? ($siteSettings['site_name'] ?? 'Pembinaan Karakter Generus') }}</p>
                </div>
            </div>
            
            <div class="card-body">
                <div class="left-section">
                    <div class="pamong-photo">
                        @if($pamong->avatar_url)
                            <img src="{{ $pamong->avatar_url }}" alt="{{ $pamong->name }}">
                        @else
                            <svg fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                    </div>
                    
                    <div class="pamong-info">
                        <div class="pamong-name">{{ $pamong->name ?? $pamong->username }}</div>
                        <div class="info-row">
                            <span class="info-label">Username</span>
                            <span class="info-value">: {{ $pamong->username }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Role</span>
                            <span class="info-value">: {{ $pamong->operationalRoleLabel() }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="qr-section">
                    <img src="{{ $qrData['qr_image_base64'] }}" alt="QR Code">
                </div>
            </div>
            
            <div class="card-footer">
                {{ $cardFooterText }}
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <p>Tidak ada pamong yang dipilih.</p>
        <a href="{{ route('pamong.qr.generate') }}" class="btn btn-secondary">Kembali</a>
    </div>
    @endif
</body>
</html>
