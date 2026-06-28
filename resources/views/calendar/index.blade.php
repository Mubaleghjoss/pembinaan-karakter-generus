@extends('layouts.app')

@section('title', 'Kalender')

@section('content')
@php
    $canViewSchedule = (auth()->user()->isAdmin() || auth()->user()->hasPamongMenuAccess('jadwal'))
        && auth()->user()->hasPamongCrudPermission('jadwal', 'view');
@endphp
<div class="py-6">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="pkg-page-header">
            <div>
                <h1 class="pkg-page-heading">Kalender Aktivitas</h1>
                <p class="pkg-page-subheading">Ringkasan presensi, tenggat tugas PKG, jadwal kegiatan, dan aktivitas karakter.</p>
            </div>
            <div class="pkg-page-actions">
                <a href="{{ route('materi-rpp-journals.index') }}" class="btn-secondary text-sm">
                    Jurnal RPP
                </a>
                <button type="button" class="btn-secondary text-sm" data-calendar-copy>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8M8 11h8m-8 4h5m-7 4h10a2 2 0 002-2V7.828a2 2 0 00-.586-1.414l-2.828-2.828A2 2 0 0013.172 3H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Salin Teks WA
                </button>
                <button type="button" class="btn-secondary text-sm" data-calendar-export>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 4h10a2 2 0 012 2v4H5V6a2 2 0 012-2z"/>
                    </svg>
                    Export Excel
                </button>
            @if($canViewSchedule)
                <a href="{{ route('attendance-schedule.index') }}" class="btn-primary text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ auth()->user()->hasPamongCrudPermission('jadwal', 'create') || auth()->user()->hasPamongCrudPermission('jadwal', 'edit') ? 'Kelola Jadwal' : 'Lihat Jadwal' }}
                </a>
            @endif
            </div>
        </div>

        <!-- Legend -->
        <div class="pkg-panel p-4 mb-6">
            <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <span>Ringkasan Presensi</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    <span>Tugas PKG Selesai</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                    <span>Tugas PKG Belum Lengkap</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                    <span>Aktivitas Karakter</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #3B82F6"></span>
                    <span>Jadwal Pengingat</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #14B8A6"></span>
                    <span>RPP Materi</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: #0F766E"></span>
                    <span>Jadwal Presensi Aktif</span>
                </div>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="pkg-panel p-4" data-admin-calendar-shell>
            <div class="mb-4 rounded-xl border border-gray-200 bg-white/70 p-3 dark:border-gray-700 dark:bg-gray-900/70">
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
                        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-950">
                            <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold transition" data-calendar-view="dayGridMonth">Bulan</button>
                            <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold transition" data-calendar-view="listWeek">Daftar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="calendar"></div>
        </div>

        <!-- Event Detail Modal -->
        <div id="eventModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 relative max-h-[80vh] overflow-y-auto">
                <button onclick="closeEventModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div id="eventModalContent"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.fc {
    font-family: inherit;
}
.fc .fc-daygrid-day.fc-day-today {
    background-color: #EEF2FF;
}
.fc-event {
    font-size: 0.75rem;
    padding: 2px 4px;
    border-radius: 4px;
}
/* Dark mode styles */
.dark .fc .fc-col-header-cell-cushion,
.dark .fc .fc-daygrid-day-number {
    color: #d1d5db;
}
.dark .fc .fc-daygrid-day.fc-day-today {
    background-color: #374151;
}
.dark .fc th,
.dark .fc td {
    border-color: #374151;
}
.dark .fc .fc-scrollgrid {
    border-color: #374151;
}
</style>
@endpush

@push('scripts')
<script>
let calendarInstance = null;

document.addEventListener('DOMContentLoaded', async function() {
    const calendarEl = document.getElementById('calendar');

    if (!calendarEl || typeof window.loadFullCalendar !== 'function') {
        return;
    }

    let calendarModules;
    try {
        calendarModules = await window.loadFullCalendar();
    } catch (error) {
        calendarEl.textContent = 'Kalender belum bisa dimuat.';
        return;
    }

    const { Calendar, dayGridPlugin, listPlugin, localeId } = calendarModules;
    const shell = calendarEl.closest('[data-admin-calendar-shell]');
    const titleEl = shell?.querySelector('[data-calendar-title]');
    const jumpInput = shell?.querySelector('[data-calendar-jump]');
    const viewButtons = shell ? Array.from(shell.querySelectorAll('[data-calendar-view]')) : [];
    const monthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });
    const activeViewClass = ['bg-blue-600', 'text-white', 'shadow-sm'];
    const idleViewClass = ['text-gray-600', 'hover:bg-white', 'dark:text-gray-300', 'dark:hover:bg-gray-900'];
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
        initialDate: @json(sprintf('%04d-%02d-01', (int) $year, (int) $month)),
        locale: localeId,
        headerToolbar: false,
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            list: 'Daftar'
        },
        events: function(info, successCallback, failureCallback) {
            fetch(`{{ route('calendar.events') }}?start=${info.startStr}&end=${info.endStr}`)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
        eventClick: function(info) {
            showEventDetail(info.event);
        },
        dateClick: function(info) {
            loadDateStats(info.dateStr);
        },
        eventDidMount: function(info) {
            info.el.style.cursor = 'pointer';
        },
        datesSet: syncToolbar,
        height: 'auto',
        dayMaxEvents: 3,
        moreLinkText: 'lainnya'
    });

    calendarInstance = calendar;
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

    document.querySelector('[data-calendar-copy]')?.addEventListener('click', copyCalendarText);
    document.querySelector('[data-calendar-export]')?.addEventListener('click', exportCalendarExcel);
});

function notifyCalendar(message, type = 'info') {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    alert(message);
}

function calendarEscape(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function currentCalendarMonthParams() {
    const activeDate = calendarInstance ? calendarInstance.getDate() : new Date();
    return new URLSearchParams({
        month: String(activeDate.getMonth() + 1),
        year: String(activeDate.getFullYear())
    });
}

async function writeCalendarText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', 'readonly');
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}

async function copyCalendarText() {
    const button = document.querySelector('[data-calendar-copy]');
    const params = currentCalendarMonthParams();

    if (button) {
        button.disabled = true;
    }

    try {
        const response = await fetch(`{{ route('calendar.share-text') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        if (!response.ok || !data.success || !data.text) {
            throw new Error(data.message || 'Teks kalender gagal dibuat.');
        }

        await writeCalendarText(data.text);
        notifyCalendar('Teks kalender bulan ini berhasil disalin.', 'success');
    } catch (error) {
        notifyCalendar(error.message || 'Teks kalender gagal disalin.', 'error');
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
}

function exportCalendarExcel() {
    const params = currentCalendarMonthParams();
    window.location.href = `{{ route('calendar.export') }}?${params.toString()}`;
}

function showEventDetail(event) {
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    const props = event.extendedProps;
    
    let html = '';
    
    if (props.type === 'presensi-summary') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4 bg-blue-100">
                    STAT
                </div>
                <h2 class="text-xl font-bold text-gray-800">Ringkasan Presensi</h2>
                <p class="text-gray-600 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">${props.hadir}</p>
                        <p class="text-sm text-green-700">Hadir</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <p class="text-2xl font-bold text-yellow-600">${props.terlambat}</p>
                        <p class="text-sm text-yellow-700">Terlambat</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <p class="text-2xl font-bold text-red-600">${props.alpha}</p>
                        <p class="text-sm text-red-700">Alpha</p>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-gray-600">Total: <span class="font-semibold">${props.total}</span> siswa</p>
                </div>
            </div>
        `;
    } else if (props.type === 'pkg_task') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4"
                     style="background-color: ${event.backgroundColor}20">
                    PKG
                </div>
                <h2 class="text-xl font-bold text-gray-800">${props.judul}</h2>
                <p class="text-gray-600 mt-1">Periode: ${props.period || props.deadline}</p>
                
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-lg font-semibold">${props.submissions} / ${props.total}</p>
                    <p class="text-gray-600">siswa sudah mengerjakan</p>
                </div>
                <p class="mt-3 text-sm text-gray-500">Kategori: ${props.kategori}</p>
                
                <a href="${props.url}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Lihat Tugas PKG
                </a>
            </div>
        `;
    } else if (props.type === 'karakter-summary') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4 bg-purple-100">
                    KAR
                </div>
                <h2 class="text-xl font-bold text-gray-800">Aktivitas Karakter</h2>
                <p class="text-gray-600 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600">${props.siswa_count}</p>
                        <p class="text-sm text-purple-700">Siswa</p>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg">
                        <p class="text-2xl font-bold text-indigo-600">${props.total_checks}</p>
                        <p class="text-sm text-indigo-700">Total Ceklis</p>
                    </div>
                </div>
            </div>
        `;
    } else if (props.type === 'materi_rpp') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-lg font-bold mb-4 bg-teal-100 text-teal-700">
                    RPP
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">${props.title}</h2>
                <p class="text-gray-600 dark:text-gray-300 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                ${props.start_time ? `<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}</p>` : ''}
                ${props.session_type === 'catch_up' ? '<span class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Kejar Target</span>' : ''}
                ${props.teacher_name ? `<span class="mt-3 ml-1 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Pengajar: ${props.teacher_name}</span>` : ''}
                ${props.journal_assignee_label ? `<p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Petugas jurnal: <strong>${props.journal_assignee_label}</strong></p>` : ''}

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="p-3 bg-teal-50 rounded-lg">
                        <p class="text-gray-500">Pertemuan</p>
                        <p class="font-semibold text-teal-700">${props.session_number || '-'}</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <p class="text-gray-500">Target</p>
                        <p class="font-semibold text-blue-700">${props.page_range || '-'}</p>
                    </div>
                </div>

                ${props.description ? `
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-200">${props.description}</p>
                </div>
                ` : ''}

                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    ${props.url ? `<a href="${props.url}" target="_blank" class="inline-block px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Buka Materi</a>` : ''}
                    ${props.journal_url ? `<a href="${props.journal_url}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">${props.journal_button_label || 'Isi Jurnal'}</a>` : ''}
                </div>
                ${props.journal_status_label ? `<p class="mt-3 text-xs text-emerald-700 dark:text-emerald-300">Status jurnal: ${props.journal_status_label}</p>` : ''}
            </div>
        `;
    } else if (props.type === 'materi') {
        const title = calendarEscape(props.title || event.title || 'Materi');
        const description = props.description ? calendarEscape(props.description) : '';
        const folder = props.folder ? calendarEscape(props.folder) : '-';
        const monthLabel = props.month_label ? calendarEscape(props.month_label) : '-';
        const adminUrl = calendarEscape(props.admin_url || props.url || '#');
        const publicUrl = calendarEscape(props.url || '#');

        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-lg font-bold mb-4 bg-blue-100 text-blue-700">
                    MTR
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">${title}</h2>
                <p class="text-gray-600 dark:text-gray-300 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="p-3 bg-blue-50 rounded-lg dark:bg-blue-900/30">
                        <p class="text-gray-500 dark:text-gray-400">Folder</p>
                        <p class="font-semibold text-blue-700 dark:text-blue-300">${folder}</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg dark:bg-slate-800">
                        <p class="text-gray-500 dark:text-gray-400">Periode</p>
                        <p class="font-semibold text-slate-700 dark:text-slate-200">${monthLabel}</p>
                    </div>
                </div>

                ${description ? `
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-200">${description}</p>
                </div>
                ` : ''}

                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <a href="${adminUrl}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Lihat Materi</a>
                    <a href="${publicUrl}" target="_blank" rel="noopener" class="inline-block px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">Halaman Publik</a>
                </div>
            </div>
        `;
    } else if (props.type === 'schedule-reminder') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4" style="background-color: ${event.backgroundColor}20">
                    JDL
                </div>
                <h2 class="text-xl font-bold text-gray-800">${props.title}</h2>
                <p class="text-gray-600 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                
                ${props.description ? `
                <div class="mt-4 p-4 bg-gray-50 rounded-lg text-left">
                    <p class="text-sm text-gray-700">${props.description}</p>
                </div>
                ` : ''}
                
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    ${props.start_time ? `
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <p class="text-gray-500">Waktu</p>
                        <p class="font-semibold text-blue-700">${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}</p>
                    </div>
                    ` : ''}
                    ${props.location ? `
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-gray-500">Lokasi</p>
                        <p class="font-semibold text-green-700">${props.location}</p>
                    </div>
                    ` : ''}
                </div>
                
                ${props.is_recurring ? `
                <div class="mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Jadwal Berulang
                    </span>
                </div>
                ` : ''}
                
                <div class="mt-4 text-xs text-gray-500">
                    Dibuat oleh: ${props.created_by}
                </div>
            </div>
        `;
    } else if (props.type === 'attendance-schedule') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4 bg-teal-100 text-teal-700">
                    ABS
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">${props.title}</h2>
                <p class="text-gray-600 dark:text-gray-300 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>

                ${props.description ? `
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-200">${props.description}</p>
                </div>
                ` : ''}

                <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-gray-500">Mulai</p>
                        <p class="font-semibold text-green-700">${props.open_time}</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <p class="text-gray-500">Tepat Waktu</p>
                        <p class="font-semibold text-yellow-700">${props.late_threshold}</p>
                    </div>
                    <div class="p-3 bg-red-50 rounded-lg">
                        <p class="text-gray-500">Tutup</p>
                        <p class="font-semibold text-red-700">${props.close_time}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                        Target: ${props.target_label}
                    </span>
                </div>

                <a href="${props.url}" class="mt-5 inline-block px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                    ${props.action_label || 'Buka Presensi Manual'}
                </a>
            </div>
        `;
    }
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function loadDateStats(dateStr) {
    fetch(`{{ route('calendar.date-stats') }}?date=${dateStr}`)
        .then(response => response.json())
        .then(data => {
            showDateStatsModal(data);
        });
}

function showDateStatsModal(data) {
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    
    let recordsHtml = data.records.length > 0 
        ? data.records.map(r => `
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="font-medium">${r.siswa}</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">${r.jam_masuk || '-'}</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium ${
                        r.status === 'hadir' ? 'bg-green-100 text-green-700' :
                        r.status === 'terlambat' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-red-100 text-red-700'
                    }">${r.status}</span>
                </div>
            </div>
        `).join('')
        : '<p class="text-gray-500 text-center py-4">Tidak ada data presensi</p>';

    const materiHtml = (data.materi || []).length > 0
        ? data.materi.map((item) => `
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <p class="font-semibold text-gray-900 dark:text-white">${calendarEscape(item.title)}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${calendarEscape(item.folder || '-')}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="${calendarEscape(item.url)}" class="inline-flex rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Lihat Materi</a>
                    <a href="${calendarEscape(item.public_url)}" target="_blank" rel="noopener" class="inline-flex rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">Halaman Publik</a>
                </div>
            </div>
        `).join('')
        : '<p class="text-gray-500 text-center py-4">Tidak ada materi pada tanggal ini</p>';
    
    content.innerHTML = `
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-white">Kalender ${data.date}</h2>
            
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="p-3 bg-green-50 rounded-lg text-center">
                    <p class="text-xl font-bold text-green-600">${data.hadir}</p>
                    <p class="text-xs text-green-700">Hadir</p>
                </div>
                <div class="p-3 bg-yellow-50 rounded-lg text-center">
                    <p class="text-xl font-bold text-yellow-600">${data.terlambat}</p>
                    <p class="text-xs text-yellow-700">Terlambat</p>
                </div>
                <div class="p-3 bg-red-50 rounded-lg text-center">
                    <p class="text-xl font-bold text-red-600">${data.alpha}</p>
                    <p class="text-xs text-red-700">Alpha</p>
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-700 mb-2 dark:text-gray-200">Detail Presensi</h3>
                <div class="max-h-60 overflow-y-auto">
                    ${recordsHtml}
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4 mt-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-700 mb-2 dark:text-gray-200">Materi Kalender</h3>
                <div class="space-y-3">
                    ${materiHtml}
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('eventModal').addEventListener('click', function(e) {
    if (e.target === this) closeEventModal();
});
</script>
@endpush

