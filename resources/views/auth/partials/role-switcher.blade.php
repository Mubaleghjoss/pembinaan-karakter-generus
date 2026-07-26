@php
    $activeRole = $activeRole ?? 'staff';
    $roles = [
        [
            'key' => 'siswa',
            'label' => 'Siswa',
            'hint' => 'NIS',
            'route' => 'siswa.login',
            'color' => 'blue',
        ],
        [
            'key' => 'ortu',
            'label' => 'Orang Tua',
            'hint' => 'Akun ortu',
            'route' => 'ortu.login',
            'color' => 'teal',
        ],
        [
            'key' => 'staff',
            'label' => 'Pamong/Guru',
            'hint' => 'Termasuk Admin',
            'route' => 'login',
            'color' => 'emerald',
        ],
    ];
@endphp

<div class="mb-4" aria-label="Pilih jenis akun">
    <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Masuk sebagai</p>
    <div class="pkg-auth-role-grid">
        @foreach($roles as $role)
            @php($isActive = $activeRole === $role['key'])
            <a
                href="{{ route($role['route']) }}"
                class="pkg-auth-role {{ $isActive ? 'is-active' : '' }}"
                @if($isActive) aria-current="page" @endif
            >
                <span class="pkg-auth-role-icon" aria-hidden="true">
                    @if($role['key'] === 'siswa')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15z"/></svg>
                    @elseif($role['key'] === 'ortu')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87m-1-11.96a4 4 0 010 7.75"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.42A12 12 0 0119 15.5c0 1.72-.36 3.36-1 4.84M12 14l-6.16-3.42A12 12 0 005 15.5c0 1.72.36 3.36 1 4.84"/></svg>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-xs font-extrabold sm:text-sm">{{ $role['label'] }}</span>
                    <span class="mt-0.5 hidden truncate text-[10px] font-medium opacity-70 min-[360px]:block">{{ $role['hint'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
