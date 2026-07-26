@extends('layouts.app')

@section('title', 'Backup & Pulihkan')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Backup & Pulihkan</h1>
            <p class="pkg-page-subheading">Kelola backup database dan file project.</p>
        </div>
    </div>

    @include('settings.partials.admin-tabs')

    @if(session('success'))
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
        <div class="flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ session('success') }}
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
        <div class="flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        {{ session('error') }}
        </div>
    </div>
    @endif

    <!-- Backup Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Database Backup -->
        <div class="pkg-panel p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Database</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Backup semua tabel</p>
                </div>
            </div>
            <form action="{{ route('settings.backup.database') }}" method="POST">
                @csrf
                <button type="submit" class="pkg-btn-primary w-full !justify-center !px-4 !py-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Backup Database
                </button>
            </form>
        </div>

        <!-- Files Backup -->
        <div class="pkg-panel p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Files (Cepat)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Backup kode saja (tanpa vendor)</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mb-3">Hanya app, config, database, public, resources, routes</p>
            <form action="{{ route('settings.backup.files') }}" method="POST">
                @csrf
                <button type="submit" class="btn-success w-full !justify-center !px-4 !py-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Backup Files
                </button>
            </form>
        </div>

        <!-- Full Backup -->
        <div class="pkg-panel p-6 ring-2 ring-purple-500">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Full Backup</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">SEMUA file + vendor + node_modules</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mb-3">Siap pakai tanpa composer install / npm install</p>
            <form action="{{ route('settings.backup.all') }}" method="POST">
                @csrf
                <button type="submit" class="pkg-btn-primary w-full !justify-center !px-4 !py-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Full Backup (Rekomendasi)
                </button>
            </form>
        </div>
    </div>

    <!-- Info Box -->
    <div class="pkg-card-soft mb-8 border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
        <div class="flex">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-green-800 dark:text-green-200">Full Backup - Siap Pakai!</h4>
                <ul class="mt-1 text-sm text-green-700 dark:text-green-300 list-disc list-inside">
                    <li><strong>Full Backup</strong> menyertakan: vendor, node_modules, semua file project</li>
                    <li>Tinggal extract dan jalankan - tidak perlu <code class="bg-green-100 dark:bg-green-800 px-1 rounded">composer install</code> atau <code class="bg-green-100 dark:bg-green-800 px-1 rounded">npm install</code></li>
                    <li>File .env tidak disertakan untuk keamanan (buat manual dari .env.example)</li>
                    <li>Proses backup mungkin memakan waktu 5-15 menit untuk full backup</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Petunjuk Pemulihan -->
    <div class="pkg-card-soft mb-8 border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Cara Pulihkan Full Backup</h4>
                <ol class="mt-1 text-sm text-blue-700 dark:text-blue-300 list-decimal list-inside space-y-1">
                    <li>Extract file ZIP ke folder htdocs/www</li>
                    <li>Copy file <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">.env.example</code> menjadi <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">.env</code></li>
                    <li>Sesuaikan konfigurasi database di file .env</li>
                    <li>Impor file SQL dari folder <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">database/</code> ke MySQL</li>
                    <li>Jalankan <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">php artisan storage:link</code></li>
                    <li>Selesai! Website siap digunakan</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Backup List -->
    <div class="pkg-panel">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Backup</h2>
        </div>
        
        @if(count($backups) > 0)
        <div class="pkg-mobile-table overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ukuran</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dibuat</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($backups as $backup)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="pkg-mobile-main px-6 py-4 whitespace-nowrap" data-label="Nama file">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $backup['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Tipe">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                @if($backup['type'] === 'Full Backup') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                @elseif($backup['type'] === 'Database') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @else bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                @endif">
                                {{ $backup['type'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="Ukuran">
                            {{ $backup['size'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="Dibuat">
                            {{ $backup['created_at'] }}
                        </td>
                        <td class="pkg-mobile-actions px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-label="Aksi">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('settings.backup.download', $backup['name']) }}" 
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                                <form action="{{ route('settings.backup.delete', $backup['name']) }}" method="POST" class="inline" data-confirm="Yakin ingin menghapus backup ini?" data-confirm-title="Hapus backup" data-confirm-button="Hapus" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-0">
            <div class="pkg-empty-state">
                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <h3 class="pkg-empty-title">Belum ada backup</h3>
                <p class="pkg-empty-copy">Mulai dengan membuat backup pertama Anda.</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Back to Settings -->
    <div class="mt-6">
        <a href="{{ route('settings.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Pengaturan
        </a>
    </div>
</div>
@endsection
