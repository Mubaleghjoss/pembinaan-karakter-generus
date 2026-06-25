@extends('layouts.app')

@section('title', 'Grup Chat')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="groupChatApp()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Grup Chat</h1>
            <p class="pkg-page-subheading">Berkomunikasi dalam grup.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Group List -->
        <div class="lg:col-span-1 pkg-card overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">Grup Anda</h2>
            </div>
            
            @forelse($groups as $group)
            <button @click="selectGroup({{ $group->id }}, '{{ addslashes($group->name) }}')"
                    :class="selectedGroupId === {{ $group->id }} ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="w-full p-4 flex items-center gap-3 text-left transition-colors">
                <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ $group->name }}</p>
                    <p class="text-xs text-gray-500">{{ $group->members->count() }} anggota</p>
                </div>
            </button>
            @empty
            <div class="pkg-empty-state">
                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="pkg-empty-title">Belum Ada Grup</h3>
                <p class="pkg-empty-copy">Anda belum bergabung dalam grup chat.</p>
            </div>
            @endforelse
        </div>

        <!-- Chat Area -->
        <div class="lg:col-span-3 pkg-card flex flex-col" style="height: 600px;">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <template x-if="selectedGroupName">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="selectedGroupName"></p>
                    </div>
                </template>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                <template x-if="!selectedGroupId">
                    <div class="h-full flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p>Pilih grup untuk memulai percakapan</p>
                        </div>
                    </div>
                </template>
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'"
                             class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
                            <template x-if="!msg.is_mine">
                                <p class="text-xs font-semibold mb-1" :class="msg.sender_type === 'pamong' ? 'text-green-600' : 'text-blue-600'" x-text="msg.sender_name"></p>
                            </template>
                            <template x-if="msg.attachment_url">
                                <img :src="msg.attachment_url" class="rounded-lg max-w-full mb-2">
                            </template>
                            <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                            <p class="text-xs mt-1 opacity-70" x-text="msg.created_at"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <form @submit.prevent="sendMessage" class="flex gap-2 items-end" data-no-csrf-handler>
                    <label class="cursor-pointer px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <input type="file" accept="image/*" @change="handleImageSelect" class="hidden" :disabled="!selectedGroupId" x-ref="imageInput">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </label>
                    <textarea x-model="newMessage" :disabled="!selectedGroupId" rows="1"
                              @keydown="if($event.key === 'Enter' && !$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                              class="flex-1 pkg-field"
                              placeholder="Ketik pesan..."
                              style="min-height: 38px; max-height: 120px;"></textarea>
                    <button type="submit" :disabled="!selectedGroupId || !newMessage.trim()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
                <div x-show="selectedImage" class="mt-2 relative inline-block">
                    <img :src="imagePreview" class="h-20 rounded-lg">
                    <button @click="clearImage" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5">&times;</button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
function groupChatApp() {
    return {
        selectedGroupId: null,
        selectedGroupName: null,
        messages: [],
        newMessage: '',
        refreshInterval: null,
        selectedImage: null,
        imagePreview: null,

        init() {
            @if($groups->isNotEmpty())
                this.selectGroup({{ $groups->first()->id }}, '{{ addslashes($groups->first()->name) }}');
            @endif
        },

        selectGroup(id, name) {
            this.selectedGroupId = id;
            this.selectedGroupName = name;
            this.loadMessages();
            if (this.refreshInterval) clearInterval(this.refreshInterval);
            this.refreshInterval = setInterval(() => this.loadMessages(), 5000);
        },

        async loadMessages() {
            if (!this.selectedGroupId) return;
            try {
                const res = await fetch(`/group-chat/${this.selectedGroupId}/messages`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.messages = data.messages;
                    this.$nextTick(() => {
                        const container = document.getElementById('messages-container');
                        container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) { console.error(e); }
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
            if (!this.newMessage.trim() || !this.selectedGroupId) return;
            try {
                const formData = new FormData();
                formData.append('message', this.newMessage);
                if (this.selectedImage) formData.append('attachment', this.selectedImage);

                const res = await fetch(`/group-chat/${this.selectedGroupId}/send`, {
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
                    this.$nextTick(() => {
                        const container = document.getElementById('messages-container');
                        container.scrollTop = container.scrollHeight;
                    });
                }
            } catch (e) { console.error(e); window.showNotification('Gagal mengirim pesan', 'error'); }
        }
    }
}
</script>
@endsection


