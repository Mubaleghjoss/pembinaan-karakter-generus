@extends('layouts.siswa')

@section('title', 'Kalender')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kalender Aktivitas</h1>
        <p class="text-gray-600 dark:text-gray-300">Lihat jadwal presensi, tenggat tugas PKG, dan aktivitas karakter</p>
    </div>

    <!-- Legend -->
    <div class="pkg-panel p-4 mb-6">
        <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                <span>Hadir</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span>Terlambat</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                <span>Izin</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                <span>Sakit / Karakter</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span>Alpha</span>
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
                <span class="w-3 h-3 rounded-full" style="background-color: #F97316"></span>
                <span>Jadwal dari Admin</span>
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
    <div class="pkg-panel p-4">
        <div id="calendar"></div>
    </div>

    <!-- Event Detail Modal -->
    <div id="eventModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 relative">
            <button onclick="closeEventModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div id="eventModalContent"></div>
        </div>
    </div>
</div>

<script>
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
            fetch(`{{ route('siswa.calendar.events') }}?start=${info.startStr}&end=${info.endStr}`)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
        eventClick: function(info) {
            showEventDetail(info.event);
        },
        eventDidMount: function(info) {
            info.el.style.cursor = 'pointer';
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
    
    let html = '';
    
    if (event.extendedProps.type === 'presensi') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4"
                     style="background-color: ${event.backgroundColor}20">
                    PRES
                </div>
                <h2 class="text-xl font-bold text-gray-800">Presensi</h2>
                <p class="text-gray-600 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                
                <div class="mt-4 p-4 rounded-lg" style="background-color: ${event.backgroundColor}20">
                    <p class="text-lg font-semibold capitalize" style="color: ${event.backgroundColor}">${props.status}</p>
                </div>
                
                ${props.jam_masuk ? `
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-gray-500">Jam Masuk</p>
                        <p class="font-semibold">${props.jam_masuk}</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-gray-500">Jam Keluar</p>
                        <p class="font-semibold">${props.jam_keluar || '-'}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    } else if (event.extendedProps.type === 'pkg_task') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4"
                     style="background-color: ${event.backgroundColor}20">
                    PKG
                </div>
                <h2 class="text-xl font-bold text-gray-800">${props.judul}</h2>
                <p class="text-gray-600 mt-1">Periode: ${props.period || props.deadline}</p>
                
                <div class="mt-4 p-4 rounded-lg ${props.submitted ? 'bg-green-50' : 'bg-red-50'}">
                    <p class="font-semibold ${props.submitted ? 'text-green-600' : 'text-red-600'}">
                        ${props.submitted ? 'Sudah Dikumpulkan' : 'Belum Dikumpulkan'}
                    </p>
                </div>
                
                <p class="mt-3 text-sm text-gray-500">Kategori: ${props.kategori}</p>
                <a href="${props.url}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Buka Tugas PKG
                </a>
            </div>
        `;
    } else if (event.extendedProps.type === 'karakter') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4 bg-purple-100">
                    KAR
                </div>
                <h2 class="text-xl font-bold text-gray-800">Karakter Diceklis</h2>
                <p class="text-gray-600 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                
                <div class="mt-4 p-4 bg-purple-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600">${props.count}</p>
                    <p class="text-purple-700">Karakter</p>
                </div>
                
                <div class="mt-4 text-left">
                    <p class="text-sm text-gray-500 mb-2">Karakter yang diceklis:</p>
                    <div class="flex flex-wrap gap-2">
                        ${props.karakters.map(k => `<span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm">${k}</span>`).join('')}
                    </div>
                </div>
            </div>
        `;
    } else if (event.extendedProps.type === 'materi_rpp') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-lg font-bold mb-4 bg-teal-100 text-teal-700">
                    RPP
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">${props.title}</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                ${props.start_time ? `<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}</p>` : ''}
                ${props.session_type === 'catch_up' ? '<span class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Kejar Target</span>' : ''}
                ${props.teacher_name ? `<span class="mt-3 ml-1 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Pengajar: ${props.teacher_name}</span>` : ''}
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="p-3 bg-teal-50 dark:bg-teal-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Pertemuan</p>
                        <p class="font-semibold text-teal-700 dark:text-teal-300">${props.session_number || '-'}</p>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Target</p>
                        <p class="font-semibold text-blue-700 dark:text-blue-300">${props.page_range || '-'}</p>
                    </div>
                </div>
                ${props.description ? `<p class="mt-4 rounded-lg bg-gray-50 p-3 text-left text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-200">${props.description}</p>` : ''}
                ${props.url ? `<a href="${props.url}" target="_blank" class="btn-primary mt-5 inline-flex justify-center">Buka Materi</a>` : ''}
            </div>
        `;
    } else if (event.extendedProps.type === 'schedule-reminder') {
        const targetLabel = props.target_audience === 'all' ? 'Semua' : (props.target_audience === 'siswa' ? 'Siswa' : 'Pamong');
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4" style="background-color: ${event.backgroundColor}20">
                    JDL
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">${props.title}</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                
                <!-- Badge: Dibuat oleh Admin -->
                <div class="mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                        Dibuat oleh: ${props.created_by}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-1">
                        Target: ${targetLabel}
                    </span>
                </div>
                
                ${props.description ? `
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-left">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Keterangan:</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">${props.description}</p>
                </div>
                ` : ''}
                
                <div class="mt-4 grid ${props.start_time && props.location ? 'grid-cols-2' : 'grid-cols-1'} gap-3 text-sm">
                    ${props.start_time ? `
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Waktu</p>
                        <p class="font-semibold text-blue-700 dark:text-blue-300">${props.start_time}${props.end_time ? ' - ' + props.end_time : ''}</p>
                    </div>
                    ` : ''}
                    ${props.location ? `
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Lokasi</p>
                        <p class="font-semibold text-green-700 dark:text-green-300">${props.location}</p>
                    </div>
                    ` : ''}
                </div>
                
                ${props.is_recurring ? `
                <div class="mt-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        Jadwal Berulang
                    </span>
                </div>
                ` : ''}
            </div>
        `;
    } else if (event.extendedProps.type === 'attendance-schedule') {
        html = `
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-lg font-bold mb-4 bg-teal-100 text-teal-700">
                    ABS
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">${props.title || event.title}</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">${event.start.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>

                ${props.description ? `
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-300">${props.description}</p>
                </div>
                ` : ''}

                <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                    <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Mulai</p>
                        <p class="font-semibold text-green-700 dark:text-green-300">${props.open_time}</p>
                    </div>
                    <div class="p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Tepat Waktu</p>
                        <p class="font-semibold text-yellow-700 dark:text-yellow-300">${props.late_threshold}</p>
                    </div>
                    <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                        <p class="text-gray-500 dark:text-gray-400">Tutup</p>
                        <p class="font-semibold text-red-700 dark:text-red-300">${props.close_time}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                        Target: ${props.target_label}
                    </span>
                </div>

                <a href="${props.url}" class="mt-5 inline-block px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                    Buka Scan Presensi
                </a>
            </div>
        `;
    }
    
    content.innerHTML = html;
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

<style>
.fc {
    font-family: inherit;
}
.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
}
.fc .fc-button {
    background-color: #4F46E5;
    border-color: #4F46E5;
}
.fc .fc-button:hover {
    background-color: #4338CA;
    border-color: #4338CA;
}
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: #3730A3;
    border-color: #3730A3;
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
.dark .fc .fc-toolbar-title {
    color: #fff;
}
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
@endsection

