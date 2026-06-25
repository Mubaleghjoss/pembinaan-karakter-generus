@php
    $selectedTeamId = old('organizational_team_id', request('target_team', $editingMember?->organizational_team_id));
    $selectedUserId = old('user_id', $editingMember?->id);
    $teamColor = old('color_hex', $editingTeam?->color_hex ?: '#16A34A');

    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $teamColor)) {
        $teamColor = '#16A34A';
    }

    $memberOptionsPayload = $assignablePamong->map(fn ($member) => [
        'id' => (string) $member->id,
        'name' => $member->name ?: $member->username,
        'username' => $member->username,
        'role' => $member->operationalRoleLabel(),
        'team' => $member->organizationalTeam?->name,
        'title' => $member->organizational_title,
        'status' => $member->status,
    ])->values();
@endphp

<div class="space-y-6">
    <div class="pkg-filter-bar">
        <div class="pkg-filter-grid xl:grid-cols-[1.1fr_1.1fr_0.8fr]">
            <div class="pkg-card-soft border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                    {{ $editingTeam ? 'Edit Bidang Tim' : 'Tambah Bidang Tim' }}
                </h3>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Data ini dipakai untuk pengelompokan akun Pengurus PKG, jabatan struktur, dan bagan organisasi frontend.
                </p>

                <form
                    action="{{ $editingTeam ? route('pamong.teams.update', $editingTeam) : route('pamong.teams.store') }}"
                    method="POST"
                    class="mt-4 space-y-4"
                    x-data="{
                        colorText: @js($teamColor),
                        get previewColor() {
                            return /^#[0-9A-Fa-f]{6}$/.test(this.colorText || '') ? this.colorText : '#2563EB';
                        },
                        normalizeColor() {
                            let value = (this.colorText || '').trim();
                            if (value && !value.startsWith('#')) value = '#' + value;
                            this.colorText = value.toUpperCase();
                        }
                    }"
                >
                    @csrf
                    @if($editingTeam)
                        @method('PUT')
                    @endif

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Bidang</label>
                        <input type="text" name="name" value="{{ old('name', $editingTeam?->name) }}" class="w-full pkg-field" placeholder="Contoh: Publikasi dan Dokumentasi" required>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Singkatan</label>
                            <input type="text" name="short_name" value="{{ old('short_name', $editingTeam?->short_name) }}" class="w-full pkg-field" placeholder="Contoh: Publikasi">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Warna Badge</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    :value="previewColor"
                                    @input="colorText = $event.target.value.toUpperCase()"
                                    class="h-11 w-14 flex-shrink-0 cursor-pointer rounded-lg border border-gray-300 bg-white p-1 dark:border-gray-700 dark:bg-gray-900"
                                    aria-label="Pilih warna badge"
                                >
                                <input
                                    type="text"
                                    name="color_hex"
                                    x-model="colorText"
                                    @blur="normalizeColor()"
                                    maxlength="7"
                                    class="w-full pkg-field font-mono"
                                    placeholder="#16A34A"
                                >
                                <span
                                    class="inline-flex h-11 min-w-11 flex-shrink-0 items-center justify-center rounded-lg px-3 text-xs font-bold text-white shadow-sm"
                                    :style="`background-color: ${previewColor}`"
                                >
                                    <span>{{ strtoupper(substr(old('short_name', $editingTeam?->short_name ?: $editingTeam?->name ?: 'PK'), 0, 2)) }}</span>
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Pilih warna atau ketik kode hex. Preview badge akan langsung berubah.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Urutan</label>
                            <input type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', $editingTeam?->sort_order ?? 0) }}" class="w-full pkg-field">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" class="pkg-check" {{ old('is_active', $editingTeam?->is_active ?? true) ? 'checked' : '' }}>
                                Bidang aktif
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                        <textarea name="description" rows="3" class="w-full pkg-field" placeholder="Ringkasan fungsi bidang ini">{{ old('description', $editingTeam?->description) }}</textarea>
                    </div>

                    <div class="pkg-page-actions justify-end">
                        @if($editingTeam)
                            <a href="{{ route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang']) }}" class="btn-secondary !px-4 !py-2 text-sm">Batal Edit</a>
                        @endif
                        <button type="submit" class="btn-primary !px-4 !py-2 text-sm">
                            {{ $editingTeam ? 'Simpan Perubahan' : 'Tambah Bidang' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="pkg-card-soft border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                    {{ $editingMember ? 'Ubah Penempatan Anggota' : 'Tempatkan Akun ke Bidang' }}
                </h3>
                <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                    Pilih akun pamong atau pengurus PKG, lalu tentukan bidang, jabatan, dan urutan tampilnya.
                </p>

                <form
                    action="{{ route('pamong.team-members.save') }}"
                    method="POST"
                    class="mt-4 space-y-4"
                    x-data="{
                        selectedUserId: @js((string) $selectedUserId),
                        members: @js($memberOptionsPayload),
                        get selectedMember() {
                            return this.members.find((member) => String(member.id) === String(this.selectedUserId));
                        }
                    }"
                >
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Akun Anggota</label>
                        <select name="user_id" x-model="selectedUserId" class="w-full pkg-field" required>
                            <option value="">Pilih akun</option>
                            @foreach($assignablePamong as $memberOption)
                            <option value="{{ $memberOption->id }}" {{ (string) $selectedUserId === (string) $memberOption->id ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Str::limit(($memberOption->name ?: $memberOption->username) . ' - ' . $memberOption->operationalRoleLabel(), 72) }}
                                </option>
                            @endforeach
                        </select>
                        <template x-if="selectedMember">
                            <div class="mt-3 rounded-xl border border-emerald-200 bg-white/85 p-3 dark:border-emerald-800 dark:bg-gray-900/50">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white" x-text="selectedMember.name?.charAt(0)?.toUpperCase()"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedMember.name"></div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                                            <span>&#64;<span x-text="selectedMember.username"></span></span>
                                            <span class="text-gray-400">|</span>
                                            <span x-text="selectedMember.role"></span>
                                            <span
                                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                :class="selectedMember.status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
                                                x-text="selectedMember.status === 'active' ? 'Aktif' : 'Nonaktif'"
                                            ></span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="selectedMember.team || 'Belum ada bidang'"></span>
                                            <span x-show="selectedMember.title"> - <span x-text="selectedMember.title"></span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        @error('user_id')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bidang Tujuan</label>
                        <select name="organizational_team_id" class="w-full pkg-field">
                            <option value="">Tanpa bidang</option>
                            @foreach($teams as $teamOption)
                                <option value="{{ $teamOption->id }}" {{ (string) $selectedTeamId === (string) $teamOption->id ? 'selected' : '' }}>
                                    {{ $teamOption->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Pilih "Tanpa bidang" jika akun ingin dilepas dari struktur bidang.
                        </p>
                        @error('organizational_team_id')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Jabatan</label>
                            <input type="text" name="organizational_title" value="{{ old('organizational_title', $editingMember?->organizational_title) }}" class="w-full pkg-field" placeholder="Contoh: Koordinator, Pamong, Sekretaris">
                            @error('organizational_title')
                                <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Urutan Tampil</label>
                            <input type="number" min="0" max="9999" name="organizational_sort_order" value="{{ old('organizational_sort_order', $editingMember?->organizational_sort_order ?? 0) }}" class="w-full pkg-field">
                            @error('organizational_sort_order')
                                <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if($editingMember)
                        <div class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-2 text-xs text-emerald-700 dark:border-emerald-800 dark:bg-gray-900/40 dark:text-emerald-200">
                            Sedang mengatur akun <span class="font-semibold">{{ $editingMember->name ?: $editingMember->username }}</span>
                            dengan bidang saat ini <span class="font-semibold">{{ $editingMember->organizationalTeam?->name ?? 'Tanpa bidang' }}</span>.
                        </div>
                    @endif

                    <div class="pkg-page-actions justify-end">
                        @if($editingMember || request('target_team'))
                            <a href="{{ route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang']) }}" class="btn-secondary !px-4 !py-2 text-sm">Batal</a>
                        @endif
                        <button type="submit" class="btn-success !px-4 !py-2 text-sm">
                            {{ $editingMember ? 'Simpan Penempatan' : 'Tambahkan ke Bidang' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                <div class="pkg-card-soft p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Bidang</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $teams->count() }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bidang Aktif</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $teams->where('is_active', true)->count() }}</p>
                </div>
                <div class="pkg-card-soft p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Akun Pengurus PKG</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $teams->sum('pkg_manager_count') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="pkg-card overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Daftar Bidang Organisasi</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sekarang penambahan anggota bisa langsung dilakukan dari tab ini melalui panel "Tempatkan Akun ke Bidang".</p>
        </div>

        @if($teams->isEmpty())
            <div class="pkg-empty-state">
                <div class="pkg-empty-icon">ORG</div>
                <h4 class="pkg-empty-title">Belum ada bidang tim</h4>
                <p class="pkg-empty-copy">Tambahkan bidang pertama untuk mulai menyusun struktur Pengurus PKG.</p>
            </div>
        @else
            <div class="overflow-x-auto pkg-mobile-table">
                <table class="min-w-[980px] divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Bidang</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Anggota</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Deskripsi</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($teams as $team)
                            <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                <td data-label="Bidang" class="px-4 py-4 pkg-mobile-main">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-xs font-semibold text-white"
                                              style="background-color: {{ $team->color_hex ?: '#2563EB' }}">
                                            {{ strtoupper(substr($team->short_name ?: $team->name, 0, 2)) }}
                                        </span>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $team->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $team->short_name ?: 'Tanpa singkatan' }} | Urutan {{ $team->sort_order }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Status" class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $team->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $team->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td data-label="Anggota" class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div>{{ $team->total_users_count }} akun</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $team->pkg_manager_count }} pengurus PKG | {{ $team->active_users_count }} aktif</div>

                                    <div class="mt-3 space-y-2">
                                        @forelse($team->users as $member)
                                            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ $member->name ?: $member->username }}
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ '@' . $member->username }} | {{ $member->operationalRoleLabel() }}
                                                        </div>
                                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $member->organizational_title ?: 'Anggota bidang' }} | Urutan {{ $member->organizational_sort_order ?? 0 }}
                                                        </div>
                                                    </div>
                                                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $member->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                                        {{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <a href="{{ route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang', 'edit_member' => $member->id]) }}" class="btn-secondary !px-3 !py-2 text-xs">
                                                        Atur
                                                    </a>
                                                    <form action="{{ route('pamong.team-members.save') }}" method="POST" onsubmit="return confirm('Lepas {{ $member->username }} dari bidang {{ $team->name }}?');">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $member->id }}">
                                                        <input type="hidden" name="organizational_team_id" value="">
                                                        <input type="hidden" name="organizational_title" value="">
                                                        <input type="hidden" name="organizational_sort_order" value="0">
                                                        <button type="submit" class="btn-danger !px-3 !py-2 text-xs">
                                                            Lepas
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                                Belum ada anggota di bidang ini.
                                            </div>
                                        @endforelse
                                    </div>
                                </td>
                                <td data-label="Deskripsi" class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $team->description ?: 'Belum ada deskripsi.' }}
                                </td>
                                <td data-label="Aksi" class="px-4 py-4 pkg-mobile-actions">
                                    <div class="flex flex-col items-end gap-2">
                                        <a href="{{ route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang', 'target_team' => $team->id]) }}" class="btn-success !px-3 !py-2 text-xs">
                                            Tambah Anggota
                                        </a>
                                        <a href="{{ route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang', 'edit_team' => $team->id]) }}" class="btn-secondary !px-3 !py-2 text-xs">
                                            Edit
                                        </a>
                                        <form action="{{ route('pamong.teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Hapus bidang {{ $team->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger !px-3 !py-2 text-xs" {{ $team->total_users_count > 0 ? 'disabled title="Masih dipakai akun tim"' : '' }}>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
