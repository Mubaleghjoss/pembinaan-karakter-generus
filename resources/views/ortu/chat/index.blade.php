@extends('layouts.ortu')

@section('title', 'Chat Pamong')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8" x-data="ortuChat()" @keydown.escape.window="closeConversation()">
    <div class="pkg-page-header" :class="selectedPamong ? 'hidden lg:flex' : ''">
        <div>
            <h1 class="pkg-page-heading">Chat Pamong</h1>
            <p class="pkg-page-subheading">Komunikasikan perkembangan atau kendala {{ $siswa->nama }}.</p>
        </div>
    </div>

    <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-4 lg:gap-6">
        <div class="pkg-card min-w-0 overflow-hidden lg:col-span-1" :class="selectedPamong ? 'hidden lg:block' : 'block'">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">Daftar Pamong</h2>
            </div>

            <div class="overflow-y-auto" style="max-height: 600px;">
                @if($pamongList->isNotEmpty())
                    @foreach($pamongList as $pamong)
                    <button
                        @click="selectPamong({{ $pamong->id }}, '{{ addslashes($pamong->username ?? $pamong->name) }}')"
                        :class="selectedPamong === {{ $pamong->id }} ? 'bg-blue-100 dark:bg-blue-900/30 border-l-4 border-blue-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="w-full p-4 flex items-center gap-3 text-left transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-semibold relative">
                            {{ strtoupper(substr($pamong->username ?? $pamong->name ?? 'P', 0, 1)) }}
                            @if($pamong->unreadCount > 0)
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold">
                                    {{ $pamong->unreadCount > 99 ? '99+' : $pamong->unreadCount }}
                                </span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ $pamong->username ?? $pamong->name }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $pamong->lastMessage->message ?? 'Belum ada pesan' }}</p>
                        </div>
                    </button>
                    @endforeach
                @else
                    <div class="pkg-empty-state">
                        <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 8h2a2 2 0 012 2v8l-4-3H9a2 2 0 01-2-2v-1m10-4V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h2l4 3v-3h4a2 2 0 002-2V8z"/>
                        </svg>
                        <h3 class="pkg-empty-title">Belum Ada Pamong</h3>
                        <p class="pkg-empty-copy">Belum ada pamong yang ditugaskan.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="pkg-portal-mobile-chat pkg-card min-w-0 flex-col lg:col-span-3 lg:h-[600px]" :class="selectedPamong ? 'flex' : 'hidden lg:flex'">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="button" @click="closeConversation()" class="btn-secondary !h-10 !w-10 !p-0 lg:hidden" aria-label="Kembali ke daftar pamong">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-if="selectedPamong">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white font-semibold">
                            <span x-text="pamongName ? pamongName.charAt(0).toUpperCase() : '?'"></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="pamongName"></p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Pamong Pembimbing</p>
                        </div>
                    </div>
                </template>
                <template x-if="!selectedPamong">
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p>Pilih pamong untuk memulai chat</p>
                    </div>
                </template>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chatMessages">
                <template x-if="!selectedPamong">
                    <div class="h-full flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m-8 6l1.5-3H18a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h.5L8 20z"/>
                            </svg>
                            <p>Silakan pilih pamong dari daftar di sebelah kiri.</p>
                        </div>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="flex justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </template>
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-red-600 text-white' : 'bg-indigo-100 dark:bg-gray-700 text-indigo-900 dark:text-white border border-indigo-200 dark:border-gray-600'" class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg shadow-sm">
                            <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                            <p class="text-[10px] mt-1 text-right" :class="msg.is_mine ? 'text-red-100' : 'text-indigo-700 dark:text-gray-400'" x-text="msg.created_at"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-4 border-t border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 rounded-b-lg" x-show="selectedPamong">
                <form @submit.prevent="sendMessage" class="flex gap-2 items-end">
                    <textarea
                        x-model="newMessage"
                        rows="1"
                        @keydown.enter.exact.prevent="sendMessage"
                        @input="autoResize($event)"
                        class="flex-1 pkg-field resize-none overflow-hidden py-3"
                        placeholder="Ketik pesan..."
                        style="min-height: 44px; max-height: 120px;"></textarea>
                    <button
                        type="submit"
                        :disabled="!newMessage.trim()"
                        class="btn-primary h-[44px] w-[44px] !px-0 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ortuChat() {
    return {
        selectedPamong: null,
        pamongName: '',
        messages: [],
        newMessage: '',
        loading: false,
        refreshInterval: null,

        init() {
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            window.addEventListener('pagehide', () => this.stopPolling());
        },

        selectPamong(id, name) {
            this.selectedPamong = id;
            this.pamongName = name;
            this.loadMessages();

            this.startPolling();
        },

        closeConversation() {
            this.selectedPamong = null;
            this.pamongName = '';
            this.messages = [];
            this.stopPolling();
        },

        startPolling() {
            this.stopPolling();
            if (this.selectedPamong && !document.hidden) {
                this.refreshInterval = setInterval(() => this.loadMessages(false), 5000);
            }
        },

        stopPolling() {
            if (this.refreshInterval) clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        },

        handleVisibilityChange() {
            if (document.hidden) {
                this.stopPolling();
            } else if (this.selectedPamong) {
                this.loadMessages(false);
                this.startPolling();
            }
        },

        autoResize(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        },

        async loadMessages(showLoading = true) {
            if (!this.selectedPamong || document.hidden) return;
            if (showLoading) this.loading = true;

            try {
                const res = await fetch(`{{ url('/ortu/chat/messages') }}?pamong_id=${this.selectedPamong}`);
                const data = await res.json();
                this.messages = data;

                if (showLoading) {
                    this.$nextTick(() => {
                        const el = document.getElementById('chatMessages');
                        if (el) el.scrollTop = el.scrollHeight;
                    });
                } else {
                    const el = document.getElementById('chatMessages');
                    if (el && (el.scrollHeight - el.scrollTop - el.clientHeight < 100)) {
                         el.scrollTop = el.scrollHeight;
                    }
                }
            } catch (e) { console.error(e); }
            finally {
                if (showLoading) this.loading = false;
            }
        },

        async sendMessage() {
            if (!this.newMessage.trim()) return;

            try {
                const res = await fetch('{{ route("ortu.chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ pamong_id: this.selectedPamong, message: this.newMessage })
                });
                const data = await res.json();
                if (data.success) {
                    this.messages.push(data.message);
                    this.newMessage = '';
                    this.$nextTick(() => {
                        const textarea = document.querySelector('textarea');
                        if (textarea) textarea.style.height = 'auto';
                        const el = document.getElementById('chatMessages');
                        if (el) el.scrollTop = el.scrollHeight;
                    });
                }
            } catch (e) { console.error(e); }
        }
    }
}
</script>
@endpush
@endsection
