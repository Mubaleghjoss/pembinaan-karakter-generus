# Deploy cPanel Tanpa SSH

Panduan ini untuk pola deploy seperti server Anda:

- source Laravel di folder misalnya `/home/inag1455/pkg`
- web root di `/home/inag1455/public_html`
- folder storage publik di `/home/inag1455/public_html/storage`
- migrasi database dijalankan manual lewat phpMyAdmin

## 1. Struktur yang disarankan

- source app: `/home/inag1455/pkg`
- web root aktif: `/home/inag1455/public_html`
- storage publik: `/home/inag1455/public_html/storage`

`public_html/index.php` harus mengarah ke source Laravel di folder app Anda, dan harus memaksa Laravel memakai `public_html` sebagai `public_path()`.

Gunakan template ini:

- [public_html_index.php.example](e:/xampp/htdocs/pkg-v3/deploy/cpanel/public_html_index.php.example)

Intinya harus ada baris ini:

```php
$app = require_once $appRoot . '/bootstrap/app.php';
$app->usePublicPath(__DIR__);
```

Tanpa itu, Laravel akan tetap menganggap folder public-nya adalah `/home/inag1455/pkg/public`, sehingga:

- Vite mencari manifest di `/home/inag1455/pkg/public/build/manifest.json`
- bukan di `/home/inag1455/public_html/build/manifest.json`

Itu penyebab klasik error:

```text
Illuminate\Foundation\ViteException
Unable to locate file in Vite manifest: resources/js/app.js
```

## 1A. Perbaikan cepat 5 menit untuk error Vite manifest

Jika error di atas muncul, jangan ubah source Blade atau `@vite(...)`.
Masalahnya hampir selalu karena Laravel membaca `public_path()` yang salah.

Lakukan ini:

1. buka `public_html/index.php`
2. ganti isinya memakai template ini:
   - [public_html_index.php.example](e:/xampp/htdocs/pkg-v3/deploy/cpanel/public_html_index.php.example)
3. pastikan ada baris ini:

```php
$app = require_once $appRoot . '/bootstrap/app.php';
$app->usePublicPath(__DIR__);
```

4. upload build frontend terbaru ke:
   - `/home/inag1455/public_html/build`
5. pastikan file ini ada:
   - `/home/inag1455/public_html/build/manifest.json`
6. buka `manifest.json` dan cek ada key:

```json
"resources/js/app.js"
```

Kalau `build` terbaru justru Anda upload ke `/home/inag1455/pkg/public/build`, maka ada 2 pilihan:

- pakai pola standar Laravel penuh, dan web root harus mengarah ke `/pkg/public`
- atau tetap pakai `public_html` sebagai web root, lalu build harus ada di `/public_html/build` dan `index.php` wajib memakai `$app->usePublicPath(__DIR__)`

Jangan campur dua pola itu.

## 2. Set `.env`

Isi minimal yang penting:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=nama_user
DB_PASSWORD=password_database

FILESYSTEM_DISK=public
FILESYSTEM_PUBLIC_ROOT=/home/inag1455/public_html/storage
```

Kunci pentingnya adalah:

```env
FILESYSTEM_PUBLIC_ROOT=/home/inag1455/public_html/storage
```

Dengan ini Laravel akan menulis file upload langsung ke `public_html/storage`, jadi tidak bergantung ke `php artisan storage:link`.

## 3. Folder yang harus ada

Pastikan folder ini ada di `public_html/storage`:

- `logos`
- `avatars`
- `berita`
- `materi`
- `photos`
- `qrcodes`
- `reward-templates`
- `chat`
- `logs`

Jika belum ada, buat manual dari File Manager cPanel.

## 4. Permission minimum

Umumnya cukup:

- folder: `755`
- file: `644`

Hindari `777` jika tidak benar-benar perlu.

## 5. Hal penting untuk menu Tarik Data

Menu `Tarik Data` hanya menarik data database.
Ia tidak menarik file fisik seperti:

- logo
- avatar
- gambar berita
- file materi
- template reward

Karena itu:

- logo lokal harus dipertahankan lokal
- file media server online tidak otomatis ikut turun

Di repo ini, `DataPullController` sudah diproteksi agar key lokal seperti:

- `site_logo`
- `card_logo`
- `sync_server_url`
- `sync_api_key`
- `sync_export_key`

tidak ditimpa dari server online.

## 6. Kalau logo 403 setelah tarik data

Penyebab umumnya:

1. database lokal menunjuk ke path logo dari server online
2. file logonya tidak ada di `public_html/storage/logos`

Langkah perbaikan:

1. buka phpMyAdmin
2. cek tabel `settings`
   - key `site_logo`
   - key `card_logo`
3. cek tabel `theme_settings`
   - kolom `logo_path`
4. arahkan ke file yang benar-benar ada di `public_html/storage/logos`

Contoh nilai:

```text
logos/e1ulQQKOrf1RqoXPymTM0cYFjkhThTyaLgkSg0aZ.png
```

## 7. Urutan deploy aman tanpa SSH

1. upload/update source code ke folder app, misalnya `/home/inag1455/pkg`
2. ganti `public_html/index.php` dengan template cPanel yang benar
3. upload/update hasil build frontend ke `public_html/build`
4. pastikan `public_html/index.php` mengarah ke source app terbaru
5. jika folder `build` lama masih ada, hapus dulu isinya lalu upload ulang hasil build baru
6. update `.env`
7. jalankan SQL manual migrasi terbaru di phpMyAdmin
8. pastikan file media yang dibutuhkan ada di `public_html/storage`
9. cek halaman:
   - `/`
   - `/login`
   - `/settings`
   - `/data-pull`
   - `/rpg-admin`

## 7A. Kalau Anda tidak bisa mengganti `public_html/index.php`

Kalau karena batasan hosting Anda tidak bisa memakai `public_html/index.php` sebagai front controller Laravel yang benar, maka jalur aman satu-satunya adalah ini:

- Laravel tetap membaca `public_path()` dari `/home/inag1455/pkg/public`
- hasil build Vite harus Anda upload ke:
  - `/home/inag1455/pkg/public/build`

Dalam mode ini:

- jangan taruh build hanya di `/public_html/build`
- dan jangan berharap Laravel otomatis membaca manifest dari `public_html`

Kalau `public_html/build` berisi asset baru tetapi `public_path()` masih menunjuk ke `/pkg/public`, error manifest tetap akan muncul.

## 8. Checklist khusus Vite

Sebelum tes hasil deploy, pastikan:

1. file ini ada:
   - `/home/inag1455/public_html/build/manifest.json`
2. file `manifest.json` berisi key:

```json
"resources/js/app.js"
```

3. file JS/CSS hasil build ikut terupload ke folder `public_html/build/assets`
4. file `public/hot` tidak ikut terupload

Jika masih error, hampir pasti salah satu dari ini:

- `public_html/index.php` belum memakai `$app->usePublicPath(__DIR__)`
- folder `build` terbaru belum terupload ke `public_html/build`
- manifest lama masih tertinggal dari build versi lama
- hasil build justru terupload ke `/pkg/public/build`, padahal web root aktif memakai `public_html`

## 9. File SQL manual

SQL manual untuk migrasi terbaru sudah disiapkan di:

- [database/manual_sql/manual_migrations_2026_04_09_to_2026_04_11.sql](e:/xampp/htdocs/pkg-v3/database/manual_sql/manual_migrations_2026_04_09_to_2026_04_11.sql)

File itu bisa dipaste ke phpMyAdmin.

## 10. Catatan penting

- Jika database production belum punya tabel `migrations`, buat dulu dengan struktur standar Laravel atau abaikan blok insert `migrations`.
- Jika phpMyAdmin menampilkan error `duplicate column` atau `duplicate entry`, berarti item itu sudah pernah dijalankan. Lanjut ke bagian berikutnya.
- Setelah update logo dari admin panel, file akan tersimpan langsung ke `public_html/storage` jika `FILESYSTEM_PUBLIC_ROOT` sudah benar.
