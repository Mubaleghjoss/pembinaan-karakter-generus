@php
    $biometricStatusAdmin = $biometricStatusAdmin ?? ($hasBiometricAdmin ? 'active' : 'inactive');
    $biometricAdminTone = match ($biometricStatusAdmin) {
        'active' => 'text-emerald-600',
        'legacy' => 'text-amber-600',
        default => 'text-gray-400',
    };
    $biometricAdminLabel = match ($biometricStatusAdmin) {
        'active' => 'Aktif',
        'legacy' => 'Perlu daftar ulang',
        default => 'Belum aktif',
    };
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <div class="lg:col-span-2">
        <div class="pkg-panel-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Menu Utama</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('presensi.index') }}" class="p-4 bg-green-50 hover:bg-green-100 rounded-xl text-center transition">
                    <div class="w-12 h-12 bg-green-500 rounded-xl mx-auto mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="font-medium text-gray-800">Presensi</p>
                </a>
                <a href="{{ route('siswa.index') }}" class="p-4 bg-blue-50 hover:bg-blue-100 rounded-xl text-center transition">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl mx-auto mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                        </svg>
                    </div>
                    <p class="font-medium text-gray-800">Siswa</p>
                </a>
                <a href="{{ route('tugas-pkg.verification') }}" class="relative p-4 bg-purple-50 hover:bg-purple-100 rounded-xl text-center transition">
                    @if(($pendingPkgVerificationCount ?? 0) > 0)
                        <span class="absolute right-3 top-3 inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">
                            {{ $pendingPkgVerificationCount }}
                        </span>
                    @endif
                    <div class="w-12 h-12 bg-purple-500 rounded-xl mx-auto mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <p class="font-medium text-gray-800">Verifikasi Tugas PKG</p>
                    <p class="text-xs {{ ($pendingPkgVerificationCount ?? 0) > 0 ? 'text-red-600' : 'text-gray-500' }}">
                        {{ ($pendingPkgVerificationCount ?? 0) > 0 ? $pendingPkgVerificationCount . ' menunggu verifikasi' : 'Belum ada antrean baru' }}
                    </p>
                </a>
                <a href="{{ route('calendar.index') }}" class="p-4 bg-orange-50 hover:bg-orange-100 rounded-xl text-center transition">
                    <div class="w-12 h-12 bg-orange-500 rounded-xl mx-auto mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="font-medium text-gray-800">Kalender</p>
                </a>
                <a href="{{ route('biometrik') }}" class="p-4 bg-emerald-50 hover:bg-emerald-100 rounded-xl text-center transition">
                    <div class="w-12 h-12 bg-emerald-500 rounded-xl mx-auto mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                        </svg>
                    </div>
                    <p class="font-medium text-gray-800">Biometrik</p>
                    <p class="text-xs {{ $biometricAdminTone }}">{{ $biometricAdminLabel }}</p>
                </a>
            </div>
        </div>

        @if($topStudents->count() > 0)
        <div class="pkg-panel-lg p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Top Siswa (Poin)</h2>
            <div class="space-y-3">
                @foreach($topStudents as $index => $sp)
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                        {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-200 text-gray-700' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600')) }}">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 dark:text-white">{{ $sp->siswa->nama ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">Level {{ $sp->level }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-indigo-600">{{ number_format($sp->total_points) }}</p>
                        <p class="text-xs text-gray-500">poin</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="lg:col-span-1 space-y-6">
        <div class="pkg-panel-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Presensi Terbaru</h2>
            <div class="space-y-3">
                @forelse($recentPresensi as $p)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full {{ $p->status === 'hadir' ? 'bg-green-500' : ($p->status === 'terlambat' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $p->siswa->nama ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $p->jam_masuk?->format('H:i') }} - {{ ucfirst($p->status) }}</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">Belum ada presensi hari ini</p>
                @endforelse
            </div>
        </div>

        <div class="pkg-panel-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Tugas PKG Sudah Dikerjakan</h2>
            <div class="space-y-3">
                @forelse($verifiedPerSiswa as $item)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $item->siswa->nama ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $item->siswa->nis ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-green-600">{{ $item->total_verified }}</span>
                        <p class="text-xs text-gray-400">tugas</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">Belum ada tugas yang diverifikasi</p>
                @endforelse
            </div>
        </div>

        <div class="pkg-panel-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Tugas PKG Terverifikasi</h2>
            <div class="space-y-3">
                @forelse($recentKarakter as $k)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">*</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $k->siswa->nama ?? '-' }}</p>
                        <p class="text-xs text-gray-500">{{ $k->karakter->nama ?? '-' }}</p>
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ $k->verified_at->diffForHumans() }}
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">Belum ada tugas terverifikasi</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
