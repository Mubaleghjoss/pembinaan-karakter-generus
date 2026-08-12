<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Halaman Tidak Ditemukan - {{ config('app.name', 'PKG Panunggangan') }}</title>
    @vite('resources/js/app.js')
</head>
<body class="min-h-full bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-5 py-12">
        <section class="pkg-panel-lg w-full text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-2xl font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300" aria-hidden="true">404</div>
            <h1 class="text-2xl font-bold">Halaman tidak ditemukan</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">Alamat mungkin salah, sudah dipindahkan, atau Anda tidak memiliki tautan yang tepat.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ url('/') }}" class="btn-primary min-h-11 justify-center">Kembali ke Beranda</a>
                <a href="{{ url()->previous() }}" class="btn-secondary min-h-11 justify-center">Kembali Sebelumnya</a>
            </div>
        </section>
    </main>
</body>
</html>
