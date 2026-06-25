# PKG Panunggangan

Sistem manajemen kegiatan PKG berbasis Laravel untuk kebutuhan admin, pamong, siswa, orang tua, dan publik. Aplikasi ini mencakup presensi QR, tracer karakter, materi, PR, chat, gamifikasi, RPG Quest, laporan penyaksian, pengaturan tema, sinkronisasi data, dan portal publik.

## Pemilik Proyek

- `Organisasi`: PKG Panunggangan
- `Pengelola/Penanggung Jawab`: MubaleghJoss
- `Kontak`: 083818393029

## Ringkasan

Proyek ini dibangun untuk kebutuhan operasional PKG dengan beberapa portal dalam satu aplikasi:

- `Admin`: pengelolaan master data, pengaturan sistem, gamifikasi, sinkronisasi, laporan, dan monitoring.
- `Pamong`: pendampingan siswa, chat, tugas, presensi, karakter, dan laporan.
- `Siswa`: akses materi, PR, presensi, chat, gamifikasi, RPG Quest, biometrik, dan profil.
- `Ortu`: melihat jadwal, tugas, kehadiran, chat, dan monitoring anak.
- `Publik`: halaman informasi umum, berita, materi, scanner, dan RPG demo/quest publik.

## Fitur Aplikasi

### 1. Autentikasi dan Portal Multi-Role

- Login terpisah untuk admin/pamong, siswa, dan orang tua.
- Otorisasi berbasis role dan permission.
- Dukungan dark mode dan light mode yang konsisten lintas portal.
- Aktivasi biometrik/WebAuthn untuk login cepat.
- Dukungan PWA/install aplikasi di perangkat.

### 2. Presensi QR

- Generate kartu QR untuk siswa dan pamong.
- Scan QR untuk kehadiran masuk/keluar.
- Jadwal presensi aktif/nonaktif.
- Verifikasi presensi.
- Input presensi manual dan massal.
- Rekap presensi harian dan statistik kehadiran.
- Cek kehadiran untuk admin, pamong, siswa, dan orang tua.

### 3. Manajemen Data Inti

- Master data siswa.
- Master data pamong.
- Master data user/admin.
- Master data kelas.
- Pengelolaan status akun aktif/nonaktif.
- Reset password massal dan individual.

### 4. Materi dan PR

- Kelola materi pembelajaran.
- Tampilkan materi untuk siswa dan publik.
- Dukungan PDF dan embed video.
- Kelola PR/tugas.
- Submission PR oleh siswa.
- Monitoring tugas oleh pamong/admin/ortu.

### 5. Tracer Karakter

- Kelola daftar karakter/tugas karakter.
- Input checklist karakter siswa.
- Verifikasi karakter.
- Riwayat karakter harian.
- Rekap karakter.
- Restore data karakter terhapus.
- Dukungan hitungan klik/zikir dan jawaban teks.

### 6. Laporan dan Monitoring

- Laporan penyaksian.
- Catatan rapat/musyawarah.
- Reminder jadwal.
- Kalender kegiatan.
- Report summary dan chart.
- Ekspor data CSV/rekap tertentu.

### 7. Chat dan Komunikasi

- Chat pribadi pamong-siswa.
- Chat orang tua.
- Group chat.
- Siaran/broadcast admin.
- Siaran/broadcast pamong.
- Share info/pengumuman.

### 8. Gamifikasi

- Poin siswa.
- Pin penghargaan.
- Level dan reward template.
- Streak dan transaksi poin.
- Analitik gamifikasi.
- Reset data gamifikasi per siswa.
- Notifikasi gamifikasi.

### 9. RPG Quest

- Editor peta RPG untuk admin.
- NPC, soal, poin, obstacle, dan enemy.
- Main RPG untuk siswa.
- Demo RPG publik tanpa akun.
- Popup CTA untuk mendorong login dan partisipasi kegiatan.

### 10. Pengaturan Sistem

- Pengaturan tema.
- Pengaturan identitas sekolah/lembaga.
- Pengaturan share info.
- Pengaturan popup.
- Pengaturan kartu identitas.
- Pengaturan backup.
- Pengaturan hak akses pamong.

### 11. Sinkronisasi Data

- Tarik data dari server online ke server lokal.
- Dukungan sinkronisasi data inti dan data RPG.
- Riwayat hasil tarik data.

## Stack Teknologi

- PHP 8.2+
- Laravel 12
- MySQL / MariaDB
- Blade
- Alpine.js
- Tailwind CSS
- Vite
- React untuk beberapa komponen tertentu
- FullCalendar
- html5-qrcode

## Requirement Lokal

Agar aplikasi bisa berjalan dengan baik di localhost XAMPP, siapkan:

- XAMPP dengan:
  - Apache
  - MySQL
  - PHP 8.2 atau lebih baru
- Composer 2.x
- Node.js 20+ dan npm
- Git

## Struktur Direktori Penting

- `app/`: logic aplikasi, model, service, controller
- `resources/views/`: Blade views
- `resources/js/`: asset JS
- `resources/css/`: asset CSS
- `routes/`: route web dan API
- `database/`: migration, seeder, factory
- `public/`: document root Laravel

## Cara Install di Localhost XAMPP

### 1. Letakkan project di folder XAMPP

Clone atau copy project ke:

```powershell
E:\xampp\htdocs\pkg-v3
```

Atau jika memakai drive lain, sesuaikan dengan lokasi `htdocs` Anda.

### 2. Jalankan Apache dan MySQL dari XAMPP

Minimal nyalakan:

- `Apache`
- `MySQL`

### 3. Buat database di phpMyAdmin

Buka phpMyAdmin dari XAMPP, lalu buat database baru. Contoh:

```text
pkg_v3
```

Gunakan collation:

```text
utf8mb4_unicode_ci
```

### 4. Install dependency PHP

Buka terminal di folder project, lalu jalankan:

```powershell
composer install
```

### 5. Install dependency frontend

```powershell
npm install
```

### 6. Siapkan file environment

Copy `.env.example` menjadi `.env`.

Jika memakai PowerShell:

```powershell
Copy-Item .env.example .env
```

### 7. Atur konfigurasi `.env`

Minimal sesuaikan nilai berikut:

```env
APP_NAME="PKG Panunggangan"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pkg_v3
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Catatan:

- Jika password MySQL root Anda kosong di XAMPP, biarkan `DB_PASSWORD=` kosong.
- Jika memakai port MySQL berbeda, sesuaikan `DB_PORT`.

### 8. Generate application key

```powershell
php artisan key:generate
```

### 9. Jalankan migration dan seeder

```powershell
php artisan migrate --seed
```

Seeder bawaan akan membuat role dasar, user awal, dan beberapa data contoh.

### 10. Buat storage link

```powershell
php artisan storage:link
```

Langkah ini penting agar file upload dapat diakses dari browser melalui `/storage/...`.

### 11. Build asset frontend

Untuk penggunaan lokal yang stabil tanpa dev server Vite:

```powershell
npm run build
```

Jika Anda sedang aktif mengembangkan tampilan:

```powershell
npm run dev
```

Catatan:

- Jika menjalankan `npm run dev`, biarkan proses itu tetap hidup selama development.
- Jika tidak ingin menjalankan Vite dev server, gunakan `npm run build`.

### 12. Jalankan aplikasi

Cara paling sederhana:

```powershell
php artisan serve
```

Lalu buka:

```text
http://127.0.0.1:8000
```

## Akun Awal Seeder

Jika Anda menjalankan `php artisan migrate --seed`, akun awal yang tersedia:

### Admin

- Username: `admin`
- Email: `admin@grattendance.com`
- Password: `admin123`

### Guru/Pamong contoh

- Username: `guru`
- Email: `guru@grattendance.com`
- Password: `guru123`

Catatan:

- Setelah login pertama di lingkungan nyata, ganti password default.
- Jangan gunakan kredensial contoh ini di server publik.

## Langkah Cek Setelah Install

Setelah aplikasi berhasil jalan, cek beberapa URL berikut:

- `/`
- `/login`
- `/dashboard`
- `/settings`
- `/presensi`
- `/tracer-karakter`
- `/rpg-admin`

Jika semua terbuka normal, berarti setup dasar sudah benar.

## Perintah yang Sering Dipakai

### Menjalankan server lokal

```powershell
php artisan serve
```

### Menjalankan migration ulang dari awal

```powershell
php artisan migrate:fresh --seed
```

### Build asset

```powershell
npm run build
```

### Development asset watcher

```powershell
npm run dev
```

### Cache view/config/route

```powershell
php artisan optimize
```

### Membersihkan cache

```powershell
php artisan optimize:clear
```

## Catatan Khusus XAMPP

- Pastikan modul `openssl`, `fileinfo`, `mbstring`, dan `pdo_mysql` aktif di PHP XAMPP.
- Jika upload file tidak tampil, cek kembali hasil `php artisan storage:link`.
- Jika halaman terlihat tanpa styling, jalankan ulang:

```powershell
npm run build
```

- Jika ada error koneksi database, cek lagi nilai `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan apakah MySQL XAMPP benar-benar hidup.

## Catatan Pengembangan

Repositori ini sudah memakai pola UI yang konsisten untuk:

- layout admin, siswa, ortu, publik
- dark mode dan light mode
- page header
- empty state
- tombol aksi
- konfirmasi destruktif
- notifikasi UI

Aturan tambahan untuk agent/developer ada di file:

```text
AGENTS.md
```

## Rekomendasi Saat Deploy

- set `APP_ENV=production`
- set `APP_DEBUG=false`
- pakai database MySQL terpisah untuk production
- jalankan `php artisan storage:link`
- jalankan `npm run build`
- jangan commit `.env`
- ganti semua kredensial default
- batasi akses endpoint utilitas jika membuat script tambahan

## Lisensi

Proyek ini menggunakan lisensi `Apache-2.0`.

Alasan pemilihan:

- tetap permisif untuk penggunaan, modifikasi, dan distribusi
- lebih kuat secara legal dibanding MIT karena mencakup grant paten
- jelas untuk kebutuhan kolaborasi tim dan publikasi di GitHub

Hak cipta dan pengelolaan proyek ini dicantumkan atas nama `PKG Panunggangan` oleh `MubaleghJoss (083818393029)`.

Lihat file [LICENSE](LICENSE) untuk teks lisensi lengkap.
