@extends('layouts.app')

@section('title', 'Edit Grup Chat')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="editGroupApp()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Edit Grup Chat</h1>
            <p class="pkg-page-subheading">Perbarui pengaturan grup {{ $chatGroup->name }}.</p>
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
            <select x-model="form.type" class="w-full pkg-field">
                <option value="custom">Custom</option>
                <option value="all_pamong">Semua Tim PKG</option>
                <option value="all_siswa">Semua Siswa</option>
                <option value="all_users">Semua Pengguna</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" x-model="form.is_active" class="pkg-check rounded">
            <label class="text-sm text-gray-700 dark:text-gray-300">Grup Aktif</label>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="addAllPamong" class="btn-secondary">Tambah Semua Tim PKG</button>
                <button type="button" @click="addAllSiswa" class="btn-secondary">Tambah Semua Siswa</button>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('chat-groups.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" :disabled="loading" class="btn-primary disabled:opacity-50">Simpan</button>
            </div>
        </div>
    </form>
</div>

<script>
function editGroupApp() {
    return {
        form: { name: '{{ $chatGroup->name }}', description: '{{ $chatGroup->description }}', type: '{{ $chatGroup->type }}', is_active: {{ $chatGroup->is_active ? 'true' : 'false' }} },
        loading: false,
        async submitForm() {
            this.loading = true;
            const res = await fetch('{{ route("chat-groups.update", $chatGroup) }}', { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(this.form) });
            const data = await res.json();
            if (data.success) { window.location.href = '{{ route("chat-groups.index") }}'; } else { window.showNotification(data.error || 'Gagal menyimpan', 'error'); }
            this.loading = false;
        },
        async addAllPamong() {
            const res = await fetch('{{ route("chat-groups.add-all-pamong", $chatGroup) }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            const data = await res.json();
            window.showNotification(data.message || 'Anggota tim ditambahkan', 'success');
        },
        async addAllSiswa() {
            const res = await fetch('{{ route("chat-groups.add-all-siswa", $chatGroup) }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            const data = await res.json();
            window.showNotification(data.message || 'Siswa ditambahkan', 'success');
        }
    }
}
</script>
@endsection
