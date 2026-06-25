@extends('layouts.app')

@section('title', 'Siaran Admin')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="adminBroadcastApp()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Siaran Admin</h1>
            <p class="pkg-page-subheading">Kirim japri massal atau broadcast ke grup pamong langsung dari satu halaman.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('chat-groups.index') }}" class="btn-secondary">
                Kelola Grup Chat
            </a>
            <a href="{{ route('chat-groups.create') }}" class="btn-primary">
                Buat Grup Chat
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Siaran ke Siswa -->
        <div class="pkg-panel-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-5">
                <div class="flex items-center gap-4 text-white">
                    <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">Siaran ke Siswa</h2>
                        <p class="text-green-100">{{ $siswaCount }} siswa aktif</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <form @submit.prevent="sendToSiswa">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Pesan untuk Siswa
                        </label>
                        <textarea x-model="siswaMessage" rows="4"
                                  @keydown="handleKeyDown($event, 'siswa')"
                                  class="w-full pkg-field resize-none"
                                  placeholder="Tulis pesan untuk semua siswa..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Lampiran Gambar (Opsional)
                        </label>
                        <div class="flex items-center gap-3">
                            <label class="btn-secondary cursor-pointer text-sm !px-3 !py-2">
                                <input type="file" accept="image/*" @change="handleImageSelect('siswa', $event)" class="hidden" x-ref="siswaImageInput">
                                <svg class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Pilih Gambar
                            </label>
                            <button type="button" @click="clearImage('siswa')" x-show="siswaImage" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                        </div>
                        <div x-show="siswaImage" class="mt-3">
                            <img :src="siswaImagePreview" class="h-20 rounded-lg border border-gray-300 dark:border-gray-600">
                        </div>
                    </div>

                    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-green-700 dark:text-green-300 text-sm">
                                <strong>{{ $siswaCount }} siswa</strong> akan menerima pesan ini
                            </p>
                        </div>
                    </div>

                    <button type="submit" :disabled="(!siswaMessage.trim() && !siswaImage) || siswaLoading"
                            class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg x-show="!siswaLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <svg x-show="siswaLoading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="siswaLoading ? 'Mengirim...' : 'Kirim ke Semua Siswa'"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Japri ke Semua User -->
        <div class="pkg-panel-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-5">
                <div class="flex items-center gap-4 text-white">
                    <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">Japri ke Semua User</h2>
                        <p class="text-purple-100">{{ $userCount }} user aktif</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <form @submit.prevent="sendToUsers">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Pesan untuk Semua User
                        </label>
                        <textarea x-model="userMessage" rows="4"
                                  @keydown="handleKeyDown($event, 'users')"
                                  class="w-full pkg-field resize-none"
                                  placeholder="Tulis pesan untuk semua user..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Lampiran Gambar (Opsional)
                        </label>
                        <div class="flex items-center gap-3">
                            <label class="btn-secondary cursor-pointer text-sm !px-3 !py-2">
                                <input type="file" accept="image/*" @change="handleImageSelect('users', $event)" class="hidden" x-ref="userImageInput">
                                <svg class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Pilih Gambar
                            </label>
                            <button type="button" @click="clearImage('users')" x-show="userImage" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                        </div>
                        <div x-show="userImage" class="mt-3">
                            <img :src="userImagePreview" class="h-20 rounded-lg border border-gray-300 dark:border-gray-600">
                        </div>
                    </div>

                    <div class="mb-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-purple-700 dark:text-purple-300 text-sm">
                                <strong>{{ $userCount }} user</strong> akan menerima japri ini
                            </p>
                        </div>
                    </div>

                    <button type="submit" :disabled="(!userMessage.trim() && !userImage) || userLoading"
                            class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg x-show="!userLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <svg x-show="userLoading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="userLoading ? 'Mengirim...' : 'Kirim Japri ke Semua User'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="pkg-panel-lg overflow-hidden">
            <div class="bg-gradient-to-r from-sky-500 to-cyan-600 px-6 py-5">
                <div class="flex items-center gap-4 text-white">
                    <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">Broadcast ke Grup Tim PKG</h2>
                        <p class="text-sky-100">{{ $pamongGroupCount }} grup tim akan menerima pesan ini</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <form @submit.prevent="sendToPamongGroups" class="pkg-filter-grid">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Pesan Broadcast Grup
                        </label>
                        <textarea x-model="groupMessage" rows="4"
                                  @keydown="handleKeyDown($event, 'groups')"
                                  class="w-full pkg-field resize-none"
                                  placeholder="Tulis pesan untuk semua grup tim PKG..."></textarea>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Lampiran Gambar (Opsional)
                        </label>
                        <div class="flex items-center gap-3">
                            <label class="btn-secondary cursor-pointer text-sm !px-3 !py-2">
                                <input type="file" accept="image/*" @change="handleImageSelect('groups', $event)" class="hidden" x-ref="groupImageInput">
                                Pilih Gambar
                            </label>
                            <button type="button" @click="clearImage('groups')" x-show="groupImage" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                        </div>
                        <div x-show="groupImage" class="mt-3">
                            <img :src="groupImagePreview" class="h-20 rounded-lg border border-gray-300 dark:border-gray-600">
                        </div>
                    </div>

                    <div class="lg:col-span-2 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                        Pesan akan dikirim ke grup otomatis per anggota tim operasional beserta siswa binaannya jika ada. Grup yang belum ada akan dibuat otomatis.
                    </div>

                    <div class="lg:col-span-2">
                        <button type="submit" :disabled="(!groupMessage.trim() && !groupImage) || groupLoading"
                                class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-sky-600 to-cyan-600 hover:from-sky-700 hover:to-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <svg x-show="!groupLoading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <svg x-show="groupLoading" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="groupLoading ? 'Mengirim...' : 'Kirim ke Semua Grup Tim PKG'"></span>
                        </button>
                    </div>
                </form>
            </div>
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
                <button @click="showSuccess = false" class="btn-primary text-sm !px-4 !py-2">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function adminBroadcastApp() {
    return {
        // Siswa
        siswaMessage: '',
        siswaImage: null,
        siswaImagePreview: null,
        siswaLoading: false,
        
        // User
        userMessage: '',
        userImage: null,
        userImagePreview: null,
        userLoading: false,

        // Group
        groupMessage: '',
        groupImage: null,
        groupImagePreview: null,
        groupLoading: false,
        
        // Modal
        showSuccess: false,
        successMessage: '',

        handleKeyDown(event, type) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                if (type === 'siswa') this.sendToSiswa();
                else if (type === 'users') this.sendToUsers();
                else this.sendToPamongGroups();
            }
        },

        handleImageSelect(type, e) {
            const file = e.target.files[0];
            if (file) {
                if (type === 'siswa') {
                    this.siswaImage = file;
                    this.siswaImagePreview = URL.createObjectURL(file);
                } else if (type === 'users') {
                    this.userImage = file;
                    this.userImagePreview = URL.createObjectURL(file);
                } else {
                    this.groupImage = file;
                    this.groupImagePreview = URL.createObjectURL(file);
                }
            }
        },

        clearImage(type) {
            if (type === 'siswa') {
                this.siswaImage = null;
                this.siswaImagePreview = null;
                this.$refs.siswaImageInput.value = '';
            } else if (type === 'users') {
                this.userImage = null;
                this.userImagePreview = null;
                this.$refs.userImageInput.value = '';
            } else {
                this.groupImage = null;
                this.groupImagePreview = null;
                this.$refs.groupImageInput.value = '';
            }
        },

        async sendToSiswa() {
            if ((!this.siswaMessage.trim() && !this.siswaImage) || this.siswaLoading) return;
            
            this.siswaLoading = true;
            
            try {
                const formData = new FormData();
                formData.append('message', this.siswaMessage);
                if (this.siswaImage) {
                    formData.append('attachment', this.siswaImage);
                }

                const res = await fetch('{{ route('admin.broadcast.siswa') }}', {
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
                    this.siswaMessage = '';
                    this.clearImage('siswa');
                } else {
                    window.showNotification(data.message || 'Gagal mengirim siaran', 'error');
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Terjadi kesalahan saat mengirim siaran', 'error');
            } finally {
                this.siswaLoading = false;
            }
        },

        async sendToUsers() {
            if ((!this.userMessage.trim() && !this.userImage) || this.userLoading) return;
            
            this.userLoading = true;
            
            try {
                const formData = new FormData();
                formData.append('message', this.userMessage);
                if (this.userImage) {
                    formData.append('attachment', this.userImage);
                }

                const res = await fetch('{{ route('admin.broadcast.users') }}', {
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
                    this.userMessage = '';
                    this.clearImage('users');
                } else {
                    window.showNotification(data.message || 'Gagal mengirim siaran', 'error');
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Terjadi kesalahan saat mengirim siaran', 'error');
            } finally {
                this.userLoading = false;
            }
        },

        async sendToPamongGroups() {
            if ((!this.groupMessage.trim() && !this.groupImage) || this.groupLoading) return;

            this.groupLoading = true;

            try {
                const formData = new FormData();
                formData.append('message', this.groupMessage);
                if (this.groupImage) {
                    formData.append('attachment', this.groupImage);
                }

                const res = await fetch('{{ route('admin.broadcast.pamong-groups') }}', {
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
                    this.groupMessage = '';
                    this.clearImage('groups');
                } else {
                    window.showNotification(data.message || 'Gagal mengirim broadcast grup', 'error');
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Terjadi kesalahan saat mengirim broadcast grup', 'error');
            } finally {
                this.groupLoading = false;
            }
        }
    }
}
</script>
@endsection


