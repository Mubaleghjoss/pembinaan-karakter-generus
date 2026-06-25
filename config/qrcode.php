<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Token Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk token QR Code yang digunakan dalam sistem presensi.
    | Token digunakan untuk validasi kehadiran siswa melalui scan QR.
    |
    */

    'token' => [
        /*
         * Waktu kadaluarsa token dalam menit.
         * Default: 60 menit (1 jam)
         */
        'expiry_minutes' => env('QR_TOKEN_EXPIRY_MINUTES', 60),

        /*
         * Algoritma hash yang digunakan untuk generate token.
         * Supported: 'sha256', 'sha384', 'sha512'
         */
        'hash_algorithm' => env('QR_TOKEN_HASH_ALGORITHM', 'sha256'),

        /*
         * Panjang random string untuk token generation.
         */
        'random_length' => env('QR_TOKEN_RANDOM_LENGTH', 32),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi enkripsi untuk keamanan QR Code.
    |
    */

    'encryption' => [
        /*
         * Algoritma HMAC untuk signature.
         * Supported: 'sha256', 'sha384', 'sha512'
         */
        'hmac_algorithm' => env('QR_HMAC_ALGORITHM', 'sha256'),

        /*
         * Panjang salt untuk secret key siswa.
         */
        'salt_length' => env('QR_SALT_LENGTH', 64),
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Generation Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk pembuatan gambar QR Code.
    |
    */

    'generation' => [
        /*
         * Ukuran QR Code dalam pixel.
         */
        'size' => env('QR_CODE_SIZE', 300),

        /*
         * Margin QR Code dalam pixel.
         */
        'margin' => env('QR_CODE_MARGIN', 10),

        /*
         * Error correction level.
         * Supported: 'L' (7%), 'M' (15%), 'Q' (25%), 'H' (30%)
         */
        'error_correction' => env('QR_ERROR_CORRECTION', 'M'),

        /*
         * Format output default.
         * Supported: 'png', 'svg'
         */
        'default_format' => env('QR_DEFAULT_FORMAT', 'png'),

        /*
         * Warna foreground QR Code (hex tanpa #).
         */
        'foreground_color' => env('QR_FOREGROUND_COLOR', '000000'),

        /*
         * Warna background QR Code (hex tanpa #).
         */
        'background_color' => env('QR_BACKGROUND_COLOR', 'ffffff'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi logo yang ditampilkan di tengah QR Code.
    |
    */

    'logo' => [
        /*
         * Aktifkan logo di QR Code.
         */
        'enabled' => env('QR_LOGO_ENABLED', true),

        /*
         * Path ke file logo (relatif dari public/).
         */
        'path' => env('QR_LOGO_PATH', 'img/logo_pkg.svg'),

        /*
         * Lebar logo dalam pixel.
         */
        'width' => env('QR_LOGO_WIDTH', 60),

        /*
         * Tinggi logo dalam pixel.
         */
        'height' => env('QR_LOGO_HEIGHT', 60),

        /*
         * Aktifkan punchout background untuk logo.
         */
        'punchout_background' => env('QR_LOGO_PUNCHOUT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payload Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi format payload QR Code.
    |
    */

    'payload' => [
        /*
         * Prefix untuk payload QR Code.
         */
        'prefix' => env('QR_PAYLOAD_PREFIX', 'PKG'),

        /*
         * Versi format payload.
         */
        'version' => env('QR_PAYLOAD_VERSION', '1'),

        /*
         * Delimiter untuk memisahkan field dalam payload.
         */
        'delimiter' => env('QR_PAYLOAD_DELIMITER', '|'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk proses scan QR Code.
    |
    */

    'scan' => [
        /*
         * Maksimum scan per siswa per hari.
         * Set 0 untuk unlimited.
         */
        'max_per_day' => env('QR_MAX_SCANS_PER_DAY', 2),

        /*
         * Cooldown antar scan dalam detik.
         * Mencegah scan berulang dalam waktu singkat.
         */
        'cooldown_seconds' => env('QR_SCAN_COOLDOWN_SECONDS', 60),

        /*
         * Aktifkan validasi lokasi saat scan.
         */
        'validate_location' => env('QR_VALIDATE_LOCATION', false),

        /*
         * Radius maksimum dari lokasi sekolah dalam meter.
         * Hanya berlaku jika validate_location = true.
         */
        'max_distance_meters' => env('QR_MAX_DISTANCE_METERS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Konfigurasi rate limiting untuk endpoint QR scan.
    |
    */

    'rate_limit' => [
        /*
         * Maksimum request per menit untuk scan endpoint.
         */
        'scan_per_minute' => env('QR_RATE_LIMIT_SCAN', 30),

        /*
         * Maksimum request per menit untuk generate endpoint.
         */
        'generate_per_minute' => env('QR_RATE_LIMIT_GENERATE', 10),
    ],

];
