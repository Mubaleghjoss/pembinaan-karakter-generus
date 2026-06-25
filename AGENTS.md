# Agent Rules

Panduan ini wajib diikuti saat menambah atau mengubah fitur di repo ini. Tujuannya: UI konsisten, performa tetap aman, dan source lebih mudah dirawat.

## 1. Prinsip Umum

- Jangan membuat halaman baru dengan gaya visual sendiri jika pola yang setara sudah ada.
- Dahulukan reuse layout, utility semantik, dan partial yang sudah dipakai proyek ini.
- Jangan menambah dependensi frontend baru, CDN baru, atau framework UI kedua tanpa alasan kuat.
- Perubahan UI harus tetap aman di light mode dan dark mode.
- Hindari solusi cepat yang menyebar inline style, inline query Blade, atau script berat global.

## 2. Layout yang Harus Dipakai

- Admin: gunakan [resources/views/layouts/app.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/app.blade.php)
- Siswa: gunakan [resources/views/layouts/siswa.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/siswa.blade.php)
- Ortu: gunakan [resources/views/layouts/ortu.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/ortu.blade.php)
- Publik: gunakan [resources/views/layouts/public.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/public.blade.php)
- Auth/Login: gunakan [resources/views/layouts/auth.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/auth.blade.php)

Jangan membuat layout baru hanya untuk variasi kecil.

## 3. Utility UI yang Wajib Diprioritaskan

Gunakan class semantik dari [resources/css/app.css](e:/xampp/htdocs/pkg-v3/resources/css/app.css) sebelum menulis kombinasi utility literal baru.

Prioritas pemakaian:

- Header halaman: `pkg-page-header`, `pkg-page-heading`, `pkg-page-subheading`, `pkg-page-actions`
- Filter/form shell: `pkg-filter-bar`, `pkg-filter-grid`
- Panel/kartu: `pkg-panel`, `pkg-panel-lg`, `pkg-card`, `pkg-card-soft`
- Modal: `pkg-modal`
- Input: `pkg-field`, `pkg-field-icon-left`, `pkg-field-icon-right`, `pkg-check`
- Tab/link sekunder: `pkg-tab-link`
- Empty state: `pkg-empty-state`, `pkg-empty-icon`, `pkg-empty-title`, `pkg-empty-copy`

Kalau pola halaman baru mirip halaman lama yang sudah rapi, salin struktur semantiknya, bukan styling literalnya.

## 4. Aturan Warna dan Tema

- Ikuti token tema yang sudah dipakai layout dan `app.css`.
- Jangan hardcode ungu/biru lama jika tidak diperlukan.
- Status semantics boleh tetap memakai warna makna:
  - hijau: sukses/aktif
  - kuning/amber: peringatan
  - merah: error/bahaya
- Tombol aksi utama gunakan `btn-primary`, aksi sekunder gunakan `btn-secondary`, aksi sukses gunakan `btn-success`, aksi destruktif gunakan `btn-danger`.
- Pastikan teks tetap terbaca di light dan dark mode. Hindari teks transparan, kontras lemah, atau badge yang hanya bagus di salah satu mode.

## 5. Aturan Auth dan Branding

- Halaman login harus memakai shell auth bersama, bukan import CDN Tailwind atau layout terpisah.
- Logo harus memakai ukuran eksplisit agar tidak flicker saat render awal.
- Brand siswa, ortu, admin, dan publik harus memakai identitas visual yang sama bila logo situs tersedia.

## 6. Aturan Blade dan Data

- Jangan query database langsung di Blade.
- Jangan hitung `count()`, `where()`, atau relasi berat berulang di view.
- Controller harus menyiapkan semua data untuk tampilan.
- Untuk tabel besar, gunakan eager loading, agregasi SQL, cache singkat, atau pagination.
- Empty state wajib eksplisit jika data bisa kosong.

## 7. Aturan Frontend dan Performa

- Jangan memasukkan library berat ke bundle global jika hanya dipakai di sedikit halaman.
- Gunakan entry/fitur on-demand yang sudah ada polanya di Vite.
- Jangan menambah script CDN untuk library yang sudah dibundle.
- Hindari inline script besar di view kalau logika bisa dipusatkan.
- Jangan menghidupkan lagi `public/hot` pada deploy normal.

## 8. Aturan Copywriting UI

- Gunakan istilah proyek yang sudah diseragamkan:
  - `Impor`
  - `Ekspor`
  - `Unduh`
  - `Siaran`
  - `Pin`
  - `Peringkat`
  - `Pulihkan`
- Hindari campuran Inggris-Indonesia untuk label yang tampil ke pengguna.
- Jangan pakai emoji atau simbol dekoratif yang rawan mojibake.
- Jika butuh ikon fallback, gunakan SVG atau label ASCII aman.

## 9. Aturan Saat Membuat Fitur Baru

Sebelum implementasi:

1. Cari halaman paling mirip yang sudah rapi.
2. Ikuti layout dan utility semantik yang sama.
3. Tentukan apakah fitur butuh JS khusus atau cukup Blade + Alpine yang ringan.
4. Pastikan query dan payload tidak membebani request umum.

Setelah implementasi:

1. Cek light mode dan dark mode.
2. Cek mobile dan desktop.
3. Cek empty state, error state, dan success state.
4. Jalankan `php artisan view:cache`
5. Jalankan `npm.cmd run build` bila menyentuh asset frontend

## 10. Larangan

- Jangan menambah Tailwind CDN di halaman baru.
- Jangan menambah React/global JS untuk fitur kecil yang cukup pakai Blade atau Alpine.
- Jangan membuat style baru dengan banyak `bg-white dark:bg-gray-800` jika utility semantik sudah tersedia.
- Jangan pakai teks/icon hasil encoding rusak seperti `â€¢`, `âœ“`, `ðŸ...`.
- Jangan membuat route utilitas publik yang berisiko.

## 11. File Rujukan Utama

- [resources/css/app.css](e:/xampp/htdocs/pkg-v3/resources/css/app.css)
- [resources/views/layouts/app.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/app.blade.php)
- [resources/views/layouts/public.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/public.blade.php)
- [resources/views/layouts/auth.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/layouts/auth.blade.php)
- [resources/views/users/index.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/users/index.blade.php)
- [resources/views/reports/index.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/reports/index.blade.php)
- [resources/views/tracer-karakter/index.blade.php](e:/xampp/htdocs/pkg-v3/resources/views/tracer-karakter/index.blade.php)

## 12. Aturan Khusus Fitur RPG

- Avatar RPG harus memakai preset visual yang sama antara editor admin, papan game, dan karakter siswa. Jangan kembali ke placeholder teks seperti `N1`, `N2`, `EN`.
- Sumber preset RPG harus dipusatkan di [app/Support/RpgCatalog.php](e:/xampp/htdocs/pkg-v3/app/Support/RpgCatalog.php).
- Data musuh di `rpg_maps.enemies` wajib konsisten berbentuk objek dengan minimal:
  - `x`
  - `y`
  - `avatar`
  - `speed_level`
  - `intelligence_level`
- Nilai `speed_level` hanya boleh: `slow`, `normal`, `fast`.
- Nilai `intelligence_level` hanya boleh: `low`, `normal`, `high`.
- Gameplay siswa dan gameplay guest publik harus memakai mekanik yang seirama:
  - kontrol gerak
  - mode tembak
  - tameng yang dikoleksi dari pickup lalu aktif berbasis durasi peta
  - pickup peluru yang dikoleksi dari jalur permainan
  - peluru otomatis ditembakkan saat musuh sejajar dalam radius dekat
  - AI musuh yang membaca `speed_level` dan `intelligence_level`
- `difficulty` peta dipakai sebagai tingkat dasar tempo musuh, sedangkan detail perilaku musuh per unit tetap datang dari `speed_level` dan `intelligence_level`.
- Pickup RPG harus muncul di tile jalan yang valid, bukan langsung ada di inventori awal pemain.
- Pengaturan peta RPG minimal yang harus ikut dipertahankan antara admin, siswa, dan guest:
  - `difficulty`
  - `shield_duration_seconds`
  - `ammo_per_pickup`
  - `shield_pickups_count`
  - `ammo_pickups_count`
- Default pickup saat game dimulai:
  - jumlah pickup harus membaca setting map, bukan hardcode di view
- Shield tidak boleh bergantung pada klik manual jika pickup memang didesain otomatis aktif.
- Saat musuh kalah karena tembakan, musuh harus respawn lagi di tile valid agar ritme game tetap hidup.
- Aura tameng pada avatar harus terlihat jelas selama durasi perlindungan aktif.
- Jika ada data avatar lama di database, tampilkan lewat resolver katalog agar tetap terlihat benar tanpa migrasi data manual.
- Jika menambah fitur tempur RPG lagi, prioritaskan solusi yang tidak menambah bundle global dan tidak memecah perilaku antara versi siswa dan publik.

## 13. Aturan Khusus Poin Periode, Presensi Historis, dan Analitik Pamong

- Monitoring poin bulanan tidak boleh memakai delete/reset destruktif sebagai jalur utama. Gunakan `point_periods` untuk memisahkan periode aktif dan histori.
- Jika fitur baru menyentuh poin siswa, utamakan metadata periode di `point_transactions.metadata` agar riwayat tetap bisa dibaca per periode.
- Import presensi historis siswa harus mendukung:
  - label sumber impor
  - pilihan periode poin
  - opsi tambah poin atau hanya simpan data
- Import presensi historis pamong harus menyimpan label sumber di metadata dan tetap bisa difilter seperti data biasa.
- Untuk import data lama, hindari efek samping streak bulanan berjalan. Jika memberi poin ke data historis, jangan memakai pembaruan streak aktif.
- Analitik keaktifan pamong harus membaca minimal dua sumber:
  - verifikasi tugas PKG (`siswa_karakter_checklist`)
  - presensi pamong (`pamong_presensi`)
- Jika menambah analitik baru, tampilkan read-only lebih dulu sebelum menambah aksi reset atau arsip.
- UI admin untuk fitur periode, analitik, dan impor historis harus tetap memakai pola:
  - `pkg-page-header`
  - `pkg-filter-bar` / `pkg-filter-grid`
  - `pkg-card` / `pkg-card-soft`
  - `pkg-empty-state`

Jika ada konflik antara kebiasaan lama file tertentu dan panduan ini, ikuti panduan ini kecuali ada alasan kompatibilitas yang jelas.
