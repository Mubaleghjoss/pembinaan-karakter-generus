@php
    // Tentukan tujuan aman untuk "coba lagi": referer di origin yang sama, jika tidak pakai beranda.
    $origin = rtrim(config('app.url'), '/');
    $referer = (string) request()->headers->get('referer', '');
    $retryUrl = url('/');
    if ($referer !== '' && str_starts_with($referer, $origin)) {
        $retryUrl = $referer;
    }
    $retryUrl = preg_replace('/[#].*$/', '', $retryUrl);
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Sesi Kedaluwarsa - {{ $siteSettings['site_title'] ?? 'PKG Presensi' }}</title>
    <link rel="icon" href="{{ asset('images/icons/pkg-logo-192.png') }}">
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%);
            color: #0f172a;
        }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(135deg, #0f172a 0%, #042f2e 100%); color: #e2e8f0; }
            .card { background: #0b1220 !important; border-color: #1e293b !important; }
            .muted { color: #94a3b8 !important; }
        }
        .card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 20px 45px -20px rgba(13, 148, 136, 0.35);
            text-align: center;
        }
        .icon {
            width: 64px; height: 64px;
            margin: 0 auto 18px;
            border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(13, 148, 136, 0.12);
            color: #0d9488;
        }
        h1 { font-size: 1.4rem; font-weight: 800; margin: 0 0 8px; }
        p { margin: 0 0 8px; line-height: 1.55; }
        .muted { color: #64748b; font-size: 0.92rem; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%;
            margin-top: 20px;
            padding: 13px 18px;
            border: none; border-radius: 12px;
            background: #0d9488; color: #fff;
            font-size: 1rem; font-weight: 700;
            text-decoration: none; cursor: pointer;
        }
        .btn:active { background: #0f766e; }
        .countdown { font-weight: 700; color: #0d9488; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1>Sesi Halaman Kedaluwarsa</h1>
        <p>Halaman ini terbuka terlalu lama atau sesi keamanannya sudah berakhir.</p>
        <p class="muted">Tidak perlu khawatir — kami akan memuat ulang halaman secara otomatis dalam <span class="countdown" id="count">3</span> detik. Silakan coba masuk kembali.</p>

        <a class="btn" id="retry" href="{{ $retryUrl }}" rel="nofollow">Muat Ulang &amp; Coba Lagi</a>
    </div>

    <script>
        (function () {
            var target = @json($retryUrl);
            var el = document.getElementById('count');
            var n = 3;
            var timer = setInterval(function () {
                n -= 1;
                if (el) el.textContent = n < 0 ? 0 : n;
                if (n <= 0) {
                    clearInterval(timer);
                    window.location.replace(target);
                }
            }, 1000);
        })();
    </script>
</body>
</html>
