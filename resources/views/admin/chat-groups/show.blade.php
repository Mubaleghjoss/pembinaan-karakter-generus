@extends('layouts.app')

@section('title', $chatGroup->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="groupChatApp()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">{{ $chatGroup->name }}</h1>
            <p class="pkg-page-subheading">{{ $chatGroup->description }}</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('chat-groups.index') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <a href="{{ route('chat-groups.edit', $chatGroup) }}" class="btn-primary">Edit</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="pkg-card p-4">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Anggota ({{ $chatGroup->members->count() }})</h2>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($chatGroup->members as $member)
                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm">
                            {{ strtoupper(substr($member->user?->username ?? $member->siswa?->nama ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->user?->username ?? $member->siswa?->nama }}</p>
                            <p class="text-xs text-gray-500">{{ $member->role }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-2 pkg-card flex flex-col" style="height: 500px;">
            <div class="p-4 border-b dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white">Pesan Grup</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container">
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.is_mine ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'" class="max-w-md px-4 py-2 rounded-lg">
                            <p class="text-xs font-semibold mb-1" x-text="msg.sender_name"></p>
                            <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                            <p class="text-xs mt-1 opacity-70" x-text="msg.created_at"></p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="p-4 border-t dark:border-gray-700">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <textarea x-model="newMessage" rows="1" class="flex-1 pkg-field resize-none" placeholder="Ketik pesan..."></textarea>
                    <button type="submit" class="btn-primary">Kirim</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function groupChatApp() {
    return {
        messages: [],
        newMessage: '',
        init() { this.loadMessages(); setInterval(() => this.loadMessages(), 5000); },
        async loadMessages() {
            const res = await fetch('{{ route("chat-groups.messages", $chatGroup) }}');
            const data = await res.json();
            if (data.success) { this.messages = data.messages; this.$nextTick(() => { document.getElementById('messages-container').scrollTop = 99999; }); }
        },
        async sendMessage() {
            if (!this.newMessage.trim()) return;
            const formData = new FormData();
            formData.append('message', this.newMessage);
            const res = await fetch('{{ route("chat-groups.send", $chatGroup) }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: formData });
            const data = await res.json();
            if (data.success) { this.messages.push(data.message); this.newMessage = ''; }
        }
    }
}
</script>
@endsection
