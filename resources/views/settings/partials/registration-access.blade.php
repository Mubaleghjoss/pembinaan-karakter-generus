<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(18rem,0.55fr)]">
    <section class="pkg-panel-lg p-5 sm:p-6">
        <div class="mb-6">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Tautan Privat</p>
            <h2 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">Akses Pendaftaran Generus PKG</h2>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Atur kode yang digunakan untuk membuka <span class="font-semibold">/daftarpkg</span>. Kode disimpan sebagai hash dan tidak dapat dibaca kembali.</p>
        </div>

        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200" role="alert">
                <p class="font-bold">Pengaturan belum dapat disimpan:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if(session('registration_access_code'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Salin kode baru sekarang</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <code class="rounded-xl bg-white px-4 py-2 text-lg font-black tracking-[0.2em] text-emerald-800 shadow-sm dark:bg-gray-900 dark:text-emerald-200">{{ session('registration_access_code') }}</code>
                    <button type="button" class="btn-secondary !px-4 !py-2 text-sm" data-copy-registration-code="{{ session('registration_access_code') }}">Salin Kode</button>
                </div>
                <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">Kode hanya ditampilkan sekali setelah disimpan.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.update.registration-access') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="registration_label" class="form-label">Nama akses</label>
                <input id="registration_label" name="label" type="text" class="pkg-field w-full" maxlength="120" value="{{ old('label', $registrationInvite?->label ?? 'Pendaftaran Generus PKG') }}" required>
            </div>

            <div>
                <label for="registration_access_code" class="form-label">{{ $registrationInvite ? 'Kode akses baru (opsional)' : 'Kode akses' }}</label>
                <input id="registration_access_code" name="access_code" type="text" class="pkg-field w-full uppercase tracking-[0.14em]" minlength="6" maxlength="32" pattern="[A-Za-z0-9]+" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="{{ $registrationInvite ? 'Kosongkan jika kode tidak diubah' : 'Contoh: B9UY7BLS' }}" @required(! $registrationInvite)>
                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">6–32 karakter, hanya huruf dan angka. Mengganti kode tidak mengubah akun atau surat yang sudah dibuat.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="registration_valid_days" class="form-label">Masa berlaku</label>
                    <div class="relative">
                        <input id="registration_valid_days" name="valid_days" type="number" class="pkg-field w-full pr-16" min="1" max="3650" value="{{ old('valid_days', 180) }}" required>
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm text-gray-500 dark:text-gray-400">hari</span>
                    </div>
                </div>
                <div>
                    <label for="registration_max_uses" class="form-label">Maksimal pendaftaran</label>
                    <input id="registration_max_uses" name="max_uses" type="number" class="pkg-field w-full" min="{{ max(1, (int) ($registrationInvite?->used_count ?? 0)) }}" max="100000" value="{{ old('max_uses', $registrationInvite?->max_uses ?? 50) }}" required>
                </div>
            </div>

            <label class="pkg-check flex items-start gap-3 rounded-2xl p-4">
                <input name="is_active" type="checkbox" value="1" class="mt-0.5" @checked(old('is_active', $registrationInvite?->is_active ?? true))>
                <span>
                    <span class="block font-semibold text-gray-900 dark:text-white">Kode akses aktif</span>
                    <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Jika dinonaktifkan, kode langsung tidak dapat membuka formulir.</span>
                </span>
            </label>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary min-h-11 px-5 py-2.5 font-bold">Simpan Pengaturan</button>
                <a href="{{ route('public.generus-registration.short.index') }}" target="_blank" rel="noopener" class="btn-secondary min-h-11 px-5 py-2.5 font-bold">Buka /daftarpkg</a>
            </div>
        </form>
    </section>

    <aside class="pkg-card-soft h-fit p-5 sm:p-6">
        <h3 class="font-bold text-gray-900 dark:text-white">Status Akses</h3>
        @if($registrationInvite)
            @php
                $registrationAvailable = $registrationInvite->isAvailable();
                $remainingUses = max(0, $registrationInvite->max_uses - $registrationInvite->used_count);
            @endphp
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Status</dt><dd class="rounded-full px-3 py-1 text-xs font-bold {{ $registrationAvailable ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200' }}">{{ $registrationAvailable ? 'Aktif' : 'Tidak tersedia' }}</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Digunakan</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $registrationInvite->used_count }} dari {{ $registrationInvite->max_uses }}</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Sisa kuota</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $remainingUses }}</dd></div>
                <div class="flex items-start justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Berlaku hingga</dt><dd class="text-right font-bold text-gray-900 dark:text-white">{{ $registrationInvite->expires_at?->translatedFormat('d M Y, H:i') ?? 'Tanpa batas' }}</dd></div>
            </dl>
        @else
            <div class="pkg-empty-state mt-5">
                <p class="pkg-empty-title">Belum ada kode akses</p>
                <p class="pkg-empty-copy">Isi formulir di samping untuk mengaktifkan pendaftaran privat.</p>
            </div>
        @endif
        <div class="mt-5 rounded-2xl border border-gray-200 bg-white/70 p-4 text-xs leading-5 text-gray-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400">
            Sesi yang sudah terbuka tetap berakhir otomatis setelah 60 menit. Kode lama berhenti berlaku segera setelah kode baru disimpan.
        </div>
    </aside>
</div>

@push('scripts')
<script>
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy-registration-code]');
        if (!button) return;

        await navigator.clipboard.writeText(button.dataset.copyRegistrationCode);
        const originalLabel = button.textContent;
        button.textContent = 'Tersalin';
        window.setTimeout(() => { button.textContent = originalLabel; }, 1600);
    });
</script>
@endpush
