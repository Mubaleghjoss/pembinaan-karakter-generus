<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1, h2 { margin: 0 0 12px; }
        p { margin: 0 0 8px; }
        .meta { margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 24px; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; text-align: left; }
        th { background: #f3f4f6; }
        @media print { body { margin: 12px; } }
    </style>
</head>
<body onload="window.print()">
    <h1>Laporan Presensi</h1>
    <div class="meta">
        <p>Periode: {{ $filters['tanggal_mulai'] }} s/d {{ $filters['tanggal_selesai'] }}</p>
        <p>Kelas: {{ $className }}</p>
    </div>

    <div class="grid">
        <div class="card">Total Siswa: {{ $dataset['summary']['total_siswa'] }}</div>
        <div class="card">Total Presensi: {{ $dataset['summary']['total_presensi'] }}</div>
        <div class="card">% Kehadiran: {{ $dataset['summary']['persentase_kehadiran'] }}%</div>
        <div class="card">Rata-rata Harian: {{ $dataset['summary']['rata_rata_harian'] }}</div>
    </div>

    <h2>Status Kehadiran</h2>
    <table>
        <thead>
            <tr>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Tidak Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $dataset['statusChart']['hadir'] }}</td>
                <td>{{ $dataset['statusChart']['terlambat'] }}</td>
                <td>{{ $dataset['statusChart']['tidak_hadir'] }}</td>
                <td>{{ $dataset['statusChart']['izin'] }}</td>
                <td>{{ $dataset['statusChart']['sakit'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Performa Kelas</h2>
    <table>
        <thead>
            <tr>
                <th>Kelas</th>
                <th>Total Siswa</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Tidak Hadir</th>
                <th>% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dataset['classPerformance'] as $kelas)
                <tr>
                    <td>{{ $kelas['nama'] }}</td>
                    <td>{{ $kelas['total_siswa'] }}</td>
                    <td>{{ $kelas['hadir'] }}</td>
                    <td>{{ $kelas['terlambat'] }}</td>
                    <td>{{ $kelas['tidak_hadir'] }}</td>
                    <td>{{ $kelas['persentase_kehadiran'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Data Presensi</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dataset['records'] as $record)
                <tr>
                    <td>{{ optional($record->tanggal)->format('Y-m-d') }}</td>
                    <td>{{ $record->siswa?->nis }}</td>
                    <td>{{ $record->siswa?->nama }}</td>
                    <td>{{ $record->siswa?->kelas?->nama }}</td>
                    <td>{{ ucfirst((string) $record->status) }}</td>
                    <td>{{ optional($record->jam_masuk)->format('H:i:s') }}</td>
                    <td>{{ optional($record->jam_keluar)->format('H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
