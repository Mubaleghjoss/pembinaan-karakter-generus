<section class="pkg-panel overflow-hidden">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <h2 class="font-bold">Pilih Generus</h2>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilihan tetap aktif saat Anda berpindah tab.</p>
    </div>
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($siswaList as $siswa)
            @php
                $studentUrl = route('quran.index', array_merge(request()->except(['page', 'tab']), [
                    'tab' => $targetTab,
                    'siswa_id' => $siswa->id,
                ])).'#'.$targetTab;
            @endphp
            <a href="{{ $studentUrl }}" class="flex min-h-16 items-center justify-between gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $selectedSiswa?->id === $siswa->id ? 'bg-emerald-50 dark:bg-emerald-950/30' : '' }}">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $siswa->nama }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $siswa->nis }} &middot; {{ $siswa->kelas?->nama ?? 'Tanpa kelas' }} &middot; {{ $siswa->kelompok_label ?? $siswa->kelompok ?? '-' }}</p>
                </div>
                <span aria-hidden="true">&rarr;</span>
            </a>
        @empty
            <div class="pkg-empty-state"><p class="pkg-empty-title">Generus tidak ditemukan</p></div>
        @endforelse
    </div>
    @if($siswaList->hasPages())
        <div class="border-t border-gray-200 p-4 dark:border-gray-700">
            {{ (clone $siswaList)->appends(array_merge(request()->except(['page', 'tab']), ['tab' => $targetTab]))->fragment($targetTab)->links() }}
        </div>
    @endif
</section>
