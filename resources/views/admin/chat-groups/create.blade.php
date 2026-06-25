@extends('layouts.app')

@section('title', 'Buat Grup Chat')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="createGroupApp()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Buat Grup Chat Baru</h1>
            <p class="pkg-page-subheading">Tentukan nama, tipe, dan anggota grup chat.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('chat-groups.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <form @submit.prevent="submitForm" class="pkg-panel p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Grup</label>
            <input type="text" x-model="form.name" required class="w-full pkg-field">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
            <textarea x-model="form.description" rows="3" class="w-full pkg-field"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Grup</label>
            <select x-model="form.type" @change="handleTypeChange" class="w-full pkg-field">
                <option value="custom">Custom (pilih manual)</option>
                <option value="all_pamong">Semua Tim PKG</option>
                <option value="all_siswa">Semua Siswa</option>
                <option value="all_users">Semua Pengguna</option>
            </select>
        </div>

        <template x-if="form.type === 'custom'">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Anggota Tim PKG</label>
                    <div class="max-h-48 overflow-y-auto border rounded-lg p-2 dark:border-gray-600">
                        @foreach($users as $user)
                        <label class="flex items-center gap-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            <input type="checkbox" value="{{ $user->id }}" x-model="form.user_ids" class="pkg-check rounded">
                            <span class="text-gray-900 dark:text-white">{{ $user->username }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilih Siswa</label>
                    <div class="max-h-48 overflow-y-auto border rounded-lg p-2 dark:border-gray-600">
                        @foreach($siswaList as $siswa)
                        <label class="flex items-center gap-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                            <input type="checkbox" value="{{ $siswa->id }}" x-model="form.siswa_ids" class="pkg-check rounded">
                            <span class="text-gray-900 dark:text-white">{{ $siswa->nama }}</span>
                            <span class="text-xs text-gray-500">({{ $siswa->kelas->nama ?? '-' }})</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </template>

        <div class="flex justify-end gap-3">
            <a href="{{ route('chat-groups.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" :disabled="loading" class="btn-primary disabled:opacity-50">
                <span x-show="!loading">Buat Grup</span>
                <span x-show="loading">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>

<script>
function createGroupApp() {
    return {
        form: { name: '', description: '', type: 'custom', user_ids: [], siswa_ids: [] },
        loading: false,
        handleTypeChange() {
            if (this.form.type !== 'custom') {
                this.form.user_ids = [];
                this.form.siswa_ids = [];
            }
        },
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch('{{ route("chat-groups.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '{{ route("chat-groups.index") }}';
                } else {
                    window.showNotification(data.error || 'Gagal membuat grup', 'error');
                }
            } catch (e) { window.showNotification('Terjadi kesalahan', 'error'); }
            this.loading = false;
        }
    }
}
</script>
@endsection
