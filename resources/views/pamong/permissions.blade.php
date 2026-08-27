@extends('layouts.app')

@section('title', 'Hak Akses Tim PKG - ' . $pamong->username)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div class="flex items-start gap-4">
            <a href="{{ route('pamong.index') }}" class="mt-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="pkg-page-heading">Hak Akses Tim PKG</h1>
                <p class="pkg-page-subheading">{{ $pamong->username }} | {{ $pamong->email }} | {{ $pamong->operationalRoleLabel() }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('pamong.permissions.update', $pamong) }}" method="POST" x-data="permissionForm()">
        @csrf
        
        <!-- Bypass toggle: deliberately separate from normal permission packages. -->
        <div class="pkg-card border border-orange-200 p-6 mb-6 dark:border-orange-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Bypass pembatasan izin</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Akun dapat membuka semua menu dan tindakan operasional. Checklist paket dan izin di bawah tidak lagi berlaku.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_excluded" value="1" x-model="isExcluded" class="sr-only peer"
                           @change="if (isExcluded && !window.confirm('Aktifkan bypass pembatasan? Akun ini akan dapat membuka seluruh menu dan tindakan operasional, terlepas dari checklist izin.')) { isExcluded = false; }"
                           {{ $pamong->pamongPermission?->is_excluded ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-600"></div>
                </label>
            </div>
        </div>

        <div class="pkg-card p-6 mb-6" x-show="!isExcluded">
            <div class="mb-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Paket Izin Bidang</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih paket cepat untuk akun ini, lalu lanjut sesuaikan bila ada kebutuhan khusus.</p>
                    </div>
                    <div class="rounded-lg border px-3 py-2 text-sm"
                         :class="currentPresetKey ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200' : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200'">
                        <template x-if="currentPresetKey">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide opacity-80">Paket Aktif</p>
                                <p class="font-medium" x-text="presets[currentPresetKey]?.label || '-'"></p>
                            </div>
                        </template>
                        <template x-if="!currentPresetKey">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide opacity-80">Status</p>
                                <p class="font-medium">Kustom manual</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($permissionPresets as $presetKey => $preset)
                <button type="button"
                        @click="applyPreset('{{ $presetKey }}')"
                        class="rounded-xl border border-gray-200 bg-white p-4 text-left transition hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700 dark:hover:bg-blue-900/20"
                        :class="currentPresetKey === '{{ $presetKey }}' ? 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-200 dark:border-emerald-700 dark:bg-emerald-900/20 dark:ring-emerald-900/40' : ''">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $preset['label'] }}</h4>
                        <span class="rounded-full px-2 py-1 text-[11px] font-medium"
                              :class="currentPresetKey === '{{ $presetKey }}' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200'"
                              x-text="currentPresetKey === '{{ $presetKey }}' ? 'Sedang dipakai' : 'Terapkan'"></span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $preset['description'] }}</p>
                </button>
                @endforeach
            </div>
        </div>

        <!-- Menu Permissions -->
        <div class="pkg-card p-6 mb-6" x-show="!isExcluded">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Akses Menu</h3>
                <button type="button" @click="toggleAllMenus()" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    <span x-text="allMenusChecked ? 'Hapus Semua' : 'Pilih Semua'"></span>
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($availableMenus as $key => $label)
                <label class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3 cursor-pointer hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600">
                    <input type="checkbox" name="menu_permissions[]" value="{{ $key }}" 
                           x-model="menuPermissions"
                           class="pkg-check">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <!-- CRUD Permissions -->
        <div class="pkg-card p-6 mb-6" x-show="!isExcluded">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Hak Akses CRUD per Modul</h3>
                <button type="button" @click="toggleAllCrud()" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    <span x-text="allCrudChecked ? 'Hapus Semua' : 'Pilih Semua'"></span>
                </button>
            </div>
            
            <div class="space-y-6">
                @foreach($availableCrud as $module => $operations)
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0 last:pb-0">
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-3 capitalize">{{ str_replace('_', ' ', $module) }}</h4>
                    <div class="flex flex-wrap gap-3">
                        @foreach($operations as $op)
                        <label class="flex items-center space-x-2 rounded bg-gray-50 px-3 py-1.5 cursor-pointer hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600">
                            <input type="checkbox" name="crud_permissions[{{ $module }}][]" value="{{ $op }}"
                                   x-model="crudPermissions['{{ $module }}']"
                                   class="pkg-check">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $crudOperationLabels[$op] ?? str($op)->replace('_', ' ')->headline() }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Submit -->
        <div class="pkg-page-actions justify-end">
            <a href="{{ route('pamong.index') }}" class="btn-secondary text-sm !px-4 !py-2">
                Batal
            </a>
            <button type="submit" class="btn-primary text-sm !px-4 !py-2">
                Simpan Perubahan
            </button>
        </div>
    </form>

    <!-- Copy Permissions Section -->
    @if($otherPamong->count() > 0)
    <div x-data="{ showCopy: false, selectedAll: false, selectedIds: [] }" class="mt-8">
        <button type="button" @click="showCopy = !showCopy" 
            class="w-full flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 hover:bg-amber-100 transition-colors dark:border-amber-800 dark:bg-amber-900/20 dark:hover:bg-amber-900/30">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">COPY</span>
                <div class="text-left">
                    <h3 class="font-semibold text-amber-900 dark:text-amber-200">Copy Akses ke Akun Lain</h3>
                    <p class="text-xs text-amber-700 dark:text-amber-400">Salin semua pengaturan hak akses {{ $pamong->username }} ke akun tim lainnya</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-amber-600 transition-transform" :class="showCopy ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="showCopy" x-collapse x-cloak class="mt-3">
            <form action="{{ route('pamong.permissions.copy', $pamong) }}" method="POST"
                  data-confirm="Yakin ingin menyalin hak akses {{ $pamong->username }} ke akun terpilih? Hak akses mereka akan ditimpa."
                  data-confirm-title="Salin hak akses akun"
                  data-confirm-button="Salin akses"
                  data-confirm-tone="warning">
                @csrf
                <div class="pkg-card border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- Header with Select All -->
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-750 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="selectedAll" 
                                @change="selectedIds = selectedAll ? @json($otherPamong->pluck('id')) : []"
                                class="pkg-check">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Semua</span>
                        </label>
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedIds.length + ' / {{ $otherPamong->count() }} dipilih'"></span>
                    </div>

                    <!-- Pamong List -->
                    <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($otherPamong as $other)
                        <label class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                            <input type="checkbox" name="target_ids[]" value="{{ $other->id }}"
                                x-model.number="selectedIds"
                                class="pkg-check">
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $other->username }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $other->email }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <!-- Submit -->
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" x-bind:disabled="selectedIds.length === 0"
                            class="btn-primary w-full text-sm !py-2.5 disabled:bg-gray-300 disabled:dark:bg-gray-600 disabled:cursor-not-allowed">
                            Salin Hak Akses ke <span x-text="selectedIds.length"></span> Akun Terpilih
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function permissionForm() {
    // Get existing data - ensure arrays are properly initialized
    const rawMenus = @json($pamong->pamongPermission?->menu_permissions);
    const rawCrud = @json($pamong->pamongPermission?->crud_permissions);
    const availableCrud = @json($availableCrud);
    const availableMenuKeys = @json(array_keys($availableMenus));
    const presets = @json($permissionPresets);
    
    // Ensure existingMenus is always an array
    const existingMenus = Array.isArray(rawMenus) ? rawMenus : [];
    const existingCrud = (rawCrud && typeof rawCrud === 'object') ? rawCrud : {};
    
    // Initialize crud permissions object with existing data
    const crudInit = {};
    Object.keys(availableCrud).forEach(module => {
        crudInit[module] = Array.isArray(existingCrud[module]) ? [...existingCrud[module]] : [];
    });
    
    console.log('Initializing permissions:', { existingMenus, crudInit, isExcluded: {{ $pamong->pamongPermission?->is_excluded ? 'true' : 'false' }} });
    
    return {
        isExcluded: {{ $pamong->pamongPermission?->is_excluded ? 'true' : 'false' }},
        menuPermissions: [...existingMenus],
        crudPermissions: crudInit,
        availableCrud: availableCrud,
        availableMenuKeys: availableMenuKeys,
        presets: presets,

        get currentPresetKey() {
            return Object.keys(this.presets).find((presetKey) => this.matchesPreset(presetKey)) || '';
        },
        
        get allMenusChecked() {
            return this.menuPermissions.length === this.availableMenuKeys.length;
        },
        
        get allCrudChecked() {
            let total = 0;
            let checked = 0;
            Object.keys(this.availableCrud).forEach(module => {
                total += this.availableCrud[module].length;
                checked += (this.crudPermissions[module] || []).length;
            });
            return total === checked;
        },
        
        toggleAllMenus() {
            if (this.allMenusChecked) {
                this.menuPermissions = [];
            } else {
                this.menuPermissions = [...this.availableMenuKeys];
            }
        },
        
        toggleAllCrud() {
            if (this.allCrudChecked) {
                Object.keys(this.availableCrud).forEach(module => {
                    this.crudPermissions[module] = [];
                });
            } else {
                Object.keys(this.availableCrud).forEach(module => {
                    this.crudPermissions[module] = [...this.availableCrud[module]];
                });
            }
        },

        applyPreset(presetKey) {
            const preset = this.presets[presetKey];
            if (!preset) {
                return;
            }

            this.menuPermissions = [...(preset.menu_permissions || [])];
            const nextCrud = {};
            Object.keys(this.availableCrud).forEach(module => {
                nextCrud[module] = Array.isArray(preset.crud_permissions?.[module]) ? [...preset.crud_permissions[module]] : [];
            });
            this.crudPermissions = nextCrud;
        },

        matchesPreset(presetKey) {
            const preset = this.presets[presetKey];
            if (!preset) {
                return false;
            }

            const selectedMenus = [...this.menuPermissions].sort();
            const presetMenus = [...(preset.menu_permissions || [])].sort();

            if (selectedMenus.length !== presetMenus.length) {
                return false;
            }

            if (selectedMenus.some((menu, index) => menu !== presetMenus[index])) {
                return false;
            }

            const modules = Array.from(new Set([
                ...Object.keys(this.availableCrud),
                ...Object.keys(this.crudPermissions || {}),
                ...Object.keys(preset.crud_permissions || {}),
            ]));

            return modules.every((module) => {
                const currentOps = [...(this.crudPermissions[module] || [])].sort();
                const presetOps = [...(preset.crud_permissions?.[module] || [])].sort();

                if (currentOps.length !== presetOps.length) {
                    return false;
                }

                return currentOps.every((operation, index) => operation === presetOps[index]);
            });
        }
    };
}
</script>
@endpush
@endsection

