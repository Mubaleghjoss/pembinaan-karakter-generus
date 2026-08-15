@extends('layouts.siswa')

@section('title', 'Kartu ID')

@section('content')
@php
    $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
    $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
    $rawCardTitle = trim($cardSettings['card_title'] ?? 'KARTU IDENTITAS');
    $displayCardTitle = strcasecmp($rawCardTitle, 'KARTU PESERTA') === 0 ? 'KARTU IDENTITAS' : $rawCardTitle;
    $cardLogoUrl = !empty($cardSettings['card_logo'])
        ? Storage::url($cardSettings['card_logo'])
        : (!empty($siteSettings['site_logo']) ? asset('storage/' . $siteSettings['site_logo']) : null);
    $siswaPhotoUrl = $siswa->foto_path ? asset('storage/' . $siswa->foto_path) : null;
@endphp
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="siswaCardPhotoManager()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kartu Siswa</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $siswa->nama }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('siswa.dashboard') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                Kembali
            </a>
            <a href="{{ route('siswa.kartu.print') }}" target="_blank" rel="noopener" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Mode Print KTP
            </a>
        </div>
    </div>

    <!-- Card Preview -->
    <div class="flex justify-center" id="card-wrapper">
        <div class="id-card">
            <!-- Background accents -->
            <div class="card-bg-pattern"></div>
            <div class="card-bg-circle"></div>
            
            <!-- Header Bar -->
            <div class="card-header">
                <div class="card-logo">
                    @if(!empty($cardSettings['card_logo']))
                        <img src="{{ Storage::url($cardSettings['card_logo']) }}" alt="Logo">
                    @elseif(!empty($siteSettings['site_logo']))
                        <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo">
                    @else
                        PKG
                    @endif
                </div>
                <div class="card-title">
                    <h3>{{ $displayCardTitle }}</h3>
                    <p>{{ $cardSettings['card_subtitle'] ?? 'Pembinaan Karakter Generus' }}</p>
                </div>
            </div>
            
            <!-- Main Content: Left (Photo+Info) | Right (QR Big) -->
            <div class="card-body">
                <!-- Left Column: Photo + Info stacked -->
                <div class="left-col">
                    <div class="photo-circle" id="siswa-card-photo">
                        @if($siswa->foto_path)
                            <img src="{{ asset('storage/' . $siswa->foto_path) }}" alt="{{ $siswa->nama }}">
                        @else
                            <svg fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="siswa-info">
                        <div class="siswa-name">{{ $siswa->nama }}</div>
                        <div class="info-row">
                            <span class="info-label">NIS</span>
                            <span class="info-sep">:</span>
                            <span class="info-value">{{ $siswa->nis }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kelas Sekolah</span>
                            <span class="info-sep">:</span>
                            <span class="info-value">{{ $siswa->school_grade_label ?? '-' }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: QR Code BIG -->
                <div class="qr-section" id="qr-container">
                    @if($qrCode)
                        <img src="{{ $qrCode }}" alt="QR Code" id="qr-image">
                    @else
                        <div class="qr-placeholder">
                            <span>{{ $siswa->isGraduated() ? 'ALUMNI' : 'QR' }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Footer -->
            <div class="card-footer">
                {{ $cardFooterText }}
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <button type="button" @click="downloadPng()" :disabled="downloading" class="btn-primary justify-center disabled:cursor-wait disabled:opacity-70">
            <span x-text="downloading ? 'Menyiapkan...' : 'Unduh PNG'"></span>
        </button>
        <button type="button" @click="shareOrSave()" :disabled="downloading" class="btn-secondary justify-center disabled:cursor-wait disabled:opacity-70">
            Simpan ke Galeri
        </button>
        <input x-ref="photoInput" type="file" accept="image/jpeg,image/png,image/jpg" class="hidden" @change="uploadPhoto($event)">
        <input x-ref="cameraInput" type="file" accept="image/*" capture="user" class="hidden" @change="uploadPhoto($event)">
        <button type="button" @click="$refs.photoInput.click()" :disabled="uploading" class="btn-secondary justify-center disabled:cursor-wait disabled:opacity-70">
            Pilih File Foto
        </button>
        <button type="button" @click="openCamera()" :disabled="uploading" class="btn-primary justify-center disabled:cursor-wait disabled:opacity-70">
            <span x-text="uploading ? 'Mengunggah...' : 'Ambil Foto Langsung'"></span>
        </button>
    </div>

    @include('components.id-card-print-help')

    <!-- Instructions -->
    @if($siswa->isActive())
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Petunjuk Penggunaan</h3>
        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>Tunjukkan QR Code pada scanner untuk melakukan presensi</li>
            <li>Jaga kerahasiaan kartu ini</li>
            <li>Gunakan tombol pilih file atau ambil foto langsung untuk memperbarui foto tanpa memuat ulang halaman</li>
            <li>Hubungi pamong jika QR Code tidak berfungsi saat presensi</li>
        </ul>
    </div>
    @else
    <div class="mt-6 rounded-lg bg-sky-50 p-4 text-sm text-sky-800 dark:bg-sky-950/30 dark:text-sky-200">Kartu Alumni tidak memiliki QR presensi aktif.</div>
    @endif

    <div x-show="cameraOpen"
         x-transition
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-6">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ambil Foto Kartu</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Posisikan wajah di tengah frame, lalu gunakan foto.</p>
                </div>
                <button type="button" @click="closeCamera()" class="btn-secondary text-sm !px-3 !py-1.5">Tutup</button>
            </div>
            <div class="overflow-hidden rounded-xl bg-black">
                <video x-ref="cameraVideo" autoplay playsinline muted class="aspect-[4/3] w-full object-cover"></video>
                <canvas x-ref="cameraCanvas" class="hidden"></canvas>
            </div>
            <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="closeCamera()" class="btn-secondary justify-center text-sm !px-4 !py-2">Batal</button>
                <button type="button" @click="captureCameraPhoto()" :disabled="uploading" class="btn-primary justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                    <span x-text="uploading ? 'Mengunggah...' : 'Gunakan Foto'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* ===== Professional ID Card — KTP size 85.6mm × 54mm ===== */
    .id-card {
        width: 85.6mm;
        height: 54mm;
        background: linear-gradient(140deg, {{ $cardBaseColor }} 0%, color-mix(in srgb, {{ $cardBaseColor }} 78%, #0f172a) 100%);
        border-radius: 2.5mm;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.35), 0 2px 8px rgba(0,0,0,0.15);
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: white;
    }

    /* Subtle background accents */
    .card-bg-pattern {
        position: absolute;
        inset: 0;
        background: 
            repeating-linear-gradient(
                120deg,
                transparent,
                transparent 8mm,
                rgba(255,255,255,0.015) 8mm,
                rgba(255,255,255,0.015) 8.3mm
            );
        pointer-events: none;
        z-index: 0;
    }
    .card-bg-circle {
        position: absolute;
        top: -15mm;
        right: -10mm;
        width: 45mm;
        height: 45mm;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    /* Header */
    .card-header {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 2mm;
        padding: 1.5mm 3mm;
        background: rgba(0,0,0,0.2);
        border-bottom: 0.3mm solid rgba(255,255,255,0.1);
    }
    .card-logo {
        width: 7mm;
        height: 7mm;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #1e3a8a;
        font-size: 2mm;
        flex-shrink: 0;
        overflow: hidden;
    }
    .card-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .card-title h3 {
        font-size: 2.4mm;
        font-weight: 700;
        letter-spacing: 0.5mm;
        margin: 0;
        line-height: 1;
    }
    .card-title p {
        font-size: 1.5mm;
        opacity: 0.7;
        margin: 0.4mm 0 0 0;
        line-height: 1;
    }

    /* Body: 2 columns — Left (photo+info) | Right (QR big) */
    .card-body {
        position: relative;
        z-index: 1;
        display: flex;
        padding: 2mm 3mm;
        gap: 2.5mm;
        height: calc(100% - 13mm);
    }

    /* Left column: photo on top, info below */
    .left-col {
        width: 30mm;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5mm;
    }

    /* Photo — rendered directly, no border/circle */
    .photo-circle {
        width: 16mm;
        height: 18mm;
        border-radius: 1.5mm;
        overflow: hidden;
        background: none;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .photo-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
    }
    .photo-circle svg {
        width: 8mm;
        height: 8mm;
        color: rgba(255,255,255,0.5);
    }

    /* Student info below photo */
    .siswa-info {
        width: 100%;
        text-align: center;
    }
    .siswa-name {
        font-size: 2.4mm;
        font-weight: 700;
        margin-bottom: 1mm;
        line-height: 1.15;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    .info-row {
        display: flex;
        justify-content: center;
        font-size: 1.7mm;
        line-height: 1.5;
    }
    .info-label {
        width: 7mm;
        text-align: right;
        opacity: 0.7;
        flex-shrink: 0;
        font-weight: 500;
    }
    .info-sep {
        width: 2mm;
        text-align: center;
        opacity: 0.7;
        flex-shrink: 0;
    }
    .info-value {
        font-weight: 600;
        text-align: left;
    }

    /* QR Code — takes remaining space, BIG */
    .qr-section {
        flex: 1;
        background: white;
        border-radius: 2mm;
        padding: 0.5mm;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 6px rgba(0,0,0,0.2);
    }
    .qr-section img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .qr-placeholder {
        color: #9ca3af;
        font-size: 4mm;
        font-weight: 700;
    }

    /* Footer */
    .card-footer {
        position: absolute;
        bottom: 0.8mm;
        left: 3mm;
        right: 3mm;
        font-size: 1.3mm;
        color: rgba(255,255,255,0.5);
        z-index: 1;
        text-align: center;
        letter-spacing: 0.1mm;
    }

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #card-wrapper, #card-wrapper * {
            visibility: visible;
        }
        #card-wrapper {
            position: absolute;
            left: 0;
            top: 0;
        }
        .id-card {
            box-shadow: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<script>
function siswaCardPhotoManager() {
    return {
        uploading: false,
        downloading: false,
        cameraOpen: false,
        cameraStream: null,
        cardData: {
            color: @json($cardBaseColor),
            title: @json($displayCardTitle),
            subtitle: @json($cardSettings['card_subtitle'] ?? 'Pembinaan Karakter Generus'),
            footer: @json($cardFooterText),
            logoUrl: @json($cardLogoUrl),
            logoText: @json(substr($siteSettings['site_title'] ?? 'PKG', 0, 3)),
            photoUrl: @json($siswaPhotoUrl),
            initial: @json(strtoupper(substr($siswa->nama, 0, 1))),
            name: @json($siswa->nama),
            nis: @json($siswa->nis),
            kelas: @json($siswa->school_grade_label ?? '-'),
            qrUrl: @json($qrCode),
        },
        init() {
            if (new URLSearchParams(window.location.search).get('download') === '1') {
                window.setTimeout(() => this.downloadPng(), 500);
            }
        },
        async uploadPhoto(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            await this.uploadPhotoFile(file);
        },
        async uploadPhotoFile(file) {
            if (!file || this.uploading) return;

            if (!file.type.startsWith('image/')) {
                this.notify('File harus berupa gambar.', 'error');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                this.notify('Ukuran foto maksimal 2MB.', 'error');
                return;
            }

            this.uploading = true;
            try {
                const formData = new FormData();
                formData.append('foto', file);

                const response = await fetch(@json(route('siswa.profile.update-photo')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors)[0] : null;
                    throw new Error(Array.isArray(firstError) ? firstError[0] : (data.message || 'Foto gagal diperbarui.'));
                }

                const photo = document.getElementById('siswa-card-photo');
                if (photo && data.foto_url) {
                    this.cardData.photoUrl = data.foto_url;
                    photo.innerHTML = `<img src="${data.foto_url}" alt="Foto ${@json($siswa->nama)}">`;
                }

                this.notify(data.message || 'Foto kartu berhasil diperbarui.', 'success');
            } catch (error) {
                this.notify(error.message || 'Foto gagal diperbarui.', 'error');
            } finally {
                this.uploading = false;
            }
        },
        async openCamera() {
            if (!navigator.mediaDevices?.getUserMedia) {
                this.$refs.cameraInput.click();
                return;
            }

            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                this.cameraOpen = true;
                this.$nextTick(() => {
                    this.$refs.cameraVideo.srcObject = this.cameraStream;
                });
            } catch (error) {
                this.notify('Kamera tidak bisa dibuka. Pastikan izin kamera diberikan, atau gunakan Pilih File Foto.', 'error');
                this.$refs.cameraInput.click();
            }
        },
        closeCamera() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach((track) => track.stop());
                this.cameraStream = null;
            }
            this.cameraOpen = false;
            if (this.$refs.cameraVideo) {
                this.$refs.cameraVideo.srcObject = null;
            }
        },
        async captureCameraPhoto() {
            const video = this.$refs.cameraVideo;
            const canvas = this.$refs.cameraCanvas;
            if (!video || !canvas || !video.videoWidth) {
                this.notify('Preview kamera belum siap.', 'error');
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
            if (!blob) {
                this.notify('Foto gagal diproses.', 'error');
                return;
            }

            const file = new File([blob], `foto-kartu-${Date.now()}.jpg`, { type: 'image/jpeg' });
            await this.uploadPhotoFile(file);
            this.closeCamera();
        },
        async downloadPng() {
            await this.saveCanvas(false);
        },
        async shareOrSave() {
            await this.saveCanvas(true);
        },
        async saveCanvas(useShareSheet) {
            if (this.downloading) return;

            this.downloading = true;
            try {
                const canvas = await this.renderCanvas();
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.96));
                if (!blob) throw new Error('Gagal membuat file kartu.');

                const fileName = `id-card-${this.slug(this.cardData.nis)}.png`;
                const file = new File([blob], fileName, { type: 'image/png' });

                if (useShareSheet && navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: 'ID Card Saya',
                        text: 'Simpan ID Card ke galeri atau bagikan.',
                        files: [file],
                    });
                    return;
                }

                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
                this.notify('ID Card berhasil disiapkan untuk diunduh.', 'success');
            } catch (error) {
                this.notify(error.message || 'ID Card gagal diunduh.', 'error');
            } finally {
                this.downloading = false;
            }
        },
        async renderCanvas() {
            const scale = 3;
            const width = 1011;
            const height = 638;
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.scale(scale, scale);

            const w = width / scale;
            const h = height / scale;
            this.roundRect(ctx, 0, 0, w, h, 15);
            ctx.clip();

            const gradient = ctx.createLinearGradient(0, 0, w, h);
            gradient.addColorStop(0, this.cardData.color);
            gradient.addColorStop(1, this.shadeHex(this.cardData.color, -34));
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, w, h);

            ctx.fillStyle = 'rgba(0,0,0,0.2)';
            ctx.fillRect(0, 0, w, 39);
            ctx.fillStyle = 'rgba(255,255,255,0.06)';
            ctx.beginPath();
            ctx.arc(w - 35, -18, 88, 0, Math.PI * 2);
            ctx.fill();

            await this.drawLogo(ctx);
            this.drawText(ctx, this.cardData.title, 42, 21, 9.5, 700, '#fff');
            this.drawText(ctx, this.cardData.subtitle, 42, 31, 6.2, 500, 'rgba(255,255,255,0.72)');
            await this.drawPhoto(ctx, 50, 48, 64, 71);
            this.drawText(ctx, this.cardData.name, 63, 137, 9.5, 700, '#fff', 112, 'center');
            this.drawInfo(ctx, 'NIS', this.cardData.nis, 33, 152);
            this.drawInfo(ctx, 'Kelas Sekolah', this.cardData.kelas, 33, 163);
            await this.drawQr(ctx, 143, 48, 184, 145);
            this.drawText(ctx, this.cardData.footer, w / 2, h - 8, 5.2, 500, 'rgba(255,255,255,0.5)', w - 28, 'center');
            return canvas;
        },
        async drawLogo(ctx) {
            this.roundRect(ctx, 12, 8, 28, 28, 14);
            ctx.fillStyle = '#fff';
            ctx.fill();

            if (this.cardData.logoUrl) {
                try {
                    const image = await this.loadImage(this.cardData.logoUrl);
                    ctx.save();
                    this.roundRect(ctx, 12, 8, 28, 28, 14);
                    ctx.clip();
                    ctx.drawImage(image, 12, 8, 28, 28);
                    ctx.restore();
                    return;
                } catch (_error) {}
            }

            this.drawText(ctx, this.cardData.logoText, 26, 26, 8, 800, '#1e3a8a', 22, 'center');
        },
        async drawPhoto(ctx, x, y, w, h) {
            ctx.save();
            this.roundRect(ctx, x, y, w, h, 7);
            ctx.clip();

            if (this.cardData.photoUrl) {
                try {
                    const image = await this.loadImage(this.cardData.photoUrl);
                    this.coverImage(ctx, image, x, y, w, h);
                    ctx.restore();
                    return;
                } catch (_error) {}
            }

            ctx.fillStyle = 'rgba(255,255,255,0.16)';
            ctx.fillRect(x, y, w, h);
            this.drawText(ctx, this.cardData.initial, x + w / 2, y + 45, 28, 800, 'rgba(255,255,255,0.8)', w, 'center');
            ctx.restore();
        },
        async drawQr(ctx, x, y, w, h) {
            this.roundRect(ctx, x, y, w, h, 8);
            ctx.fillStyle = '#fff';
            ctx.fill();
            if (!this.cardData.qrUrl) return;
            const image = await this.loadImage(this.cardData.qrUrl);
            ctx.drawImage(image, x + 2, y + 2, w - 4, h - 4);
        },
        drawInfo(ctx, label, value, x, y) {
            this.drawText(ctx, label, x, y, 6.8, 500, 'rgba(255,255,255,0.72)', 30, 'right');
            this.drawText(ctx, ':', x + 6, y, 6.8, 500, 'rgba(255,255,255,0.72)', 8);
            this.drawText(ctx, value, x + 14, y, 6.8, 700, '#fff', 76);
        },
        drawText(ctx, text, x, y, size, weight, color, maxWidth = null, align = 'left') {
            ctx.fillStyle = color;
            ctx.font = `${weight} ${size}px Segoe UI, Arial, sans-serif`;
            ctx.textAlign = align;
            ctx.textBaseline = 'alphabetic';

            const value = String(text || '');
            if (!maxWidth || ctx.measureText(value).width <= maxWidth) {
                ctx.fillText(value, x, y);
                return;
            }

            let trimmed = value;
            while (trimmed.length > 3 && ctx.measureText(trimmed + '...').width > maxWidth) {
                trimmed = trimmed.slice(0, -1);
            }
            ctx.fillText(trimmed + '...', x, y);
        },
        loadImage(src) {
            return new Promise((resolve, reject) => {
                const image = new Image();
                if (!String(src).startsWith('data:')) {
                    image.crossOrigin = 'anonymous';
                }
                image.onload = () => resolve(image);
                image.onerror = reject;
                image.src = src;
            });
        },
        coverImage(ctx, image, x, y, w, h) {
            const ratio = Math.max(w / image.width, h / image.height);
            const drawW = image.width * ratio;
            const drawH = image.height * ratio;
            ctx.drawImage(image, x + (w - drawW) / 2, y + (h - drawH) / 2, drawW, drawH);
        },
        roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        },
        shadeHex(hex, amount) {
            const normalized = String(hex || '#0f766e').replace('#', '');
            const value = normalized.length === 3
                ? normalized.split('').map((char) => char + char).join('')
                : normalized.padEnd(6, '0').slice(0, 6);
            const num = parseInt(value, 16);
            const r = Math.max(0, Math.min(255, (num >> 16) + amount));
            const g = Math.max(0, Math.min(255, ((num >> 8) & 0xff) + amount));
            const b = Math.max(0, Math.min(255, (num & 0xff) + amount));
            return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
        },
        slug(value) {
            return String(value || 'siswa').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'siswa';
        },
        notify(message, type = 'info') {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
                return;
            }
            alert(message);
        },
    };
}
</script>
@endsection
