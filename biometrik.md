# Rekam Stack Biometrik

Dokumen ini merekam stack login biometrik yang saat ini sudah ada di repo `pkg-v3`, termasuk jalur route, controller, view, model, session flow, dan titik integrasi ke ekosistem login utama.

## 1. Tujuan fitur

Fitur biometrik di repo ini dipakai untuk:

- mendaftarkan perangkat biometrik setelah user sudah login,
- login ulang memakai sidik jari atau face unlock tanpa mengetik kredensial manual,
- bekerja lintas 3 ekosistem auth:
  - pamong/admin (`guard: web`)
  - siswa (`guard: siswa`)
  - orang tua (`guard: ortu`)

Implementasi saat ini memakai browser WebAuthn API (`navigator.credentials.create()` dan `navigator.credentials.get()`) dan sudah diverifikasi di server memakai library `lbuchs/webauthn`.

## 2. Stack utama

### Backend framework

- Laravel 12
- library server-side WebAuthn: `lbuchs/webauthn`
- auth berbasis multi-guard session:
  - `web`
  - `siswa`
  - `ortu`

File kunci:

- `config/auth.php`
- `routes/web.php`
- `app/Http/Controllers/Auth/WebAuthnController.php`

### Frontend/browser

- native browser WebAuthn API:
  - `PublicKeyCredential`
  - `navigator.credentials.create`
  - `navigator.credentials.get`
- UI feedback:
  - `SweetAlert2` pada halaman login dan halaman pengaturan biometrik

### Persistence

- tabel: `webauthn_credentials`
- model: `App\Models\WebAuthnCredential`
- migrasi:
  - `database/migrations/2026_03_07_100000_create_webauthn_credentials_table.php`
  - `database/migrations/2026_03_07_110000_update_webauthn_credentials_multi_user.php`

## 3. Peta auth utama

### Login biasa

Controller login utama:

- pamong/admin: `app/Http/Controllers/Auth/LoginController.php`
- siswa: `app/Http/Controllers/Auth/SiswaAuthController.php`
- ortu: `app/Http/Controllers/Auth/OrtuAuthController.php`

View login:

- pamong/admin: `resources/views/auth/login.blade.php`
- siswa: `resources/views/auth/siswa-login.blade.php`
- ortu: `resources/views/auth/ortu-login.blade.php`

Semua halaman login di atas juga memuat tombol biometrik yang memanggil route WebAuthn publik yang sama:

- pamong/admin:
  - `POST /webauthn/login-options`
  - `POST /webauthn/login`
- siswa:
  - `POST /siswa/webauthn/login-options`
  - `POST /siswa/webauthn/login`
- ortu:
  - `POST /ortu/webauthn/login-options`
  - `POST /ortu/webauthn/login`

## 4. Route biometrik

### Route publik untuk login biometrik

Ada di `routes/web.php`:

- prefix `/webauthn`
- controller: `App\Http\Controllers\Auth\WebAuthnController`

Method yang dipakai:

- `loginOptions()`
- `login()`

Tujuan:

- login biometrik bisa dipakai dari halaman login tanpa user sudah punya session auth aktif.

### Route biometrik untuk siswa

Prefix:

- `/siswa/webauthn/*`

Route yang ada:

- `POST /siswa/webauthn/login-options`
- `POST /siswa/webauthn/login`
- `GET /siswa/webauthn/has-credentials`
- `GET /siswa/webauthn/register-options`
- `POST /siswa/webauthn/register`
- `GET /siswa/webauthn/status`
- `DELETE /siswa/webauthn/{id}`
- `POST /siswa/webauthn/dismiss-prompt`
- `GET /siswa/biometrik`

Catatan:

- route `GET /siswa/webauthn/has-credentials` sudah tersedia di `WebAuthnController`

### Route biometrik untuk ortu

Prefix:

- `/ortu/webauthn/*`

Route yang ada:

- `POST /ortu/webauthn/login-options`
- `POST /ortu/webauthn/login`
- `GET /ortu/webauthn/has-credentials`
- `GET /ortu/webauthn/register-options`
- `POST /ortu/webauthn/register`
- `GET /ortu/webauthn/status`
- `DELETE /ortu/webauthn/{id}`
- `POST /ortu/webauthn/dismiss-prompt`
- `GET /ortu/biometrik`

### Route biometrik untuk pamong/admin

Route terlindungi `auth`:

- `GET /webauthn/register-options`
- `POST /webauthn/register`
- `GET /webauthn/status`
- `DELETE /webauthn/{id}`
- `POST /webauthn/dismiss-prompt`
- `GET /biometrik`

### Route utilitas publik admin/pamong

- `POST /webauthn/login-options`
- `POST /webauthn/login`
- `GET /webauthn/has-credentials`

## 5. Controller inti

File:

- `app/Http/Controllers/Auth/WebAuthnController.php`

Method yang ada:

- `resolveUser()`
- `registerOptions()`
- `register()`
- `loginOptions()`
- `login()`
- `status()`
- `destroy()`
- `dismissPrompt()`
- `settingsPage()`

### `resolveUser()`

Fungsi:

- mendeteksi guard aktif:
  - `siswa`
  - `web`
  - `ortu`
- mengembalikan metadata user:
  - object user
  - `type`
  - `guard`
  - `name`
  - `identifier`

Mapping user type:

- `siswa` => guard `siswa`
- `admin` => guard `web`
- `ortu` => guard `ortu`

### `registerOptions()`

Fungsi:

- membuat challenge registrasi,
- menyimpan challenge ke session `webauthn_challenge`,
- mengirim opsi WebAuthn registration ke browser.

Output penting:

- `challenge`
- `rp`
- `user`
- `pubKeyCredParams`
- `authenticatorSelection`
- `timeout`
- `attestation`

### `register()`

Fungsi:

- menerima `credential_id`,
- mengecek apakah device itu sudah pernah disimpan untuk user yang sama,
- menyimpan record ke tabel `webauthn_credentials`,
- menghapus session `webauthn_challenge`.

Data yang disimpan saat ini:

- `user_id`
- `user_type`
- `credential_id`
- `credential_public_key`
- `signature_counter`
- `attestation_format`
- `aaguid`
- `transports`
- `user_handle`
- `device_name`

### `loginOptions()`

Fungsi:

- membuat challenge login,
- menyimpan challenge ke session `webauthn_login_challenge`,
- mengirim opsi assertion ke browser.

Output penting:

- `challenge`
- `rpId`
- `timeout`
- `userVerification`
- `allowCredentials`

### `login()`

Fungsi:

- menerima `credential_id`,
- mencari credential di database,
- resolve user dari credential,
- login user ke guard yang sesuai,
- update `last_login_at` atau `last_used_at`,
- regenerate session,
- kirim URL redirect dashboard sesuai user type.

Route dashboard hasil login:

- `admin` => `dashboard`
- `siswa` => `siswa.dashboard`
- `ortu` => `ortu.dashboard`

### `status()`

Fungsi:

- mengembalikan daftar perangkat biometrik milik user aktif,
- dipakai halaman pengaturan biometrik.

### `destroy()`

Fungsi:

- menghapus credential biometrik milik user yang sedang login.

### `dismissPrompt()`

Fungsi:

- menyimpan flag session `biometric_prompt_dismissed`
- dipakai popup biometrik agar tidak terus muncul pada session yang sama

### `settingsPage()`

View yang dipilih berdasarkan guard aktif:

- siswa => `resources/views/siswa/biometrik.blade.php`
- ortu => `resources/views/ortu/biometrik.blade.php`
- admin => `resources/views/admin/biometrik.blade.php`

## 6. Model dan storage

File model:

- `app/Models/WebAuthnCredential.php`

Field utama:

- `user_id`
- `user_type`
- `credential_id`
- `device_name`
- `last_used_at`

Method penting:

- `getUser()`
- `getGuardName()`
- `getDashboardRoute()`

Mapping model user:

- `siswa` => `App\Models\Siswa`
- `admin` => `App\Models\User`
- `ortu` => `App\Models\Siswa`

Catatan:

- ortu memakai model `Siswa`, tetapi autentikasinya lewat guard `ortu`
- credential baru sekarang menyimpan `credential_public_key`, `signature_counter`, `user_handle`, dan metadata authenticator lain yang dibutuhkan untuk verifikasi server-side

## 7. View dan UI biometrik

### Halaman login yang punya tombol biometrik

- `resources/views/auth/login.blade.php`
- `resources/views/auth/siswa-login.blade.php`
- `resources/views/auth/ortu-login.blade.php`

Setiap halaman:

- cek dukungan `PublicKeyCredential`
- cek `isUserVerifyingPlatformAuthenticatorAvailable()`
- sembunyikan blok biometrik bila browser/perangkat tidak mendukung
- memanggil:
  - `POST /webauthn/login-options`
  - `navigator.credentials.get(...)`
  - `POST /webauthn/login`

### Halaman pengaturan biometrik

- `resources/views/admin/biometrik.blade.php`
- `resources/views/siswa/biometrik.blade.php`
- `resources/views/ortu/biometrik.blade.php`

Tanggung jawab halaman ini:

- daftar perangkat biometrik user
- tombol tambah perangkat
- hapus perangkat biometrik
- pengecekan browser support

### Popup biometrik global

File:

- `resources/views/components/biometric-prompt.blade.php`
- `resources/views/components/biometric-environment-alert.blade.php`

Fungsi:

- muncul setelah login bila user belum punya credential biometrik valid atau masih memakai credential lama
- bisa dipaksa wajib atau opsional lewat `PopupManager`
- memanggil flow registrasi dari layout yang sedang aktif
- style dan interaksi popup sekarang dipusatkan ke `resources/css/app.css` dan `resources/js/biometric.js`, bukan inline script besar di Blade

Layout yang meng-include komponen ini:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/siswa.blade.php`
- `resources/views/layouts/ortu.blade.php`

## 8. Composer Blade dan ekosistem popup

File:

- `app/Providers/BladeServiceProvider.php`
- `app/Support/PopupManager.php`

### `BladeServiceProvider`

`View::composer('components.biometric-prompt', ...)` menyuntikkan:

- user aktif biometrik
- tipe user biometrik
- URL register biometrik
- URL dismiss popup
- URL halaman pengaturan biometrik
- status biometrik user (`active`, `legacy`, `inactive`)
- jumlah credential lama bila ada

Mapping URL yang diinjeksi:

- siswa:
  - register options => `/siswa/webauthn/register-options`
  - dismiss => `/siswa/webauthn/dismiss-prompt`
  - settings => route `siswa.biometrik`
- admin:
  - register options => `/webauthn/register-options`
  - dismiss => `/webauthn/dismiss-prompt`
  - settings => route `biometrik`
- ortu:
  - register options => `/ortu/webauthn/register-options`
  - dismiss => `/ortu/webauthn/dismiss-prompt`
  - settings => route `ortu.biometrik`

### `PopupManager`

Konfigurasi popup biometrik ada di key:

- `biometric_prompt`

Properti default:

- `default_enabled = true`
- `default_required = false`

Target popup:

- siswa
- ortu
- pamong/admin

## 9.1 Guardrail environment WebAuthn

Halaman pengaturan biometrik sekarang juga mengecek kondisi environment dasar:

- host aktif request
- origin aktif `http/https`
- host dari `APP_URL`

Jika host aktif berbeda dari `APP_URL`, atau instalasi non-local masih berjalan tanpa HTTPS, halaman biometrik menampilkan warning agar admin tahu penyebab login biometrik bisa gagal walau kode backend sudah benar.

## 9. Titik integrasi dashboard dan status biometrik

Dashboard memeriksa status biometrik untuk menampilkan badge/shortcut:

- admin: `app/Http/Controllers/DashboardController.php`
  - var: `hasBiometricAdmin`, `biometricStatusAdmin`
- siswa: `app/Http/Controllers/SiswaDashboardController.php`
  - var: `biometricStatus`
- ortu: `app/Http/Controllers/OrtuDashboardController.php`
  - var: `hasBiometricOrtu`, `biometricStatusOrtu`

View yang menampilkan status/shortcut:

- `resources/views/dashboard/partials/secondary-panels.blade.php`
- `resources/views/siswa/dashboard.blade.php`
- `resources/views/ortu/dashboard.blade.php`

## 10. Flow runtime yang berjalan sekarang

### Flow registrasi perangkat biometrik

1. User login biasa.
2. User masuk ke halaman biometrik atau menerima popup aktivasi.
3. Browser memanggil `register-options`.
4. Backend menyimpan challenge di session.
5. Browser memanggil `navigator.credentials.create()`.
6. Browser mengirim `credential_id`, `clientDataJSON`, dan `attestationObject` ke endpoint `register`.
7. Backend memverifikasi hasil registrasi di server lalu menyimpan record ke tabel `webauthn_credentials`.

### Flow login biometrik

1. User membuka halaman login pamong/siswa/ortu.
2. Browser mengecek dukungan autentikator biometrik lokal.
3. User menekan tombol login sidik jari.
4. Browser memanggil `POST /webauthn/login-options`.
5. Backend menyimpan challenge login ke session.
6. Browser memanggil `navigator.credentials.get()`.
7. Browser mengirim `credential_id`, `clientDataJSON`, `authenticatorData`, `signature`, dan `userHandle` ke `POST /webauthn/login`.
8. Backend mencari `credential_id`, memverifikasi assertion, dan mengecek signature counter.
9. Backend login ke guard yang sesuai.
10. Backend mengirim redirect dashboard berdasarkan `user_type`.

## 11. Session dan state

### Session key

- `webauthn_challenge`
- `webauthn_login_challenge`
- `biometric_prompt_dismissed`

## 12. Ketergantungan dan batasan implementasi saat ini

### Yang sudah ada

- multi-guard biometrik lintas admin, siswa, ortu
- halaman aktivasi biometrik per ekosistem
- popup aktivasi biometrik setelah login
- login biometrik dari halaman login
- status dashboard dan popup yang membedakan credential aktif vs legacy

### Yang sekarang sudah dirapikan

- verifikasi server-side registrasi via `processCreate`
- verifikasi server-side login via `processGet`
- penyimpanan public key credential
- verifikasi signature counter
- penyimpanan metadata transport dan user handle
- audit log biometrik khusus
- method `hasCredentials()` sudah tersedia

### Catatan kompatibilitas

Credential biometrik lama yang dulu hanya menyimpan `credential_id` tidak bisa lagi dipakai sebagai passkey yang tervalidasi penuh. User dengan credential lama perlu login biasa lalu mendaftarkan ulang biometrik.

## 13. File indeks stack biometrik

### Route dan auth

- `routes/web.php`
- `config/auth.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/SiswaAuthController.php`
- `app/Http/Controllers/Auth/OrtuAuthController.php`
- `app/Http/Controllers/Auth/WebAuthnController.php`

### Model dan database

- `app/Models/WebAuthnCredential.php`
- `database/migrations/2026_03_07_100000_create_webauthn_credentials_table.php`
- `database/migrations/2026_03_07_110000_update_webauthn_credentials_multi_user.php`

### Dashboard dan status

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/SiswaDashboardController.php`
- `app/Http/Controllers/OrtuDashboardController.php`
- `app/Support/BiometricStatus.php`

### Blade dan popup

- `app/Providers/BladeServiceProvider.php`
- `app/Support/PopupManager.php`
- `resources/views/components/biometric-prompt.blade.php`
- `resources/views/components/biometric-environment-alert.blade.php`
- `resources/css/app.css`
- `resources/js/biometric.js`

### View login

- `resources/views/auth/login.blade.php`
- `resources/views/auth/siswa-login.blade.php`
- `resources/views/auth/ortu-login.blade.php`

### View pengaturan biometrik

- `resources/views/admin/biometrik.blade.php`
- `resources/views/siswa/biometrik.blade.php`
- `resources/views/ortu/biometrik.blade.php`

## 14. Ringkasan singkat

Stack biometrik di repo ini sudah tersebar rapi di route, controller auth, model credential, halaman login, halaman pengaturan, popup global, dan dashboard status. Secara UX, fitur ini membentuk satu ekosistem yang utuh untuk admin, siswa, dan ortu. Secara backend security, registrasi dan login kini sudah diverifikasi penuh di server untuk credential baru yang didaftarkan ulang.
