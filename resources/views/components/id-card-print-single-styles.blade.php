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
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .id-card {
        width: 85.6mm;
        height: 54mm;
        background: linear-gradient(135deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 84%, #0f172a) 100%);
        border-radius: 3mm;
        padding: 2mm;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .id-card::before {
        content: '';
        position: absolute;
        top: -30%;
        right: -20%;
        width: 60%;
        height: 80%;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 1.5mm;
        margin-bottom: 1mm;
        padding-bottom: 0.8mm;
        border-bottom: 0.2mm solid rgba(255,255,255,0.3);
    }

    .card-logo {
        width: 5mm;
        height: 5mm;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #0f172a;
        font-size: 2mm;
        flex-shrink: 0;
        overflow: hidden;
    }

    .card-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .card-title {
        flex: 1;
    }

    .card-title h3 {
        font-size: 2.5mm;
        font-weight: 700;
        letter-spacing: 0.2mm;
        margin: 0;
    }

    .card-title p {
        font-size: 1.5mm;
        opacity: 0.8;
        margin: 0;
    }

    .card-body {
        display: flex;
        gap: 1.5mm;
        height: calc(100% - 10mm);
    }

    .left-section {
        width: 40%;
        display: flex;
        flex-direction: column;
        gap: 1mm;
    }

    .person-photo {
        width: 100%;
        height: 20mm;
        background: rgba(255,255,255,0.15);
        border-radius: 1mm;
        border: 0.2mm solid rgba(255,255,255,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .person-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .person-photo svg {
        width: 8mm;
        height: 8mm;
        opacity: 0.5;
    }

    .person-info {
        flex: 1;
    }

    .person-name {
        font-size: 2.5mm;
        font-weight: 700;
        margin-bottom: 0.8mm;
        line-height: 1.2;
    }

    .info-row {
        display: flex;
        font-size: 2mm;
        margin-bottom: 0.3mm;
        line-height: 1.2;
    }

    .info-label {
        width: {{ $labelWidth ?? '7mm' }};
        opacity: 0.85;
        flex-shrink: 0;
    }

    .info-value {
        font-weight: 600;
    }

    .qr-section {
        width: 60%;
        background: white;
        border-radius: 1.5mm;
        padding: 1mm;
        display: flex;
        align-items: center;
        justify-content: center;
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
        bottom: 0.8mm;
        left: 2mm;
        right: 2mm;
        font-size: 1.3mm;
        opacity: 0.7;
        text-align: center;
    }

    @media screen {
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #1f2937;
            width: auto;
            height: auto;
        }

        .id-card {
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
    }
</style>
