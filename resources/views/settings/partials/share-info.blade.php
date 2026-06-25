{{-- Share Info Management --}}
<div class="pkg-panel p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Buat Info Baru</h3>
    <form action="{{ route('share-info.store') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul</label>
                <input type="text" name="title" required maxlength="100"
                    class="w-full px-3 py-2 pkg-field"
                    placeholder="Judul informasi">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe</label>
                <select name="type" class="w-full px-3 py-2 pkg-field">
                    <option value="info">Info</option>
                    <option value="warning">Peringatan</option>
                    <option value="success">Sukses</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pesan</label>
            <textarea name="message" required maxlength="500" rows="2"
                class="w-full px-3 py-2 pkg-field"
                placeholder="Isi pesan informasi..."></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Auto Dismiss (detik)</label>
                <input type="number" name="auto_dismiss_seconds" value="10" min="5" max="300"
                    class="w-full px-3 py-2 pkg-field">
                <p class="text-xs text-gray-500 mt-1">Info akan otomatis hilang setelah durasi ini</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target</label>
                <select name="target" class="w-full px-3 py-2 pkg-field">
                    <option value="all">Semua (Siswa, Ortu & Pamong)</option>
                    <option value="siswa">Siswa Saja</option>
                    <option value="ortu">Orang Tua Saja</option>
                    <option value="pamong">Pamong Saja</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn-primary text-sm">
            Buat Info
        </button>
    </form>
</div>

{{-- Existing Share Infos --}}
<div class="pkg-panel p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daftar Info</h3>
    @if(isset($shareInfos) && $shareInfos->count() > 0)
    <div class="space-y-3">
        @foreach($shareInfos as $info)
        <div x-data="{ editing: false }" class="pkg-card
            @if($info->type === 'warning') border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/10
            @elseif($info->type === 'success') border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10
            @else border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/10 @endif">
            
            {{-- View Mode --}}
            <div x-show="!editing" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $info->title }}</span>
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            @if($info->is_active) bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300
                            @else bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 @endif">
                            {{ $info->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $info->auto_dismiss_seconds }}s</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{!! nl2br(e($info->message)) !!}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $info->created_at->format('d M Y H:i') }} | {{ $info->creator->username ?? 'Admin' }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2 sm:ml-4">
                    <button @click="editing = true" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Edit">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <form action="{{ route('share-info.toggle', $info->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="{{ $info->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                            @if($info->is_active)
                                <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
                        </button>
                    </form>
                    <form action="{{ route('share-info.destroy', $info->id) }}" method="POST" data-confirm="Hapus info ini?" data-confirm-title="Hapus info" data-confirm-button="Hapus" data-confirm-tone="danger">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Edit Mode --}}
            <div x-show="editing" x-cloak class="p-4">
                <form action="{{ route('share-info.update', $info->id) }}" method="POST" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Judul</label>
                            <input type="text" name="title" value="{{ $info->title }}" required maxlength="100"
                                class="w-full px-3 py-2 text-sm pkg-field">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Tipe</label>
                            <select name="type" class="w-full px-3 py-2 text-sm pkg-field">
                                <option value="info" {{ $info->type === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="warning" {{ $info->type === 'warning' ? 'selected' : '' }}>Peringatan</option>
                                <option value="success" {{ $info->type === 'success' ? 'selected' : '' }}>Sukses</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Pesan</label>
                        <textarea name="message" required maxlength="500" rows="3"
                            class="w-full px-3 py-2 text-sm pkg-field">{{ $info->message }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Auto Dismiss (detik)</label>
                            <input type="number" name="auto_dismiss_seconds" value="{{ $info->auto_dismiss_seconds }}" min="5" max="300"
                                class="w-full px-3 py-2 text-sm pkg-field">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Target</label>
                            <select name="target" class="w-full px-3 py-2 text-sm pkg-field">
                                <option value="all" {{ $info->target === 'all' ? 'selected' : '' }}>Semua (Siswa, Ortu & Pamong)</option>
                                <option value="siswa" {{ $info->target === 'siswa' ? 'selected' : '' }}>Siswa Saja</option>
                                <option value="ortu" {{ $info->target === 'ortu' ? 'selected' : '' }}>Orang Tua Saja</option>
                                <option value="pamong" {{ $info->target === 'pamong' ? 'selected' : '' }}>Pamong Saja</option>
                            </select>
                        </div>
                    </div>
                    <div class="pkg-page-actions">
                        <button type="submit" class="btn-primary text-sm !px-4 !py-2">Simpan</button>
                        <button type="button" @click="editing = false" class="btn-secondary text-sm !px-4 !py-2">Batal</button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="pkg-empty-state">
        <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
        </svg>
        <p class="pkg-empty-title">Belum ada info dibuat</p>
        <p class="pkg-empty-copy">Tambahkan info baru untuk ditampilkan ke siswa, ortu, atau pamong.</p>
    </div>
    @endif
</div>
