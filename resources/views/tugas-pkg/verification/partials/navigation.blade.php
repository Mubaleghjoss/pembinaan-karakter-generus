<div class="sticky top-0 z-30 -mx-4 mb-6 max-w-[calc(100%+2rem)] overflow-x-auto overscroll-x-contain border-b border-slate-200/80 bg-white/95 px-4 py-3 backdrop-blur dark:border-slate-700/80 dark:bg-slate-950/95 sm:-mx-6 sm:max-w-[calc(100%+3rem)] sm:px-6 lg:-mx-8 lg:max-w-[calc(100%+4rem)] lg:px-8">
    <nav class="flex min-w-max flex-nowrap gap-3" role="tablist" aria-label="Menu Verifikasi Tugas PKG">
        <button type="button" role="tab" @click="activeTab = 'siswa'" :aria-selected="activeTab === 'siswa'" :class="activeTab === 'siswa' ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link'" class="flex shrink-0 items-center gap-2 whitespace-nowrap text-sm font-medium">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Ceklis Siswa
        </button>

        <button type="button" role="tab" @click="activeTab = 'verification'" :aria-selected="activeTab === 'verification'" :class="activeTab === 'verification' ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link'" class="flex shrink-0 items-center gap-2 whitespace-nowrap text-sm font-medium">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Verifikasi Tugas PKG
            @if(isset($stats) && $stats['unverified'] > 0)
                <span class="pkg-status-badge pkg-status-danger !px-2 !py-0.5 text-[11px]">{{ $stats['unverified'] }}</span>
            @endif
        </button>

        @if(auth()->user()->hasPamongCrudPermission('tracer_karakter', 'create'))
            <button type="button" role="tab" @click="activeTab = 'import'" :aria-selected="activeTab === 'import'" :class="activeTab === 'import' ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link'" class="flex shrink-0 items-center gap-2 whitespace-nowrap text-sm font-medium">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Impor Excel
            </button>

            <button type="button" role="tab" @click="activeTab = 'karakter'; loadKarakter()" :aria-selected="activeTab === 'karakter'" :class="activeTab === 'karakter' ? 'pkg-tab-link pkg-tab-link-active' : 'pkg-tab-link'" class="flex shrink-0 items-center gap-2 whitespace-nowrap text-sm font-medium">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Kelola Karakter
            </button>
        @endif
    </nav>
</div>
