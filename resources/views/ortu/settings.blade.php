@extends('layouts.ortu')

@section('title', 'Pengaturan')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Pengaturan Akun</h1>
            <p class="pkg-page-subheading">Ubah username dan password portal orang tua.</p>
        </div>
    </div>

    <div class="pkg-panel p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ubah Username</h2>
        <form action="{{ route('ortu.settings.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Username Saat Ini</label>
                <input
                    type="text"
                    name="ortu_username"
                    value="{{ $siswa->ortu_username ?? $siswa->nis }}"
                    required
                    minlength="3"
                    maxlength="50"
                    class="w-full pkg-field"
                    placeholder="Masukkan username orang tua">
                @error('ortu_username')
                <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-primary">
                Simpan Username
            </button>
        </form>
    </div>

    <div class="pkg-panel p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ubah Password</h2>
        <form action="{{ route('ortu.settings.password') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Password Lama</label>
                <input
                    type="password"
                    name="current_password"
                    required
                    class="w-full pkg-field"
                    placeholder="Masukkan password lama">
                @error('current_password')
                <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Password Baru</label>
                <input
                    type="password"
                    name="new_password"
                    required
                    minlength="6"
                    class="w-full pkg-field"
                    placeholder="Masukkan password baru">
                @error('new_password')
                <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Konfirmasi Password Baru</label>
                <input
                    type="password"
                    name="new_password_confirmation"
                    required
                    minlength="6"
                    class="w-full pkg-field"
                    placeholder="Ulangi password baru">
            </div>
            <button type="submit" class="btn-primary">
                Ubah Password
            </button>
        </form>
    </div>

    <div class="pkg-panel p-4 bg-teal-50/90 dark:bg-teal-900/20 border-teal-200 dark:border-teal-800">
        <p class="text-sm text-teal-800 dark:text-teal-200">
            <strong>Catatan:</strong> Perubahan username dan password portal orang tua <strong>tidak mempengaruhi</strong> akun siswa. Jika Anda lupa password, hubungi admin untuk reset.
        </p>
    </div>
</div>
@endsection
