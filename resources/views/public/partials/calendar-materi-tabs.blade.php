@php
    $activePublicTab = $activePublicTab ?? 'calendar';
    $tabBaseClass = 'pkg-public-tab';
    $tabActiveClass = 'bg-emerald-600 text-white shadow-[0_16px_34px_rgba(13,148,136,0.22)] dark:bg-emerald-500 dark:text-slate-950';
    $tabIdleClass = 'bg-white/80 text-slate-700 border border-slate-200 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-200 dark:hover:border-emerald-900/60';
@endphp

<nav class="pkg-public-tab-shell mb-6" aria-label="Navigasi Kalender dan Materi" data-reveal="up">
    <a href="{{ route('public.calendar.index') }}" class="{{ $tabBaseClass }} {{ $activePublicTab === 'calendar' ? $tabActiveClass : $tabIdleClass }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Kalender
    </a>
    <a href="{{ route('materi.index') }}" class="{{ $tabBaseClass }} {{ $activePublicTab === 'materi' ? $tabActiveClass : $tabIdleClass }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
        </svg>
        Materi
    </a>
</nav>
