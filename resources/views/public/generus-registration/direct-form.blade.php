@extends('layouts.public')

@section('title', 'Daftar Ulang Generus - ' . ($theme->app_name ?? 'PKG'))

@section('content')
<div class="py-6 sm:py-10">
    <div
        id="generus-direct-app"
        class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"
    >
        <div class="pkg-page-header">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Daftar Ulang</p>
                <h1 class="pkg-page-heading mt-2">Surat Pernyataan &amp; Biodata</h1>
                <p class="pkg-page-subheading max-w-3xl">Halo, tautan ini khusus untuk <span class="font-bold text-gray-900 dark:text-white">{{ $siswa->nama }}</span>. Mohon Orang Tua memeriksa biodata, membaca pernyataan, lalu membubuhkan tanda tangan.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200" role="alert">
                <p class="font-bold">Formulir belum dapat disimpan:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form id="generus-direct-form" method="POST" action="{{ route('public.generus-registration.short.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="registration_mode" value="existing">
            {{-- selected_student_token hanya untuk lolos validasi; sumber kebenaran ada di sesi tautan langsung --}}
            <input type="hidden" name="selected_student_token" value="{{ $siswa->id }}">

            <section class="pkg-panel-lg p-4 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Data Orang Tua</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data ini terhubung dengan portal Orang Tua.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="parent_name" class="form-label">Nama Orang Tua</label><input id="parent_name" name="parent_name" type="text" value="{{ old('parent_name', $initialStudent['parent_name'] ?? '') }}" class="pkg-field w-full" maxlength="120" required></div>
                    <div><label for="parent_phone" class="form-label">No. WhatsApp Orang Tua</label><input id="parent_phone" name="parent_phone" type="tel" value="{{ old('parent_phone', $initialStudent['parent_phone'] ?? '') }}" class="pkg-field w-full" maxlength="30" inputmode="tel" placeholder="Contoh: 081234567890" required></div>
                </div>
            </section>

            <section class="pkg-panel-lg p-4 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Data Generus</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pastikan seluruh biodata sudah benar.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="student_name" class="form-label">Nama Generus</label><input id="student_name" name="student_name" type="text" value="{{ old('student_name', $initialStudent['student_name'] ?? '') }}" class="pkg-field w-full" maxlength="120" required></div>
                    <div><label for="student_phone" class="form-label">No. WhatsApp Generus</label><input id="student_phone" name="student_phone" type="tel" value="{{ old('student_phone', $initialStudent['student_phone'] ?? '') }}" class="pkg-field w-full" maxlength="30" inputmode="tel" required></div>
                    <div>
                        <label for="kelompok" class="form-label">Kelompok</label>
                        <select id="kelompok" name="kelompok" class="pkg-field w-full" required><option value="">Pilih kelompok</option>@foreach($kelompokOptions as $value => $label)<option value="{{ $value }}" @selected(old('kelompok', $initialStudent['kelompok'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label for="school_grade" class="form-label">Sekarang Sekolah Kelas</label>
                        <select id="school_grade" name="school_grade" class="pkg-field w-full" required><option value="">Pilih kelas sekolah</option>@foreach($schoolGradeOptions as $value => $label)<option value="{{ $value }}" @selected(old('school_grade', $initialStudent['school_grade'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div><label for="birth_place" class="form-label">Tempat Lahir Generus</label><input id="birth_place" name="birth_place" type="text" value="{{ old('birth_place', $initialStudent['birth_place'] ?? '') }}" class="pkg-field w-full" maxlength="120" required></div>
                    <div><label for="birth_date" class="form-label">Tanggal Lahir Generus</label><input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', $initialStudent['birth_date'] ?? '') }}" max="{{ now()->toDateString() }}" class="pkg-field w-full" required></div>
                </div>
            </section>

            <section class="pkg-panel-lg p-4 sm:p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Surat Pernyataan</h2>
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-7 text-emerald-950 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-100 sm:p-5">
                    <p>Kami, Orang Tua dan Generus, menyatakan bahwa:</p>
                    <ol class="mt-3 list-decimal space-y-2 pl-5">
                        <li>Seluruh data yang diberikan benar dan dapat dipertanggungjawabkan.</li>
                        <li>Generus bersedia mengikuti kegiatan Pembinaan Karakter Generus dan menaati tata tertib.</li>
                        <li>Orang Tua bersedia mendukung kehadiran, pembinaan, dan komunikasi perkembangan Generus.</li>
                        <li>Data boleh digunakan untuk administrasi internal, penyaksian pengurus, dan pengelolaan akun PKG.</li>
                    </ol>
                </div>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @foreach([['parent', 'Orang Tua'], ['student', 'Generus']] as [$prefix, $label])
                    <div data-signature-pad>
                        <div class="flex items-center justify-between gap-3"><label class="form-label mb-0" for="{{ $prefix }}-signature-canvas">TTD {{ $label }}</label><button type="button" data-signature-clear class="btn-secondary px-3 py-1.5 text-xs">Hapus</button></div>
                        <canvas id="{{ $prefix }}-signature-canvas" data-signature-canvas class="pkg-signature-canvas mt-2" aria-label="Area tanda tangan {{ $label }}"></canvas>
                        <input type="hidden" name="{{ $prefix }}_signature" data-signature-input>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Tulis menggunakan jari, stylus, atau mouse.</p>
                    </div>
                    @endforeach
                </div>
                <label class="mt-6 flex items-start gap-3 rounded-2xl border border-gray-200 p-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="statement_accepted" value="1" class="pkg-check mt-0.5 h-5 w-5" required @checked(old('statement_accepted'))>
                    <span>Saya menyetujui pernyataan di atas dan memastikan kedua tanda tangan dibuat oleh pihak yang bersangkutan.</span>
                </label>

                <div class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-7 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                    <p class="font-bold">Perhatian: akun akan diselaraskan</p>
                    <p class="mt-1">Setelah surat ini dikirim, <span class="font-bold">password akun Generus dan Orang Tua akan direset ke NIS</span> agar keduanya pasti dapat masuk (mengatasi kasus lupa password). Informasi login lengkap akan ditampilkan di halaman berikutnya dan dapat dikirim langsung via WhatsApp. Silakan ganti password setelah berhasil login.</p>
                </div>
            </section>

            <div class="pkg-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Biodata dan surat terbaru akan diperbarui, lalu informasi akun Generus &amp; Orang Tua ditampilkan.</p>
                <button id="direct-submit" type="submit" class="btn-success min-h-12 px-6 py-3 text-base font-bold">Simpan dan Buat Surat</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('generus-direct-form');
    if (!form) return;
    let pads = [];

    function notify(message, type = 'error') {
        if (window.showNotification) window.showNotification(message, type);
        else window.alert(message);
    }

    function initializePads() {
        pads = Array.from(document.querySelectorAll('[data-signature-pad]')).map((wrapper) => {
            const canvas = wrapper.querySelector('[data-signature-canvas]');
            const input = wrapper.querySelector('[data-signature-input]');
            const clearButton = wrapper.querySelector('[data-signature-clear]');
            const context = canvas.getContext('2d');
            let drawing = false;
            let hasInk = false;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();
            canvas.width = Math.max(1, Math.floor(rect.width * ratio));
            canvas.height = Math.max(1, Math.floor(rect.height * ratio));
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.lineWidth = 2.4;
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.strokeStyle = document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#0f172a';
            const point = (event) => { const box = canvas.getBoundingClientRect(); return {x: event.clientX - box.left, y: event.clientY - box.top}; };
            canvas.addEventListener('pointerdown', (event) => { drawing = true; hasInk = true; canvas.setPointerCapture(event.pointerId); const p = point(event); context.beginPath(); context.moveTo(p.x, p.y); event.preventDefault(); });
            canvas.addEventListener('pointermove', (event) => { if (!drawing) return; const p = point(event); context.lineTo(p.x, p.y); context.stroke(); event.preventDefault(); });
            const finish = (event) => { if (!drawing) return; drawing = false; context.closePath(); if (canvas.hasPointerCapture(event.pointerId)) canvas.releasePointerCapture(event.pointerId); input.value = hasInk ? canvas.toDataURL('image/png') : ''; };
            canvas.addEventListener('pointerup', finish);
            canvas.addEventListener('pointercancel', finish);
            clearButton.addEventListener('click', () => { context.save(); context.setTransform(1, 0, 0, 1, 0, 0); context.clearRect(0, 0, canvas.width, canvas.height); context.restore(); hasInk = false; input.value = ''; });
            return {canvas, input};
        });
    }

    form.addEventListener('submit', (event) => {
        const missing = pads.find((pad) => !pad.input.value);
        if (missing) {
            event.preventDefault();
            missing.canvas.scrollIntoView({behavior: 'smooth', block: 'center'});
            notify('Tanda tangan Orang Tua dan Generus wajib diisi.', 'warning');
            return;
        }
        const button = document.getElementById('direct-submit');
        button.disabled = true;
        button.textContent = 'Memproses...';
    });

    initializePads();
})();
</script>
@endpush
