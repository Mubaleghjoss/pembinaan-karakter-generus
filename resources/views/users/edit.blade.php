@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit User</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Perbarui akun admin, pamong, atau pengurus PKG {{ $user->username }}</p>
    </div>

    <div class="pkg-card p-6">
        <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Avatar Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Foto Profil
                </label>
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img id="avatar-preview" src="{{ $user->avatar_url ?? '/images/default-avatar.svg' }}" alt="Avatar Preview" 
                            class="w-20 h-20 rounded-full object-cover border-2 border-gray-300 dark:border-gray-600">
                    </div>
                    <div>
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/jpg"
                            class="hidden" onchange="previewAvatar(this)">
                        <label for="avatar" class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Ganti Foto
                        </label>
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG. Max 2MB</p>
                    </div>
                </div>
                @error('avatar')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nama Lengkap
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                    class="w-full px-4 py-2 pkg-field @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}"
                    class="w-full px-4 py-2 pkg-field @error('username') border-red-500 @enderror"
                    required>
                @error('username')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                    class="w-full px-4 py-2 pkg-field @error('email') border-red-500 @enderror"
                    required>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Telepon
                </label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full px-4 py-2 pkg-field">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Password Baru <span class="text-gray-400 text-xs">(kosongkan jika tidak ingin mengubah)</span>
                </label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 pkg-field @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Konfirmasi Password Baru
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full px-4 py-2 pkg-field">
            </div>

            <div>
                <label for="role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role_id" id="role_id"
                    class="w-full px-4 py-2 pkg-field @error('role_id') border-red-500 @enderror"
                    required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name ?? $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" id="status"
                    class="w-full px-4 py-2 pkg-field"
                    required>
                    <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Struktur Tim PKG</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Opsional. Dipakai untuk bidang organisasi, jabatan, dan bagan struktur frontend.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="organizational_team_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bidang / Tim
                        </label>
                        <select name="organizational_team_id" id="organizational_team_id" class="w-full pkg-field @error('organizational_team_id') border-red-500 @enderror">
                            <option value="">Belum ditempatkan</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ old('organizational_team_id', $user->organizational_team_id) == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('organizational_team_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="organizational_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Jabatan Struktur
                        </label>
                        <input type="text" name="organizational_title" id="organizational_title" value="{{ old('organizational_title', $user->organizational_title) }}"
                            placeholder="Contoh: Koordinator Publikasi"
                            class="w-full pkg-field @error('organizational_title') border-red-500 @enderror">
                        @error('organizational_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="organizational_sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Urutan Struktur
                        </label>
                        <input type="number" min="0" max="9999" name="organizational_sort_order" id="organizational_sort_order" value="{{ old('organizational_sort_order', $user->organizational_sort_order ?? 0) }}"
                            class="w-full pkg-field @error('organizational_sort_order') border-red-500 @enderror">
                        @error('organizational_sort_order')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if($teams->isEmpty())
                    <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                        Belum ada bidang tim. Buat dulu di menu Tim PKG tab Bidang agar akun bisa ditempatkan ke struktur organisasi.
                    </p>
                @endif
            </div>

            <div class="flex items-center justify-end gap-4 pt-4">
                <a href="{{ route('settings.index', ['tab' => 'user']) }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection

