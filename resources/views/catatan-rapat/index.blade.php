@extends('layouts.app')

@section('title', 'Catatan Musyawarah')

@section('content')
<div x-data="kanbanBoard()" x-init="init()" class="h-full">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Catatan Musyawarah</h1>
            <p class="pkg-page-subheading">Kelola hasil rapat, tindak lanjut, dan monitoring progres lintas agenda.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('persiapan-acara.index') }}" class="btn-secondary text-sm">
                Persiapan Acara
            </a>
            @if(auth()->user()->isAdmin())
            <button @click="showSettings = true" class="btn-secondary text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Pengaturan
            </button>
            @endif
            @if($canCreate)
            <button @click="openAddModal()" class="btn-primary text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Catatan
            </button>
            @endif
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="flex gap-4 overflow-x-auto pb-4" style="min-height: calc(100vh - 250px);">
        @foreach($columns as $column)
        <div class="kanban-column flex-1 min-w-[280px] pkg-card-soft p-3" data-column-id="{{ $column->id }}">
            <!-- Column Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $column->color }}"></div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $column->name }}</h3>
                    <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-full" x-text="getColumnCount({{ $column->id }})">{{ $column->cards->count() }}</span>
                </div>
            </div>

            <!-- Cards Container -->
            <div class="kanban-cards space-y-3 min-h-[100px]" data-column-id="{{ $column->id }}" 
                 @dragover.prevent="onDragOver($event)" 
                 @drop="onDrop($event, {{ $column->id }})">
                @foreach($column->cards as $card)
                <div class="kanban-card bg-white dark:bg-gray-700 rounded-lg p-3 shadow-sm cursor-move border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow"
                     draggable="true"
                     data-card-id="{{ $card->id }}"
                     @dragstart="onDragStart($event, {{ $card->id }})"
                     @dragend="onDragEnd($event)"
                     @click="openEditModal({{ $card->toJson() }})">
                    <!-- Priority Badge -->
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs px-2 py-0.5 rounded-full 
                            @if($card->priority == 'high') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                            @elseif($card->priority == 'medium') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 @endif">
                            {{ ucfirst($card->priority) }}
                        </span>
                        @if($card->due_date)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $card->due_date->format('d M') }}
                        </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-2">{{ $card->title }}</h4>

                    <!-- Description Preview -->
                    @if($card->description)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-2">{{ Str::limit($card->description, 80) }}</p>
                    @endif

                    <!-- Footer -->
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-600">
                        @if($card->tanggal_rapat)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Tgl {{ $card->tanggal_rapat->format('d M Y') }}
                        </span>
                        @else
                        <span></span>
                        @endif
                        
                        @if($card->assignee)
                        <div class="flex items-center gap-1">
                            <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-white text-xs">{{ substr($card->assignee->username, 0, 1) }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <!-- Activity History Panel -->
    <div class="mt-6" x-data="{ showHistory: false }">
        <button @click="showHistory = !showHistory" class="flex items-center gap-2 px-4 py-2.5 pkg-card hover:shadow-md transition-all w-full text-left border border-gray-200 dark:border-gray-700">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform" :class="showHistory && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-semibold text-gray-900 dark:text-white">Riwayat Aktivitas</span>
            <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-0.5 rounded-full">{{ $logs->count() }}</span>
        </button>

        <div x-show="showHistory" x-collapse x-cloak class="mt-3 pkg-card border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="max-h-[400px] overflow-y-auto">
                @forelse($logs as $log)
                <div class="flex items-start gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                    <!-- Action Icon -->
                    <div class="flex-shrink-0 mt-0.5">
                        @switch($log->action)
                            @case('created')
                                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                @break
                            @case('updated')
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                @break
                            @case('deleted')
                                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </div>
                                @break
                            @case('moved')
                                <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </div>
                                @break
                        @endswitch
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->user?->username ?? 'System' }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $log->action_label }}</span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">"{{ $log->card_title }}"</span>
                        </div>

                        <!-- Change details -->
                        @if($log->details)
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @if($log->action === 'moved')
                                <span class="inline-flex items-center gap-1">
                                    <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ $log->details['from_column'] ?? '-' }}</span>
                                    ke
                                    <span class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">{{ $log->details['to_column'] ?? '-' }}</span>
                                </span>
                            @elseif($log->action === 'created')
                                <span>Kolom: {{ $log->details['column'] ?? '-' }}</span>
                                @if(isset($log->details['assigned_to']))
                                    <span class="ml-2">| Ditugaskan ke: {{ $log->details['assigned_to'] }}</span>
                                @endif
                            @elseif($log->action === 'updated')
                                @foreach($log->details as $field => $change)
                                    @php
                                        $fieldLabels = [
                                            'title' => 'Judul',
                                            'description' => 'Deskripsi',
                                            'priority' => 'Prioritas',
                                            'due_date' => 'Due Date',
                                            'assigned_to' => 'Ditugaskan ke',
                                            'column' => 'Status',
                                        ];
                                    @endphp
                                    <div>
                                        {{ $fieldLabels[$field] ?? $field }}:
                                        <span class="line-through text-red-400">{{ is_array($change) ? Str::limit($change['from'] ?? '-', 30) : '-' }}</span>
                                        ke
                                        <span class="text-green-500">{{ is_array($change) ? Str::limit($change['to'] ?? '-', 30) : '-' }}</span>
                                    </div>
                                @endforeach
                            @elseif($log->action === 'deleted')
                                <span>Dari kolom: {{ $log->details['column'] ?? '-' }}</span>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Timestamp -->
                    <div class="flex-shrink-0 text-right">
                        <span class="text-xs text-gray-400 dark:text-gray-500" title="{{ $log->created_at->format('d M Y H:i:s') }}">
                            {{ $log->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <p class="text-sm">Belum ada aktivitas.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-lg p-6" @click.stop>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" x-text="editingCard ? 'Edit Catatan' : 'Tambah Catatan'"></h2>
            
            <form @submit.prevent="saveCard()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul *</label>
                        <input type="text" x-model="form.title" required class="w-full px-3 py-2 pkg-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                        <textarea x-model="form.description" rows="3" class="w-full px-3 py-2 pkg-field"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Rapat</label>
                            <input type="date" x-model="form.tanggal_rapat" class="w-full px-3 py-2 pkg-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                            <input type="date" x-model="form.due_date" class="w-full px-3 py-2 pkg-field">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select x-model="form.column_id" class="w-full px-3 py-2 pkg-field">
                                @foreach($columns as $col)
                                <option value="{{ $col->id }}">{{ $col->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prioritas</label>
                            <select x-model="form.priority" class="w-full px-3 py-2 pkg-field">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ditugaskan ke</label>
                        <select x-model="form.assigned_to" class="w-full px-3 py-2 pkg-field">
                            <option value="">-- Pilih --</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->username }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <button type="button" x-show="editingCard" @click="deleteCard()" class="text-red-600 hover:text-red-700 text-sm">Hapus</button>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Settings Modal -->
    <div x-show="showSettings" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showSettings = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md p-6" @click.stop>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Pengaturan Akses</h2>
            
            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">Siapa yang bisa membuat catatan rapat?</p>
                
                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer">
                    <input type="checkbox" x-model="settingsForm.admin" class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-gray-900 dark:text-white">Admin</span>
                </label>
                
                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer">
                    <input type="checkbox" x-model="settingsForm.teacher" class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-gray-900 dark:text-white">Pamong</span>
                </label>

                <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer">
                    <input type="checkbox" x-model="settingsForm.pkg_manager" class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-gray-900 dark:text-white">Pengurus PKG</span>
                </label>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="showSettings = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Batal</button>
                <button @click="saveSettings()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .kanban-card.dragging { opacity: 0.5; transform: rotate(3deg); }
    .kanban-cards.drag-over { background-color: rgba(59, 130, 246, 0.1); border-radius: 0.5rem; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
function kanbanBoard() {
    return {
        showModal: false,
        showSettings: false,
        editingCard: null,
        draggedCardId: null,
        form: {
            title: '',
            description: '',
            tanggal_rapat: '',
            due_date: '',
            column_id: {{ $columns->first()->id ?? 1 }},
            priority: 'medium',
            assigned_to: ''
        },
        settingsForm: {
            admin: {{ in_array('admin', json_decode($settings['can_create_roles'] ?? '[]', true)) ? 'true' : 'false' }},
            teacher: {{ in_array('teacher', json_decode($settings['can_create_roles'] ?? '[]', true)) ? 'true' : 'false' }},
            pkg_manager: {{ in_array('pkg_manager', json_decode($settings['can_create_roles'] ?? '[]', true)) ? 'true' : 'false' }}
        },

        init() {
            // Initialize
        },

        getColumnCount(columnId) {
            const container = document.querySelector(`.kanban-cards[data-column-id="${columnId}"]`);
            return container ? container.querySelectorAll('.kanban-card').length : 0;
        },

        openAddModal() {
            this.editingCard = null;
            this.form = {
                title: '',
                description: '',
                tanggal_rapat: '',
                due_date: '',
                column_id: {{ $columns->first()->id ?? 1 }},
                priority: 'medium',
                assigned_to: ''
            };
            this.showModal = true;
        },

        openEditModal(card) {
            this.editingCard = card;
            this.form = {
                title: card.title,
                description: card.description || '',
                tanggal_rapat: card.tanggal_rapat ? card.tanggal_rapat.split('T')[0] : '',
                due_date: card.due_date ? card.due_date.split('T')[0] : '',
                column_id: card.column_id,
                priority: card.priority,
                assigned_to: card.assigned_to || ''
            };
            this.showModal = true;
        },

        async saveCard() {
            const url = this.editingCard 
                ? `/catatan-rapat/${this.editingCard.id}` 
                : '/catatan-rapat';
            const method = this.editingCard ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                if (response.ok) {
                    this.showModal = false;
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async deleteCard() {
            const confirmed = await window.showConfirmation('Hapus catatan ini?', {
                title: 'Hapus catatan rapat',
                confirmText: 'Hapus',
                tone: 'danger'
            });
            if (!confirmed) return;

            try {
                const response = await fetch(`/catatan-rapat/${this.editingCard.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.showModal = false;
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async saveSettings() {
            const roles = [];
            if (this.settingsForm.admin) roles.push('admin');
            if (this.settingsForm.teacher) roles.push('teacher');
            if (this.settingsForm.pkg_manager) roles.push('pkg_manager');

            try {
                const response = await fetch('/catatan-rapat/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ can_create_roles: roles })
                });

                if (response.ok) {
                    this.showSettings = false;
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        onDragStart(event, cardId) {
            this.draggedCardId = cardId;
            event.target.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        },

        onDragEnd(event) {
            event.target.classList.remove('dragging');
            document.querySelectorAll('.kanban-cards').forEach(el => el.classList.remove('drag-over'));
        },

        onDragOver(event) {
            event.currentTarget.classList.add('drag-over');
        },

        async onDrop(event, columnId) {
            event.currentTarget.classList.remove('drag-over');
            
            const container = event.currentTarget;
            const cards = container.querySelectorAll('.kanban-card:not(.dragging)');
            let order = 0;

            // Calculate new order based on drop position
            for (let i = 0; i < cards.length; i++) {
                const rect = cards[i].getBoundingClientRect();
                if (event.clientY < rect.top + rect.height / 2) {
                    order = i;
                    break;
                }
                order = i + 1;
            }

            try {
                const response = await fetch('/catatan-rapat/move', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        card_id: this.draggedCardId,
                        column_id: columnId,
                        order: order
                    })
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    }
}
</script>
@endpush
@endsection


