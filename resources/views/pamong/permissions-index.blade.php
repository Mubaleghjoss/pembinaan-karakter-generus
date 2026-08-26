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
                            <div class="ml-3 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $p->username }}</div>
                                <div class="mt-1"><x-role-badges :user="$p" size="xs" :max-duty="2" /></div>
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
                        @php
                            $bisaManual = $p->hasPamongMenuAccess('manual_attendance')
                                && $p->hasPamongCrudPermission('manual_attendance', 'create');
                            $semuaGenerus = $p->hasPamongCrudPermission('manual_attendance', 'all_students');
                        @endphp
                        <p class="mt-1.5 text-[11px] font-semibold {{ $bisaManual ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                            Presensi manual:
                            {{ $bisaManual ? ($semuaGenerus ? 'semua generus' : 'hanya binaan') : 'tidak berizin' }}
                        </p>
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
                        <div class="flex flex-col items-end gap-1.5">
                            <a href="{{ route('pamong.permissions', $p) }}" class="text-sm font-semibold text-orange-600 hover:text-orange-900 dark:text-orange-400">
                                Edit Hak Akses
                            </a>
                            <button type="button" @click="openDutyRoles({{ $p->id }}, @js($p->username), @js(array_values((array) ($p->duty_roles ?? []))))"
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                Atur Peran Tugas
                            </button>
                        </div>
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
        <p class="mt-3 rounded-lg bg-orange-50 p-3 text-xs text-orange-800 dark:bg-orange-900/30 dark:text-orange-200">
            <strong>Perhatian:</strong> status "Full Access (Dikecualikan)" membuat akun dapat membuka SEMUA menu dan
            mengabaikan daftar izin yang dicentang. Matikan pengecualian bila ingin izin per-menu benar-benar berlaku.
        </p>
    </div>

    {{-- Panel: Tambah jenis peran tugas --}}
    <div class="pkg-card mt-6 p-4" x-data="{ open: false }">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
            <div>
                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100">Jenis Peran Tugas</h4>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ count($dutyRoleCatalog) }} jenis tersedia · dipakai sebagai badge pada akun
                </p>
            </div>
            <svg class="h-5 w-5 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak class="mt-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach($dutyRoleCatalog as $slug => $role)
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ \App\Support\DutyRole::badgeClasses($role['tone']) }}">
                        {{ $role['label'] }}
                    </span>
                @endforeach
            </div>

            <form action="{{ route('pamong.duty-roles.store') }}" method="POST" class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                @csrf
                <input type="text" name="label" required maxlength="60" placeholder="Nama peran baru, mis. Bendahara"
                       class="pkg-field text-sm">
                <select name="tone" class="pkg-field text-sm">
                    @foreach(\App\Support\DutyRole::availableTones() as $tone)
                        <option value="{{ $tone }}">{{ ucfirst($tone) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary text-sm">Tambah Peran</button>
            </form>
        </div>
    </div>

    {{-- Modal: atur peran tugas per akun (boleh beberapa) --}}
    <div x-show="dutyModalOpen" x-cloak @click.self="dutyModalOpen = false"
         class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-black/50 p-4">
        <div class="pkg-modal w-full max-w-lg overflow-hidden" @click.stop>
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Peran Tugas</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="dutyUsername"></p>
                </div>
                <button @click="dutyModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto px-5 py-4">
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Boleh dipilih lebih dari satu.</p>
                <div class="space-y-2">
                    @foreach($dutyRoleCatalog as $slug => $role)
                        <label class="flex items-start gap-2.5 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                            <input type="checkbox" value="{{ $slug }}" x-model="dutySelected" class="form-checkbox mt-0.5">
                            <span class="min-w-0">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ \App\Support\DutyRole::badgeClasses($role['tone']) }}">
                                    {{ $role['label'] }}
                                </span>
                                @if($role['description'])
                                    <span class="mt-1 block text-[11px] text-gray-500 dark:text-gray-400">{{ $role['description'] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
                <button type="button" @click="dutyModalOpen = false" class="btn-secondary text-sm">Batal</button>
                <button type="button" @click="saveDutyRoles()" :disabled="dutySaving" class="btn-primary text-sm disabled:opacity-50">
                    <span x-text="dutySaving ? 'Menyimpan…' : 'Simpan Peran'"></span>
                </button>
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

        // Peran tugas (badge) — boleh beberapa per akun.
        dutyModalOpen: false,
        dutyUserId: null,
        dutyUsername: '',
        dutySelected: [],
        dutySaving: false,

        openDutyRoles(id, username, current) {
            this.dutyUserId = id;
            this.dutyUsername = username;
            this.dutySelected = Array.isArray(current) ? [...current] : [];
            this.dutyModalOpen = true;
        },

        async saveDutyRoles() {
            if (!this.dutyUserId) return;
            this.dutySaving = true;
            try {
                const response = await fetch(`/pamong/${this.dutyUserId}/duty-roles`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ duty_roles: this.dutySelected })
                });
                const data = await response.json();
                if (data.success) {
                    window.showNotification(data.message || 'Peran tugas diperbarui', 'success');
                    this.dutyModalOpen = false;
                    setTimeout(() => location.reload(), 800);
                } else {
                    window.showNotification(data.message || 'Gagal menyimpan peran tugas', 'error');
                }
            } catch (error) {
                window.showNotification('Terjadi kesalahan: ' + error.message, 'error');
            } finally {
                this.dutySaving = false;
            }
        },

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

