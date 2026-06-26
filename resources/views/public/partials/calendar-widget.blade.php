@php
    $calendarTitle = $calendarTitle ?? 'Kalender Aktivitas';
    $calendarSubtitle = $calendarSubtitle ?? 'Agenda PKG, RPP materi, presensi, dan tenggat tugas.';
    $calendarId = $calendarId ?? 'public-calendar-' . uniqid();
@endphp

<section class="{{ $calendarSectionClass ?? 'bg-slate-50 py-10 dark:bg-slate-950' }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-public-hero-card p-6 sm:p-8 lg:p-10 mb-6" data-reveal="zoom">
            <div class="relative z-10 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(260px,0.72fr)] lg:items-center">
                <div class="pkg-page-header !mb-0">
                    <div>
                        <span class="pkg-glass-badge text-sm font-semibold">Agenda terstruktur</span>
                        <h2 class="pkg-page-heading mt-5">{{ $calendarTitle }}</h2>
                        <p class="pkg-page-subheading">{{ $calendarSubtitle }}</p>
                    </div>
                    @if($showCalendarLink ?? false)
                        <a href="{{ route('public.calendar.index') }}" class="btn-secondary text-sm">Buka Kalender</a>
                    @endif
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="pkg-inline-stat">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Kategori</p>
                            <p class="text-lg font-black text-slate-950 dark:text-white">4 Jenis</p>
                        </div>
                    </div>
                    <div class="pkg-inline-stat">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tampilan</p>
                            <p class="text-lg font-black text-slate-950 dark:text-white">Bulan & Daftar</p>
                        </div>
                    </div>
                    <div class="pkg-inline-stat">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Akses</p>
                            <p class="text-lg font-black text-slate-950 dark:text-white">Publik</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('public.partials.calendar-materi-tabs', ['activePublicTab' => 'calendar'])

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
            </div>
        </div>

        <div class="pkg-panel p-4" data-reveal="up">
            <div id="{{ $calendarId }}" data-public-calendar data-events-url="{{ route('public.calendar.events') }}"></div>
        </div>
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
.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 700;
}
.fc .fc-button {
    background-color: #0F766E;
    border-color: #0F766E;
}
.fc .fc-button:hover {
    background-color: #115E59;
    border-color: #115E59;
}
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: #134E4A;
    border-color: #134E4A;
}
.fc .fc-prev-button .fc-icon,
.fc .fc-next-button .fc-icon {
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1;
}
.fc .fc-icon-chevron-left::before {
    content: "<" !important;
}
.fc .fc-icon-chevron-right::before {
    content: ">" !important;
}
.fc .fc-daygrid-day.fc-day-today {
    background-color: #ECFDF5;
}
.fc-event {
    border-radius: 4px;
    font-size: 0.75rem;
    padding: 2px 4px;
}
.dark .fc .fc-toolbar-title,
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
        const modal = section.querySelector('[data-public-calendar-modal]');
        const content = section.querySelector('[data-public-calendar-content]');
        const closeButton = section.querySelector('[data-public-calendar-close]');

        const calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, listPlugin],
            initialView: 'dayGridMonth',
            locale: localeId,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
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
            height: 'auto',
            dayMaxEvents: 3,
            moreLinkText: 'lainnya'
        });

        calendar.render();

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

    return `<div class="text-center"><h2 class="text-xl font-bold text-gray-900 dark:text-white">${publicCalendarText(event.title)}</h2><p class="mt-1 text-gray-600 dark:text-gray-300">${dateLabel}</p></div>`;
}
</script>
@endpush
@endonce
