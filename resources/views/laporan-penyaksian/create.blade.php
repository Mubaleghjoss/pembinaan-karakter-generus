<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lapor PKG - Formulir Penyaksian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .form-section {
            background: white;
            border-left: 4px solid #10B981;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" x-data="laporForm()">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-2xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <a href="{{ route('public.index') }}" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="font-medium">Kembali ke Beranda</span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="{{ route('public.scanner') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Scan Presensi
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-emerald-600 text-white rounded-t-lg p-6 mb-0">
            <h1 class="text-2xl font-bold">Formulir Laporan Penyaksian PKG</h1>
            <p class="mt-2 text-emerald-100">Laporan ini bertujuan untuk membantu proses pembinaan generus, bukan untuk menyudutkan.</p>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-b-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
        @endif

        <form action="{{ route('laporan-penyaksian.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Nama Pelapor -->
            <div class="form-section p-6">
                <label class="block text-gray-800 font-medium mb-2">
                    Nama Anda (Pelapor) <span class="text-red-500">*</span>
                </label>
                <p class="text-sm text-gray-500 mb-3">Mohon tuliskan nama Anda dengan jelas. Data ini hanya akan digunakan untuk keperluan pembinaan dan <strong>tidak akan disebarluaskan</strong>.</p>
                <input type="text" name="nama_pelapor" value="{{ old('nama_pelapor') }}" required
                       class="w-full border-b-2 border-gray-300 focus:border-emerald-500 outline-none py-2 bg-transparent"
                       placeholder="Jawaban Anda">
                @error('nama_pelapor')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nama Generus atau Pamong -->
            <div class="form-section p-6">
                <label class="block text-gray-800 font-medium mb-2">
                    Nama Generus atau Pamong yang Ingin Anda Sampaikan Catatan Perilakunya <span class="text-red-500">*</span>
                </label>
                <p class="text-sm text-gray-500 mb-3">Silakan tuliskan nama <strong>generus (peserta PKG)</strong> atau <strong>pamong</strong> yang menurut pengamatan Anda menunjukkan <strong>sikap atau perilaku yang belum mencerminkan 29 karakter luhur</strong>.</p>
                
                <!-- Search Siswa & Pamong -->
                <div class="relative mb-3">
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchGenerusList()"
                           class="w-full border-2 border-gray-300 focus:border-emerald-500 rounded-lg px-4 py-2"
                           placeholder="Cari nama generus (peserta) atau pamong...">
                    
                    <!-- Dropdown hasil pencarian -->
                    <div x-show="showDropdown && searchResults.length > 0" 
                         @click.outside="showDropdown = false"
                         class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="item in searchResults" :key="item.id">
                            <div @click="selectGenerus(item)" 
                                 class="flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b last:border-0">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden bg-gray-200 mr-3">
                                    <template x-if="item.foto_url">
                                        <img :src="item.foto_url" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!item.foto_url">
                                        <div class="w-full h-full flex items-center justify-center text-white font-bold"
                                             :class="item.type === 'pamong' ? 'bg-blue-500' : 'bg-emerald-500'" 
                                             x-text="item.nama.charAt(0)"></div>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-800" x-text="item.nama"></div>
                                    <div class="text-sm text-gray-500 line-clamp-1" x-text="item.kelas"></div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full flex-shrink-0"
                                      :class="item.type === 'pamong' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                                      x-text="item.label"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Selected Generus Display -->
                <div x-show="selectedGenerus" class="flex items-start p-3 rounded-lg mb-3"
                     :class="selectedGenerus?.type === 'pamong' ? 'bg-blue-50' : 'bg-emerald-50'">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full overflow-hidden bg-gray-200 mr-3">
                        <template x-if="selectedGenerus?.foto_url">
                            <img :src="selectedGenerus.foto_url" class="w-full h-full object-cover">
                        </template>
                        <template x-if="selectedGenerus && !selectedGenerus.foto_url">
                            <div class="w-full h-full flex items-center justify-center text-white font-bold text-lg"
                                 :class="selectedGenerus?.type === 'pamong' ? 'bg-blue-500' : 'bg-emerald-500'" 
                                 x-text="selectedGenerus?.nama?.charAt(0)"></div>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-800" x-text="selectedGenerus?.nama"></div>
                        <div class="text-sm text-gray-500 line-clamp-2" x-text="selectedGenerus?.kelas"></div>
                    </div>
                    <div class="flex items-start gap-2 flex-shrink-0">
                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                              :class="selectedGenerus?.type === 'pamong' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                              x-text="selectedGenerus?.label"></span>
                        <button type="button" @click="clearGenerus()" class="text-gray-400 hover:text-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="siswa_id" :value="selectedGenerus?.type === 'siswa' ? selectedGenerus?.id : ''">
                <input type="hidden" name="pamong_id" :value="selectedGenerus?.type === 'pamong' ? selectedGenerus?.id : ''">
                <input type="text" name="nama_generus" x-model="namaGenerus" required
                       class="w-full border-b-2 border-gray-300 focus:border-emerald-500 outline-none py-2 bg-transparent"
                       placeholder="Atau ketik nama manual jika tidak ditemukan">
                @error('nama_generus')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Karakter Belum Optimal -->
            <div class="form-section p-6">
                <label class="block text-gray-800 font-medium mb-2">
                    Karakter yang Menurut Anda Belum Ditunjukkan Secara Optimal <span class="text-red-500">*</span>
                </label>
                <p class="text-sm text-gray-500 mb-3">Silakan tuliskan kejadian / karakter apa yang menurut Anda <strong>belum ditunjukkan dengan baik</strong> oleh generus tersebut pada situasi tertentu.</p>
                <p class="text-sm text-gray-400 mb-3">Contoh: Masuk rumah orang lain tanpa ketuk pintu, langsung masuk</p>
                <textarea name="karakter_belum_optimal" rows="4" required
                          class="w-full border-2 border-gray-300 focus:border-emerald-500 rounded-lg px-4 py-2"
                          placeholder="Jawaban Anda">{{ old('karakter_belum_optimal') }}</textarea>
                @error('karakter_belum_optimal')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tanggal Kejadian -->
            <div class="form-section p-6">
                <label class="block text-gray-800 font-medium mb-2">
                    Tanggal Kejadian <span class="text-red-500">*</span>
                </label>
                <p class="text-sm text-gray-500 mb-3">Kapan kejadian tersebut terjadi?</p>
                <input type="date" name="tanggal_kejadian" value="{{ old('tanggal_kejadian', date('Y-m-d')) }}" required
                       class="w-full border-2 border-gray-300 focus:border-emerald-500 rounded-lg px-4 py-2">
                @error('tanggal_kejadian')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Pertanyaan Opsional Section -->
            <div class="bg-gray-50 rounded-lg p-4 mt-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pertanyaan Opsional
                </h3>
                <p class="text-sm text-gray-500 mb-4">Kosongkan jika tidak perlu</p>
            </div>

            <!-- Email & Phone (Optional) -->
            <div class="form-section p-6 border-l-gray-300">
                <label class="block text-gray-800 font-medium mb-2">Email / No. HP</label>
                <p class="text-sm text-gray-500 mb-3">Jika Anda ingin dihubungi untuk konfirmasi lebih lanjut. <span class="text-gray-400 italic">Kosongkan jika tidak perlu.</span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="email" name="email_pelapor" value="{{ old('email_pelapor') }}"
                           class="w-full border-b-2 border-gray-300 focus:border-emerald-500 outline-none py-2 bg-transparent"
                           placeholder="Email (opsional)">
                    <input type="text" name="phone_pelapor" value="{{ old('phone_pelapor') }}"
                           class="w-full border-b-2 border-gray-300 focus:border-emerald-500 outline-none py-2 bg-transparent"
                           placeholder="No. HP (opsional)">
                </div>
            </div>

            <!-- Deskripsi Tambahan -->
            <div class="form-section p-6 border-l-gray-300">
                <label class="block text-gray-800 font-medium mb-2">Deskripsi Tambahan</label>
                <p class="text-sm text-gray-500 mb-3">Ceritakan lebih detail jika diperlukan. <span class="text-gray-400 italic">Kosongkan jika tidak perlu.</span></p>
                <textarea name="deskripsi_kejadian" rows="3"
                          class="w-full border-2 border-gray-300 focus:border-emerald-500 rounded-lg px-4 py-2"
                          placeholder="Jawaban Anda (opsional)">{{ old('deskripsi_kejadian') }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-4">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-8 py-3 rounded-lg transition">
                    Kirim
                </button>
                <button type="reset" @click="resetForm()" class="text-gray-500 hover:text-gray-700">
                    Kosongkan formulir
                </button>
            </div>
        </form>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8">
            @if(isset($theme))
            <p class="font-medium text-gray-600">{{ $theme->footer_organization ?? 'SMA AFBS' }}</p>
            <p>{{ $theme->footer_text ?? 'Pembinaan Karakter Generus' }}</p>
            <p class="mt-2">© {{ date('Y') }} {{ $theme->app_name ?? 'PKG' }}</p>
            @else
            <p>© {{ date('Y') }} PKG - Pusat Kegiatan Guru</p>
            @endif
        </div>
    </div>

    <script>
    function laporForm() {
        return {
            searchQuery: '',
            searchResults: [],
            showDropdown: false,
            selectedGenerus: null,
            namaGenerus: '{{ old("nama_generus") }}',

            async searchGenerusList() {
                if (this.searchQuery.length < 2) {
                    this.searchResults = [];
                    this.showDropdown = false;
                    return;
                }

                try {
                    const response = await fetch(`{{ route('laporan-penyaksian.generus-list') }}?q=${encodeURIComponent(this.searchQuery)}`);
                    this.searchResults = await response.json();
                    this.showDropdown = true;
                } catch (error) {
                    console.error('Error searching:', error);
                }
            },

            selectGenerus(item) {
                this.selectedGenerus = item;
                this.namaGenerus = item.nama;
                this.searchQuery = '';
                this.showDropdown = false;
            },

            clearGenerus() {
                this.selectedGenerus = null;
                this.namaGenerus = '';
            },

            resetForm() {
                this.selectedGenerus = null;
                this.namaGenerus = '';
                this.searchQuery = '';
            }
        };
    }
    </script>
</body>
</html>
