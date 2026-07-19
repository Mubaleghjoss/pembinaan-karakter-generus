@extends('layouts.public')

@section('title', 'Pendaftaran Generus PKG - ' . ($theme->app_name ?? 'PKG'))

@section('content')
@php
    $initialMode = old('registration_mode', $initialStudent ? 'existing' : '');
@endphp
<div class="py-6 sm:py-10">
    <div
        id="generus-registration-app"
        class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"
        data-search-url="{{ route('public.generus-registration.short.search') }}"
        data-verify-url="{{ route('public.generus-registration.short.verify') }}"
        data-initial-mode="{{ $initialMode }}"
        data-initial-selection="{{ old('selected_student_token', $initialSelectionToken) }}"
        data-initial-student="{{ json_encode($initialStudent, JSON_UNESCAPED_UNICODE) }}"
    >
        <div class="pkg-page-header">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Formulir Privat</p>
                <h1 class="pkg-page-heading mt-2">Pendaftaran dan Biodata Generus PKG</h1>
                <p class="pkg-page-subheading max-w-3xl">Daftarkan Generus baru atau lengkapi biodata akun yang sudah tersedia, lalu bubuhkan tanda tangan Orang Tua dan Generus.</p>
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

        <section class="pkg-panel-lg p-4 sm:p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Pilih Jenis Pendaftaran</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Akun lama wajib diverifikasi agar biodata tidak dapat diubah oleh orang lain.</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <button type="button" data-registration-mode="new" class="rounded-2xl border-2 border-gray-200 p-4 text-left transition hover:border-emerald-400 dark:border-gray-700 dark:hover:border-emerald-500">
                    <span class="block font-bold text-gray-900 dark:text-white">Generus Baru</span>
                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Belum memiliki akun siswa PKG.</span>
                </button>
                <button type="button" data-registration-mode="existing" class="rounded-2xl border-2 border-gray-200 p-4 text-left transition hover:border-emerald-400 dark:border-gray-700 dark:hover:border-emerald-500">
                    <span class="block font-bold text-gray-900 dark:text-white">Sudah Terdaftar</span>
                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Cari nama dan lengkapi biodata akun lama.</span>
                </button>
            </div>
        </section>

        <section id="existing-account-panel" class="pkg-panel-lg mt-6 hidden p-4 sm:p-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Cari Generus Terdaftar</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ketik minimal 3 huruf, misalnya “Angga”, lalu pilih nama yang sesuai.</p>
            </div>
            <div class="relative mt-5">
                <label for="student-search" class="form-label">Nama Generus</label>
                <input id="student-search" type="search" class="pkg-field w-full" autocomplete="off" placeholder="Ketik nama Generus...">
                <div id="student-search-loading" class="mt-2 hidden text-sm text-gray-500 dark:text-gray-400">Mencari data...</div>
                <div id="student-search-results" class="absolute z-30 mt-1 hidden max-h-72 w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-1 shadow-xl dark:border-gray-700 dark:bg-gray-900"></div>
            </div>

            <div id="selected-student-card" class="mt-5 hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Generus dipilih</p>
                <p id="selected-student-name" class="mt-1 font-bold text-gray-900 dark:text-white"></p>
                <p id="selected-student-meta" class="mt-1 text-sm text-gray-600 dark:text-gray-300"></p>
            </div>

            <form id="existing-verification-form" class="mt-5 hidden space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="login_type" class="form-label">Gunakan Akun</label>
                        <select id="login_type" class="pkg-field w-full">
                            <option value="siswa">Akun Siswa</option>
                            <option value="ortu">Akun Orang Tua</option>
                        </select>
                    </div>
                    <div>
                        <label for="verification_username" class="form-label">Username</label>
                        <input id="verification_username" type="text" class="pkg-field w-full" autocomplete="username" required>
                    </div>
                </div>
                <div>
                    <label for="verification_password" class="form-label">Password</label>
                    <input id="verification_password" type="password" class="pkg-field w-full" autocomplete="current-password" required>
                </div>
                <button id="verification-submit" type="submit" class="btn-success min-h-11 px-5 py-2.5 font-bold">Verifikasi dan Lanjutkan</button>
            </form>
        </section>

        <form id="generus-registration-form" method="POST" action="{{ $formAction }}" class="mt-6 hidden space-y-6">
            @csrf
            <input id="registration_mode" type="hidden" name="registration_mode" value="{{ $initialMode }}">
            <input id="selected_student_token" type="hidden" name="selected_student_token" value="{{ old('selected_student_token', $initialSelectionToken) }}">

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
            </section>

            <div class="pkg-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <p id="submit-helper" class="text-sm text-gray-500 dark:text-gray-400">Surat PDF akan tersedia setelah formulir disimpan.</p>
                <button id="registration-submit" type="submit" class="btn-success min-h-12 px-6 py-3 text-base font-bold">Simpan dan Buat Surat</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const app = document.getElementById('generus-registration-app');
    if (!app) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
    const mainForm = document.getElementById('generus-registration-form');
    const existingPanel = document.getElementById('existing-account-panel');
    const verificationForm = document.getElementById('existing-verification-form');
    const selectedCard = document.getElementById('selected-student-card');
    const resultsBox = document.getElementById('student-search-results');
    const searchInput = document.getElementById('student-search');
    const loading = document.getElementById('student-search-loading');
    const modeInput = document.getElementById('registration_mode');
    const selectionInput = document.getElementById('selected_student_token');
    const fieldNames = ['parent_name', 'parent_phone', 'student_name', 'student_phone', 'kelompok', 'birth_place', 'birth_date', 'school_grade'];
    let selectedStudent = null;
    let searchTimer = null;
    let pads = [];

    function notify(message, type = 'error') {
        if (window.showNotification) window.showNotification(message, type);
        else window.alert(message);
    }

    function setModeButton(mode) {
        document.querySelectorAll('[data-registration-mode]').forEach((button) => {
            const active = button.dataset.registrationMode === mode;
            button.classList.toggle('border-emerald-500', active);
            button.classList.toggle('bg-emerald-50', active);
            button.classList.toggle('dark:bg-emerald-950/30', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function fillFields(data) {
        fieldNames.forEach((name) => {
            const field = document.getElementById(name);
            if (field && Object.prototype.hasOwnProperty.call(data || {}, name)) field.value = data[name] || '';
        });
    }

    function clearFields() {
        fillFields(Object.fromEntries(fieldNames.map((name) => [name, ''])));
    }

    function showMainForm(mode, data = null, selectionToken = '') {
        modeInput.value = mode;
        selectionInput.value = selectionToken;
        if (data) fillFields(data);
        mainForm.classList.remove('hidden');
        initializePads();
        document.getElementById('submit-helper').textContent = mode === 'new'
            ? 'Akun siswa, akun orang tua, dan surat PDF akan dibuat.'
            : 'Biodata dan surat terbaru akan diperbarui tanpa mereset password.';
    }

    function chooseMode(mode) {
        setModeButton(mode);
        modeInput.value = mode;
        if (mode === 'new') {
            existingPanel.classList.add('hidden');
            verificationForm.classList.add('hidden');
            selectedCard.classList.add('hidden');
            selectionInput.value = '';
            selectedStudent = null;
            clearFields();
            showMainForm('new');
            return;
        }
        existingPanel.classList.remove('hidden');
        mainForm.classList.add('hidden');
        selectionInput.value = '';
        searchInput.focus();
    }

    document.querySelectorAll('[data-registration-mode]').forEach((button) => {
        button.addEventListener('click', () => chooseMode(button.dataset.registrationMode));
    });

    function renderResults(items) {
        resultsBox.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'p-3 text-sm text-gray-500 dark:text-gray-400';
            empty.textContent = 'Nama tidak ditemukan. Periksa ejaan atau gunakan alur Generus Baru.';
            resultsBox.appendChild(empty);
        }
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-800';
            const identity = document.createElement('span');
            identity.className = 'min-w-0';
            const name = document.createElement('span');
            name.className = 'block truncate font-bold text-gray-900 dark:text-white';
            name.textContent = item.nama;
            const group = document.createElement('span');
            group.className = 'mt-0.5 block truncate text-xs text-gray-500 dark:text-gray-400';
            group.textContent = item.kelompok;
            const nis = document.createElement('span');
            nis.className = 'shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300';
            nis.textContent = item.nis_masked;
            identity.append(name, group);
            button.append(identity, nis);
            button.addEventListener('click', () => selectStudent(item));
            resultsBox.appendChild(button);
        });
        resultsBox.classList.remove('hidden');
    }

    async function searchStudents() {
        const query = searchInput.value.trim();
        if (query.length < 3) {
            resultsBox.classList.add('hidden');
            return;
        }
        loading.classList.remove('hidden');
        try {
            const response = await fetch(`${app.dataset.searchUrl}?q=${encodeURIComponent(query)}`, {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Pencarian gagal.');
            renderResults(payload.data || []);
        } catch (error) {
            notify(error.message || 'Tidak dapat mencari data Generus.');
        } finally {
            loading.classList.add('hidden');
        }
    }

    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(searchStudents, 300);
    });

    function selectStudent(item) {
        selectedStudent = item;
        resultsBox.classList.add('hidden');
        searchInput.value = item.nama;
        document.getElementById('selected-student-name').textContent = item.nama;
        document.getElementById('selected-student-meta').textContent = `${item.kelompok} · NIS ${item.nis_masked}`;
        selectedCard.classList.remove('hidden');
        verificationForm.classList.remove('hidden');
        mainForm.classList.add('hidden');
        document.getElementById('verification_username').value = '';
        document.getElementById('verification_password').value = '';
        document.getElementById('verification_username').focus();
    }

    verificationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedStudent) return;
        const button = document.getElementById('verification-submit');
        button.disabled = true;
        try {
            const response = await fetch(app.dataset.verifyUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({
                    selection_token: selectedStudent.selection_token,
                    login_type: document.getElementById('login_type').value,
                    username: document.getElementById('verification_username').value,
                    password: document.getElementById('verification_password').value
                })
            });
            const payload = await response.json();
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || 'Verifikasi gagal.');
            }
            showMainForm('existing', payload.student, selectedStudent.selection_token);
            mainForm.scrollIntoView({behavior: 'smooth', block: 'start'});
            notify(payload.message || 'Akun berhasil diverifikasi.', 'success');
        } catch (error) {
            notify(error.message || 'Username atau password tidak sesuai.');
        } finally {
            button.disabled = false;
        }
    });

    function initializePads() {
        if (pads.length) return;
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

    mainForm.addEventListener('submit', (event) => {
        const missing = pads.find((pad) => !pad.input.value);
        if (missing) {
            event.preventDefault();
            missing.canvas.scrollIntoView({behavior: 'smooth', block: 'center'});
            notify('Tanda tangan Orang Tua dan Generus wajib diisi.', 'warning');
            return;
        }
        const button = document.getElementById('registration-submit');
        button.disabled = true;
        button.textContent = 'Memproses...';
    });

    let initialStudent = null;
    try { initialStudent = JSON.parse(app.dataset.initialStudent || 'null'); } catch (error) { initialStudent = null; }
    if (app.dataset.initialMode === 'new') {
        setModeButton('new');
        showMainForm('new');
    } else if (app.dataset.initialMode === 'existing') {
        setModeButton('existing');
        existingPanel.classList.remove('hidden');
        if (initialStudent && app.dataset.initialSelection) showMainForm('existing', initialStudent, app.dataset.initialSelection);
    }
})();
</script>
@endpush
