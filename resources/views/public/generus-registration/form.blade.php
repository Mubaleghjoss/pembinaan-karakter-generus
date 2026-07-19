@extends('layouts.public')

@section('title', 'Pendaftaran Generus Baru - ' . ($theme->app_name ?? 'PKG'))

@section('content')
<div class="py-6 sm:py-10">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Tautan Pendaftaran Privat</p>
                <h1 class="pkg-page-heading mt-2">Pendaftaran Generus PKG Baru</h1>
                <p class="pkg-page-subheading max-w-3xl">Lengkapi data orang tua dan Generus, baca surat pernyataan, lalu bubuhkan kedua tanda tangan. Sistem akan membuat akun Generus dan Orang Tua secara otomatis.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200" role="alert">
                <p class="font-bold">Pendaftaran belum dapat disimpan:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="generus-registration-form" method="POST" action="{{ route('public.generus-registration.store', ['token' => $token]) }}" class="space-y-6">
            @csrf

            <section class="pkg-panel-lg p-4 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Data Orang Tua</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data ini digunakan untuk membuat akun portal Orang Tua.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="parent_name" class="form-label">Nama Orang Tua</label>
                        <input id="parent_name" name="parent_name" type="text" value="{{ old('parent_name') }}" class="pkg-field w-full" maxlength="120" autocomplete="name" required>
                    </div>
                    <div>
                        <label for="parent_phone" class="form-label">No. WhatsApp Orang Tua</label>
                        <input id="parent_phone" name="parent_phone" type="tel" value="{{ old('parent_phone') }}" class="pkg-field w-full" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="Contoh: 081234567890" required>
                    </div>
                </div>
            </section>

            <section class="pkg-panel-lg p-4 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Data Generus</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pastikan penulisan nama dan tanggal lahir sudah benar karena akan masuk ke akun PKG.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="student_name" class="form-label">Nama Generus</label>
                        <input id="student_name" name="student_name" type="text" value="{{ old('student_name') }}" class="pkg-field w-full" maxlength="120" autocomplete="name" required>
                    </div>
                    <div>
                        <label for="student_phone" class="form-label">No. WhatsApp Generus</label>
                        <input id="student_phone" name="student_phone" type="tel" value="{{ old('student_phone') }}" class="pkg-field w-full" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="Contoh: 081234567890" required>
                    </div>
                    <div>
                        <label for="kelompok" class="form-label">Kelompok</label>
                        <select id="kelompok" name="kelompok" class="pkg-field w-full" required>
                            <option value="">Pilih kelompok</option>
                            @foreach($kelompokOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('kelompok') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="school_grade" class="form-label">Sekarang Sekolah Kelas</label>
                        <select id="school_grade" name="school_grade" class="pkg-field w-full" required>
                            <option value="">Pilih kelas sekolah</option>
                            @foreach($schoolGradeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('school_grade') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="birth_place" class="form-label">Tempat Lahir Generus</label>
                        <input id="birth_place" name="birth_place" type="text" value="{{ old('birth_place') }}" class="pkg-field w-full" maxlength="120" required>
                    </div>
                    <div>
                        <label for="birth_date" class="form-label">Tanggal Lahir Generus</label>
                        <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" max="{{ now()->toDateString() }}" class="pkg-field w-full" required>
                    </div>
                </div>
            </section>

            <section class="pkg-panel-lg p-4 sm:p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Surat Pernyataan</h2>
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-7 text-emerald-950 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-100 sm:p-5">
                    <p>Kami, Orang Tua dan Generus yang mengisi formulir ini, menyatakan bahwa:</p>
                    <ol class="mt-3 list-decimal space-y-2 pl-5">
                        <li>Seluruh data yang diberikan adalah benar dan dapat dipertanggungjawabkan.</li>
                        <li>Generus bersedia mengikuti kegiatan Pembinaan Karakter Generus dan menaati tata tertib yang berlaku.</li>
                        <li>Orang Tua bersedia mendukung kehadiran, pembinaan, dan komunikasi terkait perkembangan Generus.</li>
                        <li>Data pada formulir ini boleh digunakan untuk administrasi internal, penyaksian pengurus, dan pembuatan akun PKG.</li>
                    </ol>
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div data-signature-pad>
                        <div class="flex items-center justify-between gap-3">
                            <label class="form-label mb-0" for="parent-signature-canvas">TTD Orang Tua</label>
                            <button type="button" data-signature-clear class="btn-secondary px-3 py-1.5 text-xs">Hapus</button>
                        </div>
                        <canvas id="parent-signature-canvas" data-signature-canvas class="pkg-signature-canvas mt-2" aria-label="Area tanda tangan Orang Tua"></canvas>
                        <input type="hidden" name="parent_signature" data-signature-input value="">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Tulis menggunakan jari, stylus, atau mouse.</p>
                    </div>
                    <div data-signature-pad>
                        <div class="flex items-center justify-between gap-3">
                            <label class="form-label mb-0" for="student-signature-canvas">TTD Generus</label>
                            <button type="button" data-signature-clear class="btn-secondary px-3 py-1.5 text-xs">Hapus</button>
                        </div>
                        <canvas id="student-signature-canvas" data-signature-canvas class="pkg-signature-canvas mt-2" aria-label="Area tanda tangan Generus"></canvas>
                        <input type="hidden" name="student_signature" data-signature-input value="">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Tulis menggunakan jari, stylus, atau mouse.</p>
                    </div>
                </div>

                <label class="mt-6 flex items-start gap-3 rounded-2xl border border-gray-200 p-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="statement_accepted" value="1" class="pkg-check mt-0.5 h-5 w-5" required @checked(old('statement_accepted'))>
                    <span>Saya menyetujui surat pernyataan di atas dan memastikan kedua tanda tangan dibuat oleh pihak yang bersangkutan.</span>
                </label>
            </section>

            <div class="pkg-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Setelah dikirim, akun dan surat PDF akan langsung dibuat.</p>
                <button id="registration-submit" type="submit" class="btn-success min-h-12 px-6 py-3 text-base font-bold">Kirim Pendaftaran</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('generus-registration-form');
        if (!form) return;

        function initializePad(wrapper) {
            const canvas = wrapper.querySelector('[data-signature-canvas]');
            const input = wrapper.querySelector('[data-signature-input]');
            const clearButton = wrapper.querySelector('[data-signature-clear]');
            const context = canvas.getContext('2d');
            let drawing = false;
            let hasInk = false;

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = Math.max(1, Math.floor(rect.width * ratio));
                canvas.height = Math.max(1, Math.floor(rect.height * ratio));
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 2.4;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#0f172a';
                hasInk = false;
                input.value = '';
            }

            function coordinates(event) {
                const rect = canvas.getBoundingClientRect();
                return { x: event.clientX - rect.left, y: event.clientY - rect.top };
            }

            canvas.addEventListener('pointerdown', function (event) {
                drawing = true;
                hasInk = true;
                canvas.setPointerCapture(event.pointerId);
                const point = coordinates(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
                event.preventDefault();
            });

            canvas.addEventListener('pointermove', function (event) {
                if (!drawing) return;
                const point = coordinates(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                event.preventDefault();
            });

            function finish(event) {
                if (!drawing) return;
                drawing = false;
                context.closePath();
                if (event?.pointerId && canvas.hasPointerCapture(event.pointerId)) canvas.releasePointerCapture(event.pointerId);
                input.value = hasInk ? canvas.toDataURL('image/png') : '';
            }

            canvas.addEventListener('pointerup', finish);
            canvas.addEventListener('pointercancel', finish);
            clearButton.addEventListener('click', function () {
                context.save();
                context.setTransform(1, 0, 0, 1, 0, 0);
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.restore();
                hasInk = false;
                input.value = '';
            });

            resizeCanvas();
            return { input, canvas };
        }

        const pads = Array.from(document.querySelectorAll('[data-signature-pad]')).map(initializePad);

        form.addEventListener('submit', function (event) {
            const missing = pads.find((pad) => !pad.input.value);
            if (missing) {
                event.preventDefault();
                missing.canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.showNotification('Tanda tangan Orang Tua dan Generus wajib diisi', 'warning');
                return;
            }

            const button = document.getElementById('registration-submit');
            button.disabled = true;
            button.textContent = 'Memproses Pendaftaran...';
        });
    })();
</script>
@endpush
