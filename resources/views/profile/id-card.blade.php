@extends('layouts.app')

@section('title', 'ID Card Saya')

@section('content')
@php
    $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
    $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
    $rawCardTitle = trim($cardSettings['card_title'] ?? 'KARTU IDENTITAS');
    $displayCardTitle = strcasecmp($rawCardTitle, 'KARTU PESERTA') === 0 ? 'KARTU IDENTITAS' : $rawCardTitle;
    $cardLogoUrl = !empty($cardSettings['card_logo'])
        ? Storage::url($cardSettings['card_logo'])
        : (!empty($siteSettings['site_logo']) ? asset('storage/' . $siteSettings['site_logo']) : null);
    $roleLabel = $user->operationalRoleLabel();
    $orgLabel = $user->organizationalLabel();
@endphp

<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8" x-data="idCardManager()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">ID Card Saya</h1>
            <p class="pkg-page-subheading">Kelola foto kartu, QR presensi, dan unduh kartu dalam ukuran kartu identitas.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('profile.show') }}" class="btn-secondary text-sm !px-4 !py-2">Profil</a>
            <a href="{{ route('profile.id-card.print') }}" target="_blank" rel="noopener" class="btn-secondary text-sm !px-4 !py-2">Mode Print</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-100">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-100">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="pkg-panel p-4 sm:p-6">
            <div class="flex justify-center overflow-x-auto pb-2">
                <div id="profile-id-card" class="id-card" data-card-name="{{ $user->display_name }}">
                    <div class="card-bg-pattern"></div>
                    <div class="card-bg-circle"></div>

                    <div class="card-header">
                        <div class="card-logo">
                            @if($cardLogoUrl)
                                <img src="{{ $cardLogoUrl }}" alt="Logo">
                            @else
                                {{ substr($siteSettings['site_title'] ?? 'PKG', 0, 3) }}
                            @endif
                        </div>
                        <div class="card-title">
                            <h3>{{ $displayCardTitle }}</h3>
                            <p>{{ $cardSettings['card_subtitle'] ?? ($siteSettings['site_name'] ?? 'Pembinaan Karakter Generus') }}</p>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="left-col">
                            <div class="photo-circle">
                                @if($user->avatar_url)
                                    <img id="id-card-avatar" src="{{ $user->avatar_url }}" alt="{{ $user->display_name }}">
                                @else
                                    <svg fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                @endif
                            </div>

                            <div class="siswa-info">
                                <div class="siswa-name">{{ $user->display_name }}</div>
                                <div class="info-row">
                                    <span class="info-label">User</span>
                                    <span class="info-sep">:</span>
                                    <span class="info-value">{{ $user->username }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Role</span>
                                    <span class="info-sep">:</span>
                                    <span class="info-value">{{ $roleLabel }}</span>
                                </div>
                                @if($orgLabel)
                                    <div class="info-row">
                                        <span class="info-label">Bidang</span>
                                        <span class="info-sep">:</span>
                                        <span class="info-value">{{ Str::limit($orgLabel, 16) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="qr-section">
                            <img id="id-card-qr" src="{{ $qrData['qr_image_base64'] }}" alt="QR Code">
                        </div>
                    </div>

                    <div class="card-footer">{{ $cardFooterText }}</div>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <button type="button" @click="downloadPng()" :disabled="isDownloading" class="btn-primary justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                    <span x-text="isDownloading ? 'Menyiapkan...' : 'Unduh PNG'"></span>
                </button>
                <button type="button" @click="shareOrSave()" :disabled="isDownloading" class="btn-secondary justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                    Simpan ke Galeri
                </button>
                <button type="button" @click="refreshQr()" :disabled="isRefreshing" class="btn-secondary justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                    <span x-text="isRefreshing ? 'Memperbarui...' : 'Refresh QR'"></span>
                </button>
            </div>

            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                Ukuran file mengikuti rasio kartu 85.6 x 54 mm, bukan lembar A4. Di ponsel, tombol simpan akan membuka pilihan berbagi jika browser mendukung.
            </p>
        </section>

        <aside class="space-y-4">
            <div class="pkg-card p-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Foto ID Card</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Foto ini sama dengan foto profil akun.</p>

                <div class="mt-4 space-y-3">
                    <input x-ref="photoInput" type="file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="hidden" @change="uploadPhoto($event)">
                    <input x-ref="cameraInput" type="file" accept="image/*" capture="user" class="hidden" @change="uploadPhoto($event)">
                    <button type="button" @click="$refs.photoInput.click()" :disabled="isUploadingPhoto" class="btn-secondary w-full justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                        Pilih File Foto
                    </button>
                    <button type="button" @click="openCamera()" :disabled="isUploadingPhoto" class="btn-primary w-full justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                        <span x-text="isUploadingPhoto ? 'Mengunggah...' : 'Ambil Foto Langsung'"></span>
                    </button>
                </div>
            </div>

            <div class="pkg-card-soft p-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Petunjuk</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-600 dark:text-slate-300">
                    <li>Gunakan kartu ini untuk scan presensi pamong/admin.</li>
                    <li>Unduh ulang kartu setelah menekan Refresh QR karena token lama diganti.</li>
                    <li>Foto bisa dipilih dari galeri atau diambil langsung dari kamera perangkat.</li>
                </ul>
            </div>

            <div class="pkg-card-soft p-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Info Kartu</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Nama</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Username</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $user->username }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Role</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $roleLabel }}</dd>
                    </div>
                    @if($orgLabel)
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Bidang</dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ $orgLabel }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </aside>
    </div>

    <div x-show="isCameraOpen"
         x-transition
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-6">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ambil Foto ID Card</h2>
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
                <button type="button" @click="captureCameraPhoto()" :disabled="isUploadingPhoto" class="btn-primary justify-center text-sm !px-4 !py-2 disabled:cursor-wait disabled:opacity-70">
                    <span x-text="isUploadingPhoto ? 'Mengunggah...' : 'Gunakan Foto'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
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

    .card-bg-pattern {
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(120deg, transparent, transparent 8mm, rgba(255,255,255,0.015) 8mm, rgba(255,255,255,0.015) 8.3mm);
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

    .card-body {
        position: relative;
        z-index: 1;
        display: flex;
        padding: 2mm 3mm;
        gap: 2.5mm;
        height: calc(100% - 13mm);
    }

    .left-col {
        width: 30mm;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5mm;
    }

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
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .qr-section {
        flex: 1;
        background: white;
        border-radius: 2mm;
        padding: 0.5mm;
        background: #fff;
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

    .card-footer {
        position: absolute;
        z-index: 1;
        left: 3mm;
        right: 3mm;
        bottom: 0.8mm;
        text-align: center;
        font-size: 1.3mm;
        color: rgba(255,255,255,0.5);
        letter-spacing: 0.1mm;
    }
</style>

<script>
function idCardManager() {
    return {
        isDownloading: false,
        isRefreshing: false,
        isUploadingPhoto: false,
        isCameraOpen: false,
        cameraStream: null,
        cardData: {
            color: @json($cardBaseColor),
            title: @json($displayCardTitle),
            subtitle: @json($cardSettings['card_subtitle'] ?? ($siteSettings['site_name'] ?? 'Pembinaan Karakter Generus')),
            footer: @json($cardFooterText),
            logoUrl: @json($cardLogoUrl),
            logoText: @json(substr($siteSettings['site_title'] ?? 'PKG', 0, 3)),
            avatarUrl: @json($user->avatar_url),
            initial: @json(strtoupper(substr($user->display_name, 0, 1))),
            name: @json($user->display_name),
            username: @json($user->username),
            role: @json($roleLabel),
            organization: @json($orgLabel),
            qrUrl: @json($qrData['qr_image_base64']),
        },

        async uploadPhoto(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            await this.uploadPhotoFile(file);
        },

        async uploadPhotoFile(file) {
            if (!file || this.isUploadingPhoto) return;

            if (!file.type.startsWith('image/')) {
                this.notify('File harus berupa gambar.', 'error');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                this.notify('Ukuran foto maksimal 2MB.', 'error');
                return;
            }

            this.isUploadingPhoto = true;
            try {
                const formData = new FormData();
                formData.append('avatar', file);

                const response = await fetch(@json(route('profile.update.post')), {
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

                const avatarUrl = data.user?.avatar_url;
                if (avatarUrl) {
                    this.cardData.avatarUrl = avatarUrl;
                    const photo = document.querySelector('#profile-id-card .photo-circle');
                    if (photo) {
                        photo.innerHTML = `<img id="id-card-avatar" src="${avatarUrl}" alt="${this.escapeHtml(this.cardData.name)}">`;
                    }
                }

                this.notify(data.message || 'Foto ID Card berhasil diperbarui.', 'success');
            } catch (error) {
                this.notify(error.message || 'Foto gagal diperbarui.', 'error');
            } finally {
                this.isUploadingPhoto = false;
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
                this.isCameraOpen = true;
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
            this.isCameraOpen = false;
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

            const file = new File([blob], `foto-id-card-${Date.now()}.jpg`, { type: 'image/jpeg' });
            await this.uploadPhotoFile(file);
            this.closeCamera();
        },

        async refreshQr() {
            if (this.isRefreshing) return;

            this.isRefreshing = true;
            try {
                const response = await fetch(@json(route('profile.id-card.refresh-qr')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'QR gagal diperbarui.');
                }

                this.cardData.qrUrl = data.data.qr_image_base64;
                document.getElementById('id-card-qr').src = this.cardData.qrUrl;
                this.notify(data.message || 'QR ID Card berhasil diperbarui.', 'success');
            } catch (error) {
                this.notify(error.message || 'QR gagal diperbarui.', 'error');
            } finally {
                this.isRefreshing = false;
            }
        },

        async downloadPng() {
            await this.saveCanvas(false);
        },

        async shareOrSave() {
            await this.saveCanvas(true);
        },

        async saveCanvas(useShareSheet) {
            if (this.isDownloading) return;

            this.isDownloading = true;
            try {
                const canvas = await this.renderCanvas();
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.96));
                if (!blob) throw new Error('Gagal membuat file kartu.');

                const fileName = `id-card-${this.slug(this.cardData.username)}.png`;
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
                this.isDownloading = false;
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
            const radius = 18;
            this.roundRect(ctx, 0, 0, w, h, radius);
            ctx.clip();

            const gradient = ctx.createLinearGradient(0, 0, w, h);
            gradient.addColorStop(0, this.cardData.color);
            gradient.addColorStop(1, this.shadeHex(this.cardData.color, -32));
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, w, h);

            ctx.fillStyle = 'rgba(255,255,255,0.10)';
            ctx.beginPath();
            ctx.ellipse(w * 0.92, -15, 95, 120, 0, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = 'rgba(0,0,0,0.2)';
            ctx.fillRect(0, 0, w, 39);
            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(0, 39);
            ctx.lineTo(w, 39);
            ctx.stroke();

            await this.drawLogo(ctx);
            this.drawText(ctx, this.cardData.title, 42, 21, 9.5, 700, '#fff');
            this.drawText(ctx, this.cardData.subtitle, 42, 31, 6.2, 500, 'rgba(255,255,255,0.72)');

            await this.drawPhoto(ctx, 50, 48, 64, 71);

            this.drawText(ctx, this.cardData.name, 63, 137, 9.5, 700, '#fff', 112, 'center');
            this.drawInfo(ctx, 'User', this.cardData.username, 33, 152);
            this.drawInfo(ctx, 'Role', this.cardData.role, 33, 163);
            if (this.cardData.organization) {
                this.drawInfo(ctx, 'Bidang', this.cardData.organization, 33, 174);
            }

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

            if (this.cardData.avatarUrl) {
                try {
                    const image = await this.loadImage(this.cardData.avatarUrl);
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
            return String(value || 'user').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'user';
        },

        escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));
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
