@extends('layouts.app')

@section('title', 'Analitik Gamifikasi')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="pkg-page-header">
            <div>
                <h1 class="pkg-page-heading">Analitik Gamifikasi</h1>
                <p class="pkg-page-subheading">Statistik, grafik, konsistensi, dan analisis aktivitas gamifikasi siswa.</p>
            </div>
            <div class="pkg-page-actions">
                <a href="{{ route('admin.gamification.export-analytics', ['type' => 'consistency']) }}" class="pkg-btn-secondary inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium">
                    Ekspor Konsistensi
                </a>
                <a href="{{ route('admin.gamification.export-analytics', ['type' => 'tasks']) }}" class="pkg-btn-secondary inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium">
                    Ekspor Tugas
                </a>
                <a href="{{ route('admin.gamification.export-analytics', ['type' => 'ranking']) }}" class="pkg-btn-primary inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium">
                    Ekspor Peringkat
                </a>
            </div>
        </div>

        @include('admin.gamification.partials.navigation')

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="pkg-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Poin</p>
                        <p class="text-3xl font-bold text-blue-600">{{ number_format($totalPoints) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <span class="text-2xl font-semibold text-blue-700">PTS</span>
                    </div>
                </div>
            </div>
            
            <div class="pkg-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pin Diperoleh</p>
                        <p class="text-3xl font-bold text-yellow-600">{{ number_format($totalBadgesEarned) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                        <span class="text-2xl font-semibold text-yellow-700">PIN</span>
                    </div>
                </div>
            </div>
            
            <div class="pkg-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Rata-rata Poin</p>
                        <p class="text-3xl font-bold text-green-600">{{ number_format($avgPointsPerSiswa ?? 0, 1) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <span class="text-2xl font-semibold text-green-700">AVG</span>
                    </div>
                </div>
            </div>
            
            <div class="pkg-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Siswa Aktif</p>
                        <p class="text-3xl font-bold text-purple-600">{{ number_format($activePointStudents) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                        <span class="text-2xl font-semibold text-purple-700">AKT</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Level Distribution Chart -->
            <div class="pkg-panel p-6">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Distribusi Level</h2>
                <div class="relative" style="height: 280px;">
                    <canvas id="levelChart"></canvas>
                </div>
            </div>

            <!-- Daily Activity Chart (14 days) -->
            <div class="pkg-panel p-6">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Aktivitas Harian (14 Hari Terakhir)</h2>
                <div class="relative" style="height: 280px;">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Task Participation Bar Chart -->
        @if(isset($taskAnalytics) && $taskAnalytics->count() > 0)
        <div class="pkg-panel p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Partisipasi Per Tugas</h2>
                <a href="{{ route('admin.gamification.export-analytics', ['type' => 'tasks']) }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Unduh CSV</a>
            </div>
            <div class="relative" style="height: 300px;">
                <canvas id="taskChart"></canvas>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Badges -->
            <div class="pkg-panel p-6">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Pin Terpopuler</h2>
                <div class="space-y-3">
                    @forelse($topBadges as $badge)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl" 
                             style="background-color: {{ $badge->warna }}20">
                            {{ $badge->icon_url }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 dark:text-white">{{ $badge->nama }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($badge->kategori) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-800 dark:text-white">{{ $badge->user_badges_count }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">siswa</p>
                        </div>
                    </div>
                    @empty
                    <div class="pkg-empty-state py-4">
                        <h3 class="pkg-empty-title">Belum ada pin yang diperoleh</h3>
                        <p class="pkg-empty-copy">Pin yang aktif dan sudah diperoleh siswa akan muncul di sini.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Task Analytics Table (Compact) -->
            <div class="pkg-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">Analisis Per Tugas</h2>
                    <a href="{{ route('tugas-pkg.detail-siswa') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Lihat Detail</a>
                </div>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @foreach($taskAnalytics as $task)
                    <div class="flex items-center gap-3 p-2">
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full flex-shrink-0
                            {{ $task->kategori === 'harian' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                               ($task->kategori === 'mingguan' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200') }}">
                            {{ $task->kategori_label }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $task->nama }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2 overflow-hidden">
                                @php
                                    $color = $task->participation_rate >= 70 ? 'bg-green-500' : 
                                             ($task->participation_rate >= 40 ? 'bg-yellow-500' : 'bg-red-500');
                                @endphp
                                <div class="{{ $color }} h-full rounded-full" style="width: {{ max($task->participation_rate, 3) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 w-10 text-right">{{ $task->participation_rate }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- === Konsistensi Harian Siswa === -->
        @if(isset($dailyConsistency) && $dailyConsistency->count() > 0)
        <div class="pkg-panel overflow-hidden mb-8">
            <div class="p-6 border-b dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">Konsistensi Harian Siswa</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Analisis kerutinan mengerjakan tugas, siswa rutin (minimal 3 hari per minggu) vs tidak rutin.</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs font-medium rounded-full">
                        Rutin: {{ $dailyConsistency->where('is_consistent', true)->count() }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 text-xs font-medium rounded-full">
                        Tidak Rutin: {{ $dailyConsistency->where('is_consistent', false)->count() }}
                    </span>
                    <a href="{{ route('admin.gamification.export-analytics', ['type' => 'consistency']) }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 text-xs font-medium rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        CSV
                    </a>
                </div>
            </div>
            <div class="pkg-mobile-table overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-12">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hari Aktif</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hari Skip</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rata-rata/Minggu</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($dailyConsistency as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400" data-label="Peringkat">{{ $index + 1 }}</td>
                            <td class="pkg-mobile-main px-4 py-3" data-label="Siswa">
                                <a href="{{ route('tugas-pkg.history', $item->siswa_id) }}" class="group">
                                    <p class="font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-800 dark:group-hover:text-indigo-300 transition">{{ $item->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->siswa->nis ?? '-' }}</p>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center" data-label="Hari aktif">
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $item->active_days }}</span>
                                <span class="text-xs text-gray-400"> / {{ $item->total_span_days }}</span>
                            </td>
                            <td class="px-4 py-3 text-center" data-label="Hari terlewat">
                                <span class="text-sm font-medium {{ $item->skip_days > $item->active_days ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-300' }}">{{ $item->skip_days }}</span>
                            </td>
                            <td class="px-4 py-3 text-center" data-label="Rata-rata per minggu">
                                <span class="text-sm font-bold {{ $item->days_per_week >= 3 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400' }}">
                                    {{ $item->days_per_week }}
                                </span>
                                <span class="text-xs text-gray-400">hari</span>
                            </td>
                            <td class="px-4 py-3 text-center" data-label="Status">
                                @if($item->is_consistent)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Rutin</span>
                                @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Tidak Rutin</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- === Ranking Siswa Paling Aktif === -->
        @if(isset($studentTaskStats) && $studentTaskStats->count() > 0)
        <div class="pkg-panel overflow-hidden mb-8">
            <div class="p-6 border-b dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">Peringkat Siswa Paling Aktif (Tugas PKG)</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Top 20 siswa berdasarkan jumlah tugas yang dikerjakan</p>
                </div>
                <a href="{{ route('admin.gamification.export-analytics', ['type' => 'ranking']) }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">CSV</a>
            </div>
            <div class="pkg-mobile-table overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-16">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Tugas</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Terverifikasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($studentTaskStats as $index => $stat)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-center" data-label="Peringkat">
                                @if($index < 3)
                                <span class="text-xs font-semibold">{{ ['P1','P2','P3'][$index] }}</span>
                                @else
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="pkg-mobile-main px-6 py-4" data-label="Siswa">
                                <a href="{{ route('tugas-pkg.history', $stat->siswa_id) }}" class="group">
                                    <p class="font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-800 dark:group-hover:text-indigo-300 transition">{{ $stat->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat->siswa->nis ?? '-' }}</p>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-center" data-label="Total tugas">
                                <span class="text-lg font-bold text-blue-600">{{ $stat->total_tasks }}</span>
                            </td>
                            <td class="px-6 py-4 text-center" data-label="Terverifikasi">
                                <span class="text-lg font-bold text-green-600">{{ $stat->verified_tasks }}</span>
                            </td>
                            <td class="px-6 py-4" data-label="Progres">
                                @php
                                    $verifyPercent = $stat->total_tasks > 0 ? round(($stat->verified_tasks / $stat->total_tasks) * 100) : 0;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-3 overflow-hidden">
                                        <div class="bg-gradient-to-r from-green-400 to-emerald-600 h-full rounded-full" 
                                             style="width: {{ max($verifyPercent, 3) }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300 w-10 text-right">{{ $verifyPercent }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Manual Point Adjustment -->
        <div class="pkg-panel p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Penyesuaian Poin Manual</h2>
            <form id="adjustPointsForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Siswa</label>
                    <select id="siswaSelect" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Pilih Siswa...</option>
                        @foreach($siswaOptions as $siswa)
                        <option value="{{ $siswa->id }}">{{ $siswa->nama }} ({{ $siswa->nis }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poin (+/-)</label>
                    <input type="number" id="pointsInput" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white" placeholder="Contoh: 50 atau -20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan</label>
                    <input type="text" id="descInput" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white" placeholder="Alasan penyesuaian">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Activity -->
        <div class="pkg-panel overflow-hidden">
            <div class="p-6 border-b dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Aktivitas Terbaru</h2>
            </div>
            <div class="pkg-mobile-table overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Waktu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Poin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentActivity as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" data-label="Waktu">
                                {{ $activity->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="pkg-mobile-main px-6 py-4" data-label="Siswa">
                                <p class="font-medium text-gray-800 dark:text-white">{{ $activity->siswa->nama ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4" data-label="Tipe">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $activity->type === 'attendance' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' : 
                                       ($activity->type === 'character' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200' : 
                                       ($activity->type === 'badge' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200')) }}">
                                    {{ ucfirst($activity->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300" data-label="Deskripsi">
                                {{ $activity->description }}
                            </td>
                            <td class="px-6 py-4 text-right" data-label="Poin">
                                <span class="font-bold {{ $activity->points >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $activity->points >= 0 ? '+' : '' }}{{ $activity->points }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="pkg-mobile-empty px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada aktivitas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Detect dark mode
const isDark = document.documentElement.classList.contains('dark') || window.matchMedia('(prefers-color-scheme: dark)').matches;
const textColor = isDark ? '#d1d5db' : '#374151';
const gridColor = isDark ? '#374151' : '#e5e7eb';

(async () => {
const Chart = await window.loadChartJs();

// Level Distribution (Doughnut)
const levelData = @json($levelDistribution);
if (levelData.length > 0) {
    const levelColors = ['#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#6366F1', '#EF4444', '#14B8A6'];
    new Chart(document.getElementById('levelChart'), {
        type: 'doughnut',
        data: {
            labels: levelData.map(l => l.name),
            datasets: [{
                data: levelData.map(l => l.count),
                backgroundColor: levelColors.slice(0, levelData.length),
                borderWidth: 2,
                borderColor: isDark ? '#1f2937' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { color: textColor, padding: 12, font: { size: 12 } } }
            }
        }
    });
}

// Daily Activity (Line + Bar combo)
const dailyData = @json($dailyActivity);
if (dailyData.length > 0) {
    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: {
            labels: dailyData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
            }),
            datasets: [
                {
                    label: 'Total Selesai',
                    data: dailyData.map(d => d.total),
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderRadius: 6,
                    order: 2
                },
                {
                    label: 'Siswa Unik',
                    data: dailyData.map(d => d.unique_siswa),
                    type: 'line',
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#F59E0B',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } }
            },
            plugins: {
                legend: { labels: { color: textColor, font: { size: 11 } } }
            }
        }
    });
}

// Task Participation (Horizontal Bar)
const taskData = @json($taskAnalytics->map(fn($t) => ['nama' => $t->nama, 'participation_rate' => $t->participation_rate, 'verification_rate' => $t->verification_rate]));
if (taskData.length > 0 && document.getElementById('taskChart')) {
    new Chart(document.getElementById('taskChart'), {
        type: 'bar',
        data: {
            labels: taskData.map(t => t.nama.length > 25 ? t.nama.substring(0, 25) + '...' : t.nama),
            datasets: [
                {
                    label: 'Partisipasi %',
                    data: taskData.map(t => t.participation_rate),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 4
                },
                {
                    label: 'Verifikasi %',
                    data: taskData.map(t => t.verification_rate),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { max: 100, ticks: { color: textColor, callback: v => v + '%' }, grid: { color: gridColor } },
                y: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } }
            },
            plugins: {
                legend: { labels: { color: textColor, font: { size: 11 } } }
            }
        }
    });
}
})();

// Point Adjustment Form
document.getElementById('adjustPointsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        siswa_id: document.getElementById('siswaSelect').value,
        points: parseInt(document.getElementById('pointsInput').value),
        description: document.getElementById('descInput').value
    };
    
    fetch('/gamification/adjust-points', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            window.showNotification('Poin berhasil disesuaikan', 'success');
            location.reload();
        } else {
            window.showNotification(result.message || 'Gagal menyesuaikan poin', 'error');
        }
    })
    .catch(err => {
        window.showNotification('Terjadi kesalahan saat menyesuaikan poin', 'error');
        console.error(err);
    });
});
</script>
@endsection
