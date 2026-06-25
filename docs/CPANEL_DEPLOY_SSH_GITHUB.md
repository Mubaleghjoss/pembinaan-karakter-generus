# Deploy SSH cPanel via GitHub

Panduan ini untuk repo:

```text
https://github.com/Mubaleghjoss/pembinaan-karakter-generus.git
```

Target struktur server:

```text
/home/pkgj2934/pembinaan-karakter-generus   -> source Laravel, di luar public_html
/home/pkgj2934/public_html                  -> document root domain
/home/pkgj2934/public_html/storage          -> file upload publik
```

Database server:

```text
DB_DATABASE=pkgj2934_app
DB_USERNAME=pkgj2934_app
DB_PASSWORD=isi manual di .env server
```

Jangan commit `.env` atau password database ke GitHub.

## 1. Setup pertama di server

Buka Terminal cPanel/SSH:

```bash
cd ~
git clone https://github.com/Mubaleghjoss/pembinaan-karakter-generus.git pembinaan-karakter-generus
cd ~/pembinaan-karakter-generus
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate --force
```

Edit `.env` server:

```bash
nano .env
```

Minimal isi production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=pkgj2934_app
DB_USERNAME=pkgj2934_app
DB_PASSWORD=password_database_server

FILESYSTEM_DISK=public
FILESYSTEM_PUBLIC_ROOT=/home/pkgj2934/public_html/storage

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Jika website sudah HTTPS, tambahkan:

```env
SESSION_SECURE_COOKIE=true
```

## 2. Hubungkan public_html ke app Laravel

Jalankan:

```bash
cp ~/pembinaan-karakter-generus/deploy/cpanel/public_html_index.pembinaan-karakter-generus.php.example ~/public_html/index.php
cp -a ~/pembinaan-karakter-generus/public/. ~/public_html/
rm -f ~/public_html/hot
```

Pastikan file ini ada:

```text
/home/pkgj2934/public_html/build/manifest.json
```

## 3. Buat folder upload publik

```bash
mkdir -p \
  ~/public_html/storage/logos \
  ~/public_html/storage/avatars \
  ~/public_html/storage/berita \
  ~/public_html/storage/materi \
  ~/public_html/storage/photos \
  ~/public_html/storage/qrcodes \
  ~/public_html/storage/reward-templates \
  ~/public_html/storage/chat \
  ~/public_html/storage/logs \
  ~/public_html/storage/certificates \
  ~/public_html/storage/pr-submissions \
  ~/public_html/storage/siswa \
  ~/public_html/storage/tugas-bukti \
  ~/public_html/storage/tugas-bukti-audio
```

File media lama seperti logo, avatar, PDF materi, bukti tugas, dan foto siswa harus dipindahkan/manual upload ke `public_html/storage` sesuai path database.

## 4. Sinkron skema database server

Backup dulu:

```bash
mysqldump -u pkgj2934_app -p pkgj2934_app > ~/backup_pkgj2934_app_$(date +%F_%H%M).sql
```

Lalu jalankan SQL manual terbaru untuk fitur RPP, target materi, jurnal, dan sync:

```bash
cd ~/pembinaan-karakter-generus
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migration_2026_06_25_rpp_sync_schema.sql
```

Jika server belum pernah menerima update manual sebelumnya, jalankan file manual SQL lama berurutan sesuai kebutuhan:

```bash
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migrations_2026_04_09_to_2026_04_11.sql
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migrations_2026_04_12_full.sql
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migrations_2026_04_15_organizational_teams.sql
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migrations_2026_04_23_full.sql
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migration_2026_04_29_attendance_schedule_date_range.sql
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migration_2026_05_09_lapor_pkg_permissions.sql
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migration_2026_06_25_rpp_sync_schema.sql
```

Jika memakai migrasi Laravel langsung, alternatifnya:

```bash
php artisan migrate --force
```

Pilih salah satu jalur utama. Jangan mencampur tanpa cek tabel `migrations`, karena bisa membuat status migrasi tidak rapi.

## 5. Deploy update berikutnya

Setiap selesai push dari lokal ke GitHub, di server cukup jalankan:

```bash
cd ~/pembinaan-karakter-generus
bash deploy/cpanel/deploy_ssh.sh
```

Jika ada perubahan database baru, jalankan SQL manual/migration setelah `git pull`.

## 6. Alur kerja lokal ke GitHub

Di laptop lokal:

```bash
git add .
git commit -m "Update fitur PKG"
git push origin main
```

Yang masuk GitHub:

- source code Laravel;
- migration dan manual SQL;
- `public/build` untuk asset frontend production;
- `composer.lock` dan `package-lock.json`.

Yang tidak masuk GitHub:

- `.env`;
- `vendor`;
- `node_modules`;
- `uploads`;
- backup database;
- `storage/logs`;
- file upload runtime di `storage/app/public`;
- `public/hot`.

## 7. Checklist setelah deploy

Jalankan:

```bash
cd ~/pembinaan-karakter-generus
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Cek:

- halaman login terbuka;
- `/materi` terbuka;
- `/materi-rpp-journals` terbuka;
- `public_html/build/manifest.json` ada;
- upload baru masuk ke `public_html/storage`;
- menu Tarik Data tidak error `rpp_is_enabled cannot be null`.
