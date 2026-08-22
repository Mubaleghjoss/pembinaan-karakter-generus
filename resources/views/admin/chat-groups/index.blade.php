@extends('layouts.app')

@section('title', 'Kelola Grup Chat')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Kelola Grup Chat</h1>
            <p class="pkg-page-subheading">Buat dan kelola grup chat untuk komunikasi massal.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('chat-groups.create') }}" class="btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Grup Baru
            </a>
        </div>
    </div>

    {{-- Kartu (mobile) --}}
    <div class="pkg-cards-mobile">
        @forelse($groups as $group)
            <div class="pkg-data-card">
                <div class="pkg-data-card-head">
                    <div class="min-w-0">
                        <p class="pkg-data-card-title">{{ $group->name }}</p>
                        @if($group->description)<p class="pkg-data-card-sub">{{ Str::limit($group->description, 60) }}</p>@endif
                    </div>
                    <span class="pkg-status-badge shrink-0
                        @if($group->type === 'custom') pkg-status-info
                        @elseif($group->type === 'all_pamong') pkg-status-success
                        @else pkg-status-warning @endif">
                        {{ ucfirst(str_replace('_', ' ', $group->type)) }}
                    </span>
                </div>
                <div class="pkg-data-card-meta">
                    <div class="pkg-data-card-row"><span class="k">Anggota</span><span class="v">{{ $group->members->count() }}</span></div>
                    <div class="pkg-data-card-row"><span class="k">Dibuat</span><span class="v">{{ $group->created_at->format('d M Y') }}</span></div>
                </div>
                <div class="pkg-data-card-actions">
                    <a href="{{ route('chat-groups.show', $group) }}" class="btn-secondary">Detail</a>
                    <a href="{{ route('chat-groups.edit', $group) }}" class="btn-primary">Edit</a>
                </div>
            </div>
        @empty
            <div class="pkg-empty-state pkg-card">
                <h3 class="pkg-empty-title">Belum Ada Grup Chat</h3>
                <p class="pkg-empty-copy">Buat grup pertama untuk memulai komunikasi massal.</p>
                <a href="{{ route('chat-groups.create') }}" class="btn-primary mt-4">Buat Grup Baru</a>
            </div>
        @endforelse
        @if($groups->hasPages())
            <div class="mt-3">{{ $groups->links() }}</div>
        @endif
    </div>

    <div class="pkg-table-desktop pkg-panel overflow-hidden">
        <div class="pkg-mobile-table">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Grup</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Anggota</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dibuat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($groups as $group)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="pkg-mobile-main px-6 py-4" data-label="Nama grup">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $group->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($group->description, 50) }}</div>
                    </td>
                    <td class="px-6 py-4" data-label="Tipe">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($group->type === 'custom') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($group->type === 'all_pamong') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($group->type === 'all_siswa') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                            {{ ucfirst(str_replace('_', ' ', $group->type)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-900 dark:text-white" data-label="Anggota">{{ $group->members->count() }}</td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400" data-label="Dibuat">{{ $group->created_at->format('d M Y') }}</td>
                    <td class="pkg-mobile-actions px-6 py-4" data-label="Aksi">
                        <div class="flex gap-2">
                            <a href="{{ route('chat-groups.show', $group) }}" class="btn-secondary !px-3 !py-2" aria-label="Detail grup">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </a>
                            <a href="{{ route('chat-groups.edit', $group) }}" class="btn-secondary !px-3 !py-2" aria-label="Edit grup">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="pkg-mobile-empty px-6 py-10">
                        <div class="pkg-empty-state">
                            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <h3 class="pkg-empty-title">Belum Ada Grup Chat</h3>
                            <p class="pkg-empty-copy">Buat grup pertama untuk memulai komunikasi massal.</p>
                            <a href="{{ route('chat-groups.create') }}" class="btn-primary mt-4">Buat Grup Baru</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($groups->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $groups->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
