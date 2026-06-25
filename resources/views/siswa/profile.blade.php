@extends('layouts.siswa')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Profil Saya</h1>
            <p class="pkg-page-subheading">Kelola informasi profil dan foto kamu.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="pkg-panel p-4 mb-6 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800" role="alert">
        <span class="block text-green-700 dark:text-green-200">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="pkg-panel p-4 mb-6 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800" role="alert">
        <span class="block text-red-700 dark:text-red-200">{{ session('error') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <div class="pkg-panel-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Foto Profil</h2>

                <div class="flex flex-col items-center mb-6">
                    <div class="relative">
                        @if($siswa->foto_path)
                            <img src="{{ asset('storage/' . $siswa->foto_path) }}" alt="Foto {{ $siswa->nama }}" class="w-40 h-40 rounded-full object-cover border-4 border-blue-500 shadow-lg" id="preview-photo">
                        @else
                            <div class="w-40 h-40 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center border-4 border-blue-500 shadow-lg" id="preview-placeholder">
                                <span class="text-white text-5xl font-bold">{{ strtoupper(substr($siswa->nama, 0, 1)) }}</span>
                            </div>
                            <img src="" alt="" class="w-40 h-40 rounded-full object-cover border-4 border-blue-500 shadow-lg hidden" id="preview-photo">
                        @endif

                        <label for="foto-input" class="absolute bottom-2 right-2 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-full cursor-pointer shadow-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </label>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">NIS: {{ $siswa->nis }}</p>
                </div>

                <form action="{{ route('siswa.profile.update-photo') }}" method="POST" enctype="multipart/form-data" id="photo-form">
                    @csrf
                    <input type="file" name="foto" id="foto-input" accept="image/jpeg,image/png,image/jpg" class="hidden" onchange="previewImage(this)">

                    @error('foto')
                        <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                    @enderror

                    <div id="upload-actions" class="hidden space-y-3">
                        <button type="submit" class="btn-primary w-full">Simpan Foto</button>
                        <button type="button" onclick="cancelUpload()" class="btn-secondary w-full">Batal</button>
                    </div>
                </form>

                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <p>Format: JPG, JPEG, PNG</p>
                    <p>Ukuran maksimal: 2MB</p>
                    <p>Foto akan dipotong menjadi persegi</p>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('siswa.kartu') }}" class="btn-secondary w-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                        Lihat Kartu ID
                    </a>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="pkg-panel-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Edit Data Profil</h2>

                <form action="{{ route('siswa.profile.update') }}" method="POST">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nama" id="nama" value="{{ old('nama', $siswa->nama) }}" required class="w-full pkg-field">
                            @error('nama')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="jenis_kelamin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="w-full pkg-field">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="L" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('jenis_kelamin')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir', $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : '') }}" class="w-full pkg-field">
                                @error('tanggal_lahir')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="target_grade_override" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level Kelas PKG</label>
                            <select name="target_grade_override" id="target_grade_override" class="w-full pkg-field">
                                <option value="">Otomatis dari tanggal lahir{{ $siswa->target_grade_label ? ' (' . $siswa->target_grade_label . ')' : '' }}</option>
                                @foreach($targetGradeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('target_grade_override', $siswa->target_grade_override) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan untuk mengikuti perhitungan usia pada 1 Juli tahun berjalan.</p>
                            @error('target_grade_override')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="kelompok" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kelompok</label>
                            <select name="kelompok" id="kelompok" class="w-full pkg-field">
                                <option value="">Pilih Kelompok</option>
                                @foreach($kelompokOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('kelompok', $siswa->kelompok) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('kelompok')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. HP Pribadi</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $siswa->phone) }}" class="w-full pkg-field" placeholder="Contoh: 081234567890">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-4">Data Wali atau Orang Tua</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="nama_wali" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Wali</label>
                                    <input type="text" name="nama_wali" id="nama_wali" value="{{ old('nama_wali', $siswa->nama_wali) }}" class="w-full pkg-field" placeholder="Nama orang tua atau wali">
                                    @error('nama_wali')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="phone_wali" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. HP Wali</label>
                                    <input type="tel" name="phone_wali" id="phone_wali" value="{{ old('phone_wali', $siswa->phone_wali) }}" class="w-full pkg-field" placeholder="Contoh: 081234567890">
                                    @error('phone_wali')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-4">Informasi Kelas</h3>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">NIS atau Username</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $siswa->nis }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelas</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $siswa->kelas->nama ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Level PKG Efektif</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $siswa->target_grade_label ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="btn-primary w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="pkg-panel-lg p-6 mt-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Ubah Username dan Password</h2>

                <form action="{{ route('siswa.profile.update-account') }}" method="POST">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Username (NIS) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="username" id="username" value="{{ old('username', $siswa->nis) }}" required class="w-full pkg-field">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Username digunakan untuk login.</p>
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Password Saat Ini <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="current_password" id="current_password" required class="w-full pkg-field" placeholder="Masukkan password saat ini">
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Password Baru <span class="text-gray-400">(opsional)</span>
                            </label>
                            <input type="password" name="new_password" id="new_password" class="w-full pkg-field" placeholder="Kosongkan jika tidak ingin mengubah password">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimal 4 karakter.</p>
                            @error('new_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="w-full pkg-field" placeholder="Ulangi password baru">
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="btn-secondary w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Simpan Akun
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let originalPhotoSrc = null;
let hasPhoto = {{ $siswa->foto_path ? 'true' : 'false' }};

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const previewPhoto = document.getElementById('preview-photo');
            const previewPlaceholder = document.getElementById('preview-placeholder');

            if (!originalPhotoSrc && hasPhoto) {
                originalPhotoSrc = previewPhoto.src;
            }

            previewPhoto.src = e.target.result;
            previewPhoto.classList.remove('hidden');
            if (previewPlaceholder) {
                previewPlaceholder.classList.add('hidden');
            }

            document.getElementById('upload-actions').classList.remove('hidden');
        };

        reader.readAsDataURL(input.files[0]);
    }
}

function cancelUpload() {
    const previewPhoto = document.getElementById('preview-photo');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const fotoInput = document.getElementById('foto-input');

    fotoInput.value = '';

    if (hasPhoto && originalPhotoSrc) {
        previewPhoto.src = originalPhotoSrc;
    } else {
        previewPhoto.classList.add('hidden');
        if (previewPlaceholder) {
            previewPlaceholder.classList.remove('hidden');
        }
    }

    document.getElementById('upload-actions').classList.add('hidden');
}
</script>
@endsection
