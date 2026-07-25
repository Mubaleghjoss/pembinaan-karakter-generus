<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gambar Jadwal MT/MS</title>
    @vite('resources/js/app.js')
</head>
<body class="min-h-screen bg-gray-100 p-4 dark:bg-gray-950 sm:p-8">
    <main class="mx-auto max-w-5xl">
        <div class="mb-4 flex flex-wrap gap-3">
            <button id="downloadImage" class="btn-success">Unduh PNG</button>
            <button id="shareImage" class="btn-secondary">Bagikan</button>
            <button onclick="window.close()" class="btn-secondary">Tutup</button>
        </div>
        <div class="overflow-auto rounded-2xl bg-white p-3 shadow">
            <canvas id="scheduleCanvas" class="mx-auto block max-w-none"></canvas>
        </div>
    </main>
    <script>
        const rows = @json($rows);
        const canvas = document.getElementById('scheduleCanvas');
        const context = canvas.getContext('2d');
        const width = 1400;
        const headerHeight = 150;
        const rowHeight = 62;
        canvas.width = width;
        canvas.height = headerHeight + 58 + (rows.length * rowHeight) + 50;
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = '#064e3b';
        context.fillRect(0, 0, width, headerHeight);
        context.fillStyle = '#ffffff';
        context.font = '700 40px Arial';
        context.fillText('Jadwal MT/MS PKG Desa Panunggangan', 44, 64);
        context.font = '24px Arial';
        context.fillText(@json($period->month->translatedFormat('F Y')), 44, 108);

        const columns = [
            ['Tanggal', 44, 230],
            ['Rombel', 274, 150],
            ['Waktu', 424, 165],
            ['Pengajar Utama', 589, 385],
            ['Pengajar Cadangan', 974, 382],
        ];
        context.fillStyle = '#d1fae5';
        context.fillRect(0, headerHeight, width, 58);
        context.fillStyle = '#064e3b';
        context.font = '700 20px Arial';
        columns.forEach(([label, x]) => context.fillText(label, x, headerHeight + 37));
        context.font = '19px Arial';
        rows.forEach((row, index) => {
            const y = headerHeight + 58 + (index * rowHeight);
            context.fillStyle = index % 2 === 0 ? '#ffffff' : '#f8fafc';
            context.fillRect(0, y, width, rowHeight);
            context.strokeStyle = '#e2e8f0';
            context.beginPath();
            context.moveTo(0, y + rowHeight);
            context.lineTo(width, y + rowHeight);
            context.stroke();
            context.fillStyle = '#111827';
            context.fillText(row.date, 44, y + 38);
            context.fillText(row.rombel, 274, y + 38);
            context.fillText(row.time, 424, y + 38);
            context.fillText(row.main.slice(0, 34), 589, y + 38);
            context.fillText(row.backup.slice(0, 34), 974, y + 38);
        });

        const filename = @json('jadwal-mt-ms-'.$period->month->format('Y-m').'.png');
        const makeBlob = () => new Promise(resolve => canvas.toBlob(resolve, 'image/png', 1));
        document.getElementById('downloadImage').addEventListener('click', async () => {
            const blob = await makeBlob();
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        });
        document.getElementById('shareImage').addEventListener('click', async () => {
            const blob = await makeBlob();
            const file = new File([blob], filename, { type: 'image/png' });
            if (navigator.share && navigator.canShare?.({ files: [file] })) {
                await navigator.share({ title: 'Jadwal MT/MS', files: [file] });
            } else {
                document.getElementById('downloadImage').click();
            }
        });
    </script>
</body>
</html>
