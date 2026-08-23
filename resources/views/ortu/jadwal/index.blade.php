@extends('layouts.ortu')

@section('title', 'Jadwal')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Kalender Aktivitas</h1>
            <p class="pkg-page-subheading">Jadwal presensi dan kegiatan untuk {{ $siswa->nama }}.</p>
        </div>
    </div>

    <div class="pkg-panel p-4 mb-6">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Keterangan warna</p>
        <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-green-500"></span><span>Hadir / Tugas selesai</span></div>
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-yellow-500"></span><span>Izin / Tugas belum lengkap</span></div>
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-red-500"></span><span>Sakit</span></div>
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-gray-500"></span><span>Tidak hadir</span></div>
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#F97316"></span><span>Jadwal Admin</span></div>
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#14B8A6"></span><span>RPP Materi</span></div>
            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:#0F766E"></span><span>Jadwal Presensi Aktif</span></div>
        </div>
    </div>

    <div class="pkg-panel p-3 sm:p-4">
        <div id="calendar" class="pkg-ortu-calendar"></div>
    </div>

    <div id="eventModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <div id="eventModalContent"></div>
            <button type="button" onclick="closeEventModal()" class="btn-secondary mt-5 w-full justify-center">Tutup</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const calendarEl = document.getElementById('calendar');
    const { Calendar, dayGridPlugin, listPlugin, localeId } = await window.loadFullCalendar();
    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, listPlugin],
        initialView: 'dayGridMonth',
        locale: localeId,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
        events: function(info, successCallback, failureCallback) {
            fetch('{{ route("ortu.jadwal.events") }}?start=' + info.startStr + '&end=' + info.endStr)
                .then(r => r.json())
                .then(data => successCallback(data))
                .catch(err => failureCallback(err));
        },
        eventClick: function(info) {
            showEventDetail(info.event);
        },
        height: 'auto',
        dayMaxEvents: 3,
        moreLinkText: 'lainnya'
    });
    calendar.render();
});

function showEventDetail(event) {
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    const props = event.extendedProps;

    if (props.type === 'attendance-schedule') {
        content.innerHTML = `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 text-lg font-bold text-teal-700">ABS</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${event.title}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200">${props.description}</p>` : ''}
                <div class="mt-4 grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-lg bg-green-50 p-3 text-green-700">Mulai<br><strong>${props.open_time}</strong></div>
                    <div class="rounded-lg bg-yellow-50 p-3 text-yellow-700">Tepat<br><strong>${props.late_threshold}</strong></div>
                    <div class="rounded-lg bg-red-50 p-3 text-red-700">Tutup<br><strong>${props.close_time}</strong></div>
                </div>
                <a href="${props.url}" class="btn-primary mt-5 inline-flex justify-center">Buka Scan Presensi</a>
            </div>
        `;
    } else if (props.type === 'pkg_task') {
        content.innerHTML = `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full ${props.submitted ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'} text-lg font-bold">PKG</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${props.judul}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Periode: ${props.period || props.deadline}</p>
                <div class="mt-4 rounded-lg ${props.submitted ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700'} p-3 text-sm font-semibold">
                    ${props.submitted ? 'Sudah dikumpulkan' : 'Belum dikumpulkan'}
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Kategori: ${props.kategori}</p>
                <a href="${props.url}" class="btn-primary mt-5 inline-flex justify-center">Lihat Tugas PKG</a>
            </div>
        `;
    } else if (props.type === 'materi_rpp') {
        content.innerHTML = `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 text-lg font-bold text-teal-700">RPP</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${props.title || event.title}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                ${props.start_time ? `<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}</p>` : ''}
                ${props.session_type === 'catch_up' ? '<span class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Kejar Target</span>' : ''}
                ${props.teacher_name ? `<span class="mt-3 ml-1 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Pengajar: ${props.teacher_name}</span>` : ''}
                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-lg bg-teal-50 p-3 text-teal-700">Pertemuan<br><strong>${props.session_number || '-'}</strong></div>
                    <div class="rounded-lg bg-blue-50 p-3 text-blue-700">Target<br><strong>${props.page_range || '-'}</strong></div>
                </div>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200">${props.description}</p>` : ''}
                ${props.url ? `<a href="${props.url}" target="_blank" class="btn-primary mt-5 inline-flex justify-center">Buka Materi</a>` : ''}
            </div>
        `;
    } else if (props.type === 'schedule-reminder') {
        const targetLabel = props.target_audience === 'all' ? 'Semua' : (props.target_audience === 'siswa' ? 'Siswa' : 'Pamong');
        content.innerHTML = `
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-100 text-lg font-bold text-orange-700">JDL</div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${props.title || event.title}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                <div class="mt-3">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Target: ${targetLabel}</span>
                </div>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200">${props.description}</p>` : ''}
                <div class="mt-4 grid ${props.start_time && props.location ? 'grid-cols-2' : 'grid-cols-1'} gap-2 text-sm">
                    ${props.start_time ? `<div class="rounded-lg bg-blue-50 p-3 text-blue-700">Jam<br><strong>${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}</strong></div>` : ''}
                    ${props.location ? `<div class="rounded-lg bg-green-50 p-3 text-green-700">Lokasi<br><strong>${props.location}</strong></div>` : ''}
                </div>
            </div>
        `;
    } else {
        content.innerHTML = `
            <div class="text-center">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">${event.title}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200">${props.description}</p>` : ''}
            </div>
        `;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<style>
/* Bungkus tampilan FullCalendar agar tidak muncul kotak polos tanpa gaya. */
.pkg-ortu-calendar {
    --pkg-cal-border: #e5e7eb;
    font-family: inherit;
}
.pkg-ortu-calendar .fc {
    font-family: inherit;
}
.pkg-ortu-calendar .fc-theme-standard .fc-scrollgrid,
.pkg-ortu-calendar .fc-theme-standard td,
.pkg-ortu-calendar .fc-theme-standard th {
    border-color: var(--pkg-cal-border);
}
.pkg-ortu-calendar .fc-theme-standard .fc-scrollgrid {
    border-radius: 0.75rem;
    overflow: hidden;
}
.pkg-ortu-calendar .fc .fc-toolbar {
    flex-wrap: wrap;
    gap: 0.5rem;
}
.pkg-ortu-calendar .fc .fc-toolbar-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #111827;
}
.pkg-ortu-calendar .fc .fc-button {
    background-color: #0d9488;
    border-color: #0d9488;
    box-shadow: none;
    border-radius: 0.6rem;
    padding: 0.35rem 0.7rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}
.pkg-ortu-calendar .fc .fc-button:hover,
.pkg-ortu-calendar .fc .fc-button:focus {
    background-color: #0f766e;
    border-color: #0f766e;
    box-shadow: none;
}
.pkg-ortu-calendar .fc .fc-button-primary:not(:disabled).fc-button-active,
.pkg-ortu-calendar .fc .fc-button-primary:not(:disabled):active {
    background-color: #115e59;
    border-color: #115e59;
}
.pkg-ortu-calendar .fc .fc-button:disabled {
    background-color: #9ca3af;
    border-color: #9ca3af;
    opacity: 1;
}
.pkg-ortu-calendar .fc .fc-col-header-cell {
    background-color: #f9fafb;
    padding: 0.4rem 0;
}
.pkg-ortu-calendar .fc .fc-col-header-cell-cushion {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    text-decoration: none;
}
.pkg-ortu-calendar .fc .fc-daygrid-day-number {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    padding: 0.25rem 0.4rem;
}
.pkg-ortu-calendar .fc .fc-daygrid-day.fc-day-today {
    background-color: #ccfbf1;
}
.pkg-ortu-calendar .fc .fc-day-other .fc-daygrid-day-top {
    opacity: 0.4;
}
.pkg-ortu-calendar .fc-event,
.pkg-ortu-calendar .fc .fc-daygrid-event {
    border: none;
    border-radius: 0.4rem;
    padding: 1px 5px;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
}
.pkg-ortu-calendar .fc .fc-daygrid-event-dot {
    display: none;
}
.pkg-ortu-calendar .fc .fc-daygrid-more-link {
    font-size: 0.7rem;
    font-weight: 700;
    color: #0f766e;
}
.pkg-ortu-calendar .fc .fc-list {
    border-radius: 0.75rem;
    overflow: hidden;
    border-color: var(--pkg-cal-border);
}
.pkg-ortu-calendar .fc .fc-list-day-cushion {
    background-color: #f3f4f6;
}
.pkg-ortu-calendar .fc .fc-list-event:hover td {
    background-color: #f0fdfa;
}

/* Dark mode */
.dark .pkg-ortu-calendar {
    --pkg-cal-border: #374151;
}
.dark .pkg-ortu-calendar .fc .fc-toolbar-title {
    color: #f9fafb;
}
.dark .pkg-ortu-calendar .fc .fc-col-header-cell {
    background-color: #1f2937;
}
.dark .pkg-ortu-calendar .fc .fc-col-header-cell-cushion {
    color: #9ca3af;
}
.dark .pkg-ortu-calendar .fc .fc-daygrid-day-number {
    color: #d1d5db;
}
.dark .pkg-ortu-calendar .fc .fc-daygrid-day.fc-day-today {
    background-color: rgba(13, 148, 136, 0.25);
}
.dark .pkg-ortu-calendar .fc .fc-list-day-cushion {
    background-color: #1f2937;
    color: #e5e7eb;
}
.dark .pkg-ortu-calendar .fc .fc-list-event:hover td {
    background-color: rgba(13, 148, 136, 0.2);
}
.dark .pkg-ortu-calendar .fc .fc-list-event-title a,
.dark .pkg-ortu-calendar .fc .fc-list-event-time {
    color: #e5e7eb;
}

/* Mobile: rapatkan agar tidak melebar/terpotong */
@media (max-width: 640px) {
    .pkg-ortu-calendar .fc .fc-toolbar {
        justify-content: center;
    }
    .pkg-ortu-calendar .fc .fc-toolbar-title {
        font-size: 0.95rem;
    }
    .pkg-ortu-calendar .fc .fc-button {
        padding: 0.3rem 0.55rem;
        font-size: 0.72rem;
    }
    .pkg-ortu-calendar .fc-event,
    .pkg-ortu-calendar .fc .fc-daygrid-event {
        font-size: 0.62rem;
        padding: 1px 3px;
    }
    .pkg-ortu-calendar .fc .fc-daygrid-day-number {
        font-size: 0.72rem;
        padding: 0.15rem 0.25rem;
    }
}
</style>
@endsection
