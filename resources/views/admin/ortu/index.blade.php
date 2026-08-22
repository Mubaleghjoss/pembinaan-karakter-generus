@extends('layouts.app')

@section('title', 'Data Orang Tua Siswa')

@section('content')
<div class="space-y-6">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Data Orang Tua</h1>
            <p class="pkg-page-subheading">Kelola akun dan pantau aktivitas orang tua siswa</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <form action="{{ route('ortu-management.index') }}" method="GET" class="pkg-filter-bar">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <select name="school_grade" onchange="this.form.submit()" class="pkg-field w-full text-sm">
                <option value="">Semua Kelas Sekolah</option>
                @foreach($schoolGradeOptions as $value => $label)
                <option value="{{ $value }}" {{ request('school_grade') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="pamong_id" onchange="this.form.submit()" class="pkg-field w-full text-sm"><option value="">Semua Pamong</option>@foreach($pamongOptions as $pamong)<option value="{{ $pamong->id }}" @selected((string) request('pamong_id') === (string) $pamong->id)>{{ $pamong->name ?: $pamong->username }}</option>@endforeach</select>
            <div class="relative sm:col-span-2 xl:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Siswa / NIS..." class="w-full pl-10 pr-4 py-2 pkg-field text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </form>

    {{-- Kartu (mobile) --}}
    <div class="pkg-cards-mobile">
        @forelse($siswa as $s)
            <div class="pkg-data-card">
                <div class="pkg-data-card-head">
                    <div class="flex min-w-0 items-start gap-3">
                        @if($s->foto_path)
                            <img class="h-11 w-11 flex-none rounded-full object-cover" src="{{ asset('storage/' . $s->foto_path) }}" alt="">
                        @else
                            <span class="pkg-data-card-badge">{{ substr($s->nama, 0, 1) }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="pkg-data-card-title">{{ $s->nama }}</p>
                            <p class="pkg-data-card-sub">{{ $s->nis }} · {{ $s->school_grade_label }}</p>
                        </div>
                    </div>
                </div>
                <div class="pkg-data-card-meta">
                    <div class="pkg-data-card-row"><span class="k">Pamong</span><span class="v">{{ $s->pamongAssignments->pluck('pamong')->filter()->map(fn ($pamong) => $pamong->name ?: $pamong->username)->join(', ') ?: 'Belum ada' }}</span></div>
                    <div class="pkg-data-card-row"><span class="k">Username Ortu</span><span class="v font-mono">{{ $s->ortu_username ?? '-' }}</span></div>
                    <div class="pkg-data-card-row" x-data="{ show: false }">
                        <span class="k">Password</span>
                        <span class="v flex items-center justify-end gap-2">
                            <span x-show="!show" class="text-gray-400">••••••</span>
                            <span x-show="show" class="font-mono">{{ $s->ortu_password_plain ?? 'Sama dgn Siswa' }}</span>
                            <button type="button" @click="show = !show" class="text-gray-400 hover:text-gray-600" aria-label="Lihat password">
                                <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </span>
                    </div>
                    <div class="pkg-data-card-row"><span class="k">Aktivitas</span><span class="v">{{ $s->last_activity ? \Carbon\Carbon::parse($s->last_activity)->diffForHumans() : 'Belum ada' }}</span></div>
                </div>
                <div class="pkg-data-card-actions">
                    <form action="{{ route('ortu-management.reset', $s->id) }}" method="POST" data-confirm="Yakin reset password ortu siswa ini?" data-confirm-title="Reset password ortu" data-confirm-button="Reset" data-confirm-tone="warning">
                        @csrf
                        <button type="submit" class="btn-secondary">Reset Password Ortu</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="pkg-empty-state pkg-card">
                <p class="pkg-empty-title">Tidak ada data</p>
                <p class="pkg-empty-copy">Tidak ada siswa yang cocok dengan filter.</p>
            </div>
        @endforelse
        <p class="mt-3 px-1 text-sm text-gray-500 dark:text-gray-400">Menampilkan semua {{ $siswa->count() }} data.</p>
    </div>

    <!-- Table (desktop) -->
    <div class="pkg-table-desktop bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto pkg-mobile-table">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siswa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username Ortu</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Password</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aktivitas Terakhir</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="pkg-table-body divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($siswa as $s)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap pkg-mobile-main" data-label="Siswa">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    @if($s->foto_path)
                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $s->foto_path) }}" alt="">
                                    @else
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                        {{ substr($s->nama, 0, 1) }}
                                    </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $s->nama }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $s->nis }} | {{ $s->school_grade_label }} | {{ $s->pamongAssignments->pluck('pamong')->filter()->map(fn ($pamong) => $pamong->name ?: $pamong->username)->join(', ') ?: 'Belum ada Pamong' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Username Ortu">
                            <span class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 rounded text-gray-800 dark:text-gray-200">
                                {{ $s->ortu_username ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Password">
                            <div x-data="{ show: false }" class="flex items-center gap-2">
                                <span x-show="!show" class="text-sm text-gray-400">******</span>
                                <span x-show="show" class="font-mono text-sm text-gray-800 dark:text-gray-200 bg-yellow-50 dark:bg-yellow-900/30 px-1 rounded">
                                    {{ $s->ortu_password_plain ?? 'Sama dgn Siswa' }}
                                </span>
                                <button @click="show = !show" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap" data-label="Aktivitas Terakhir">
                            @if($s->last_activity)
                                <div>
                                    <div class="text-sm text-gray-900 dark:text-white font-medium">
                                        {{ \Carbon\Carbon::parse($s->last_activity)->diffForHumans() }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $s->last_activity_description }}
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-400 italic">Belum ada aktivitas</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium pkg-mobile-actions" data-label="Aksi">
                            <form action="{{ route('ortu-management.reset', $s->id) }}" method="POST" class="inline-block" data-confirm="Yakin reset password ortu siswa ini?" data-confirm-title="Reset password ortu" data-confirm-button="Reset" data-confirm-tone="warning">
                                @csrf
                                <button type="submit" class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 bg-orange-50 dark:bg-orange-900/20 px-3 py-1.5 rounded-lg border border-orange-200 dark:border-orange-800 transition-colors">
                                    Reset Pass
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 pkg-mobile-empty" data-label="">
                            Tidak ada data siswa ditemukan matching filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-300">Menampilkan semua {{ $siswa->count() }} data orang tua.</p>
        </div>
    </div>
</div>
@endsection


