# Rancangan: Portal Pamong & Pengurus PKG — Dashboard Verifikasi + Peran/Badge + Hak Presensi Manual

> **Untuk Hermes:** rancangan ini belum dieksekusi. Implementasi bertahap per fase, uji lokal dulu, deploy setelah disetujui user.

**Tujuan:** Membuat tampilan Pamong & Pengurus PKG serapi portal Ortu/Generus, dashboardnya berorientasi VERIFIKASI (bukan statistik), memberi badge peran yang jelas, dan mengatur hak akses presensi manual per orang.

**Arsitektur:** Tetap memakai satu layout staff (`layouts/app.blade.php`) + satu controller dashboard (`DashboardController`), tetapi isinya disusun ulang mengikuti pola portal ortu: kartu identitas → antrean kerja → aksi cepat. Hak akses tetap memakai sistem `PamongPermission` yang sudah ada (tidak membuat sistem izin baru), ditambah lapisan "peran tugas" untuk badge.

**Tech Stack:** Laravel 12 + Blade + Alpine.js + Tailwind. Tanpa paket baru.

---

## Temuan Audit (kondisi nyata saat ini)

### Peran di database
| role | display_name | jumlah user |
|---|---|---|
| admin | Administrator | 2 |
| teacher | Pamong | 11 |
| pkg_manager | Pengurus PKG | 10 |
| guru | Guru | 0 |
| student | Siswa | 0 |

### TEMUAN KRITIS — izin per-menu praktis tidak berlaku
- `PamongPermission` ada 22 record, **21 di antaranya `is_excluded = true`**.
- `PamongPermission::hasMenuAccess()` dan `hasCrudPermission()` me-return `true` tanpa syarat bila `is_excluded` true.
- Akibatnya: **21 akun operasional bisa mengakses semua menu**, termasuk presensi manual, walau daftar `menu_permissions` mereka hanya berisi 2 item.
- Verifikasi: hitung akses `manual_attendance` → 21 dari 21 akun operasional lolos.
- Kesimpulan: permintaan "hak akses presensi manual hanya untuk pamong dan beberapa pengurus" **tidak akan berpengaruh** sebelum flag `is_excluded` dibersihkan. Ini keputusan kebijakan, bukan teknis → butuh persetujuan user (lihat Pertanyaan Terbuka Q1).

### Infrastruktur presensi manual yang SUDAH ada (tidak perlu dibuat baru)
- Menu `manual_attendance` dengan operasi `view`, `create`, `all_students` sudah terdaftar di `PamongPermission::getAvailableCrudOperations()`.
- `User::canAccessAllManualAttendanceStudents()` sudah memeriksa `manual_attendance` + `create`.
- `Siswa::forManualAttendance($user)` sudah membatasi daftar siswa (pamong → hanya binaan; yang punya `all_students` → semua).
- Route sudah ada: `absen-manual/siswa`, `absen-manual/pamong`, dan redirect `/presensi?tab=input`.
- Artinya: pekerjaan tinggal **mengatur data izin + UI pengaturannya**, bukan membangun fiturnya.

### Basis badge peran yang sudah ada
- Kolom `users.organizational_title` (terisi hanya 4 user) dan relasi `organizationalTeam` (1 tim).
- `User::getRoleLabelAttribute()` sudah memetakan: admin → "Admin", pkg_manager → "Pengurus PKG", teacher/guru → "Pamong".
- Layout staff baru menampilkan `role->display_name` polos di pojok profil (1 tempat saja).

### Masalah tampilan yang ditemukan
- `resources/views/dashboard.blade.php` = **723 baris**, memuat: hero QR besar, kartu presensi pamong, banner info, jurnal, 4 kartu statistik, leaderboard siswa binaan, blok "Presensi Pamong Hari Ini" (admin), 3 kartu karakter, lalu panel sekunder yang dimuat lazy.
- Isi dashboard didominasi **statistik**, sementara antrean pekerjaan (yang perlu diverifikasi) hanya muncul sebagai teks kecil `{{ $pendingVerifications }} belum diverifikasi`.
- Sidebar `layouts/app.blade.php` punya **45 item nav** — jauh lebih padat dari portal ortu (±10) dan siswa (±12).
- Antrean verifikasi nyata di data produksi lokal: **Tugas PKG 36**, **Presensi belum diverifikasi 76**, **Laporan penyaksian 8**, Bacaan Qur'an 0.
  → Angka-angka inilah yang seharusnya menjadi isi utama dashboard.

---

## Rancangan Dashboard Staff (fokus verifikasi)

Mengikuti pola portal ortu: identitas → antrean → aksi cepat → ringkasan tipis.

### Urutan blok baru
1. **Kartu identitas + badge peran**
   Nama, foto/inisial, badge peran utama (Admin / Pengurus PKG / Pamong) + badge peran tugas bila ada (mis. "Verifikator Tugas", "Petugas Presensi", "Koordinator Kelompok Sukamaju"). QR presensi pamong dipindah ke kartu ringkas (bukan hero besar).

2. **Presensi diri sendiri** (hanya bila jadwal presensi pamong terbuka)
   Satu baris status + tombol. Blok ini dipertahankan karena fungsional, tapi dipadatkan dari ±55 baris menjadi ±15 baris.

3. **ANTREAN VERIFIKASI — blok utama (baru)**
   Grid kartu, masing-masing menampilkan jumlah + tombol menuju halaman kerjanya, dan HANYA tampil bila user punya izinnya:
   | Kartu | Sumber angka | Tautan | Izin |
   |---|---|---|---|
   | Tugas PKG menunggu verifikasi | `SiswaKarakterChecklist` `verified_at` null (scope binaan) | halaman Tugas PKG | `tugas_pkg`/`pr` + `verify` |
   | Bacaan Al-Qur'an menunggu | `QuranReadingEntry` status pending | Tracer Bacaan Qur'an | `tracer_bacaan_quran` + `verify` |
   | Presensi belum diverifikasi | `Presensi` `is_verified` false (scope binaan) | Presensi | `presensi` + `verify` |
   | Laporan penyaksian pending | `LaporanPenyaksian::pending()` (scope pamong) | Laporan Penyaksian | `laporan_penyaksian` |
   | Jurnal RPP perlu diisi | `journalWorkflow->staffTasks()` | Jurnal RPP | `materi` |
   Kartu berangka 0 ditandai "aman/selesai" dengan warna netral, bukan disembunyikan (agar tidak terasa hilang).

4. **Aksi cepat** (4 tombol, mengikuti gaya menu cepat portal siswa)
   Input Presensi Manual (hanya bila berizin), Tracer Bacaan Qur'an, Data Generus, Kalender.

5. **Ringkasan tipis** — 1 baris angka: total generus binaan, kehadiran hari ini (%), rata-rata progres karakter. Leaderboard siswa binaan dipindah ke halaman gamifikasi (tidak lagi di dashboard), sejalan dengan keputusan sebelumnya untuk portal siswa.

6. **Blok khusus admin** — "Presensi Pamong Hari Ini" dan panel sekunder tetap ada tapi hanya untuk admin, dimuat lazy seperti sekarang.

### Yang DIHAPUS dari dashboard staff
- Hero QR besar (jadi kartu ringkas).
- Leaderboard siswa binaan (pindah ke halaman gamifikasi).
- Tren mingguan grafik (pindah ke panel sekunder/admin).
- 3 kartu karakter terpisah → dipadatkan jadi 1 baris ringkasan.

**Alasan (trade-off jujur):** dashboard sekarang informatif tapi tidak actionable — pamong harus menebak apa yang harus dikerjakan. Menukar statistik dengan antrean membuat halaman lebih berguna, dengan konsekuensi admin kehilangan pandangan cepat atas grafik; itu dikompensasi dengan menyisakan panel sekunder khusus admin.

---

## Rancangan Peran & Badge

### Konsep dua lapis (tidak menambah tabel role baru)
- **Lapis 1 — Peran akun** (sudah ada): Admin / Pengurus PKG / Pamong. Dipakai untuk badge utama dan warna.
- **Lapis 2 — Peran tugas** (baru, opsional per user): label bebas yang menjelaskan tugas spesifik, disimpan di kolom yang sudah ada `users.organizational_title` agar tanpa migrasi. Contoh: "Verifikator Tugas PKG", "Petugas Presensi", "Koordinator Kelompok X", "Bendahara".

### Tampilan badge
- Sidebar/profil: badge peran akun (warna: Admin indigo, Pengurus PKG slate, Pamong emerald) + peran tugas sebagai badge kedua abu.
- Dashboard: badge di kartu identitas.
- Daftar pengguna admin: kolom badge agar admin bisa melihat siapa berperan apa.
- Chat (ortu & siswa): sudah menampilkan badge "Admin"; ditambah menampilkan peran tugas bila ada.

### Penetapan peran tugas
Di halaman kelola pengguna (admin), tambah field "Peran tugas" berupa input teks dengan saran pilihan (datalist) agar konsisten tapi tetap fleksibel.

---

## Rancangan Hak Akses Presensi Manual

### Langkah wajib lebih dulu
Bersihkan `is_excluded = true` pada 21 akun operasional, lalu tetapkan izin eksplisit. Tanpa ini, pengaturan apa pun tidak berefek.

### Matriks izin yang diusulkan (default per peran)
| Menu / operasi | Pamong (teacher) | Pengurus PKG (pkg_manager) | Catatan |
|---|---|---|---|
| `manual_attendance` view+create | ✅ | ⬜ opsional per orang | inti permintaan user |
| `manual_attendance` all_students | ⬜ | ✅ bila ditunjuk | pamong hanya binaannya |
| `presensi` view+verify | ✅ | ✅ | verifikasi presensi |
| `tugas_pkg`/`pr` view+verify | ✅ | ✅ | verifikasi tugas |
| `tracer_bacaan_quran` view+verify | ✅ | ✅ | verifikasi bacaan |
| `laporan_penyaksian` view+tindak_lanjut | ✅ | ✅ | |
| `siswa` create/edit/delete | ⬜ | ⬜ | tetap admin |
| `gamification` adjust/reset | ⬜ | ⬜ | tetap admin |

### UI pengaturan
Di halaman izin pamong yang sudah ada, tambah:
- Tombol **"Terapkan Paket Izin"**: paket "Pamong Pembimbing", "Pengurus Verifikator", "Petugas Presensi" — sekali klik mengisi menu+CRUD sesuai matriks.
- Peringatan jelas pada toggle `is_excluded`: "Mengaktifkan ini membuat akun bisa mengakses SEMUA menu dan mengabaikan daftar izin di bawah."
- Kolom indikator di daftar: "Presensi manual: semua generus / hanya binaan / tidak berizin".

---

## Rencana Langkah (bertahap, tiap fase bisa dideploy sendiri)

### FASE 1 — Badge peran (risiko rendah, terlihat langsung)
1. Tambah accessor `roleBadge()` di `app/Models/User.php`: mengembalikan label + kelas warna untuk peran akun.
2. Buat komponen `resources/views/components/role-badge.blade.php` (peran akun + peran tugas opsional).
3. Pakai komponen di `layouts/app.blade.php` (pojok profil) dan daftar pengguna admin.
4. Uji: `php -l`, compile view, render dashboard staff untuk 1 akun teacher + 1 pkg_manager.
5. Commit + deploy (PHP/Blade saja).

### FASE 2 — Dashboard staff berorientasi verifikasi
1. `DashboardController::getPrimaryDashboardData()`: tambah `verificationQueue` (5 angka di tabel atas), masing-masing dihitung dengan scope binaan + dijaga izin. Pertahankan cache 60 detik.
2. Tulis ulang `resources/views/dashboard.blade.php` mengikuti urutan blok baru (target < 300 baris).
3. Pindahkan leaderboard binaan ke halaman gamifikasi admin.
4. Uji render untuk 3 tipe akun: admin, teacher, pkg_manager. Pastikan kartu yang tidak berizin tidak muncul.
5. Commit + deploy.

### FASE 3 — Perapian menu staff
1. Kelompokkan 45 item sidebar menjadi 4 grup: **Verifikasi** (tugas, bacaan, presensi, laporan), **Data** (generus, materi, kalender), **Komunikasi** (chat, grup, berita), **Pengelolaan** (admin-only).
2. Sembunyikan grup yang seluruh isinya tidak berizin (bukan menampilkan menu mati).
3. Tambah badge angka pada menu Verifikasi (jumlah antrean) seperti pola badge chat yang sudah ada.
4. Uji: sidebar untuk akun teacher tidak menampilkan menu admin.
5. Commit + deploy.

### FASE 4 — Hak akses presensi manual + paket izin
1. Tambah paket izin di model `PamongPermission` (konstanta `PERMISSION_PRESETS`).
2. Tambah tombol "Terapkan Paket Izin" + peringatan `is_excluded` di halaman izin pamong.
3. Tambah indikator status presensi manual di daftar pamong.
4. **Perubahan data (butuh persetujuan user):** matikan `is_excluded` untuk 21 akun dan terapkan paket sesuai peran. Dilakukan lewat script yang menampilkan dry-run dulu.
5. Uji: akun tanpa izin membuka `/absen-manual/siswa` → 403; akun pamong hanya melihat binaannya; akun berizin `all_students` melihat semua.
6. Commit + deploy, lalu jalankan perubahan data di produksi setelah izin user.

---

## Berkas yang Akan Berubah

| Berkas | Fase | Jenis |
|---|---|---|
| `app/Models/User.php` | 1 | tambah accessor badge |
| `resources/views/components/role-badge.blade.php` | 1 | baru |
| `resources/views/layouts/app.blade.php` | 1, 3 | badge + kelompok menu |
| `app/Http/Controllers/DashboardController.php` | 2 | tambah `verificationQueue` |
| `resources/views/dashboard.blade.php` | 2 | tulis ulang |
| `app/Models/PamongPermission.php` | 4 | tambah preset izin |
| `resources/views/admin/pamong-permissions/*.blade.php` | 4 | tombol preset + peringatan |
| `resources/views/admin/users/*.blade.php` | 1, 4 | kolom badge + indikator izin |
| `scripts/terapkan-izin-operasional.php` | 4 | script dry-run + apply |

Tanpa migrasi database. Tanpa `npm run build` (kecuali Fase 3 menyentuh CSS kustom — akan dicek dulu).

---

## Pengujian & Verifikasi

Untuk setiap fase:
1. `php -l` pada file PHP yang diubah.
2. Compile view yang diubah lewat `blade.compiler`.
3. Render nyata per tipe akun (admin / teacher / pkg_manager) memakai script sementara di `scripts/`, memeriksa: blok yang seharusnya muncul, blok yang seharusnya tersembunyi, dan angka antrean cocok dengan query langsung.
4. Uji izin: panggil route presensi manual dengan akun tanpa izin → harus 403.
5. Setelah deploy: cek log produksi tidak memunculkan error baru.

---

## Risiko & Trade-off

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Mematikan `is_excluded` mengunci akses orang yang selama ini bebas | Pamong/pengurus tiba-tiba tidak bisa membuka menu | Dry-run dulu (tampilkan siapa kehilangan apa), terapkan paket izin sebelum mematikan flag, siapkan perintah pembalik |
| Menghapus leaderboard dari dashboard | Sebagian staff merasa fitur hilang | Tetap ada di halaman gamifikasi + tautan dari dashboard |
| Dashboard ditulis ulang (723 → <300 baris) | Ada blok yang tak sengaja hilang | Daftar blok lama dicatat di plan ini sebagai checklist saat menulis ulang |
| Peran tugas memakai `organizational_title` | Bercampur dengan makna lama kolom (baru 4 terisi) | Cek 4 nilai yang ada dulu; bila maknanya beda, buat kolom baru dengan migrasi ringan |

---

## Pertanyaan Terbuka (SUDAH DIJAWAB USER — 25 Agu 2026)

**Q1 → SETUJU izin eksplisit.** `is_excluded` dimatikan, diganti izin eksplisit, dan harus ada satu tempat pengaturan yang mudah.
**Q2 → Beberapa pengurus ditunjuk** (user menunjuk sendiri nanti). Yang ditunjuk mendapat badge "Pengisi Presensi"; peran lain juga punya badge.
**Q3 → TIDAK dibatasi.** SEMUA pamong boleh mengisi presensi manual. Tambahan: perlu halaman baru yang menampilkan — saat jadwal presensi aktif — daftar anak binaan tiap pamong beserta status sudah/belum absen, agar semua pamong bisa saling membantu mengisi.
**Q4 → Peran tugas BISA BEBERAPA sekaligus** per orang; badge boleh ganda, tapi UI harus rapi.
**Q5 → SETUJU** leaderboard dipindah. Tambahan: dashboard akun ADMIN juga dirapikan.

---

## Keputusan Desain Final (menggantikan rancangan awal di atas bila berbeda)

### A. Peran tugas jamak — butuh kolom baru (bukan `organizational_title`)
Kolom `organizational_title` TIDAK dipakai karena hanya menampung satu nilai dan isinya sudah bermakna lain (terisi 4: "Pamong tes", "Pamong", "-", "PJ absen").
→ Migrasi ringan: tambah kolom JSON `users.duty_roles` (array slug). Daftar peran tugas disimpan sebagai preset yang bisa diubah admin.

Peran tugas awal yang disediakan:
| slug | badge | warna |
|---|---|---|
| `pengisi_presensi` | Pengisi Presensi | sky |
| `verifikator_tugas` | Verifikator Tugas PKG | emerald |
| `verifikator_quran` | Verifikator Bacaan Qur'an | teal |
| `koordinator_kelompok` | Koordinator Kelompok | amber |
| `penanggung_jawab_acara` | PJ Acara | violet |
| `operator_data` | Operator Data | slate |

UI badge ganda dibuat rapi: badge peran akun tampil penuh; peran tugas maksimal 2 tampil, sisanya diringkas "+N" dengan tooltip berisi daftar lengkap.

### B. Presensi manual untuk SEMUA pamong
- Paket izin "Pamong Pembimbing" mencakup `manual_attendance` view+create.
- Cakupan siswa: semua generus aktif (bukan hanya binaan) karena pamong harus bisa saling membantu → butuh operasi `all_students` ikut diberikan ke pamong.

### C. Halaman baru: "Bantu Isi Presensi" (fitur baru, bukan sekadar tampilan)
Halaman ini hanya berguna saat ada jadwal presensi yang aktif/terbuka.
- Menampilkan seluruh generus aktif dikelompokkan per pamong pembimbing.
- Tiap anak: nama, kelompok, status hari ini (Sudah absen / Belum), jam scan bila ada.
- Ringkasan per pamong: "5 dari 8 binaan sudah absen".
- Tombol isi cepat pada anak yang belum absen (hadir/terlambat/izin/sakit) memakai endpoint `absen-manual/siswa` yang sudah ada.
- Filter: hanya yang belum absen, per kelompok, cari nama; plus penanda "binaan saya".
- Route baru: `GET /bantu-presensi` → `name('manual-attendance.helper')`, dijaga `pamong.permission:manual_attendance,create`.

### D. Dashboard admin juga dirapikan
Dashboard staff dibuat satu view dengan blok kondisional:
- Pamong & Pengurus: identitas+badge → presensi diri → antrean verifikasi → aksi cepat (termasuk "Bantu Isi Presensi") → ringkasan tipis.
- Admin: identitas+badge → ringkasan operasional 4 angka (generus aktif, kehadiran hari ini, antrean verifikasi total, laporan pending) → antrean verifikasi → blok admin (presensi pamong hari ini, panel sekunder lazy) → aksi cepat pengelolaan.
Target: satu view < 350 baris (dari 723), tanpa duplikasi blok.

### E. Tempat pengaturan izin yang mudah (permintaan Q1)
Halaman `pamong/permissions` yang sudah ada dijadikan pusat "Peran & Izin" dengan 3 bagian dalam satu layar:
1. Tabel akun: nama, peran akun, badge peran tugas, indikator "Presensi manual: ya/tidak", tombol Atur.
2. Panel terapkan cepat: pilih beberapa akun (checkbox) → pilih paket izin (memakai `OperationalPermissionPreset` yang SUDAH ADA: Tim Presensi, Operator Presensi, dll) → Terapkan.
3. Panel peran tugas: pilih akun → centang peran tugas (jamak) → Simpan. Termasuk tombol "Tambah jenis peran" agar user bisa menambah peran baru sendiri.
Peringatan eksplisit pada toggle `is_excluded` bahwa itu membuka semua menu.

---

## Rencana Langkah FINAL (revisi setelah jawaban user)

### FASE 1 — Peran tugas + badge (fondasi)
1. Migrasi: `php artisan make:migration add_duty_roles_to_users` → kolom `json duty_roles` nullable.
2. `app/Support/DutyRole.php`: daftar peran tugas (slug, label, warna) + penyimpanan tambahan lewat `Setting` agar admin bisa menambah.
3. `app/Models/User.php`: `duty_roles` di `$fillable` + cast array; accessor `dutyRoleBadges()`.
4. Komponen `resources/views/components/role-badges.blade.php`: badge peran akun + peran tugas (maks 2 + "+N").
5. Pakai di `layouts/app.blade.php`, daftar pengguna, dan chat ortu/siswa.
6. Uji: `php -l`, migrasi lokal, compile view, render untuk akun teacher & pkg_manager.

### FASE 2 — Halaman "Bantu Isi Presensi"
1. `app/Http/Controllers/ManualAttendanceController.php`: method `helper()` — data jadwal aktif + generus per pamong + status hari ini (1 query presensi, hindari N+1).
2. View `resources/views/manual-attendance/helper.blade.php` — daftar per pamong, filter, tombol isi cepat (Alpine + endpoint yang ada).
3. Route + menu sidebar (grup Verifikasi) + tombol di dashboard.
4. Uji: buka sebagai pamong (harus tampil semua binaan pamong lain juga), isi 1 presensi lalu pastikan status berubah.

### FASE 3 — Dashboard staff & admin dirapikan
1. `DashboardController`: tambah `verificationQueue` + `adminSummary`, pertahankan cache 60 detik.
2. Tulis ulang `resources/views/dashboard.blade.php` (< 350 baris) dengan blok kondisional per peran.
3. Pindahkan leaderboard binaan ke halaman gamifikasi.
4. Uji render 3 tipe akun; pastikan kartu tanpa izin tidak muncul.

### FASE 4 — Pusat Peran & Izin + penerapan data
1. Rombak `pamong/permissions` menjadi 3 panel (tabel + terapkan paket + peran tugas).
2. Tambah paket "Pamong Pembimbing" & "Pengurus Verifikator" ke `OperationalPermissionPreset::builtin()`.
3. Script `scripts/terapkan-izin-operasional.php` dengan mode dry-run: tampilkan perubahan sebelum apply.
4. Setelah user setuju hasil dry-run: matikan `is_excluded` + terapkan paket di produksi.
5. Uji: akun tanpa izin → 403; pamong bisa input manual semua generus.

### Berkas tambahan yang berubah (di luar tabel sebelumnya)
- `database/migrations/*_add_duty_roles_to_users.php` (baru)
- `app/Support/DutyRole.php` (baru)
- `resources/views/components/role-badges.blade.php` (baru)
- `app/Http/Controllers/ManualAttendanceController.php` (tambah `helper()`)
- `resources/views/manual-attendance/helper.blade.php` (baru)
- `app/Support/OperationalPermissionPreset.php` (tambah 2 paket)
- `resources/views/pamong/permissions*.blade.php` (rombak)

**Catatan deploy:** Fase 1 dan 4 butuh `artisan migrate --force` di produksi (hanya Fase 1 yang menambah kolom). Fase 2 dan 3 murni PHP/Blade.

