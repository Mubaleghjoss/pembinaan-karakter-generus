# Analisa Stack Rekaman / Voicenote Tes Baca Quran

## Kesimpulan

Di proyek `spmb-alfurqon`, fitur tes baca Quran **memang memakai rekaman suara langsung dari browser**. Jadi ini bukan integrasi WhatsApp voice note atau layanan pihak ketiga, tetapi **voice recorder web** yang dibuat dari kombinasi:

- **Frontend browser API**: `navigator.mediaDevices.getUserMedia()` + `MediaRecorder`
- **Frontend UI**: Blade + Bootstrap 5 + JavaScript inline
- **Backend**: Laravel 11 + PHP 8.2
- **Penyimpanan file**: Laravel filesystem `public` disk

## Stack yang Dipakai

### 1. Backend utama

- **PHP 8.2**  
  Sumber: `composer.json`
- **Laravel Framework 11**  
  Sumber: `composer.json`
- **Eloquent Model** untuk menyimpan metadata rekaman ke tabel `wawancara`  
  Sumber: `app/Models/Wawancara.php`

### 2. Frontend utama

- **Blade template** untuk halaman peserta  
  Sumber: `resources/views/peserta/wawancara-info.blade.php`
- **Vite 6** untuk asset bundling  
  Sumber: `package.json`
- **Bootstrap 5** untuk UI  
  Sumber: `package.json`, `resources/js/app.js`
- **Alpine.js** tersedia di project, tetapi fitur rekaman Quran ini **tidak bergantung pada Alpine**  
  Sumber: `resources/js/app.js`

### 3. Stack khusus fitur voicenote

Fitur rekaman suara Quran memakai API browser native:

- `navigator.mediaDevices.getUserMedia({ audio: true })`
- `new MediaRecorder(stream)`
- `Blob(..., { type: 'audio/webm' })`
- `new File(..., 'bacaan-quran.webm', { type: 'audio/webm' })`
- `DataTransfer()` untuk memasukkan file hasil rekaman ke input file tersembunyi
- `<audio controls>` untuk playback hasil rekaman

Sumber utama:

- `resources/views/peserta/wawancara-info.blade.php:613`
- `resources/views/peserta/wawancara-info.blade.php:614`
- `resources/views/peserta/wawancara-info.blade.php:619`
- `resources/views/peserta/wawancara-info.blade.php:625`
- `resources/views/peserta/wawancara-info.blade.php:628`
- `resources/views/peserta/wawancara-info.blade.php:484`

## Alur Kerja Fitur

### 1. Peserta membuka langkah 6

Halaman `Langkah 6: Tes Bacaan Al-Quran` ada di:

- `resources/views/peserta/wawancara-info.blade.php:446`

Di sana peserta:

- melihat surat acak Juz 30
- menekan tombol mulai rekam
- menghentikan rekaman
- mendengarkan ulang
- mengirim hasil rekaman

### 2. Browser merekam suara

Saat tombol rekam ditekan:

- browser meminta izin mikrofon
- audio direkam dengan `MediaRecorder`
- hasil rekaman dikumpulkan dalam `audioChunks`
- saat stop, hasil digabung menjadi `Blob` bertipe `audio/webm`

Implementasi:

- `resources/views/peserta/wawancara-info.blade.php:611-630`

### 3. Hasil rekaman dijadikan file upload

Hasil `Blob` diubah menjadi `File` bernama `bacaan-quran.webm`, lalu dimasukkan ke:

- input file tersembunyi `file_voice_quran`

Implementasi:

- `resources/views/peserta/wawancara-info.blade.php:497`
- `resources/views/peserta/wawancara-info.blade.php:625-628`

### 4. Form dikirim ke Laravel

Form dikirim sebagai `multipart/form-data` ke route:

- `peserta.wawancara.simpan`

Implementasi:

- `resources/views/peserta/wawancara-info.blade.php:493`
- `routes/web.php:103-107`

### 5. Laravel menyimpan file

Pada step 6:

- file `file_voice_quran` disimpan ke folder `wawancara/voice`
- disk yang dipakai adalah `public`
- nama surat acak juga disimpan ke kolom `surat_quran_random`

Implementasi:

- `app/Http/Controllers/Peserta/DashboardSpmbController.php:171-177`

### 6. Metadata disimpan ke tabel `wawancara`

Kolom database yang dipakai:

- `file_voice_quran`
- `surat_quran_random`

Implementasi:

- `database/migrations/2026_02_26_130000_add_wawancara_extended_fields.php:11-17`
- `app/Models/Wawancara.php:35-39`

### 7. Audio bisa diputar ulang

Rekaman yang sudah tersimpan ditampilkan lagi dengan `<audio controls>` di sisi peserta dan admin.

Implementasi:

- `resources/views/peserta/wawancara-info.blade.php:503-509`
- `resources/views/admin/verifikasi/form-wawancara.blade.php:393-400`

## Penyimpanan File

Disk yang dipakai:

- `public` disk Laravel

Lokasi fisik:

- `storage/app/public/wawancara/voice`

URL publik:

- melalui `public/storage` hasil `storage:link`

Sumber:

- `config/filesystems.php:41-48`
- `config/filesystems.php:76-78`

## Yang Tidak Dipakai

Dari kode yang ada, fitur ini **tidak memakai**:

- library recorder pihak ketiga
- Livewire untuk proses rekaman
- WebRTC call/streaming ke server
- FFmpeg/transcoding di backend
- cloud storage khusus untuk audio
- WhatsApp voice note API

Jadi stack rekamannya relatif sederhana: **rekam di browser -> ubah jadi file webm -> upload ke Laravel -> simpan ke storage publik**.

## Catatan Teknis

1. Format hasil rekaman yang dibentuk di frontend adalah **`audio/webm`**.
2. Controller saat ini **langsung menyimpan file** tanpa validasi khusus mime/size untuk `file_voice_quran`.
3. Karena memakai `MediaRecorder`, kompatibilitas bergantung pada dukungan browser pengguna.
4. Implementasi saat ini lebih tepat disebut **web voice recorder**, bukan format voicenote platform tertentu.

## Jawaban Singkat

Kalau ditanya stack fitur rekaman / voicenote tes baca Quran di proyek ini, jawabannya:

**Laravel 11 + PHP 8.2 di backend, Blade + Bootstrap 5 + Vite di frontend, dan perekaman dilakukan langsung di browser memakai `getUserMedia()` dan `MediaRecorder`, lalu file audio `webm` diupload ke storage publik Laravel.**
