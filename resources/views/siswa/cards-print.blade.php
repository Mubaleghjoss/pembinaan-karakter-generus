<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
        $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
        $rawCardTitle = trim($cardSettings['card_title'] ?? 'KARTU IDENTITAS');
        $displayCardTitle = strcasecmp($rawCardTitle, 'KARTU PESERTA') === 0 ? 'KARTU IDENTITAS' : $rawCardTitle;
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unduh Kartu ID Siswa - {{ $siteSettings['site_title'] ?? 'PKG Presensi' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background: #e5e7eb;
            color: #0f172a;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            padding: 16px;
        }

        .toolbar {
            align-items: center;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            margin: 0 auto 18px;
            max-width: 210mm;
            padding: 14px 16px;
        }

        .toolbar h1 {
            font-size: 18px;
            line-height: 1.2;
        }

        .toolbar p {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn {
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #0f172a;
        }

        .card-grid {
            display: grid;
            gap: 6mm;
            grid-template-columns: repeat(2, 85.6mm);
            justify-content: center;
            margin: 0 auto;
            max-width: 210mm;
        }

        .id-card {
            background: linear-gradient(140deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 78%, #0f172a) 100%);
            border-radius: 2.5mm;
            color: #ffffff;
            height: 54mm;
            overflow: hidden;
            page-break-inside: avoid;
            position: relative;
            width: 85.6mm;
        }

        .card-bg-pattern {
            background:
                repeating-linear-gradient(
                    120deg,
                    transparent,
                    transparent 8mm,
                    rgba(255,255,255,0.015) 8mm,
                    rgba(255,255,255,0.015) 8.3mm
                );
            inset: 0;
            pointer-events: none;
            position: absolute;
            z-index: 0;
        }

        .card-bg-circle {
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            border-radius: 50%;
            height: 45mm;
            pointer-events: none;
            position: absolute;
            right: -10mm;
            top: -15mm;
            width: 45mm;
            z-index: 0;
        }

        .card-header {
            align-items: center;
            background: rgba(0,0,0,0.2);
            border-bottom: 0.3mm solid rgba(255,255,255,0.1);
            display: flex;
            gap: 2mm;
            padding: 1.5mm 3mm;
            position: relative;
            z-index: 1;
        }

        .card-logo {
            align-items: center;
            background: #ffffff;
            border-radius: 50%;
            color: #1e3a8a;
            display: flex;
            flex-shrink: 0;
            font-size: 2mm;
            font-weight: 800;
            height: 7mm;
            justify-content: center;
            overflow: hidden;
            width: 7mm;
        }

        .card-logo img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .card-title h3 {
            font-size: 2.4mm;
            font-weight: 700;
            letter-spacing: 0.5mm;
            line-height: 1;
        }

        .card-title p {
            font-size: 1.5mm;
            line-height: 1;
            margin-top: 0.4mm;
            opacity: 0.7;
        }

        .card-body {
            display: flex;
            gap: 2.5mm;
            height: calc(100% - 13mm);
            padding: 2mm 3mm;
            position: relative;
            z-index: 1;
        }

        .left-col {
            align-items: center;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            gap: 1.5mm;
            width: 30mm;
        }

        .photo-circle {
            align-items: center;
            border-radius: 1.5mm;
            display: flex;
            flex-shrink: 0;
            height: 18mm;
            justify-content: center;
            overflow: hidden;
            width: 16mm;
        }

        .photo-circle img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .photo-circle svg {
            height: 8mm;
            opacity: 0.75;
            width: 8mm;
        }

        .siswa-info {
            text-align: left;
            width: 100%;
        }

        .siswa-name {
            display: -webkit-box;
            font-size: 3mm;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1mm;
            overflow: hidden;
            text-transform: uppercase;
            word-break: break-word;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .info-row {
            align-items: start;
            display: grid;
            font-size: 2mm;
            gap: 0.8mm;
            grid-template-columns: 7mm 2mm 1fr;
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
            align-items: center;
            background: rgba(255,255,255,0.98);
            border-radius: 2mm;
            display: flex;
            flex: 1;
            justify-content: center;
            min-width: 0;
            padding: 2mm;
        }

        .qr-section img {
            display: block;
            height: auto;
            max-height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .qr-placeholder {
            align-items: center;
            background: #f1f5f9;
            border-radius: 1.5mm;
            color: #64748b;
            display: flex;
            font-size: 2.4mm;
            font-weight: 800;
            height: 100%;
            justify-content: center;
            line-height: 1.2;
            padding: 2mm;
            text-align: center;
            width: 100%;
        }

        .card-footer {
            bottom: 1.4mm;
            color: rgba(255,255,255,0.72);
            font-size: 1.35mm;
            font-weight: 600;
            left: 3mm;
            line-height: 1.2;
            position: absolute;
            right: 3mm;
            text-align: center;
            z-index: 1;
        }

        .empty-state {
            background: #ffffff;
            border-radius: 10px;
            color: #475569;
            margin: 0 auto;
            max-width: 210mm;
            padding: 32px;
            text-align: center;
        }

        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .toolbar {
                display: none !important;
            }

            .card-grid {
                gap: 5mm;
            }

            .id-card {
                border: 0.2mm solid #cbd5e1;
            }
        }

        @media screen and (max-width: 720px) {
            .card-grid {
                grid-template-columns: 1fr;
            }

            .id-card {
                height: auto;
                max-width: 100%;
                aspect-ratio: 85.6 / 54;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Unduh Kartu ID Siswa</h1>
            <p>
                @if($className)
                    Kelas {{ $className }} |
                @endif
                Total {{ $students->count() }} siswa | Dicetak {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            <a href="{{ route('siswa.index', ['tab' => 'qr']) }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    @if($cards->count() > 0)
        <div class="card-grid">
            @foreach($cards as $card)
                @php($siswa = $card['siswa'])
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
                            @if($card['qrCode'])
                                <img src="{{ $card['qrCode'] }}" alt="QR Code">
                            @else
                                <div class="qr-placeholder">QR belum tersedia</div>
                            @endif
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
            <p>Tidak ada siswa aktif yang sesuai filter.</p>
        </div>
    @endif
</body>
</html>
