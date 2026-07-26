<div class="pkg-subnav-scroll mb-6" aria-label="Menu Gamifikasi">
    <nav class="flex min-w-max flex-nowrap gap-2">
        <a href="{{ route('admin.gamification.badges') }}" class="{{ request()->routeIs('admin.gamification.badges') ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link' }} shrink-0 whitespace-nowrap text-sm font-medium">
            Pin Penghargaan
        </a>
        <a href="{{ route('admin.gamification.levels') }}" class="{{ request()->routeIs('admin.gamification.levels') ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link' }} shrink-0 whitespace-nowrap text-sm font-medium">
            Kelola Level
        </a>
        <a href="{{ route('admin.gamification.analytics') }}" class="{{ request()->routeIs('admin.gamification.analytics') ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link' }} shrink-0 whitespace-nowrap text-sm font-medium">
            Analitik
        </a>
        <a href="{{ route('admin.gamification.transactions') }}" class="{{ request()->routeIs('admin.gamification.transactions') ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link' }} shrink-0 whitespace-nowrap text-sm font-medium">
            Riwayat Transaksi
        </a>
    </nav>
</div>
