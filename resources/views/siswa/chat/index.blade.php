@extends('layouts.siswa')

@section('title', 'Chat')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8" x-data="chatApp()" @keydown.escape.window="closeConversation()">
    <div class="pkg-page-header" :class="selectedId ? 'hidden lg:flex' : ''">
        <div>
            <h1 class="pkg-page-heading">Chat</h1>
            <p class="pkg-page-subheading">Berkomunikasi dengan pamong, admin, dan teman sekelas.</p>
        </div>
    </div>

    @if($siswa->isGraduated())
        <div class="pkg-card-soft mb-4 border border-sky-200 p-4 dark:border-sky-900">
            <p class="font-semibold text-sky-900 dark:text-sky-100">Riwayat chat Alumni</p>
            <p class="mt-1 text-sm text-sky-800/80 dark:text-sky-200/80">Percakapan lama tetap dapat dibaca. Pengiriman pesan baru sudah dinonaktifkan.</p>
        </div>
    @endif

    <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-4 lg:gap-6">
        <!-- Contact List -->
        <div class="pkg-card min-w-0 overflow-hidden lg:col-span-1" :class="selectedId ? 'hidden lg:block' : 'block'">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">Kontak</h2>
            </div>
            
            <!-- Pamong Section -->
            @if($pamongList->isNotEmpty())
            <div class="p-3 bg-gray-50 dark:bg-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Pamong & Admin</p>
            </div>
            @foreach($pamongList as $pamong)
            <button @click="selectContact('pamong', {{ $pamong->id }}, '{{ $pamong->username }}', '{{ $pamong->contact_role_label }}')"
                    :class="selectedType === 'pamong' && selectedId === {{ $pamong->id }} ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="w-full p-4 flex items-center gap-3 text-left transition-colors">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-semibold relative">
                    {{ strtoupper(substr($pamong->username, 0, 1)) }}
                    <template x-if="getUnreadCount('pamong', {{ $pamong->id }}) > 0">
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
                              x-text="getUnreadCount('pamong', {{ $pamong->id }}) > 99 ? '99+' : getUnreadCount('pamong', {{ $pamong->id }})"></span>
                    </template>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ $pamong->username }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pamong->contact_role_label }}</p>
                </div>
            </button>
            @endforeach
            @endif

            <!-- Siswa Section -->
            @if($relatedSiswa->isNotEmpty())
            <div class="p-3 bg-gray-50 dark:bg-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Teman</p>
            </div>
            @foreach($relatedSiswa as $teman)
            <button @click="selectContact('siswa', {{ $teman->id }}, '{{ $teman->nama }}', 'Teman')"
                    :class="selectedType === 'siswa' && selectedId === {{ $teman->id }} ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="w-full p-4 flex items-center gap-3 text-left transition-colors">
                <div class="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-semibold relative">
                    {{ strtoupper(substr($teman->nama, 0, 1)) }}
                    <template x-if="getUnreadCount('siswa', {{ $teman->id }}) > 0">
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold"
                              x-text="getUnreadCount('siswa', {{ $teman->id }}) > 99 ? '99+' : getUnreadCount('siswa', {{ $teman->id }})"></span>
                    </template>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ $teman->nama }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $teman->school_grade_label ?? 'Kelas belum dikonfirmasi' }}</p>
                </div>
            </button>
            @endforeach
            @endif

            @if($pamongList->isEmpty() && $relatedSiswa->isEmpty())
            <div class="pkg-empty-state">
                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 8h2a2 2 0 012 2v8l-4-3H9a2 2 0 01-2-2v-1m10-4V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h2l4 3v-3h4a2 2 0 002-2V8z"/>
                </svg>
                <h3 class="pkg-empty-title">Belum Ada Kontak</h3>
                <p class="pkg-empty-copy">Kontak chat belum tersedia.</p>
            </div>
            @endif

            <!-- Group Chat Section -->
            @if(isset($groups) && $groups->isNotEmpty())
            <div class="p-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Grup Chat</p>
            </div>
            @foreach($groups as $group)
            <a href="{{ route('siswa.group-chat.index') }}?group={{ $group->id }}"
               class="w-full p-4 flex items-center gap-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 block">
                <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ $group->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $group->members_count }} anggota</p>
                </div>
            </a>
            @endforeach
            @endif
        </div>

        <!-- Chat Area -->
        <div class="pkg-portal-mobile-chat pkg-card min-w-0 flex-col lg:col-span-3 lg:h-[600px]" :class="selectedId ? 'flex' : 'hidden lg:flex'">
            <!-- Chat Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="button" @click="closeConversation()" class="btn-secondary !h-10 !w-10 !p-0 lg:hidden" aria-label="Kembali ke daftar kontak">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-if="selectedName">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold"
                             :class="selectedType === 'pamong' ? 'bg-green-500' : 'bg-purple-500'">
                            <span x-text="selectedName.charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="selectedName"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedLabel"></p>
                        </div>
                    </div>
                </template>
                <template x-if="!selectedName">
                    <p class="text-gray-500 dark:text-gray-400">Pilih kontak untuk memulai chat</p>
                </template>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                <template x-if="!selectedId">
                    <div class="h-full flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p>Pilih kontak untuk memulai percakapan</p>
                        </div>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="flex justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </template>
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'"
                             class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
                            <!-- Image with optional caption -->
                            <template x-if="msg.message_type === 'image' && msg.attachment_url">
                                <div>
                                    <img :src="msg.attachment_url" class="rounded-lg max-w-full mb-2 cursor-pointer" @click="window.open(msg.attachment_url, '_blank')">
                                    <template x-if="msg.caption">
                                        <p class="text-sm whitespace-pre-wrap" x-html="parseLinks(msg.caption)"></p>
                                    </template>
                                </div>
                            </template>
                            <!-- Text with link parsing -->
                            <template x-if="msg.message_type === 'text' || (!msg.message_type && msg.message)">
                                <p class="text-sm whitespace-pre-wrap" x-html="parseLinks(msg.message)"></p>
                            </template>
                            <p class="text-xs mt-1 opacity-70" x-text="msg.created_at"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            @unless($siswa->isGraduated())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <form @submit.prevent="sendMessage" class="flex gap-2 items-end" enctype="multipart/form-data" data-no-csrf-handler>
                    <!-- Image Upload Button -->
                    <label class="cursor-pointer px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                           :class="!selectedId ? 'opacity-50 cursor-not-allowed' : ''">
                        <input type="file" accept="image/*" @change="handleImageSelect" class="hidden" :disabled="!selectedId" x-ref="imageInput">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </label>
                    <textarea x-model="newMessage" :disabled="!selectedId" rows="1"
                              @keydown="handleKeyDown($event)"
                              @input="autoResize($event)"
                              class="flex-1 pkg-field focus:border-blue-500 focus:ring-blue-500 resize-none overflow-hidden"
                              placeholder="Ketik pesan... (Shift+Enter untuk baris baru)"
                              style="min-height: 38px; max-height: 120px;"></textarea>
                    <button type="submit" :disabled="!selectedId || (!newMessage.trim() && !selectedImage)"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
                <!-- Image Preview -->
                <div x-show="selectedImage" class="mt-2 relative inline-block">
                    <img :src="imagePreview" class="h-20 rounded-lg">
                    <button @click="clearImage" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&times;</button>
                </div>
            </div>
            @endunless
        </div>
    </div>
</div>

<script>
function chatApp() {
    return {
        selectedType: null,
        selectedId: null,
        selectedName: null,
        selectedLabel: null,
        messages: [],
        newMessage: '',
        loading: false,
        refreshInterval: null,
        unreadInterval: null,
        selectedImage: null,
        imagePreview: null,
        unreadCounts: {},

        init() {
            this.loadUnreadCounts();
            this.startUnreadPolling();
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            window.addEventListener('pagehide', () => this.stopPolling());

            // Auto-select pamong if pamong_id is in URL params
            const urlParams = new URLSearchParams(window.location.search);
            const pamongId = urlParams.get('pamong_id');
            const prefillMessage = urlParams.get('message') || '';
            if (pamongId) {
                // Find pamong button and auto-click after a short delay
                this.$nextTick(() => {
                    @if($pamongList->isNotEmpty())
                        @foreach($pamongList as $pamong)
                            if (parseInt(pamongId) === {{ $pamong->id }}) {
                                this.selectContact('pamong', {{ $pamong->id }}, @js($pamong->username), @js($pamong->contact_role_label));
                                this.newMessage = prefillMessage;
                            }
                        @endforeach
                    @endif
                    // Clean URL
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                });
            }
        },

        async loadUnreadCounts() {
            if (document.hidden) return;
            try {
                const res = await fetch('/siswa/chat/unread-counts', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.unreadCounts = data.unread_counts || {};
            } catch (e) {
                console.error('Failed to load unread counts:', e);
            }
        },

        getUnreadCount(type, id) {
            return this.unreadCounts[`${type}_${id}`] || 0;
        },

        // Parse URLs in text and convert to clickable links
        parseLinks(text) {
            if (!text) return '';
            // Escape HTML first
            let escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            // Convert URLs with protocol
            escaped = escaped.replace(/(https?:\/\/[^\s<]+)/gi, '<a href="$1" target="_blank" rel="noopener" class="underline hover:opacity-80">$1</a>');
            // Convert domains without protocol (e.g., google.com)
            escaped = escaped.replace(/(?<!href="|>)((?:[a-zA-Z0-9][-a-zA-Z0-9]*\.)+(?:com|org|net|io|co\.id|id|edu|gov|info|biz|me|app|dev|xyz|site|online|tech|co|uk|de|fr|jp|au|ca|br|in|ru|nl|es|it))(?![^<]*>)/gi, '<a href="https://$1" target="_blank" rel="noopener" class="underline hover:opacity-80">$1</a>');
            return escaped;
        },

        handleKeyDown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.sendMessage();
            }
        },

        autoResize(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        },

        selectContact(type, id, name, label = null) {
            this.selectedType = type;
            this.selectedId = id;
            this.selectedName = name;
            this.selectedLabel = label || (type === 'pamong' ? 'Pamong' : 'Teman');
            this.loadMessages();
            // Refresh unread counts after opening chat (messages are marked as read)
            setTimeout(() => this.loadUnreadCounts(), 500);
            
            this.startMessagePolling();
        },

        closeConversation() {
            this.selectedType = null;
            this.selectedId = null;
            this.selectedName = null;
            this.selectedLabel = null;
            this.messages = [];
            this.stopMessagePolling();
        },

        startMessagePolling() {
            this.stopMessagePolling();
            if (this.selectedId && !document.hidden) {
                this.refreshInterval = setInterval(() => this.loadMessages(false), 5000);
            }
        },

        stopMessagePolling() {
            if (this.refreshInterval) clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        },

        startUnreadPolling() {
            if (this.unreadInterval) clearInterval(this.unreadInterval);
            if (!document.hidden) this.unreadInterval = setInterval(() => this.loadUnreadCounts(), 10000);
        },

        stopPolling() {
            this.stopMessagePolling();
            if (this.unreadInterval) clearInterval(this.unreadInterval);
            this.unreadInterval = null;
        },

        handleVisibilityChange() {
            if (document.hidden) {
                this.stopPolling();
                return;
            }
            this.loadUnreadCounts();
            this.startUnreadPolling();
            if (this.selectedId) {
                this.loadMessages(false);
                this.startMessagePolling();
            }
        },

        async loadMessages(showLoading = true) {
            if (!this.selectedId || document.hidden) return;
            const container = document.getElementById('messages-container');
            const shouldStickToBottom = showLoading || !container || (container.scrollHeight - container.scrollTop - container.clientHeight < 100);
            this.loading = showLoading && this.messages.length === 0;
            
            try {
                const res = await fetch(`/siswa/chat/messages?type=${this.selectedType}&target_id=${this.selectedId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.messages = data.messages;
                    this.$nextTick(() => {
                        const messagesContainer = document.getElementById('messages-container');
                        if (messagesContainer && shouldStickToBottom) messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    });
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
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

        async sendMessage() {
            if ((!this.newMessage.trim() && !this.selectedImage) || !this.selectedId) return;
            
            try {
                const formData = new FormData();
                formData.append('type', this.selectedType);
                formData.append('target_id', this.selectedId);
                formData.append('message', this.newMessage);
                if (this.selectedImage) {
                    formData.append('attachment', this.selectedImage);
                }

                const res = await fetch('/siswa/chat/send', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.messages.push(data.message);
                    this.newMessage = '';
                    this.clearImage();
                    // Reset textarea height
                    this.$nextTick(() => {
                        const textarea = document.querySelector('textarea');
                        if (textarea) textarea.style.height = 'auto';
                        const container = document.getElementById('messages-container');
                        container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) {
                console.error(e);
                window.showNotification('Gagal mengirim pesan', 'error');
            }
        }
    }
}
</script>
@endsection


