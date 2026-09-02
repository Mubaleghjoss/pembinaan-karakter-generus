@extends('layouts.public')

@section('title', 'Unduh Aplikasi - ' . ($theme->app_name ?? 'PKGenerus'))
@section('og_title', 'Unduh Aplikasi PKGenerus')
@section('og_description', 'Pasang aplikasi PKGenerus di ponsel Android untuk memantau tugas, karakter, dan materi.')

@section('content')
    <section class="bg-slate-50 py-10 dark:bg-slate-950">
        <div class="mx-auto max-w-3xl px-4">

            <div class="rounded-2xl bg-white p-6 shadow-sm dark:bg-slate-900">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                    Unduh Aplikasi PKGenerus
                </h1>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Aplikasi Android untuk siswa dan orang tua. Untuk memasang, buka halaman ini
                    dari ponsel Android.
                </p>

                @if (! $isSecure)
                    <p role="alert" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">
                        Halaman ini sedang diakses tanpa HTTPS. Sebagian browser Android menolak
                        memasang berkas yang diunduh lewat koneksi tidak aman.
                    </p>
                @endif

                @if ($release === null)
                    <p role="status" class="mt-6 rounded-lg bg-slate-100 p-4 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        Berkas aplikasi belum tersedia. Silakan hubungi pengurus PKG.
                    </p>
                @else
                    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Versi</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ $release['version_name'] }}
                                <span class="font-normal text-slate-500">(build {{ $release['version_code'] }})</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Ukuran</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ number_format($release['size'] / 1048576, 1, ',', '.') }} MB
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Diperbarui</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ $release['released_at']->translatedFormat('j F Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Minimal Android</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $minAndroid }}</dd>
                        </div>
                    </dl>

                    <a id="unduh-apk"
                       href="{{ route('public.download-app.apk') }}"
                       class="mt-6 inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-base font-semibold text-white hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                        Unduh &amp; Pasang ({{ number_format($release['size'] / 1048576, 1, ',', '.') }} MB)
                    </a>

                    @if ($autoStart)
                        <p role="status" class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                            Unduhan dimulai otomatis. Setelah selesai, ketuk notifikasi unduhan
                            lalu pilih <strong>Pasang</strong>.
                        </p>
                    @endif

                    @if ($release['notes'] !== [])
                        <h2 class="mt-8 text-lg font-semibold text-slate-900 dark:text-slate-100">Perubahan versi ini</h2>
                        <ul class="mt-2 list-inside list-disc text-sm text-slate-700 dark:text-slate-200">
                            @foreach ($release['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <h2 class="mt-8 text-lg font-semibold text-slate-900 dark:text-slate-100">Cara memasang</h2>
                    <ol class="mt-2 list-inside list-decimal space-y-1 text-sm text-slate-700 dark:text-slate-200">
                        <li>Ketuk tombol unduh di atas dan tunggu sampai selesai.</li>
                        <li>Ketuk notifikasi unduhan, lalu pilih <strong>Pasang</strong>.</li>
                        <li>
                            Bila muncul peringatan sumber tidak dikenal, pilih <strong>Setelan</strong>
                            lalu izinkan browser memasang aplikasi, kemudian ulangi langkah 2.
                        </li>
                    </ol>

                    <p class="mt-6 break-all text-xs text-slate-500 dark:text-slate-400">
                        SHA-256: {{ $release['sha256'] }}
                    </p>
                @endif
            </div>
        </div>
    </section>
@endsection

@if (($autoStart ?? false) && $release !== null)
    @push('scripts')
        <script>
            // Mulai unduhan otomatis di Android. Menggunakan click() pada anchor
            // (bukan location.assign) agar atribut download dan header attachment
            // tetap dihormati Download Manager.
            window.addEventListener('load', function () {
                setTimeout(function () {
                    var a = document.getElementById('unduh-apk');
                    if (a) { a.click(); }
                }, 600);
            });
        </script>
    @endpush
@endif
