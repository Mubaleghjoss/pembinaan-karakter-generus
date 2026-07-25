@php
    $calendarId = $calendarId ?? 'public-calendar-' . uniqid();
    $showInlineMateri = $showInlineMateri ?? false;
    $homeMateri = $homeMateri ?? collect();
    $homeMateriFolders = $homeMateriFolders ?? collect();
    $homeMateriCount = $homeMateriCount ?? $homeMateri->count();
    $homeMateriFolderCount = $homeMateriFolderCount ?? 0;
    $calendarPanelId = $calendarId . '-calendar-panel';
    $materiPanelId = $calendarId . '-materi-panel';
    $calendarInitialDate = $calendarInitialDate ?? now()->format('Y-m-01');
@endphp

<section
    class="{{ $calendarSectionClass ?? 'bg-slate-50 py-10 dark:bg-slate-950' }}"
    @if($showInlineMateri) x-data="{ activePublicPanel: 'calendar' }" @endif
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('public.partials.calendar-materi-tabs', [
            'activePublicTab' => 'calendar',
            'usePanelTabs' => $showInlineMateri,
            'calendarPanelId' => $calendarPanelId,
            'materiPanelId' => $materiPanelId,
        ])

        <div
            @if($showInlineMateri)
                id="{{ $calendarPanelId }}"
                x-show="activePublicPanel === 'calendar'"
                x-transition.opacity
                role="tabpanel"
            @endif
        >
            <div class="pkg-panel p-4 mb-6" data-reveal="up">
                <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
                        <span>Tugas PKG</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: #F97316"></span>
                        <span>Jadwal dari Admin</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: #14B8A6"></span>
                        <span>RPP Materi</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: #0F766E"></span>
                        <span>Jadwal Presensi</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: #047857"></span>
                        <span>KBM</span>
                    </div>
                </div>
            </div>

            <div class="pkg-panel p-4" data-reveal="up" data-public-calendar-shell>
                <div class="mb-4 rounded-xl border border-gray-200 bg-white/70 p-3 dark:border-slate-800 dark:bg-slate-900/70" data-public-calendar-toolbar>
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex min-w-0 items-center gap-2">
                            <button type="button" class="btn-secondary !h-10 !w-10 !p-0" data-calendar-prev aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button type="button" class="btn-secondary !h-10 !w-10 !p-0" data-calendar-next aria-label="Bulan berikutnya" title="Bulan berikutnya">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            <button type="button" class="btn-secondary text-sm !px-3 !py-2" data-calendar-today>Hari Ini</button>
                            <div class="min-w-0 pl-2">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Periode</p>
                                <p class="truncate text-base font-bold text-gray-900 dark:text-white" data-calendar-title>Kalender</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                            <div class="flex items-center gap-2">
                                <input type="month" class="pkg-field text-sm sm:w-44" data-calendar-jump aria-label="Pilih bulan kalender">
                                <button type="button" class="btn-primary text-sm !px-3 !py-2" data-calendar-go>Lihat</button>
                            </div>
                            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-slate-700 dark:bg-slate-950">
                                <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold transition" data-calendar-view="dayGridMonth">Bulan</button>
                                <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold transition" data-calendar-view="listWeek">Daftar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="{{ $calendarId }}" data-public-calendar data-events-url="{{ route('public.calendar.events') }}" data-initial-date="{{ $calendarInitialDate }}"></div>
            </div>
        </div>

        @if($showInlineMateri)
            <div
                id="{{ $materiPanelId }}"
                class="space-y-5"
                x-show="activePublicPanel === 'materi'"
                x-cloak
                x-transition.opacity
                role="tabpanel"
            >
                <div class="pkg-page-header mb-0">
                    <div>
                        <h3 class="pkg-page-heading text-2xl">Folder Materi</h3>
                        <p class="pkg-page-subheading">
                            {{ $homeMateriCount }} materi aktif dalam {{ $homeMateriFolderCount }} folder utama. Buka folder utama untuk melihat daftar materi.
                        </p>
                    </div>
                    <a href="{{ route('materi.index') }}" class="btn-secondary text-sm">Lihat Semua Materi</a>
                </div>

                @if($homeMateriFolders->isEmpty())
                    <div class="pkg-empty-state">
                        <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="pkg-empty-title">Belum ada materi</p>
                        <p class="pkg-empty-copy">Materi publik belum tersedia.</p>
                    </div>
                @else
                    @include('materi.partials.read-only-folder-tree', [
                        'folders' => $homeMateriFolders,
                        'detailRouteName' => 'public.materi.show',
                    ])
                @endif
            </div>
        @endif
    </div>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" data-public-calendar-modal>
        <div class="relative max-h-[82vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 dark:bg-slate-900">
            <button type="button" class="absolute right-4 top-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" data-public-calendar-close>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div data-public-calendar-content></div>
        </div>
    </div>
</section>

@once
@push('styles')
<style>
.fc {
    font-family: inherit;
}
.fc .fc-daygrid-day.fc-day-today {
    background-color: #ECFDF5;
}
.fc-event {
    border-radius: 4px;
    font-size: 0.75rem;
    padding: 2px 4px;
}
.dark .fc .fc-col-header-cell-cushion,
.dark .fc .fc-daygrid-day-number {
    color: #E5E7EB;
}
.dark .fc .fc-daygrid-day.fc-day-today {
    background-color: #143832;
}
.dark .fc th,
.dark .fc td,
.dark .fc .fc-scrollgrid {
    border-color: #334155;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const widgets = document.querySelectorAll('[data-public-calendar]');

    if (!widgets.length || typeof window.loadFullCalendar !== 'function') {
        return;
    }

    let calendarModules;
    try {
        calendarModules = await window.loadFullCalendar();
    } catch (error) {
        widgets.forEach((calendarEl) => {
            calendarEl.textContent = 'Kalender belum bisa dimuat.';
        });
        return;
    }

    const { Calendar, dayGridPlugin, listPlugin, localeId } = calendarModules;

    widgets.forEach((calendarEl) => {
        const section = calendarEl.closest('section');
        const shell = calendarEl.closest('[data-public-calendar-shell]');
        const titleEl = shell?.querySelector('[data-calendar-title]');
        const jumpInput = shell?.querySelector('[data-calendar-jump]');
        const viewButtons = shell ? Array.from(shell.querySelectorAll('[data-calendar-view]')) : [];
        const modal = section.querySelector('[data-public-calendar-modal]');
        const content = section.querySelector('[data-public-calendar-content]');
        const closeButton = section.querySelector('[data-public-calendar-close]');
        const monthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });
        const activeViewClass = ['bg-emerald-600', 'text-white', 'shadow-sm'];
        const idleViewClass = ['text-gray-600', 'hover:bg-white', 'dark:text-gray-300', 'dark:hover:bg-slate-900'];
        let calendar;

        const syncToolbar = () => {
            if (!calendar) return;

            const date = calendar.getDate();
            const monthValue = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

            if (titleEl) {
                titleEl.textContent = monthFormatter.format(date);
            }

            if (jumpInput) {
                jumpInput.value = monthValue;
            }

            viewButtons.forEach((button) => {
                const isActive = button.dataset.calendarView === calendar.view.type;
                activeViewClass.forEach((className) => button.classList.toggle(className, isActive));
                idleViewClass.forEach((className) => button.classList.toggle(className, !isActive));
            });
        };

        const goToSelectedMonth = () => {
            if (!jumpInput?.value || !calendar) return;

            calendar.gotoDate(`${jumpInput.value}-01`);
        };

        calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, listPlugin],
            initialView: 'dayGridMonth',
            initialDate: calendarEl.dataset.initialDate || undefined,
            locale: localeId,
            headerToolbar: false,
            buttonText: {
                today: 'Hari Ini',
                month: 'Bulan',
                list: 'Daftar'
            },
            events: function(info, successCallback, failureCallback) {
                fetch(`${calendarEl.dataset.eventsUrl}?start=${info.startStr}&end=${info.endStr}`)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => failureCallback(error));
            },
            eventClick: function(info) {
                content.innerHTML = publicCalendarEventHtml(info.event);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            },
            eventDidMount: function(info) {
                info.el.style.cursor = 'pointer';
            },
            datesSet: syncToolbar,
            height: 'auto',
            dayMaxEvents: 3,
            moreLinkText: 'lainnya'
        });

        calendar.render();
        syncToolbar();

        shell?.querySelector('[data-calendar-prev]')?.addEventListener('click', () => calendar.prev());
        shell?.querySelector('[data-calendar-next]')?.addEventListener('click', () => calendar.next());
        shell?.querySelector('[data-calendar-today]')?.addEventListener('click', () => calendar.today());
        shell?.querySelector('[data-calendar-go]')?.addEventListener('click', goToSelectedMonth);
        jumpInput?.addEventListener('change', goToSelectedMonth);
        viewButtons.forEach((button) => {
            button.addEventListener('click', () => {
                calendar.changeView(button.dataset.calendarView);
                syncToolbar();
            });
        });

        closeButton?.addEventListener('click', function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });

        modal?.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });
});

function publicCalendarText(value, fallback = '-') {
    return value || fallback;
}

function publicCalendarEscape(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function publicCalendarEventHtml(event) {
    const props = event.extendedProps || {};
    const type = props.type || event.extendedProps.type;
    const dateLabel = event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const timeLabel = props.start_time ? `${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}` : null;

    if (type === 'materi_rpp') {
        return `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-lg font-bold text-teal-700">RPP</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${publicCalendarText(props.materi_title || props.title)}</h2>
                <p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}${timeLabel ? ' | ' + timeLabel : ''}</p>
                ${props.teacher_name ? `<span class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Pengajar: ${props.teacher_name}</span>` : ''}
                ${props.session_type === 'catch_up' ? '<span class="mt-3 ml-1 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Kejar Target</span>' : ''}
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-teal-50 p-3 dark:bg-teal-900/30">
                        <p class="text-gray-500 dark:text-gray-400">Pertemuan</p>
                        <p class="font-semibold text-teal-700 dark:text-teal-300">${publicCalendarText(props.session_number)}</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/30">
                        <p class="text-gray-500 dark:text-gray-400">Target</p>
                        <p class="font-semibold text-blue-700 dark:text-blue-300">${publicCalendarText(props.page_range)}</p>
                    </div>
                </div>
                ${props.url ? `<a href="${props.url}" class="btn-primary mt-5 inline-flex justify-center">Buka Materi</a>` : ''}
            </div>
        `;
    }

    if (type === 'materi') {
        const title = publicCalendarEscape(props.title || event.title || 'Materi');
        const description = props.description ? publicCalendarEscape(props.description) : '';
        const folder = props.folder ? publicCalendarEscape(props.folder) : '-';
        const monthLabel = props.month_label ? publicCalendarEscape(props.month_label) : '-';
        const url = publicCalendarEscape(props.url || '#');

        return `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700">MTR</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${title}</h2>
                <p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}</p>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/30">
                        <p class="text-gray-500 dark:text-gray-400">Folder</p>
                        <p class="font-semibold text-blue-700 dark:text-blue-300">${folder}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
                        <p class="text-gray-500 dark:text-gray-400">Periode</p>
                        <p class="font-semibold text-slate-700 dark:text-slate-200">${monthLabel}</p>
                    </div>
                </div>
                ${description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-slate-800 dark:text-gray-200">${description}</p>` : ''}
                <a href="${url}" class="btn-primary mt-5 inline-flex justify-center">Buka Materi</a>
            </div>
        `;
    }

    if (type === 'pkg_task') {
        return `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-lg font-bold text-amber-700">PKG</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${publicCalendarText(props.judul)}</h2>
                <p class="mt-1 text-gray-600 dark:text-gray-300">Periode: ${publicCalendarText(props.period || props.deadline)}</p>
                <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">Kategori: ${publicCalendarText(props.kategori)}</p>
            </div>
        `;
    }

    if (type === 'schedule-reminder') {
        const targetLabel = props.target_audience === 'all' ? 'Semua' : (props.target_audience === 'siswa' ? 'Siswa' : 'Pamong');
        return `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-lg font-bold text-orange-700">JDL</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${publicCalendarText(props.title)}</h2>
                <p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}${timeLabel ? ' | ' + timeLabel : ''}</p>
                <span class="mt-3 inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">Target: ${targetLabel}</span>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-slate-800 dark:text-gray-200">${props.description}</p>` : ''}
                ${props.location ? `<p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Lokasi: ${props.location}</p>` : ''}
            </div>
        `;
    }

    if (type === 'attendance-schedule') {
        return `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-lg font-bold text-teal-700">ABS</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${publicCalendarText(props.title || event.title)}</h2>
                <p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}</p>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-slate-800 dark:text-gray-200">${props.description}</p>` : ''}
                <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-lg bg-green-50 p-3 dark:bg-green-900/30"><p class="text-gray-500 dark:text-gray-400">Mulai</p><p class="font-semibold text-green-700 dark:text-green-300">${publicCalendarText(props.open_time)}</p></div>
                    <div class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/30"><p class="text-gray-500 dark:text-gray-400">Tepat Waktu</p><p class="font-semibold text-yellow-700 dark:text-yellow-300">${publicCalendarText(props.late_threshold)}</p></div>
                    <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/30"><p class="text-gray-500 dark:text-gray-400">Tutup</p><p class="font-semibold text-red-700 dark:text-red-300">${publicCalendarText(props.close_time)}</p></div>
                </div>
                <span class="mt-4 inline-flex rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800">Target: ${publicCalendarText(props.target_label)}</span>
                ${props.url ? `<a href="${props.url}" class="btn-primary mt-5 inline-flex justify-center">${props.action_label || 'Buka Scan Presensi'}</a>` : ''}
            </div>
        `;
    }

    if (type === 'teacher_schedule') {
        const sessions = Array.isArray(props.sessions) ? props.sessions : [];
        const sessionCards = sessions.map((session) => {
            const rombel = publicCalendarEscape(session.rombel || '-');
            const mainTeacher = publicCalendarEscape(session.main_teacher || 'Belum diisi');
            const backupTeacher = publicCalendarEscape(session.backup_teacher || 'Belum diisi');
            const location = publicCalendarEscape(session.location || 'Belum ditentukan');
            const sessionTime = session.start_time
                ? `${publicCalendarEscape(session.start_time)}${session.end_time ? ' - ' + publicCalendarEscape(session.end_time) : ''} WIB`
                : '-';

            return `
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-left dark:border-emerald-900 dark:bg-emerald-950/30">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-bold text-emerald-800 dark:text-emerald-200">${rombel}</h3>
                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">${sessionTime}</span>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Pengajar utama</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">${mainTeacher}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Pengajar cadangan</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">${backupTeacher}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Lokasi</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">${location}</dd>
                        </div>
                    </dl>
                </div>
            `;
        }).join('');

        return `
            <div>
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-lg font-bold text-emerald-700">KBM</div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Kegiatan Belajar Mengajar</h2>
                    <p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}${timeLabel ? ' | ' + timeLabel + ' WIB' : ''}</p>
                </div>
                <div class="mt-5 grid gap-3">
                    ${sessionCards || '<p class="rounded-xl bg-gray-50 p-4 text-center text-sm text-gray-600 dark:bg-slate-800 dark:text-gray-300">Rincian rombel belum tersedia.</p>'}
                </div>
            </div>
        `;
    }

    return `<div class="text-center"><h2 class="text-xl font-bold text-gray-900 dark:text-white">${publicCalendarText(event.title)}</h2><p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}</p></div>`;
}
</script>
@endpush
@endonce
