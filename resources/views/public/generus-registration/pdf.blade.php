<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan Pendaftaran Generus</title>
    <style>
        @page { margin: 32px 42px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.55; }
        h1 { font-size: 18px; margin: 0; text-align: center; text-transform: uppercase; }
        .subtitle { margin: 3px 0 24px; text-align: center; }
        table { border-collapse: collapse; width: 100%; }
        .data td { border-bottom: 1px solid #d1d5db; padding: 7px 5px; vertical-align: top; }
        .data td:first-child { font-weight: bold; width: 34%; }
        .statement { border: 1px solid #9ca3af; margin-top: 20px; padding: 12px 16px; }
        .statement ol { margin: 8px 0 0; padding-left: 18px; }
        .signatures { margin-top: 30px; table-layout: fixed; }
        .signatures td { text-align: center; vertical-align: top; width: 50%; }
        .signature { height: 80px; margin: 8px auto 2px; max-width: 210px; object-fit: contain; }
        .name { font-weight: bold; text-decoration: underline; }
        .meta { color: #4b5563; font-size: 9px; margin-top: 28px; }
    </style>
</head>
<body>
    <h1>Surat Pernyataan Pendaftaran Generus PKG</h1>
    <p class="subtitle">Nomor pendaftaran: {{ $registration->public_id }}</p>

    <table class="data">
        <tr><td>Nama Orang Tua</td><td>{{ $registration->parent_name }}</td></tr>
        <tr><td>No. WhatsApp Orang Tua</td><td>{{ $registration->parent_phone }}</td></tr>
        <tr><td>Nama Generus</td><td>{{ $registration->student_name }}</td></tr>
        <tr><td>No. WhatsApp Generus</td><td>{{ $registration->student_phone }}</td></tr>
        <tr><td>Kelompok</td><td>{{ \App\Models\Siswa::kelompokOptions()[$registration->kelompok] ?? $registration->kelompok }}</td></tr>
        <tr><td>Tempat, Tanggal Lahir</td><td>{{ $registration->birth_place }}, {{ $registration->birth_date->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Sekarang Sekolah Kelas</td><td>{{ \App\Support\TargetGrade::schoolClassOptions()[$registration->school_grade] ?? $registration->school_grade }}</td></tr>
    </table>

    <div class="statement">
        <strong>Kami menyatakan bahwa:</strong>
        <ol>
            <li>Seluruh data yang diberikan adalah benar dan dapat dipertanggungjawabkan.</li>
            <li>Generus bersedia mengikuti kegiatan Pembinaan Karakter Generus dan menaati tata tertib yang berlaku.</li>
            <li>Orang Tua bersedia mendukung kehadiran, pembinaan, dan komunikasi terkait perkembangan Generus.</li>
            <li>Data ini boleh digunakan untuk administrasi internal, penyaksian pengurus, dan pembuatan akun PKG.</li>
        </ol>
    </div>

    <table class="signatures">
        <tr>
            <td>Orang Tua<br><img class="signature" src="{{ $parentSignature }}" alt="Tanda tangan Orang Tua"><br><span class="name">{{ $registration->parent_name }}</span></td>
            <td>Generus<br><img class="signature" src="{{ $studentSignature }}" alt="Tanda tangan Generus"><br><span class="name">{{ $registration->student_name }}</span></td>
        </tr>
    </table>

    <p class="meta">Dikirim secara elektronik pada {{ $registration->submitted_at->translatedFormat('d F Y H:i') }} WIB. Dokumen ini dihasilkan oleh Sistem Pembinaan Karakter Generus.</p>
</body>
</html>
