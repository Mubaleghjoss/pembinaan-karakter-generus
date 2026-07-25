<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan Kesediaan Guru</title>
    <style>
        @page { margin: 30px 40px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        h1 { font-size: 17px; margin: 0; text-align: center; text-transform: uppercase; }
        .subtitle { margin: 3px 0 18px; text-align: center; }
        table { border-collapse: collapse; width: 100%; }
        .data td { border-bottom: 1px solid #d1d5db; padding: 5px; vertical-align: top; }
        .data td:first-child { font-weight: bold; width: 38%; }
        .statement { border: 1px solid #9ca3af; margin-top: 16px; padding: 10px 14px; }
        .signature-block { margin-top: 18px; text-align: center; }
        .signature { height: 78px; margin: 6px auto 1px; max-width: 220px; object-fit: contain; }
        .signature-missing { color: #92400e; height: 50px; margin: 12px auto 4px; padding-top: 22px; }
        .name { font-weight: bold; text-decoration: underline; }
        .meta { color: #4b5563; font-size: 9px; margin-top: 18px; }
    </style>
</head>
<body>
    <h1>Surat Pernyataan Kesediaan MT/MS</h1>
    <p class="subtitle">Program Tambahan Keilmuan PKG Desa Panunggangan</p>

    <table class="data">
        <tr><td>Nama lengkap</td><td>{{ $teacher->name }}</td></tr>
        <tr><td>Kelompok</td><td>{{ $teacher->kelompokLabel() }}</td></tr>
        <tr><td>Nomor WhatsApp</td><td>{{ $teacher->whatsapp }}</td></tr>
        <tr><td>Kesediaan berpartisipasi</td><td>{{ $participationRole }}</td></tr>
        <tr><td>Rombel yang siap didampingi</td><td>{{ $rombelLabels->join(', ') ?: '-' }}</td></tr>
        <tr><td>Malam yang memungkinkan</td><td>{{ $nightLabels->join(', ') ?: '-' }}</td></tr>
        <tr><td>Maksimal penugasan per bulan</td><td>{{ $teacher->monthly_limit ? $teacher->monthly_limit.' kali' : '4 kali atau lebih' }}</td></tr>
        <tr><td>Kemampuan/materi</td><td>{{ $competencyLabels->join(', ') ?: '-' }}</td></tr>
        <tr><td>Kesiapan mempelajari bahan ajar</td><td>{{ $materialReadiness }}</td></tr>
        <tr><td>Kesediaan dihubungi sebagai cadangan</td><td>{{ $backupPreference }}</td></tr>
        <tr><td>Kendala atau waktu yang perlu diperhatikan</td><td>{{ $teacher->constraints ?: '-' }}</td></tr>
    </table>

    <div class="statement">
        <strong>Pernyataan Kesediaan</strong>
        <p>Saya bersedia ikut membantu Program Tambahan Keilmuan PKG Desa Panunggangan sesuai kemampuan dan waktu yang saya pilih. Saya siap mempelajari bahan ajar yang diberikan, menjalankan jadwal dengan amanah, serta segera memberikan informasi kepada admin apabila berhalangan hadir.</p>
    </div>

    <div class="signature-block">
        Yang menyatakan,<br>
        @if($signature)
            <img class="signature" src="{{ $signature }}" alt="Tanda tangan guru"><br>
        @else
            <div class="signature-missing">Tanda tangan belum tersedia (data lama)</div>
        @endif
        <span class="name">{{ $teacher->name }}</span>
    </div>

    <p class="meta">Formulir dikirim secara elektronik pada {{ $teacher->submitted_at->translatedFormat('d F Y H:i') }} WIB. Dokumen ini dihasilkan oleh Sistem Pembinaan Karakter Generus.</p>
</body>
</html>
