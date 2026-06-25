@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div x-data="profileManager()" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <x-breadcrumb :items="[
        ['name' => 'Dashboard', 'url' => route('dashboard')],
        ['name' => 'Profil Saya', 'url' => null]
    ]" />

    <div class="space-y-6">
        <div
            x-show="notification.show"
            x-transition
            class="fixed right-4 top-20 z-[70] w-[calc(100%-2rem)] max-w-md rounded-xl border p-4 shadow-xl"
            :class="notification.type === 'success'
                ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-100'
                : notification.type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-100'
                    : 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-100'"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold" x-text="notification.title"></p>
                    <p class="mt-1 text-sm leading-6" x-text="notification.message"></p>
                </div>
                <button type="button" @click="notification.show = false" class="rounded-lg px-2 py-1 text-sm font-semibold opacity-80 hover:opacity-100">
                    Tutup
                </button>
            </div>
        </div>

        <!-- Profile Header -->
        <div class="pkg-card-sm p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-4">
            <div class="relative">
                <img :src="user.avatar_url || '/images/default-avatar.svg'"
                     :alt="user.display_name || user.username"
                     class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-4 border-blue-100 dark:border-blue-900">
                <button @click="$refs.avatarInput.click()" 
                        class="absolute -bottom-1 -right-1 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>
                <input type="file" x-ref="avatarInput" @change="handleAvatarChange" accept="image/*" class="hidden">
            </div>
            <div class="text-center sm:text-left">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white" x-text="user.display_name || user.username"></h1>
                <p class="text-gray-600 dark:text-gray-400" x-text="user.role?.display_name || user.role?.name || 'User'"></p>
                <p class="text-sm text-gray-500 dark:text-gray-500" x-text="user.email"></p>
                <p x-show="isPamong" class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                    <span x-text="user.organizational_team?.name || 'Tanpa bidang'"></span>
                    <span> - </span>
                    <span x-text="user.organizational_title || 'Belum ada jabatan'"></span>
                </p>
            </div>
        </div>
    </div>

        <!-- Profile Information -->
        <div class="pkg-card-sm p-4 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Profil</h2>
            
            <form @submit.prevent="updateProfile" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nama Lengkap
                    </label>
                    <input type="text" 
                           x-model="profileForm.name"
                           :class="fieldErrors.name ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                           required>
                    <p x-show="fieldErrors.name" x-text="fieldErrors.name" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Username
                    </label>
                    <input type="text" 
                           x-model="profileForm.username"
                           :class="fieldErrors.username ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           required>
                    <p x-show="fieldErrors.username" x-text="fieldErrors.username" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Email
                    </label>
                    <input type="email" 
                           x-model="profileForm.email"
                           :class="fieldErrors.email ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           required>
                    <p x-show="fieldErrors.email" x-text="fieldErrors.email" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nomor Telepon
                    </label>
                    <input type="tel" 
                           x-model="profileForm.phone"
                           :class="fieldErrors.phone ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <p x-show="fieldErrors.phone" x-text="fieldErrors.phone" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
                </div>

                <template x-if="isPamong">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Bidang
                        </label>
                        <select x-model="profileForm.organizational_team_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Tanpa bidang</option>
                            <template x-for="team in teams" :key="team.id">
                                <option :value="String(team.id)" x-text="team.short_name ? `${team.name} (${team.short_name})` : team.name"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <template x-if="isPamong">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Jabatan
                        </label>
                        <input type="text"
                               x-model="profileForm.organizational_title"
                               placeholder="Contoh: Pamong, Koordinator, Sekretaris"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </template>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        :disabled="profileLoading"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span x-show="!profileLoading">Simpan Perubahan</span>
                    <span x-show="profileLoading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="pkg-card-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ubah Password</h2>
        
        <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Password Saat Ini
                </label>
                <div class="relative">
                    <input :type="showCurrentPassword ? 'text' : 'password'" 
                           x-model="passwordForm.current_password"
                           :class="fieldErrors.current_password ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                           class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           required>
                    <button type="button" 
                            @click="showCurrentPassword = !showCurrentPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg x-show="!showCurrentPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showCurrentPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                        </svg>
                    </button>
                </div>
                <p x-show="fieldErrors.current_password" x-text="fieldErrors.current_password" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Password Baru
                    </label>
                    <div class="relative">
                        <input :type="showNewPassword ? 'text' : 'password'" 
                               x-model="passwordForm.password"
                               :class="fieldErrors.password ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               required>
                        <button type="button" 
                                @click="showNewPassword = !showNewPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg x-show="!showNewPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showNewPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                            </svg>
                        </button>
                    </div>
                    <p x-show="fieldErrors.password" x-text="fieldErrors.password" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Konfirmasi Password Baru
                    </label>
                    <div class="relative">
                        <input :type="showConfirmPassword ? 'text' : 'password'" 
                               x-model="passwordForm.password_confirmation"
                               :class="fieldErrors.password_confirmation ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                               class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               required>
                        <button type="button" 
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg x-show="!showConfirmPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showConfirmPassword" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                            </svg>
                        </button>
                    </div>
                    <p x-show="fieldErrors.password_confirmation" x-text="fieldErrors.password_confirmation" class="mt-1 text-xs text-red-600 dark:text-red-300"></p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        :disabled="passwordLoading"
                        class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span x-show="!passwordLoading">Ubah Password</span>
                    <span x-show="passwordLoading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mengubah...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function profileManager() {
    const endpoints = {
        updateProfile: @json(route('profile.update.post')),
        updatePassword: @json(route('profile.password')),
    };

    return {
        user: @json($user),
        teams: @json($teams ?? []),
        isPamong: @json($user->usesPamongPermissionSystem()),
        profileForm: {
            name: @json($user->name),
            username: @json($user->username),
            email: @json($user->email),
            phone: @json($user->phone ?? ''),
            organizational_team_id: @json((string) ($user->organizational_team_id ?? '')),
            organizational_title: @json($user->organizational_title ?? ''),
        },
        passwordForm: {
            current_password: '',
            password: '',
            password_confirmation: ''
        },
        profileLoading: false,
        passwordLoading: false,
        showCurrentPassword: false,
        showNewPassword: false,
        showConfirmPassword: false,
        notificationTimer: null,
        fieldErrors: @json($errors->any() ? collect($errors->toArray())->map(fn ($messages) => $messages[0] ?? 'Data tidak valid.')->toArray() : []),
        notification: {
            show: @json(session()->has('success') || session()->has('error') || $errors->any()),
            type: @json(session()->has('success') ? 'success' : (session()->has('error') || $errors->any() ? 'error' : 'info')),
            title: @json(session()->has('success') ? 'Berhasil' : (session()->has('error') || $errors->any() ? 'Perlu diperbaiki' : 'Info')),
            message: @json(session('success') ?? session('error') ?? ($errors->first() ?: '')),
        },

        async updateProfile() {
            this.profileLoading = true;
            this.clearFeedback();
            
            try {
                const formData = new FormData();
                Object.keys(this.profileForm).forEach(key => {
                    if (this.profileForm[key] !== null && this.profileForm[key] !== undefined) {
                        formData.append(key, this.profileForm[key]);
                    }
                });

                const response = await fetch(endpoints.updateProfile, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await this.readJsonResponse(response);

                if (response.ok) {
                    this.user = data.user;
                    this.showNotification(data.message || 'Profil berhasil diperbarui', 'success', 'Profil tersimpan');
                } else {
                    throw this.buildRequestError(data, 'Profil gagal diperbarui.');
                }
            } catch (error) {
                this.showNotification(error.message || 'Profil gagal diperbarui.', 'error', 'Profil gagal disimpan');
            } finally {
                this.profileLoading = false;
            }
        },

        async updatePassword() {
            this.passwordLoading = true;
            this.clearFeedback();
            
            try {
                const response = await fetch(endpoints.updatePassword, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.passwordForm)
                });

                const data = await this.readJsonResponse(response);

                if (response.ok) {
                    this.passwordForm = {
                        current_password: '',
                        password: '',
                        password_confirmation: ''
                    };
                    this.showNotification(data.message || 'Password berhasil diperbarui', 'success', 'Password tersimpan');
                } else {
                    throw this.buildRequestError(data, 'Password gagal diperbarui.');
                }
            } catch (error) {
                this.showNotification(error.message || 'Password gagal diperbarui.', 'error', 'Password gagal disimpan');
            } finally {
                this.passwordLoading = false;
            }
        },

        async handleAvatarChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.clearFeedback();

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                this.showNotification('Ukuran file maksimal 2MB', 'error', 'Foto gagal diunggah');
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.showNotification('Format file harus jpeg, png, jpg, gif, atau webp', 'error', 'Foto gagal diunggah');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetch(endpoints.updateProfile, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await this.readJsonResponse(response);

                if (response.ok) {
                    this.user = data.user;
                    this.showNotification('Avatar berhasil diperbarui', 'success', 'Foto tersimpan');
                } else {
                    throw this.buildRequestError(data, 'Avatar gagal diperbarui.');
                }
            } catch (error) {
                this.showNotification(error.message || 'Avatar gagal diperbarui.', 'error', 'Foto gagal diunggah');
            } finally {
                event.target.value = '';
            }
        },

        clearFeedback() {
            this.fieldErrors = {};
            this.notification.show = false;
            if (this.notificationTimer) {
                clearTimeout(this.notificationTimer);
                this.notificationTimer = null;
            }
        },

        buildRequestError(data, fallbackMessage) {
            let message = data?.message || fallbackMessage;

            if (data?.errors) {
                this.fieldErrors = Object.fromEntries(
                    Object.entries(data.errors).map(([field, messages]) => [
                        field,
                        Array.isArray(messages) ? messages[0] : messages,
                    ])
                );

                const firstError = Object.values(this.fieldErrors)[0];
                if (firstError) {
                    message = firstError;
                }
            }

            return new Error(message);
        },

        async readJsonResponse(response) {
            try {
                return await response.json();
            } catch (_error) {
                if (response.status === 419) {
                    return { message: 'Sesi halaman sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.' };
                }

                if (response.status >= 500) {
                    return { message: 'Server belum bisa memproses permintaan. Coba lagi atau cek log server.' };
                }

                return { message: 'Respons server tidak bisa dibaca. Muat ulang halaman lalu coba lagi.' };
            }
        },

        showNotification(message, type = 'info', title = null) {
            this.notification = {
                show: true,
                type,
                title: title || (type === 'success' ? 'Berhasil' : type === 'error' ? 'Perlu diperbaiki' : 'Info'),
                message,
            };

            if (this.notificationTimer) {
                clearTimeout(this.notificationTimer);
            }

            const displayMs = type === 'error' ? 15000 : 10000;
            this.notificationTimer = setTimeout(() => {
                this.notification.show = false;
                this.notificationTimer = null;
            }, displayMs);
        }
    }
}
</script>
@endsection
