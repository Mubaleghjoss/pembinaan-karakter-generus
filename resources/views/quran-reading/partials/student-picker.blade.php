@php
    $collapsed = $collapsed ?? false;
@endphp

@if($collapsed)
    <details class="pkg-panel overflow-hidden" data-quran-student-picker>
        <summary class="cursor-pointer border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <span class="block font-bold">Ganti Generus</span>
            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $selectedSiswa->nama }} sedang dipilih. Buka untuk memilih Generus lain.</span>
        </summary>
@else
    <section class="pkg-panel overflow-hidden" data-quran-student-picker>
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="font-bold">Pilih Generus</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilihan tetap aktif saat Anda berpindah tab.</p>
        </div>
@endif
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($siswaList as $siswa)
            @php
                $studentUrl = route('quran.index', array_merge(request()->except(['page', 'tab']), [
                    'tab' => $targetTab,
                    'siswa_id' => $siswa->id,
                ])).'#'.$targetTab;
            @endphp
            <div class="flex min-h-16 items-center gap-2 px-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $selectedSiswa?->id === $siswa->id ? 'bg-emerald-50 dark:bg-emerald-950/30' : '' }}">
                @if($bulkSelectable ?? false)
                    <label class="flex min-h-11 min-w-11 shrink-0 cursor-pointer items-center justify-center" aria-label="Pilih {{ $siswa->nama }} untuk cetak massal"><input type="checkbox" class="pkg-check" value="{{ $siswa->id }}" data-quran-bulk-student></label>
                @endif
                <a href="{{ $studentUrl }}" class="flex min-w-0 flex-1 items-center justify-between gap-3 py-3">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $siswa->nama }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $siswa->nis }} &middot; {{ $siswa->school_grade_label }} &middot; {{ $siswa->kelompok_label ?? $siswa->kelompok ?? '-' }}</p>
                </div>
                <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        @empty
            <div class="pkg-empty-state"><p class="pkg-empty-title">Generus tidak ditemukan</p></div>
        @endforelse
    </div>
    @if($siswaList->hasPages())
        <div class="border-t border-gray-200 p-4 dark:border-gray-700">
            {{ (clone $siswaList)->appends(array_merge(request()->except(['page', 'tab']), ['tab' => $targetTab]))->fragment($targetTab)->links() }}
        </div>
    @endif
@if($collapsed)
    </details>
@else
    </section>
@endif
