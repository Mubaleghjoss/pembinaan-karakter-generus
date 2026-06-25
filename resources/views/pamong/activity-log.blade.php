@extends('layouts.app')

@section('title', 'Log Aktivitas - ' . $pamong->username)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="pkg-page-header">
        <div class="flex items-center gap-4">
            <a href="{{ route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'data']) }}" class="btn-secondary !h-10 !w-10 !rounded-full !p-0" aria-label="Kembali ke Tim PKG">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="pkg-page-heading">Log Aktivitas Pamong</h1>
                <p class="pkg-page-subheading">{{ $pamong->username }} - {{ $pamong->email ?? 'Tanpa email' }}</p>
            </div>
        </div>
    </div>

    <!-- Login Info Card -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="pkg-card p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $pamong->last_login_at ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                    <span class="text-sm font-semibold">{{ $pamong->last_login_at ? 'ON' : 'OFF' }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status Login</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        @if($pamong->last_login_at)
                            @if($pamong->last_login_at->diffInMinutes(now()) < 30)
                                <span class="text-green-600">Online</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-300">Offline</span>
                            @endif
                        @else
                            <span class="text-red-600">Belum Pernah Login</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="pkg-card p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <span class="text-sm font-semibold">JAM</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Login Terakhir</p>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">
                        {{ $pamong->last_login_at ? $pamong->last_login_at->format('d M Y H:i') : '-' }}
                    </p>
                    @if($pamong->last_login_at)
                        <p class="text-xs text-gray-400">{{ $pamong->last_login_at->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="pkg-card p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <span class="text-sm font-semibold">LOG</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Aktivitas</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $logs->total() }} log</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="pkg-filter-bar mb-6">
        <form method="GET" action="{{ route('pamong.activity-log', $pamong) }}" class="pkg-filter-grid items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aksi</label>
                <select name="action" class="w-full pkg-field text-sm">
                    <option value="">Semua Aksi</option>
                    @foreach($actionLabels as $key => $label)
                        <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Modul</label>
                <select name="module" class="w-full pkg-field text-sm">
                    <option value="">Semua Modul</option>
                    @foreach($moduleLabels as $key => $label)
                        <option value="{{ $key }}" {{ request('module') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}" 
                       class="w-full pkg-field text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary !px-4 !py-2.5 text-sm">
                    Filter
                </button>
                <a href="{{ route('pamong.activity-log', $pamong) }}" class="btn-secondary !px-4 !py-2.5 text-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Activity Log Table -->
    <div class="pkg-card border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Riwayat Aktivitas</h3>
        </div>

        @if($logs->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Modul</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">IP Address</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm">{{ $log->action_label }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ $log->module_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $log->description }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $log->ip_address ?? '-' }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $logs->withQueryString()->links() }}
        </div>
        @else
        <div class="pkg-empty-state m-6">
            <div class="pkg-empty-icon">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="pkg-empty-title">Belum ada aktivitas</h3>
            <p class="pkg-empty-copy">Aktivitas pamong akan tercatat di sini setelah login dan melakukan aksi.</p>
        </div>
        @endif
    </div>
</div>
@endsection
