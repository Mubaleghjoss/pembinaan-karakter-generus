@php
    $folders = $folders ?? collect();
    $detailRouteName = $detailRouteName ?? 'public.materi.show';
    $emptyTitle = $emptyTitle ?? 'Belum ada materi';
    $emptyCopy = $emptyCopy ?? 'Materi belum tersedia di folder ini.';
    $level = $level ?? 0;
@endphp

<div class="{{ $level === 0 ? 'space-y-4' : 'space-y-3' }}">
    @foreach($folders as $folder)
        @php
            $children = $folder->childrenTree ?? collect();
            $materiItems = $folder->materi ?? collect();
            $totalCount = (int) ($folder->total_materi_count ?? $folder->materi_count ?? $materiItems->count());
        @endphp
        <details class="{{ $level === 0 ? 'pkg-panel' : 'rounded-xl border border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/60' }} group overflow-hidden">
            <summary class="flex cursor-pointer list-none flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h3 class="{{ $level === 0 ? 'text-xl' : 'text-base' }} font-bold text-gray-900 dark:text-white">
                        {{ $folder->name }}
                    </h3>
                    @if($folder->description)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $folder->description }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                        {{ $totalCount }} materi
                    </span>
                    <span class="btn-secondary px-3 py-2 text-xs">
                        <span class="group-open:hidden">Buka</span>
                        <span class="hidden group-open:inline">Tutup</span>
                    </span>
                </div>
            </summary>

            <div class="space-y-4 border-t border-gray-200 p-5 dark:border-gray-700">
                @if($children->isNotEmpty())
                    @include('materi.partials.read-only-folder-tree', [
                        'folders' => $children,
                        'detailRouteName' => $detailRouteName,
                        'emptyTitle' => $emptyTitle,
                        'emptyCopy' => $emptyCopy,
                        'level' => $level + 1,
                    ])
                @endif

                @if($materiItems->isNotEmpty())
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($materiItems as $item)
                            <a href="{{ route($detailRouteName, $item) }}" class="pkg-card-soft p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $item->bulan?->format('F Y') ?? 'Periode belum diatur' }}</div>
                                <h5 class="text-base font-semibold text-gray-900 dark:text-white">{{ $item->judul }}</h5>
                                <p class="mt-2 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->deskripsi ?? 'Tidak ada deskripsi' }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if($item->hasPdfFiles())
                                        <span class="rounded bg-red-100 px-2 py-1 text-xs text-red-800 dark:bg-red-900 dark:text-red-200">PDF</span>
                                    @endif
                                    @if($item->video_url)
                                        <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-200">Video</span>
                                    @endif
                                    @if($item->isRppPublished())
                                        <span class="rounded bg-teal-100 px-2 py-1 text-xs text-teal-800 dark:bg-teal-900 dark:text-teal-200">RPP</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @elseif($children->isEmpty())
                    <div class="pkg-empty-state py-8">
                        <p class="pkg-empty-title">{{ $emptyTitle }}</p>
                        <p class="pkg-empty-copy">{{ $emptyCopy }}</p>
                    </div>
                @endif
            </div>
        </details>
    @endforeach
</div>
