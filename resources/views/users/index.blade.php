@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Manajemen User</h1>
            <p class="pkg-page-subheading">Kelola admin, pamong, dan pengurus PKG dari satu daftar yang konsisten.</p>
        </div>
        <div class="pkg-page-actions">
            <button onclick="openImportModal()" class="pkg-btn-secondary inline-flex items-center justify-center px-4 py-2 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Impor Pamong
            </button>
            <a href="{{ route('users.create') }}" class="pkg-btn-primary inline-flex items-center justify-center px-4 py-2 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah User
            </a>
        </div>
    </div>

    @include('settings.partials.admin-tabs')

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
        {{ $errors->first() }}
    </div>
    @endif

    @if(session('warning'))
    <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded-lg">
        <p class="font-medium">{{ session('warning') }}</p>
        @if(session('import_errors'))
        <ul class="mt-2 text-sm list-disc list-inside">
            @foreach(session('import_errors') as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        @endif
    </div>
    @endif

    @php($legacyBiometricUsers = $users->getCollection()->where('legacy_biometric_credentials_count', '>', 0)->count())
    @if($legacyBiometricUsers > 0)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
        Ada {{ $legacyBiometricUsers }} user di halaman ini yang masih punya credential biometrik format lama. Minta user login biasa lalu daftar ulang biometrik dari menu pengaturan.
    </div>
    @endif

    <!-- Filters -->
    <div class="pkg-filter-bar mb-6">
        <form method="GET" class="pkg-filter-grid sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_auto_auto_auto_auto]">
            @if(($tab ?? null) === 'user')
            <input type="hidden" name="tab" value="user">
            @endif
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari username atau email..."
                    class="w-full px-4 py-2 pkg-field">
            </div>
            <select name="role" class="px-4 py-2 pkg-field">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ $role->display_name ?? $role->name }}</option>
                @endforeach
            </select>
            <select name="status" class="px-4 py-2 pkg-field">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit" class="pkg-btn-primary px-4 py-2">Filter</button>
            <a href="{{ ($tab ?? null) === 'user' ? route('settings.index', ['tab' => 'user']) : route('users.index') }}" class="pkg-btn-secondary px-4 py-2">Reset</a>
        </form>
    </div>

    <!-- Users Table -->
    <div class="pkg-card overflow-hidden">
        <div class="overflow-x-auto pkg-mobile-table">
        <table class="min-w-[860px] divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Biometrik</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Login Terakhir</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($users as $user)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap pkg-mobile-main" data-label="User">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                                <span class="text-white font-medium">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->username }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Role">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ match($user->role->name) {
                            'admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                            'pkg_manager' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        } }}">
                            {{ $user->role->display_name ?? $user->role->name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-label="Status">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm" data-label="Biometrik">
                        @if(($user->valid_biometric_credentials_count ?? 0) > 0)
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                Aktif
                            </span>
                        @elseif(($user->legacy_biometric_credentials_count ?? 0) > 0)
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                Legacy
                            </span>
                        @else
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                Belum aktif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="Login Terakhir">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Belum pernah' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium pkg-mobile-actions" data-label="Aksi">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Edit</a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400">
                                    {{ $user->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" data-confirm="Yakin ingin menghapus user ini?" data-confirm-title="Hapus user" data-confirm-button="Hapus" data-confirm-tone="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-0 pkg-mobile-empty" data-label="">
                        <div class="pkg-empty-state">
                            <svg class="pkg-empty-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <h3 class="pkg-empty-title">Tidak ada user ditemukan</h3>
                            <p class="pkg-empty-copy">Ubah filter atau tambahkan user baru untuk mulai mengelola akun.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeImportModal()"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom pkg-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Impor Data Pamong
                            </h3>
                            <div class="mt-4">
                                <!-- Download Template -->
                                <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <p class="text-sm text-blue-800 dark:text-blue-200 mb-2">
                                        <strong>Langkah 1:</strong> Download template Excel terlebih dahulu
                                    </p>
                                    <a href="{{ route('users.template') }}" class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Unduh Template
                                    </a>
                                </div>

                                <!-- Upload File -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <strong>Langkah 2:</strong> Upload file Excel yang sudah diisi
                                    </label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-blue-400 dark:hover:border-blue-500 transition-colors" id="dropZone">
                                        <div class="space-y-1 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                                <label for="file-upload" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                                    <span>Pilih file</span>
                                                    <input id="file-upload" name="file" type="file" class="sr-only" accept=".xlsx,.xls" required>
                                                </label>
                                                <p class="pl-1">atau drag & drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Excel (.xlsx, .xls) max 10MB</p>
                                        </div>
                                    </div>
                                    <p id="fileName" class="mt-2 text-sm text-green-600 dark:text-green-400 hidden"></p>
                                </div>

                                <!-- Info -->
                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    <p>- Semua akun impor dari template ini akan memiliki role <strong>Pamong</strong></p>
                                    <p>- Password default: <strong>pamong123</strong> (jika tidak diisi)</p>
                                    <p>- Username dan email harus unik</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" id="importBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 mr-2 animate-spin hidden" id="importSpinner" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="importBtnText">Impor</span>
                    </button>
                    <button type="button" onclick="closeImportModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('importForm').reset();
    document.getElementById('fileName').classList.add('hidden');
    document.getElementById('importBtn').disabled = true;
}

// File input change handler
document.getElementById('file-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = 'FILE: ' + file.name;
        document.getElementById('fileName').classList.remove('hidden');
        document.getElementById('importBtn').disabled = false;
    } else {
        document.getElementById('fileName').classList.add('hidden');
        document.getElementById('importBtn').disabled = true;
    }
});

// Drag and drop
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('file-upload');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.add('border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
    });
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.remove('border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
    });
});

dropZone.addEventListener('drop', function(e) {
    const files = e.dataTransfer.files;
    if (files.length) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

// Form submit handler
document.getElementById('importForm').addEventListener('submit', function() {
    document.getElementById('importBtn').disabled = true;
    document.getElementById('importSpinner').classList.remove('hidden');
    document.getElementById('importBtnText').textContent = 'Mengimport...';
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('importModal').classList.contains('hidden')) {
        closeImportModal();
    }
});
</script>
@endsection
