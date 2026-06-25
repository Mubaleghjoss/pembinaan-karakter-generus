@extends('layouts.app')

@section('title', 'Laporan Presensi - PKG Presensi')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
     x-data="reportPage()"
     x-init="init()">
    <x-breadcrumb :items="[
        ['name' => 'Dashboard', 'url' => route('dashboard')],
        ['name' => 'Laporan Presensi', 'url' => null]
    ]" />

    <div class="pkg-page-header mb-6 lg:mb-8">
        <div>
            <h1 class="pkg-page-heading text-xl sm:text-2xl lg:text-3xl">Laporan Presensi</h1>
            <p class="pkg-page-subheading">Analisis kehadiran siswa, performa kelas, dan ringkasan presensi dalam satu layar.</p>
        </div>
        <div class="pkg-page-actions mt-4 sm:mt-0">
            <button @click="exportReport('pdf')"
                    class="pkg-btn-secondary inline-flex items-center justify-center px-4 py-2 text-sm font-medium w-full sm:w-auto">
                Ekspor PDF
            </button>
            <button @click="exportReport('excel')"
                    class="pkg-btn-primary inline-flex items-center justify-center px-4 py-2 text-sm font-medium w-full sm:w-auto">
                Ekspor Excel
            </button>
        </div>
    </div>

    <div class="pkg-filter-bar mb-6">
        <div class="p-0 sm:p-0">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Filter Laporan</h3>
            <div class="pkg-filter-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Periode</label>
                    <select x-model="periode" @change="updateDateRange()" class="w-full pkg-field text-sm">
                        <option value="hari_ini">Hari Ini</option>
                        <option value="minggu_ini">Minggu Ini</option>
                        <option value="bulan_ini">Bulan Ini</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Mulai</label>
                    <input type="date" x-model="tanggal_mulai" :disabled="periode !== 'custom'" class="w-full pkg-field disabled:opacity-50 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Selesai</label>
                    <input type="date" x-model="tanggal_selesai" :disabled="periode !== 'custom'" class="w-full pkg-field disabled:opacity-50 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kelas</label>
                    <select x-model="kelas_id" class="w-full pkg-field text-sm">
                        <option value="">Semua Kelas</option>
                        <template x-for="kelas in classes" :key="kelas.id">
                            <option :value="kelas.id" x-text="kelas.nama"></option>
                        </template>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                    <button @click="loadReports()"
                            class="pkg-btn-primary py-2 px-6">
                        Generate Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <template x-for="card in [
            { key: 'total_siswa', label: 'Total Siswa', color: 'bg-blue-500', suffix: '' },
            { key: 'total_presensi', label: 'Total Presensi', color: 'bg-green-500', suffix: '' },
            { key: 'persentase_kehadiran', label: '% Kehadiran', color: 'bg-yellow-500', suffix: '%' },
            { key: 'rata_rata_harian', label: 'Rata-rata Harian', color: 'bg-purple-500', suffix: '' }
        ]" :key="card.key">
            <div class="pkg-card overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-md flex items-center justify-center text-white" :class="card.color"></div>
                        <div class="ml-5 w-0 flex-1">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate" x-text="card.label"></div>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">
                                <span x-show="loadingSummary">...</span>
                                <span x-show="!loadingSummary" x-text="`${summary[card.key] ?? 0}${card.suffix}`"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="pkg-card">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Status Kehadiran</h3>
            </div>
            <div class="p-6 h-80">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="pkg-card">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tren Kehadiran Harian</h3>
            </div>
            <div class="p-6 h-80">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="pkg-card mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performa Kelas</h3>
        </div>
        <div x-show="loadingPerformance" class="p-6 text-sm text-gray-500 dark:text-gray-400">Memuat performa kelas...</div>
        <div x-show="!loadingPerformance" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Terlambat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tidak Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="kelas in classPerformance" :key="kelas.id">
                        <tr>
                            <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white pkg-mobile-main" x-text="kelas.nama"></td>
                            <td data-label="Total Siswa" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="kelas.total_siswa"></td>
                            <td data-label="Hadir" class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400" x-text="kelas.hadir"></td>
                            <td data-label="Terlambat" class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 dark:text-yellow-400" x-text="kelas.terlambat"></td>
                            <td data-label="Tidak Hadir" class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400" x-text="kelas.tidak_hadir"></td>
                            <td data-label="% Kehadiran" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="`${kelas.persentase_kehadiran}%`"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pkg-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Siswa Terbaik (Kehadiran Tertinggi)</h3>
        </div>
        <div x-show="loadingStudents" class="p-6 text-sm text-gray-500 dark:text-gray-400">Memuat siswa terbaik...</div>
        <div x-show="!loadingStudents" class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ranking</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Hadir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="(siswa, index) in topStudents" :key="siswa.id">
                        <tr>
                            <td data-label="Peringkat" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="index + 1"></td>
                            <td data-label="Siswa" class="px-6 py-4 whitespace-nowrap pkg-mobile-main">
                                <div class="flex items-center gap-3">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                         :src="siswa.foto_path || '/images/default-avatar.png'"
                                         :alt="siswa.nama">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="siswa.nama"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="siswa.nis"></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Kelas" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="siswa.kelas?.nama || '-'"></td>
                            <td data-label="Total Hadir" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="siswa.total_hadir"></td>
                            <td data-label="% Kehadiran" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white" x-text="`${siswa.persentase_kehadiran}%`"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let statusChart = null;
    let trendChart = null;

    async function renderStatusChart(data) {
        const Chart = await window.loadChartJs();
        const ctx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();
        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Tidak Hadir', 'Izin', 'Sakit'],
                datasets: [{
                    data: [data.hadir || 0, data.terlambat || 0, data.tidak_hadir || 0, data.izin || 0, data.sakit || 0],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    async function renderTrendChart(data) {
        const Chart = await window.loadChartJs();
        const ctx = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    { label: 'Hadir', data: data.hadir || [], borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)', tension: 0.4 },
                    { label: 'Terlambat', data: data.terlambat || [], borderColor: '#F59E0B', backgroundColor: 'rgba(245, 158, 11, 0.1)', tension: 0.4 },
                    { label: 'Tidak Hadir', data: data.tidak_hadir || [], borderColor: '#EF4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function reportPage() {
        return {
            periode: 'hari_ini',
            tanggal_mulai: new Date().toISOString().split('T')[0],
            tanggal_selesai: new Date().toISOString().split('T')[0],
            kelas_id: '',
            classes: [],
            summary: { total_siswa: 0, total_presensi: 0, persentase_kehadiran: 0, rata_rata_harian: 0 },
            loadingSummary: true,
            classPerformance: [],
            loadingPerformance: true,
            topStudents: [],
            loadingStudents: true,
            async init() {
                this.updateDateRange();
                await this.loadClasses();
                await this.loadReports();
            },
            updateDateRange() {
                if (this.periode === 'custom') return;
                const today = new Date();
                let startDate;
                let endDate;

                if (this.periode === 'hari_ini') {
                    startDate = new Date();
                    endDate = new Date();
                } else if (this.periode === 'minggu_ini') {
                    const currentDay = today.getDay();
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - currentDay);
                    endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 6);
                } else {
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                }

                this.tanggal_mulai = startDate.toISOString().split('T')[0];
                this.tanggal_selesai = endDate.toISOString().split('T')[0];
            },
            getReportParams() {
                return new URLSearchParams({
                    tanggal_mulai: this.tanggal_mulai,
                    tanggal_selesai: this.tanggal_selesai,
                    kelas_id: this.kelas_id || ''
                });
            },
            async loadClasses() {
                const response = await fetch('/api/v1/kelas', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                this.classes = data.data || [];
            },
            async loadReports() {
                this.loadingSummary = true;
                this.loadingPerformance = true;
                this.loadingStudents = true;

                await Promise.all([
                    this.loadSummary(),
                    this.loadCharts(),
                    this.loadClassPerformance(),
                    this.loadTopStudents()
                ]);
            },
            async loadSummary() {
                try {
                    const response = await fetch(`{{ route('reports.summary') }}?${this.getReportParams().toString()}`, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    this.summary = data.data || this.summary;
                } finally {
                    this.loadingSummary = false;
                }
            },
            async loadCharts() {
                const [statusResponse, trendResponse] = await Promise.all([
                    fetch(`{{ route('reports.status-chart') }}?${this.getReportParams().toString()}`, { headers: { 'Accept': 'application/json' } }),
                    fetch(`{{ route('reports.trend-chart') }}?${this.getReportParams().toString()}`, { headers: { 'Accept': 'application/json' } })
                ]);
                await Promise.all([
                    renderStatusChart((await statusResponse.json()).data || {}),
                    renderTrendChart((await trendResponse.json()).data || {})
                ]);
            },
            async loadClassPerformance() {
                try {
                    const response = await fetch(`{{ route('reports.class-performance') }}?${this.getReportParams().toString()}`, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    this.classPerformance = data.data || [];
                } finally {
                    this.loadingPerformance = false;
                }
            },
            async loadTopStudents() {
                try {
                    const response = await fetch(`{{ route('reports.top-students') }}?${this.getReportParams().toString()}`, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    this.topStudents = data.data || [];
                } finally {
                    this.loadingStudents = false;
                }
            },
            exportReport(format) {
                const params = this.getReportParams();
                params.append('format', format);
                window.open(`{{ route('reports.export') }}?${params.toString()}`, '_blank');
            }
        };
    }
</script>
@endpush
@endsection
