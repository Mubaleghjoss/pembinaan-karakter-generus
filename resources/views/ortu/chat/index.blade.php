@extends('layouts.ortu')

@section('title', 'Chat Pamong & Pengurus')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8" x-data="ortuChat()" @keydown.escape.window="closeConversation()">
    <div class="pkg-page-header" :class="selectedPamong ? 'hidden lg:flex' : ''">
        <div>
            <h1 class="pkg-page-heading">Chat Pamong & Pengurus</h1>
            <p class="pkg-page-subheading">Komunikasikan perkembangan atau kendala {{ $siswa->nama }}.</p>
        </div>
    </div>

    @if($siswa->isGraduated())
        <div class="pkg-card-soft mb-4 border border-sky-200 p-4 dark:border-sky-900">
            <p class="font-semibold text-sky-900 dark:text-sky-100">Riwayat chat Alumni</p>
            <p class="mt-1 text-sm text-sky-800/80 dark:text-sky-200/80">Percakapan lama tetap dapat dibaca. Pengiriman pesan baru kepada Pamong sudah dinonaktifkan.</p>
        </div>
    @endif

    <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-4 lg:gap-6">
        <div class="pkg-card min-w-0 overflow-hidden lg:col-span-1" :class="selectedPamong ? 'hidden lg:block' : 'block'">
            @php
                $pamongUnread = $pamongList->sum('unreadCount');
                $pengurusUnread = $pengurusList->sum('unreadCount');
            @endphp
            <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">Pilih Tujuan Chat</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pamong = pembimbing ananda. Pengurus PKG/Admin = pengelola program.</p>
            </div>

            {{-- Tab pemisah: Pamong vs Pengurus PKG (termasuk Admin) --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button type="button" @click="tab = 'pamong'"
                    :class="tab === 'pamong' ? 'border-teal-600 text-teal-700 dark:text-teal-300' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                    class="flex-1 border-b-2 px-3 py-2.5 text-sm font-semibold transition-colors">
                    Pamong
                    <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $pamongList->count() }}</span>
                    @if($pamongUnread > 0)
                        <span class="ml-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pamongUnread > 99 ? '99+' : $pamongUnread }}</span>
                    @endif
                </button>
                <button type="button" @click="tab = 'pengurus'"
                    :class="tab === 'pengurus' ? 'border-teal-600 text-teal-700 dark:text-teal-300' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                    class="flex-1 border-b-2 px-3 py-2.5 text-sm font-semibold transition-colors">
                    Pengurus PKG
                    <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $pengurusList->count() }}</span>
                    @if($pengurusUnread > 0)
                        <span class="ml-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pengurusUnread > 99 ? '99+' : $pengurusUnread }}</span>
                    @endif
                </button>
            </div>

            <div class="overflow-y-auto" style="max-height: 560px;">
                {{-- Daftar per tab --}}
                @foreach([['pamong', $pamongList], ['pengurus', $pengurusList]] as [$tabKey, $list])
                    <div x-show="tab === '{{ $tabKey }}'">
                        @forelse($list as $contact)
                            @php $contactName = $contact->username ?? $contact->name; @endphp
                            <button
                                @click="selectPamong({{ $contact->id }}, @js($contactName), @js($contact->roleLabel), {{ $contact->isAdmin ? 'true' : 'false' }})"
                                :class="selectedPamong === {{ $contact->id }} ? 'bg-teal-50 dark:bg-teal-900/30 border-l-4 border-teal-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                class="flex w-full items-center gap-3 border-b border-gray-100 p-4 text-left transition-colors last:border-0 dark:border-gray-700">
                                <div class="relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full font-semibold text-white {{ $contact->isAdmin ? 'bg-indigo-600' : ($tabKey === 'pamong' ? 'bg-teal-600' : 'bg-slate-500') }}">
                                    {{ strtoupper(mb_substr($contactName ?? 'P', 0, 1)) }}
                                    @if($contact->unreadCount > 0)
                                        <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                                            {{ $contact->unreadCount > 99 ? '99+' : $contact->unreadCount }}
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-1.5 truncate font-medium text-gray-900 dark:text-white">
                                        <span class="truncate">{{ $contactName }}</span>
                                        @if($contact->isAdmin)
                                            <span class="shrink-0 rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200">Admin</span>
                                        @endif
                                    </p>
                                    <p class="truncate text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ $contact->roleLabel }}</p>
                                    <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $contact->lastMessage->message ?? 'Belum ada pesan' }}</p>
                                </div>
                            </button>
                        @empty
                            <div class="pkg-empty-state">
                                <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 8h2a2 2 0 012 2v8l-4-3H9a2 2 0 01-2-2v-1m10-4V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2h2l4 3v-3h4a2 2 0 002-2V8z"/>
                                </svg>
                                <h3 class="pkg-empty-title">{{ $tabKey === 'pamong' ? 'Belum Ada Pamong' : 'Belum Ada Pengurus' }}</h3>
                                <p class="pkg-empty-copy">
                                    {{ $tabKey === 'pamong'
                                        ? 'Pamong pembimbing ananda belum ditugaskan. Sementara ini Bapak/Ibu dapat menghubungi Pengurus PKG pada tab sebelah.'
                                        : 'Belum ada pengurus yang dapat dihubungi.' }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pkg-portal-mobile-chat pkg-card min-w-0 flex-col lg:col-span-3 lg:h-[600px]" :class="selectedPamong ? 'flex' : 'hidden lg:flex'">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <button type="button" @click="closeConversation()" class="btn-secondary !h-10 !w-10 !p-0 lg:hidden" aria-label="Kembali ke daftar pamong">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-if="selectedPamong">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full font-semibold text-white" :class="pamongIsAdmin ? 'bg-indigo-600' : 'bg-teal-600'">
                            <span x-text="pamongName ? pamongName.charAt(0).toUpperCase() : '?'"></span>
                        </div>
                        <div>
                            <p class="flex items-center gap-1.5 font-semibold text-gray-900 dark:text-white">
                                <span x-text="pamongName"></span>
                                <template x-if="pamongIsAdmin">
                                    <span class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200">Admin</span>
                                </template>
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400" x-text="pamongRole || 'Pamong Pembimbing'"></p>
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

            @unless($siswa->isGraduated())
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
            @endunless
        </div>
    </div>
</div>

@push('scripts')
<script>
function ortuChat() {
    return {
        tab: @js($pamongList->isNotEmpty() ? 'pamong' : 'pengurus'),
        selectedPamong: null,
        pamongName: '',
        pamongRole: '',
        pamongIsAdmin: false,
        messages: [],
        newMessage: '',
        loading: false,
        refreshInterval: null,

        init() {
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            window.addEventListener('pagehide', () => this.stopPolling());
        },

        selectPamong(id, name, role = '', isAdmin = false) {
            this.selectedPamong = id;
            this.pamongName = name;
            this.pamongRole = role;
            this.pamongIsAdmin = isAdmin;
            this.loadMessages();

            this.startPolling();
        },

        closeConversation() {
            this.selectedPamong = null;
            this.pamongName = '';
            this.pamongRole = '';
            this.pamongIsAdmin = false;
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
