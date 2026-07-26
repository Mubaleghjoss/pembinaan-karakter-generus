@extends('layouts.app')

@section('title', 'Kelola Hak Akses Tim PKG')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="permissionsManager()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('pamong.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Hak Akses Tim PKG</h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">Atur menu dan CRUD yang dapat diakses oleh setiap pamong atau pengurus PKG</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert -->
    <div x-show="alertMessage" x-cloak
         :class="alertType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'"
         class="mb-6 border px-4 py-3 rounded-lg">
        <span x-text="alertMessage"></span>
    </div>

    <!-- Bulk Actions -->
    <div class="pkg-card p-4 mb-6" x-show="selectedPamong.length > 0">
        <div class="flex flex-wrap items-center gap-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="selectedPamong.length"></span> akun dipilih
            </span>
            <div class="flex flex-wrap gap-2">
                <button @click="bulkAction('allow_all')" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition">
                    Izinkan Semua
                </button>
                <button @click="bulkAction('restrict_all')" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                    Batasi Semua
                </button>
                <button @click="bulkAction('set_excluded')" class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded-lg transition">
                    Set Pengecualian
                </button>
                <button @click="bulkAction('remove_excluded')" class="px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg transition">
                    Hapus Pengecualian
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select x-model="selectedPreset" class="pkg-field text-sm">
                    <option value="">Pilih paket bidang</option>
                    @foreach($permissionPresets as $presetKey => $preset)
                    <option value="{{ $presetKey }}">{{ $preset['label'] }}</option>
                    @endforeach
                </select>
                <button @click="applyPreset()" type="button" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                    Terapkan Paket
                </button>
            </div>
        </div>
    </div>

    <!-- Pamong List -->
    <div class="pkg-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" @change="toggleSelectAll($event)" 
                           :checked="selectedPamong.length === pamongList.length && pamongList.length > 0"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pilih Semua</span>
                </label>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Total: {{ count($pamongList) }} akun tim</span>
        </div>
        
        <div class="pkg-mobile-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-12"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akun Tim</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status Akses</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Menu Diizinkan</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($pamongList as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-4" data-label="Pilih">
                        <input type="checkbox" :value="{{ $p->id }}" x-model="selectedPamong"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    </td>
                    <td class="pkg-mobile-main px-4 py-4" data-label="Akun tim">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
                                <span class="text-white font-medium">{{ strtoupper(substr($p->username, 0, 1)) }}</span>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $p->username }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $p->email }} | {{ $p->operationalRoleLabel() }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4" data-label="Status akses">
                        @if($p->pamongPermission?->is_excluded)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                Full Access (Dikecualikan)
                            </span>
                        @elseif($p->pamongPermission)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Terbatas
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                Default (Semua)
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-4" data-label="Menu diizinkan">
                        @if($p->pamongPermission?->is_excluded)
                            <span class="text-sm text-orange-600 dark:text-orange-400">Semua Menu</span>
                        @elseif($p->pamongPermission?->menu_permissions)
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($p->pamongPermission->menu_permissions, 0, 3) as $menu)
                                    <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                        {{ $availableMenus[$menu] ?? $menu }}
                                    </span>
                                @endforeach
                                @if(count($p->pamongPermission->menu_permissions) > 3)
                                    <span class="px-2 py-0.5 text-xs bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded">
                                        +{{ count($p->pamongPermission->menu_permissions) - 3 }} lainnya
                                    </span>
                                @endif
                            </div>
                        @else
                            <span class="text-sm text-gray-500 dark:text-gray-400">Semua Menu (Default)</span>
                        @endif
                    </td>
                    <td class="pkg-mobile-actions px-4 py-4 text-right" data-label="Aksi">
                        <a href="{{ route('pamong.permissions', $p) }}" class="text-orange-600 hover:text-orange-900 dark:text-orange-400">
                            Edit Hak Akses
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="pkg-mobile-empty px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada akun tim ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="mt-6 pkg-card p-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Keterangan Status:</h4>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Full Access</span>
                <span class="text-gray-600 dark:text-gray-400">Akun dikecualikan dari pembatasan</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Terbatas</span>
                <span class="text-gray-600 dark:text-gray-400">Akses dibatasi sesuai pengaturan</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">Default</span>
                <span class="text-gray-600 dark:text-gray-400">Belum ada pengaturan khusus</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function permissionsManager() {
    return {
        pamongList: @json($pamongList->pluck('id')),
        selectedPamong: [],
        selectedPreset: '',
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedPamong = [...this.pamongList];
            } else {
                this.selectedPamong = [];
            }
        },
        
        async bulkAction(action) {
            if (this.selectedPamong.length === 0) {
                window.showNotification('Pilih minimal satu akun tim', 'warning');
                return;
            }
            
            try {
                const response = await fetch('{{ route("pamong.permissions.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        pamong_ids: this.selectedPamong,
                        action: action
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.showNotification(data.message || 'Hak akses berhasil diperbarui', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showNotification(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                window.showNotification('Terjadi kesalahan: ' + error.message, 'error');
            }
        }
        ,
        async applyPreset() {
            if (!this.selectedPreset) {
                window.showNotification('Pilih paket bidang terlebih dahulu', 'warning');
                return;
            }

            if (this.selectedPamong.length === 0) {
                window.showNotification('Pilih minimal satu akun tim', 'warning');
                return;
            }

            try {
                const response = await fetch('{{ route("pamong.permissions.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        pamong_ids: this.selectedPamong,
                        action: 'apply_preset',
                        preset_key: this.selectedPreset
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.showNotification(data.message || 'Paket izin berhasil diterapkan', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showNotification(data.message || 'Terjadi kesalahan', 'error');
                }
            } catch (error) {
                window.showNotification('Terjadi kesalahan: ' + error.message, 'error');
            }
        }
    };
}
</script>
@endpush
@endsection

