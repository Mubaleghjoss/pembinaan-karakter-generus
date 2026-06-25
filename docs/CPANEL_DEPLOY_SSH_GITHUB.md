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

Jika muncul:

```text
bash: composer: command not found
```

lanjut ke bagian "Jika Composer belum tersedia" di bawah. Jangan lanjut `php artisan` dulu sebelum folder `vendor` tersedia.

## 1A. Jika Composer belum tersedia

Coba cek dulu apakah cPanel menyediakan Composer di path lain:

```bash
php -v
which php
which composer
which composer2
/opt/cpanel/composer/bin/composer --version
```

Jika `/opt/cpanel/composer/bin/composer` ada, jalankan:

```bash
cd ~/pembinaan-karakter-generus
/opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
```

Jika tetap tidak ada Composer, ada dua pilihan.

### Pilihan A: install Composer lokal di akun hosting

```bash
mkdir -p ~/bin
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=$HOME/bin --filename=composer
php -r "unlink('composer-setup.php');"

cd ~/pembinaan-karakter-generus
~/bin/composer install --no-dev --optimize-autoloader
```

Jika hosting memblokir download dari terminal, pakai pilihan B.

### Pilihan B: upload `vendor` manual dari lokal

Di laptop lokal, dari folder project:

```powershell
composer install --no-dev --optimize-autoloader
Compress-Archive -Path vendor -DestinationPath vendor.zip -Force
```

Upload `vendor.zip` ke:

```text
/home/pkgj2934/pembinaan-karakter-generus/vendor.zip
```

Lalu di Terminal cPanel:

```bash
cd ~/pembinaan-karakter-generus
unzip -oq vendor.zip
rm -f vendor.zip
```

Catatan: `vendor` memang tidak masuk GitHub supaya repo tetap ringan. Jika tidak ada Composer di server, `vendor` wajib di-upload manual setiap dependency PHP berubah.

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

Pastikan `APP_KEY` server tidak kosong. Jika website menampilkan 500 dan log Laravel berisi:

```text
Unsupported cipher or incorrect key length
```

jalankan:

```bash
cd ~/pembinaan-karakter-generus
/opt/alt/php82/usr/bin/php artisan key:generate --force
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

Lalu jalankan ulang deploy. Catatan: mengganti `APP_KEY` akan membuat session login lama tidak valid. Untuk deploy pertama ini aman.

Catatan PHP cPanel:

MultiPHP Manager mengubah PHP untuk domain/website. Terminal cPanel bisa tetap memakai system PHP lama, misalnya PHP 7.4. Jika command `php artisan ...` menampilkan:

```text
Composer detected issues in your platform:
Your Composer dependencies require a PHP version ">= 8.2.0"
```

pakai PHP 8.2 eksplisit:

```bash
/opt/alt/php82/usr/bin/php -v
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

Jika path itu tidak ada, coba:

```bash
/opt/cpanel/ea-php82/root/usr/bin/php -v
/opt/cpanel/ea-php82/root/usr/bin/php artisan optimize:clear
```

Script `deploy/cpanel/deploy_ssh.sh` sudah otomatis mencari PHP CLI 8.2 pada path tersebut.

### Extension PHP 8.2 wajib

Pastikan extension PHP 8.2 berikut aktif di cPanel:

```text
ctype
curl
dom
fileinfo
gd
iconv
mbstring
openssl
pdo
pdo_mysql
session
simplexml
tokenizer
xml
xmlreader
xmlwriter
zip
zlib
```

Jika deploy berhenti dengan:

```text
Class "DOMDocument" not found
```

aktifkan minimal `dom` dan `xml`. Pada cPanel biasanya ada di:

```text
cPanel -> Select PHP Version -> Extensions
```

Pilih PHP 8.2, centang extension yang kurang, lalu jalankan ulang deploy.

Jika website tetap menampilkan:

```text
Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.2.0".
```

padahal MultiPHP Manager sudah PHP 8.2, penyebab paling umum adalah `public_html/.htaccess` kehilangan handler PHP cPanel saat asset public disalin. Solusi cepat:

1. buka cPanel -> MultiPHP Manager;
2. pilih domain;
3. pilih PHP 8.2;
4. klik Apply lagi.

Setelah itu jalankan ulang:

```bash
cd ~/pembinaan-karakter-generus
git pull origin main
bash deploy/cpanel/deploy_ssh.sh
```

Script deploy terbaru akan mempertahankan block handler PHP cPanel di `public_html/.htaccess`.

Jika `cat ~/public_html/.htaccess` tidak menampilkan block handler sama sekali, tambahkan fallback alt-php82:

```bash
cat >> ~/public_html/.htaccess <<'EOF'

# php -- BEGIN cPanel-generated handler, do not edit
# Set the alt-php82 package as the default PHP programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-alt-php82 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
EOF
```

Lalu refresh website. Jika masih memakai PHP lama, ulangi Apply PHP 8.2 dari MultiPHP Manager.

## 2. Hubungkan public_html ke app Laravel

Cara utama adalah menjalankan script deploy:

```bash
cd ~/pembinaan-karakter-generus
bash deploy/cpanel/deploy_ssh.sh
```

Script deploy akan menyalin asset publik, tetapi tidak menimpa `public_html/storage`.

Jika perlu copy manual, jangan copy folder `storage` dari source ke `public_html`. Jalankan:

```bash
cp ~/pembinaan-karakter-generus/deploy/cpanel/public_html_index.pembinaan-karakter-generus.php.example ~/public_html/index.php
rsync -a --exclude='/storage' --exclude='/hot' ~/pembinaan-karakter-generus/public/ ~/public_html/
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

Catatan storage:

- Di lokal Windows, `public/storage` biasanya terlihat seperti shortcut/junction. Itu normal. Targetnya adalah `storage/app/public`.
- Di server cPanel, jangan bergantung pada symlink jika hosting membatasi symlink. Project ini memakai `FILESYSTEM_PUBLIC_ROOT=/home/pkgj2934/public_html/storage`, sehingga upload server disimpan langsung ke folder fisik `public_html/storage`.
- Folder `public_html/storage` adalah data runtime user. Jangan dihapus saat deploy, jangan diisi dari GitHub, dan jangan ditimpa dari folder lokal kecuali memang sedang restore/unggah file media lama.

## 4. Database: pilih skema saja atau isi penuh dari lokal

Ada dua jenis pekerjaan database:

1. **Backup database server**: menyimpan kondisi database server sebelum diubah. Ini bukan mengambil data dari lokal.
2. **Sinkron database**:
   - **skema saja**: hanya menambah kolom/tabel baru di server, data server tetap dipertahankan;
   - **isi penuh dari lokal**: export database lokal lalu import ke server, cocok jika server masih kosong atau memang ingin server sama persis dengan lokal.

Jangan jalankan import penuh dari lokal kalau server sudah punya data penting yang tidak ada di lokal, karena data server bisa tertimpa.

### 4A. Backup database server dulu

Jalankan satu per satu. Saat muncul `Enter password:`, ketik password database. Password tidak akan terlihat di layar; itu normal.

Backup dulu:

```bash
mysqldump -u pkgj2934_app -p pkgj2934_app > ~/backup_pkgj2934_app_$(date +%F_%H%M).sql
```

Jika muncul pesan:

```text
mysqldump: Deprecated program name. It will be removed in a future release, use '/usr/bin/mariadb-dump' instead
```

itu hanya warning. Tetap masukkan password dan tunggu sampai kembali ke prompt.

Alternatif jika ingin mengikuti saran MariaDB:

```bash
mariadb-dump -u pkgj2934_app -p pkgj2934_app > ~/backup_pkgj2934_app_$(date +%F_%H%M).sql
```

### 4B. Jika server sudah punya data: jalankan skema saja

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

### 4C. Jika server kosong: export database lokal lalu import ke server

Database lokal project ini terbaca dari `.env` lokal sebagai:

```text
DB_DATABASE=pkgv2
```

Export dari laptop lokal Windows:

```powershell
E:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --default-character-set=utf8mb4 pkgv2 > E:\xampp\htdocs\pkg-v3\pkgv2_local_export.sql
```

Jika MySQL lokal memakai password, gunakan:

```powershell
E:\xampp\mysql\bin\mysqldump.exe -u root -p --single-transaction --default-character-set=utf8mb4 pkgv2 > E:\xampp\htdocs\pkg-v3\pkgv2_local_export.sql
```

Upload file ini ke server:

```text
/home/pkgj2934/pkgv2_local_export.sql
```

Import ke database server:

```bash
mysql -u pkgj2934_app -p pkgj2934_app < ~/pkgv2_local_export.sql
```

Setelah import penuh, tetap aman menjalankan SQL skema terbaru untuk memastikan kolom deploy terakhir ada:

```bash
cd ~/pembinaan-karakter-generus
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_migration_2026_06_25_rpp_sync_schema.sql
```

Jika import phpMyAdmin berhenti pada foreign key `materi_rpp_journals_schedule_reminder_id_foreign`, artinya ada jurnal RPP yang menunjuk event kalender yang tidak ikut ada di tabel `schedule_reminders`.

Jangan import ulang penuh dulu. Jalankan SQL pemulihan ini di phpMyAdmin tab SQL, atau lewat SSH:

```bash
cd ~/pembinaan-karakter-generus
mysql -u pkgj2934_app -p pkgj2934_app < database/manual_sql/manual_fix_2026_06_26_rpp_journal_orphan_foreigns.sql
```

SQL tersebut mempertahankan jurnalnya, tetapi mengosongkan link parent yang sudah tidak valid, lalu menambahkan foreign key yang gagal.

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
- file upload runtime lokal di `storage/app/public`;
- file upload runtime server di `public_html/storage`;
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
