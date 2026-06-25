@extends('layouts.app')

@section('title', 'Siaran Pesan')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="broadcastApp()">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('pamong.chat.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Siaran Pesan</h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400">Kirim pesan ke semua siswa yang Anda bimbing sekaligus</p>
    </div>

    <div class="pkg-panel-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-5">
            <div class="flex items-center gap-4 text-white">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold">Pesan Siaran</h2>
                    <p class="text-blue-100">{{ $siswaCount }} siswa akan menerima pesan ini</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="p-6">
            <form @submit.prevent="sendBroadcast">
                <!-- Message Input -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pesan
                    </label>
                    <textarea x-model="message" rows="4"
                              @keydown="handleKeyDown($event)"
                              class="w-full pkg-field focus:border-blue-500 focus:ring-blue-500 resize-none"
                              placeholder="Tulis pesan broadcast... (Shift+Enter untuk baris baru)"></textarea>
                </div>

                <!-- Image Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Lampiran Gambar (Opsional)
                    </label>
                    <div class="flex items-center gap-4">
                        <label class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <input type="file" accept="image/*" @change="handleImageSelect" class="hidden" x-ref="imageInput">
                            <svg class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Pilih Gambar
                        </label>
                        <button type="button" @click="clearImage" x-show="selectedImage" 
                                class="text-red-600 hover:text-red-800 text-sm">
                            Hapus
                        </button>
                    </div>
                    
                    <!-- Image Preview -->
                    <div x-show="selectedImage" class="mt-4">
                        <img :src="imagePreview" class="h-32 rounded-lg border border-gray-300 dark:border-gray-600">
                    </div>
                </div>

                <!-- Recipients Info -->
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-medium text-blue-800 dark:text-blue-200">Penerima Pesan</span>
                    </div>
                    <p class="text-blue-700 dark:text-blue-300 text-sm">
                        Pesan akan dikirim ke <strong>{{ $siswaCount }} siswa</strong> yang Anda bimbing
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between">
                    <a href="{{ route('pamong.chat.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Batal
                    </a>
                    <button type="submit" :disabled="(!message.trim() && !selectedImage) || loading"
                            class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <svg x-show="loading" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="loading ? 'Mengirim...' : 'Kirim Siaran'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccess" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showSuccess = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Siaran Berhasil!</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4" x-text="successMessage"></p>
                <a href="{{ route('pamong.chat.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Kembali ke Chat
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function broadcastApp() {
    return {
        message: '',
        selectedImage: null,
        imagePreview: null,
        loading: false,
        showSuccess: false,
        successMessage: '',

        handleKeyDown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.sendBroadcast();
            }
        },

        handleImageSelect(e) {
            const file = e.target.files[0];
            if (file) {
                this.selectedImage = file;
                this.imagePreview = URL.createObjectURL(file);
            }
        },

        clearImage() {
            this.selectedImage = null;
            this.imagePreview = null;
            this.$refs.imageInput.value = '';
        },

        async sendBroadcast() {
            if ((!this.message.trim() && !this.selectedImage) || this.loading) return;
            
            this.loading = true;
            
            try {
                const formData = new FormData();
                formData.append('message', this.message);
                if (this.selectedImage) {
                    formData.append('attachment', this.selectedImage);
                }

                const res = await fetch('{{ route('pamong.chat.broadcast.send') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    this.successMessage = data.message;
                    this.showSuccess = true;
                    this.message = '';
                    this.clearImage();
                } else {
                    window.showNotification(data.message || 'Gagal mengirim siaran', 'error');
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Terjadi kesalahan saat mengirim siaran', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection

