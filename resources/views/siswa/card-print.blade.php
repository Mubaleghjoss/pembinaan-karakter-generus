<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
        $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
        $rawCardTitle = trim($cardSettings['card_title'] ?? 'KARTU IDENTITAS');
        $displayCardTitle = strcasecmp($rawCardTitle, 'KARTU PESERTA') === 0 ? 'KARTU IDENTITAS' : $rawCardTitle;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Siswa - {{ $siswa->nama }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            size: 85.6mm 54mm;
            margin: 0;
        }

        html, body {
            width: 85.6mm;
            height: 54mm;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: transparent;
            color: #fff;
        }

        .print-shell {
            width: 85.6mm;
            height: 54mm;
            position: relative;
        }

        .id-card {
            width: 85.6mm;
            height: 54mm;
            background: linear-gradient(140deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 78%, #0f172a) 100%);
            border-radius: 2.5mm;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .card-bg-pattern {
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(
                    120deg,
                    transparent,
                    transparent 8mm,
                    rgba(255,255,255,0.015) 8mm,
                    rgba(255,255,255,0.015) 8.3mm
                );
            pointer-events: none;
            z-index: 0;
        }

        .card-bg-circle {
            position: absolute;
            top: -15mm;
            right: -10mm;
            width: 45mm;
            height: 45mm;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .card-header {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 2mm;
            padding: 1.5mm 3mm;
            background: rgba(0,0,0,0.2);
            border-bottom: 0.3mm solid rgba(255,255,255,0.1);
        }

        .card-logo {
            width: 7mm;
            height: 7mm;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #1e3a8a;
            font-size: 2mm;
            flex-shrink: 0;
            overflow: hidden;
        }

        .card-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-title h3 {
            font-size: 2.4mm;
            font-weight: 700;
            letter-spacing: 0.5mm;
            line-height: 1;
        }

        .card-title p {
            font-size: 1.5mm;
            opacity: 0.7;
            margin-top: 0.4mm;
            line-height: 1;
        }

        .card-body {
            position: relative;
            z-index: 1;
            display: flex;
            padding: 2mm 3mm;
            gap: 2.5mm;
            height: calc(100% - 13mm);
        }

        .left-col {
            width: 30mm;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5mm;
        }

        .photo-circle {
            width: 16mm;
            height: 18mm;
            border-radius: 1.5mm;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .photo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-circle svg {
            width: 8mm;
            height: 8mm;
            opacity: 0.75;
        }

        .siswa-info {
            width: 100%;
            text-align: left;
        }

        .siswa-name {
            font-size: 3mm;
            font-weight: 800;
            line-height: 1.1;
            text-transform: uppercase;
            margin-bottom: 1mm;
            word-break: break-word;
        }

        .info-row {
            display: grid;
            grid-template-columns: 7mm 2mm 1fr;
            gap: 0.8mm;
            align-items: start;
            font-size: 2mm;
            line-height: 1.25;
            margin-bottom: 0.5mm;
        }

        .info-label {
            opacity: 0.82;
        }

        .info-value {
            font-weight: 700;
            min-width: 0;
            word-break: break-word;
        }

        .qr-section {
            flex: 1;
            min-width: 0;
            border-radius: 2mm;
            background: rgba(255,255,255,0.98);
            padding: 2mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-section img {
            width: 100%;
            height: auto;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }

        .qr-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1.5mm;
            background: #f1f5f9;
            color: #64748b;
            font-size: 3mm;
            font-weight: 800;
        }

        .card-footer {
            position: absolute;
            left: 3mm;
            right: 3mm;
            bottom: 1.4mm;
            z-index: 1;
            text-align: center;
            font-size: 1.35mm;
            font-weight: 600;
            color: rgba(255,255,255,0.72);
            line-height: 1.2;
        }

        .print-toolbar {
            display: none;
        }

        @media screen {
            html, body {
                width: 100%;
                height: 100%;
            }

            body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background:
                    radial-gradient(circle at top, rgba(99, 102, 241, 0.18), transparent 40%),
                    linear-gradient(180deg, #e2e8f0 0%, #cbd5e1 100%);
                padding: 24px;
            }

            .print-shell {
                width: min(100%, 85.6mm);
                height: auto;
            }

            .id-card {
                box-shadow: 0 24px 72px rgba(15, 23, 42, 0.24);
            }

            .print-toolbar {
                display: flex;
                justify-content: center;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .print-button,
            .close-button {
                border: none;
                border-radius: 999px;
                padding: 0.75rem 1.1rem;
                font-size: 0.9rem;
                font-weight: 700;
                cursor: pointer;
            }

            .print-button {
                background: #2563eb;
                color: #fff;
            }

            .close-button {
                background: rgba(15, 23, 42, 0.08);
                color: #0f172a;
            }
        }

        @media print {
            .print-toolbar {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="setTimeout(() => window.print(), 250)">
    <div class="print-shell">
        <div class="id-card">
            <div class="card-bg-pattern"></div>
            <div class="card-bg-circle"></div>

            <div class="card-header">
                <div class="card-logo">
                    @if(!empty($cardSettings['card_logo']))
                        <img src="{{ Storage::url($cardSettings['card_logo']) }}" alt="Logo">
                    @elseif(!empty($siteSettings['site_logo']))
                        <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo">
                    @else
                        PKG
                    @endif
                </div>
                <div class="card-title">
                    <h3>{{ $displayCardTitle }}</h3>
                    <p>{{ $cardSettings['card_subtitle'] ?? 'Pembinaan Karakter Generus' }}</p>
                </div>
            </div>

            <div class="card-body">
                <div class="left-col">
                    <div class="photo-circle">
                        @if($siswa->foto_path)
                            <img src="{{ asset('storage/' . $siswa->foto_path) }}" alt="{{ $siswa->nama }}">
                        @else
                            <svg fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                    </div>

                    <div class="siswa-info">
                        <div class="siswa-name">{{ $siswa->nama }}</div>
                        <div class="info-row">
                            <span class="info-label">NIS</span>
                            <span>:</span>
                            <span class="info-value">{{ $siswa->nis }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kelas Sekolah</span>
                            <span>:</span>
                            <span class="info-value">{{ $siswa->school_grade_label ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="qr-section">
                    @if($qrCode)
                        <img src="{{ $qrCode }}" alt="QR Code">
                    @else
                        <div class="qr-placeholder">{{ $siswa->isGraduated() ? 'ALUMNI' : 'QR' }}</div>
                    @endif
                </div>
            </div>

            <div class="card-footer">
                {{ $cardFooterText }}
            </div>
        </div>

        <div class="print-toolbar">
            <button type="button" class="print-button" onclick="window.print()">Cetak Lagi</button>
            <button type="button" class="close-button" onclick="window.close()">Tutup</button>
        </div>
    </div>
</body>
</html>
