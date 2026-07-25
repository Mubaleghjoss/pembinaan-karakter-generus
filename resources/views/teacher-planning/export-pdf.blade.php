<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        p { margin: 0 0 14px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        th { background: #064e3b; color: white; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>Jadwal MT/MS PKG Desa Panunggangan</h1>
    <p>Periode {{ $period->month->translatedFormat('F Y') }} · Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
    <table>
        <thead><tr><th>Tanggal</th><th>Hari</th><th>Rombel</th><th>Waktu</th><th>Pengajar Utama</th><th>Pengajar Cadangan</th></tr></thead>
        <tbody>
            @foreach($period->sessions->sortBy('session_date') as $session)
                <tr>
                    <td>{{ $session->session_date->format('d/m/Y') }}</td>
                    <td>{{ $session->session_date->translatedFormat('l') }}</td>
                    <td>{{ strtoupper($session->rombel) }}</td>
                    <td>{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</td>
                    <td>{{ $session->assignments->firstWhere('role', 'main')?->teacher?->name ?? 'Belum diisi' }}</td>
                    <td>{{ $session->assignments->firstWhere('role', 'backup')?->teacher?->name ?? 'Belum diisi' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
