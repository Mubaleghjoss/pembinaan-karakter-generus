@extends('layouts.app')

@section('title', 'Detail Pamong - ' . $pamong->username)

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Detail Pamong</h1>
            <p class="pkg-page-subheading">{{ $pamong->username }} | {{ $pamong->email }}</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('pamong.index') }}" class="btn-secondary">Kembali</a>
            <a href="{{ route('pamong-presensi.card', $pamong) }}" class="btn-success text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                Kartu QR
            </a>
            <form action="{{ route('pamong.reset-password', $pamong) }}" method="POST" class="inline" data-confirm="Reset password ke username ({{ $pamong->username }})?" data-confirm-title="Reset password pamong" data-confirm-button="Reset" data-confirm-tone="warning">
                @csrf
                <button type="submit" class="btn-secondary text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Reset Password
                </button>
            </form>
            <a href="{{ route('pamong.assign.form', $pamong) }}" class="btn-primary text-sm">Assign Siswa</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Pamong Info -->
    <div class="mb-6 pkg-card p-6">
        <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-full bg-green-600 flex items-center justify-center">
                <span class="text-white text-2xl font-bold">{{ strtoupper(substr($pamong->username, 0, 1)) }}</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $pamong->username }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ $pamong->email }}</p>
                <span class="mt-2 inline-block px-3 py-1 text-sm font-medium rounded-full {{ $pamong->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $pamong->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Assigned Students -->
    <div class="pkg-card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Siswa yang Ditugaskan ({{ $pamong->assigned_students_count }})
            </h3>
        </div>
        
        @if($assignedStudents->count() > 0)
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Siswa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">NIS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kelas</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($assignedStudents as $assignment)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $assignment->siswa->nama }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $assignment->siswa->nis }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $assignment->siswa->kelas->nama ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <form action="{{ route('pamong.remove-assignment', [$pamong, $assignment->siswa]) }}" method="POST" class="inline" data-confirm="Yakin ingin menghapus siswa ini dari penugasan?" data-confirm-title="Hapus penugasan siswa" data-confirm-button="Hapus" data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 text-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $assignedStudents->links() }}
        </div>
        @else
        <div class="pkg-empty-state">
            <svg class="pkg-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="pkg-empty-title">Belum ada siswa tertaut</p>
            <p class="pkg-empty-copy">Pamong ini belum memiliki penugasan siswa.</p>
        </div>
        @endif
    </div>
</div>
@endsection

