@php
    $rppMateri = $materi ?? null;
    $rppEnabled = old('rpp_is_enabled', $rppMateri?->rpp_is_enabled ? '1' : '');
    $rppExtraSessions = old('rpp_extra_sessions', $rppMateri?->rpp_extra_sessions ?? []);
    $rppExtraSessions = is_array($rppExtraSessions) ? array_values($rppExtraSessions) : [];
    $rppCatchUpRanges = old('rpp_catch_up_ranges', $rppMateri?->rpp_catch_up_ranges ?? []);
    $rppCatchUpRanges = is_array($rppCatchUpRanges) ? array_values($rppCatchUpRanges) : [];
    $rppTeacherPool = old('rpp_teacher_pool', $rppMateri?->rpp_teacher_pool ?? []);
    $rppTeacherPool = is_array($rppTeacherPool) ? array_values($rppTeacherPool) : [];
    $pamongOptions = $pamongOptions ?? collect();
@endphp

<div class="pkg-panel p-5" data-rpp-panel data-preview-url="{{ route('materi.rpp-preview') }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">RPP Kalender</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Opsional. Jika tidak diaktifkan, materi tetap tersimpan tanpa event kalender.</p>
        </div>
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="rpp_is_enabled" value="1" class="pkg-check rounded" @checked((bool) $rppEnabled)>
            Aktifkan RPP Kalender
        </label>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Total Halaman</label>
            <input type="number" name="rpp_total_pages" min="1" value="{{ old('rpp_total_pages', $rppMateri?->rpp_total_pages) }}" class="w-full pkg-field">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Halaman Mulai</label>
            <input type="number" name="rpp_start_page" min="1" value="{{ old('rpp_start_page', $rppMateri?->rpp_start_page ?? 1) }}" class="w-full pkg-field">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Target Halaman per Pertemuan</label>
            <input type="number" name="rpp_pages_per_session" min="1" value="{{ old('rpp_pages_per_session', $rppMateri?->rpp_pages_per_session) }}" class="w-full pkg-field">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
            <input type="date" name="rpp_start_date" value="{{ old('rpp_start_date', $rppMateri?->rpp_start_date?->format('Y-m-d')) }}" class="w-full pkg-field">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Waktu Mulai</label>
            <input type="time" name="rpp_start_time" value="{{ old('rpp_start_time', $rppMateri?->rpp_start_time ? substr($rppMateri->rpp_start_time, 0, 5) : '') }}" class="w-full pkg-field">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Waktu Selesai</label>
            <input type="time" name="rpp_end_time" value="{{ old('rpp_end_time', $rppMateri?->rpp_end_time ? substr($rppMateri->rpp_end_time, 0, 5) : '') }}" class="w-full pkg-field">
        </div>
    </div>

    <div class="mt-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Hari Tambahan Mingguan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal yang dipilih akan diulang setiap minggu bersama hari Tanggal Mulai.</p>
            </div>
            <button type="button" class="btn-secondary px-3 py-2 text-xs" data-add-rpp-session>Tambah Hari</button>
        </div>

        <div class="mt-3 space-y-3" data-rpp-extra-sessions>
            @forelse($rppExtraSessions as $index => $session)
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[1fr_1fr_auto]" data-rpp-session-row>
                    <input type="date" name="rpp_extra_sessions[{{ $index }}][date]" value="{{ $session['date'] ?? '' }}" class="pkg-field">
                    <input type="number" name="rpp_extra_sessions[{{ $index }}][pages]" min="1" value="{{ $session['pages'] ?? '' }}" placeholder="Target halaman per hari, kosong = default" class="pkg-field">
                    <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-rpp-session>Hapus</button>
                </div>
            @empty
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[1fr_1fr_auto]" data-rpp-session-row>
                    <input type="date" name="rpp_extra_sessions[0][date]" class="pkg-field">
                    <input type="number" name="rpp_extra_sessions[0][pages]" min="1" placeholder="Target halaman per hari, kosong = default" class="pkg-field">
                    <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-rpp-session>Hapus</button>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Kejar Target Rentang Tanggal</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Rentang ini dibuat sekali jalan dan target dihitung per hari.</p>
            </div>
            <button type="button" class="btn-secondary px-3 py-2 text-xs" data-add-rpp-catch-up-range>Tambah Rentang</button>
        </div>

        <div class="mt-3 space-y-3" data-rpp-catch-up-ranges>
            @forelse($rppCatchUpRanges as $index => $range)
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[1fr_1fr_1fr_auto]" data-rpp-catch-up-row>
                    <input type="date" name="rpp_catch_up_ranges[{{ $index }}][start_date]" value="{{ $range['start_date'] ?? '' }}" class="pkg-field" aria-label="Tanggal awal kejar target">
                    <input type="date" name="rpp_catch_up_ranges[{{ $index }}][end_date]" value="{{ $range['end_date'] ?? '' }}" class="pkg-field" aria-label="Tanggal akhir kejar target">
                    <input type="number" name="rpp_catch_up_ranges[{{ $index }}][pages]" min="1" value="{{ $range['pages'] ?? '' }}" placeholder="Target halaman per hari" class="pkg-field">
                    <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-rpp-catch-up-range>Hapus</button>
                </div>
            @empty
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[1fr_1fr_1fr_auto]" data-rpp-catch-up-row>
                    <input type="date" name="rpp_catch_up_ranges[0][start_date]" class="pkg-field" aria-label="Tanggal awal kejar target">
                    <input type="date" name="rpp_catch_up_ranges[0][end_date]" class="pkg-field" aria-label="Tanggal akhir kejar target">
                    <input type="number" name="rpp_catch_up_ranges[0][pages]" min="1" placeholder="Target halaman per hari" class="pkg-field">
                    <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-rpp-catch-up-range>Hapus</button>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengajar RPP</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Urutan ini dipakai otomatis bergantian. Jika memilih akun pamong, nama otomatis terbaca; isi manual hanya untuk pengajar tanpa akun.</p>
            </div>
            <button type="button" class="btn-secondary px-3 py-2 text-xs" data-add-rpp-teacher>Tambah Pengajar</button>
        </div>

        <div class="mt-3 space-y-3" data-rpp-teachers>
            @forelse($rppTeacherPool as $index => $teacher)
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[1fr_1fr_auto]" data-rpp-teacher-row>
                    <select name="rpp_teacher_pool[{{ $index }}][user_id]" class="pkg-field" data-rpp-teacher-select>
                        <option value="">Input manual</option>
                        @foreach($pamongOptions as $pamong)
                            @php
                                $pamongName = trim((string) $pamong->name);
                                $pamongUsername = trim((string) $pamong->username);
                                $pamongLabel = $pamongName !== '' ? $pamongName : ($pamongUsername !== '' ? $pamongUsername : 'Akun #' . $pamong->id);
                                $pamongSuffix = $pamongName !== '' && $pamongUsername !== '' ? ' - ' . $pamongUsername : '';
                            @endphp
                            <option value="{{ $pamong->id }}" data-name="{{ $pamongLabel }}" @selected((int) ($teacher['user_id'] ?? 0) === $pamong->id)>{{ $pamongLabel }}{{ $pamongSuffix }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="rpp_teacher_pool[{{ $index }}][name]" value="{{ $teacher['name'] ?? '' }}" placeholder="Nama pengajar manual" class="pkg-field" data-rpp-teacher-name>
                    <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-rpp-teacher>Hapus</button>
                </div>
            @empty
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[1fr_1fr_auto]" data-rpp-teacher-row>
                    <select name="rpp_teacher_pool[0][user_id]" class="pkg-field" data-rpp-teacher-select>
                        <option value="">Input manual</option>
                        @foreach($pamongOptions as $pamong)
                            @php
                                $pamongName = trim((string) $pamong->name);
                                $pamongUsername = trim((string) $pamong->username);
                                $pamongLabel = $pamongName !== '' ? $pamongName : ($pamongUsername !== '' ? $pamongUsername : 'Akun #' . $pamong->id);
                                $pamongSuffix = $pamongName !== '' && $pamongUsername !== '' ? ' - ' . $pamongUsername : '';
                            @endphp
                            <option value="{{ $pamong->id }}" data-name="{{ $pamongLabel }}">{{ $pamongLabel }}{{ $pamongSuffix }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="rpp_teacher_pool[0][name]" placeholder="Nama pengajar manual" class="pkg-field" data-rpp-teacher-name>
                    <button type="button" class="btn-danger px-3 py-2 text-xs" data-remove-rpp-teacher>Hapus</button>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-3">
        <button type="button" class="btn-secondary px-4 py-2 text-sm" data-rpp-preview>Hitung Preview</button>
        <button type="button" class="btn-secondary hidden px-4 py-2 text-sm" data-rpp-copy>Salin Teks WA</button>
        <span class="hidden text-xs font-semibold text-teal-600 dark:text-teal-300" data-rpp-copy-status>Teks tersalin.</span>
        @if($rppMateri?->isRppPublished())
            <span class="pkg-status-badge pkg-status-success">Terpublikasi sampai {{ $rppMateri->rpp_end_date?->format('d M Y') }}</span>
        @elseif($rppMateri?->hasRpp())
            <span class="pkg-status-badge pkg-status-neutral">Draft RPP</span>
        @endif
    </div>

    <div class="mt-4 hidden rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900 dark:border-teal-900/60 dark:bg-teal-950/30 dark:text-teal-200" data-rpp-preview-result></div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-rpp-action-button]').forEach((button) => {
        button.addEventListener('click', function () {
            const form = button.closest('form');
            const actionField = form?.querySelector('[data-rpp-action-field]');

            if (actionField) {
                actionField.value = button.dataset.rppActionButton || 'draft';
            }
        });
    });

    document.querySelectorAll('[data-rpp-panel]').forEach((panel) => {
        const container = panel.querySelector('[data-rpp-extra-sessions]');
        const addButton = panel.querySelector('[data-add-rpp-session]');
        const catchUpContainer = panel.querySelector('[data-rpp-catch-up-ranges]');
        const addCatchUpButton = panel.querySelector('[data-add-rpp-catch-up-range]');
        const teacherContainer = panel.querySelector('[data-rpp-teachers]');
        const addTeacherButton = panel.querySelector('[data-add-rpp-teacher]');
        const previewButton = panel.querySelector('[data-rpp-preview]');
        const copyButton = panel.querySelector('[data-rpp-copy]');
        const copyStatus = panel.querySelector('[data-rpp-copy-status]');
        const resultBox = panel.querySelector('[data-rpp-preview-result]');
        let latestShareText = '';

        function reindexRows() {
            container.querySelectorAll('[data-rpp-session-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/rpp_extra_sessions\[\d+\]/, `rpp_extra_sessions[${index}]`);
                });
            });
        }

        function reindexCatchUpRows() {
            catchUpContainer.querySelectorAll('[data-rpp-catch-up-row]').forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/rpp_catch_up_ranges\[\d+\]/, `rpp_catch_up_ranges[${index}]`);
                });
            });
        }

        function reindexTeacherRows() {
            teacherContainer.querySelectorAll('[data-rpp-teacher-row]').forEach((row, index) => {
                row.querySelectorAll('input, select').forEach((input) => {
                    input.name = input.name.replace(/rpp_teacher_pool\[\d+\]/, `rpp_teacher_pool[${index}]`);
                });
            });
        }

        function bindRemoveButtons() {
            container.querySelectorAll('[data-remove-rpp-session]').forEach((button) => {
                button.onclick = function () {
                    const rows = container.querySelectorAll('[data-rpp-session-row]');
                    if (rows.length === 1) {
                        button.closest('[data-rpp-session-row]').querySelectorAll('input').forEach((input) => input.value = '');
                    } else {
                        button.closest('[data-rpp-session-row]').remove();
                    }
                    reindexRows();
                };
            });
        }

        function bindCatchUpRemoveButtons() {
            catchUpContainer.querySelectorAll('[data-remove-rpp-catch-up-range]').forEach((button) => {
                button.onclick = function () {
                    const rows = catchUpContainer.querySelectorAll('[data-rpp-catch-up-row]');
                    if (rows.length === 1) {
                        button.closest('[data-rpp-catch-up-row]').querySelectorAll('input').forEach((input) => input.value = '');
                    } else {
                        button.closest('[data-rpp-catch-up-row]').remove();
                    }
                    reindexCatchUpRows();
                };
            });
        }

        function bindTeacherRemoveButtons() {
            teacherContainer.querySelectorAll('[data-remove-rpp-teacher]').forEach((button) => {
                button.onclick = function () {
                    const rows = teacherContainer.querySelectorAll('[data-rpp-teacher-row]');
                    if (rows.length === 1) {
                        button.closest('[data-rpp-teacher-row]').querySelectorAll('input, select').forEach((input) => input.value = '');
                    } else {
                        button.closest('[data-rpp-teacher-row]').remove();
                    }
                    reindexTeacherRows();
                    bindTeacherSelects();
                };
            });
        }

        function bindTeacherSelects() {
            panel.querySelectorAll('[data-rpp-teacher-select]').forEach((select) => {
                syncTeacherName(select, false);
                select.onchange = function () {
                    syncTeacherName(select, true);
                };
            });
        }

        function syncTeacherName(select, fromChange) {
            const selected = select.options[select.selectedIndex];
            const row = select.closest('[data-rpp-teacher-row]');
            const nameInput = row?.querySelector('[data-rpp-teacher-name]');

            if (!nameInput) {
                return;
            }

            if (select.value) {
                nameInput.value = teacherNameFromOption(selected);
                nameInput.classList.add('hidden');
                return;
            }

            nameInput.classList.remove('hidden');

            if (fromChange) {
                nameInput.value = '';
            }
        }

        function appendTeacherRows(formData) {
            teacherContainer.querySelectorAll('[data-rpp-teacher-row]').forEach((row, index) => {
                const select = row.querySelector('[data-rpp-teacher-select]');
                const nameInput = row.querySelector('[data-rpp-teacher-name]');
                const selected = select?.options[select.selectedIndex];
                const userId = select?.value || '';
                const name = userId
                    ? teacherNameFromOption(selected)
                    : (nameInput?.value || '').trim();

                if (!userId && !name) {
                    return;
                }

                formData.append(`rpp_teacher_pool[${index}][user_id]`, userId);
                formData.append(`rpp_teacher_pool[${index}][name]`, name);
            });
        }

        function teacherNameFromOption(option) {
            const dataName = option?.dataset.name?.trim();

            if (dataName) {
                return dataName;
            }

            return (option?.textContent || '')
                .replace(/^\s*-\s*/, '')
                .split(' - ')[0]
                .trim();
        }

        async function copyTextToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        addButton?.addEventListener('click', function () {
            const firstRow = container.querySelector('[data-rpp-session-row]');
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => input.value = '');
            container.appendChild(clone);
            reindexRows();
            bindRemoveButtons();
        });

        addCatchUpButton?.addEventListener('click', function () {
            const firstRow = catchUpContainer.querySelector('[data-rpp-catch-up-row]');
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => input.value = '');
            catchUpContainer.appendChild(clone);
            reindexCatchUpRows();
            bindCatchUpRemoveButtons();
        });

        addTeacherButton?.addEventListener('click', function () {
            const firstRow = teacherContainer.querySelector('[data-rpp-teacher-row]');
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input, select').forEach((input) => input.value = '');
            teacherContainer.appendChild(clone);
            reindexTeacherRows();
            bindTeacherRemoveButtons();
            bindTeacherSelects();
        });

        previewButton?.addEventListener('click', async function () {
            const form = panel.closest('form');
            const formData = new FormData();
            formData.append('_token', form.querySelector('input[name="_token"]').value);
            formData.append('judul', form.querySelector('input[name="judul"]')?.value || '');

            panel.querySelectorAll('input[name^="rpp_"], select[name^="rpp_"]').forEach((input) => {
                if (input.name.startsWith('rpp_teacher_pool[')) return;
                if (input.type === 'checkbox' && !input.checked) return;
                formData.append(input.name, input.value);
            });
            appendTeacherRows(formData);

            resultBox.classList.remove('hidden');
            resultBox.textContent = 'Menghitung preview...';
            latestShareText = '';
            copyButton?.classList.add('hidden');
            copyStatus?.classList.add('hidden');

            try {
                const response = await fetch(panel.dataset.previewUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Preview gagal dihitung.');
                    resultBox.textContent = errors;
                    return;
                }

                const rows = data.sessions.map((session) => {
                    const catchUpRange = session.range_start_date && session.range_end_date
                        ? `Kejar target ${session.range_start_date} s/d ${session.range_end_date} - `
                        : 'Kejar target - ';
                    const label = session.type === 'catch_up' ? catchUpRange : '';
                    const teacher = session.teacher_name ? ` - Pengajar: ${session.teacher_name}` : '';
                    return `${session.number}. ${label}${session.weekday_label ? session.weekday_label + ' ' : ''}${session.date} - ${session.page_range}${teacher}`;
                }).join('<br>');
                latestShareText = data.share_text || '';
                if (latestShareText) {
                    copyButton?.classList.remove('hidden');
                }
                const timeSummary = data.summary.start_time
                    ? ` Waktu ${String(data.summary.start_time).slice(0, 5)}${data.summary.end_time ? ' - ' + String(data.summary.end_time).slice(0, 5) : ''}.`
                    : '';
                resultBox.innerHTML = `<strong>${data.summary.total_sessions} pertemuan</strong>, selesai pada <strong>${data.summary.end_date}</strong>.${timeSummary}<div class="mt-3">${rows}</div>`;
            } catch (error) {
                resultBox.textContent = 'Preview gagal dihitung.';
            }
        });

        copyButton?.addEventListener('click', async function () {
            if (!latestShareText) {
                return;
            }

            copyButton.disabled = true;
            copyStatus?.classList.add('hidden');

            try {
                await copyTextToClipboard(latestShareText);
                if (copyStatus) {
                    copyStatus.textContent = 'Teks tersalin.';
                    copyStatus.classList.remove('hidden');
                }
            } catch (error) {
                if (copyStatus) {
                    copyStatus.textContent = 'Teks gagal disalin.';
                    copyStatus.classList.remove('hidden');
                }
            } finally {
                copyButton.disabled = false;
            }
        });

        bindRemoveButtons();
        bindCatchUpRemoveButtons();
        bindTeacherRemoveButtons();
        bindTeacherSelects();
        reindexRows();
        reindexCatchUpRows();
        reindexTeacherRows();
    });
});
</script>
@endpush
@endonce
