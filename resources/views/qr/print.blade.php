<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
        $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print {{ $cardSettings['card_title'] ?? 'Kartu Peserta' }} - {{ $siteSettings['site_title'] ?? 'PKG Presensi' }}</title>
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
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
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

        /* ID Card Grid - 2 kartu per baris untuk ukuran KTP di A4 */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            gap: 8mm;
            justify-content: center;
            max-width: 210mm;
            margin: 0 auto;
        }
        
        /* Professional ID Card Design - Ukuran KTP Indonesia: 85.6mm x 54mm */
        .id-card {
            width: 85.6mm;
            height: 54mm;
            background: linear-gradient(135deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 88%, #2563eb) 50%, color-mix(in srgb, {{ $cardBaseColor }} 72%, #ffffff) 100%);
            border-radius: 3mm;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            page-break-inside: avoid;
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
            color: #1e3a8a;
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
        
        .student-photo {
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
        
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .student-photo svg {
            width: 10mm;
            height: 10mm;
            color: #9ca3af;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
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
            width: 7mm;
            opacity: 0.85;
            flex-shrink: 0;
        }
        
        .info-value {
            font-weight: 600;
        }
        
        .alamat-row {
            margin-top: 0.5mm;
        }
        
        .alamat-text {
            font-size: 1.5mm;
            font-weight: 400;
            opacity: 0.9;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
        
        .qr-section img,
        .qr-section canvas {
            width: 100% !important;
            height: auto !important;
            max-height: 100%;
            object-fit: contain;
        }
        
        .qr-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5mm;
            color: #999;
            background: #f0f0f0;
            border-radius: 1mm;
        }
        
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
            
            .no-print {
                display: none !important;
            }
            
            .print-info {
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
        
        /* Responsive untuk preview di layar */
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
        <h2 style="margin-bottom: 10px; color: #333;">🎴 {{ $cardSettings['card_title'] ?? 'Kartu Peserta' }}</h2>
        <p style="margin-bottom: 15px; color: #666; font-size: 13px;">
            Ukuran kartu: 85.6mm x 54mm (Standar KTP Indonesia) | 2 kartu per baris
        </p>
        <button class="btn btn-primary" onclick="window.print()">
            🖨️ Print Kartu Peserta
        </button>
        <a href="{{ route('qr.generate') }}" class="btn btn-secondary">
            ← Kembali
        </a>
    </div>

    <div class="print-info">
        @if(isset($className))
            <strong>Kelas Sekolah: {{ $className }}</strong> |
        @endif
        Total: {{ $students->count() }} siswa | 
        Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>

    @if($students->count() > 0)
    <div class="card-grid">
        @foreach($students as $student)
        <div class="id-card">
            <div class="card-header">
                @if(!empty($cardSettings['card_logo']))
                    <img src="{{ Storage::url($cardSettings['card_logo']) }}" alt="Logo" style="width: 5mm; height: 5mm; border-radius: 50%; object-fit: cover;">
                @else
                    <div class="card-logo">{{ substr($siteSettings['site_title'] ?? 'PKG', 0, 3) }}</div>
                @endif
                <div class="card-title">
                    <h3>{{ $cardSettings['card_title'] ?? 'KARTU PESERTA' }}</h3>
                    <p>{{ $cardSettings['card_subtitle'] ?? 'Pusat Kegiatan Guru' }}</p>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Left Side: Photo + Info (40%) -->
                <div class="left-section">
                    <div class="student-photo">
                        @if($student->foto_path)
                            <img src="{{ asset('storage/' . $student->foto_path) }}" alt="{{ $student->nama }}">
                        @else
                            <svg fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                    </div>
                    
                    <div class="student-info">
                        <div class="student-name">{{ $student->nama }}</div>
                        <div class="info-row">
                            <span class="info-label">NIS</span>
                            <span class="info-value">: {{ $student->nis }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kelas Sekolah</span>
                            <span class="info-value">: {{ $student->school_grade_label ?? 'Belum dikonfirmasi' }}</span>
                        </div>
                        @if($student->alamat)
                        <div class="info-row alamat-row">
                            <span class="info-value alamat-text">{{ Str::limit($student->alamat, 40) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Right Side: QR Code (60%) - BESAR -->
                <div class="qr-section" id="qr-{{ $student->id }}">
                    <div class="qr-placeholder">Loading...</div>
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
        <p>Tidak ada siswa yang ditemukan.</p>
        <a href="{{ route('qr.generate') }}" class="btn btn-secondary">Kembali</a>
    </div>
    @endif


    <script>
        window.pkgQrPrintStudents = @json($students->map(function($s) {
            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'qr_data' => $s->getQrData()
            ];
        }));
    </script>
    @vite(['resources/js/qr-print.js'])
</body>
</html>
