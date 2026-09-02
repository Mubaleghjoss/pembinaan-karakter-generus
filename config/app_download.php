<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Distribusi APK PKGenerus (halaman /download_app)
    |--------------------------------------------------------------------------
    |
    | APK TIDAK disimpan di dalam public/. File dilayani lewat controller agar
    | header Content-Type/Content-Length benar (syarat Android memunculkan
    | dialog install setelah unduhan selesai) dan agar file bisa diganti tanpa
    | menyentuh direktori web.
    |
    | Struktur direktori rilis:
    |   storage/app/private/app-releases/
    |       pkgenerus-1.4.0-14.apk        <- boleh beberapa versi
    |       releases.json                 <- opsional, metadata + changelog
    |
    | Di produksi cukup set APP_RELEASE_DIR bila lokasinya berbeda.
    |
    */

    'dir' => env('APP_RELEASE_DIR', storage_path('app/private/app-releases')),

    // Nama file yang diterima klien. {version} diganti version_name.
    'filename' => env('APP_RELEASE_FILENAME', 'pkgenerus-{version}.apk'),

    // Ditampilkan di halaman bila metadata tidak menyebut minimum Android.
    'min_android' => env('APP_RELEASE_MIN_ANDROID', '7.0'),

    /*
    | Android hanya memunculkan popup install SETELAH file selesai diunduh.
    | Dengan auto_start=true, membuka /download_app dari HP Android langsung
    | memulai unduhan sehingga notifikasi "Buka" (yang memicu popup install)
    | muncul tanpa langkah tambahan.
    */
    'auto_start' => env('APP_RELEASE_AUTO_START', true),
];
