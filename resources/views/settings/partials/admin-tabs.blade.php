@php
    // Definisi tiap tab. 'type' = 'content' (tab isi di halaman settings) atau 'route' (halaman terpisah).
    $iconGear = 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z';

    $settingsTabClient = $settingsTabClient ?? false;
    $currentTab = $tab ?? 'general';

    $defineContent = function (string $key, string $label, string $icon) use ($settingsTabClient, $currentTab) {
        return [
            'type' => 'content', 'key' => $key, 'label' => $label, 'icon' => $icon,
            'route' => route('settings.index', ['tab' => $key]),
            'active' => !$settingsTabClient && $currentTab === $key,
        ];
    };

    // Kelompok tab agar alurnya jelas: Tim & Akses, lalu Peserta, Tampilan, Fitur, Data.
    $tabGroups = [
        'Tim & Akses' => [
            ['type' => 'route', 'key' => 'user', 'label' => 'Pengguna', 'route' => route('settings.index', ['tab' => 'user']), 'active' => ($currentTab === 'user') || request()->routeIs('users.*'), 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197'],
            ['type' => 'route', 'key' => 'pamong', 'label' => 'Tim PKG', 'route' => route('settings.index', ['tab' => 'pamong']), 'active' => ($currentTab === 'pamong') || request()->routeIs('pamong.*'), 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857'],
            $defineContent('permissions', 'Hak Akses Default', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'),
        ],
        'Peserta' => [
            ['type' => 'route', 'key' => 'daftarulang', 'label' => 'Daftar Ulang', 'route' => route('admin.generus-registration.index'), 'active' => request()->routeIs('admin.generus-registration.*'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            $defineContent('registration', 'Daftar PKG', 'M15 7a3 3 0 11-6 0 3 3 0 016 0zm-9 13a6 6 0 0112 0M19 8v6m3-3h-6'),
            $defineContent('kelas', 'Kelas', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1'),
        ],
        'Tampilan' => [
            $defineContent('general', 'Umum', $iconGear),
            $defineContent('id_card', 'ID Card', 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4z'),
            $defineContent('theme', 'Tema', 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'),
        ],
        'Fitur' => [
            $defineContent('face_attendance', 'Scan Wajah', 'M15 11a3 3 0 11-6 0 3 3 0 016 0zM4 21a8 8 0 0116 0M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2'),
            $defineContent('popup', 'Popup', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5'),
            $defineContent('share_info', 'Share Info', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'),
        ],
        'Data' => [
            ['type' => 'route', 'key' => 'backup', 'label' => 'Backup', 'route' => route('settings.index', ['tab' => 'backup']), 'active' => ($currentTab === 'backup') || request()->routeIs('settings.backup*'), 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
        ],
    ];
@endphp

<div class="mb-6 overflow-x-auto">
    <nav class="flex min-w-max items-stretch gap-1 pb-1">
        @foreach($tabGroups as $groupLabel => $items)
            @if(!$loop->first)
                <span class="mx-1 self-center h-6 w-px bg-gray-200 dark:bg-gray-700"></span>
            @endif
            <div class="flex flex-col">
                <span class="mb-1 px-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $groupLabel }}</span>
                <div class="flex gap-2">
                    @foreach($items as $item)
                        <button type="button"
                            @if($settingsTabClient && $item['type'] === 'content')
                                @click="switchSettingsTab('{{ $item['key'] }}')"
                                :class="activeSettingsTab === '{{ $item['key'] }}' ? 'pkg-tab-link-active' : ''"
                            @else
                                onclick="window.location.href='{{ $item['route'] }}'"
                            @endif
                            class="pkg-tab-link text-sm font-medium whitespace-nowrap {{ $item['active'] ? 'pkg-tab-link-active' : '' }}">
                            <svg class="mr-1 inline-block h-4 w-4 md:h-5 md:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                            </svg>
                            <span class="hidden sm:inline">{{ $item['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>
</div>
