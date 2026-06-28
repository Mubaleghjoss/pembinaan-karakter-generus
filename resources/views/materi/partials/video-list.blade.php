@php
    $videoItems = $videoItems ?? $materi->video_items;
    $withBorder = $withBorder ?? false;
@endphp

@if(! empty($videoItems))
<div class="p-6 {{ $withBorder ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
    <h2 class="mb-4 flex items-center text-lg font-semibold text-gray-900 dark:text-white">
        <svg class="mr-2 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10,15L15.19,12L10,9V15M21.56,7.17C21.69,7.64 21.78,8.27 21.84,9.07C21.91,9.87 21.94,10.56 21.94,11.16L22,12C22,14.19 21.84,15.8 21.56,16.83C21.31,17.73 20.73,18.31 19.83,18.56C19.36,18.69 18.5,18.78 17.18,18.84C15.88,18.91 14.69,18.94 13.59,18.94L12,19C7.81,19 5.2,18.84 4.17,18.56C3.27,18.31 2.69,17.73 2.44,16.83C2.31,16.36 2.22,15.73 2.16,14.93C2.09,14.13 2.06,13.44 2.06,12.84L2,12C2,9.81 2.16,8.2 2.44,7.17C2.69,6.27 3.27,5.69 4.17,5.44C4.64,5.31 5.5,5.22 6.82,5.16C8.12,5.09 9.31,5.06 10.41,5.06L12,5C16.19,5 18.8,5.16 19.83,5.44C20.73,5.69 21.31,6.27 21.56,7.17Z"/>
        </svg>
        Video Pembelajaran
    </h2>

    <div class="space-y-4">
        @foreach($videoItems as $video)
            <div class="pkg-card-soft overflow-hidden p-0">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $video['title'] }}</p>
                        <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ $video['url'] }}</p>
                    </div>
                    <a href="{{ $video['url'] }}" target="_blank" rel="noopener" class="btn-secondary justify-center text-sm !px-3 !py-2">
                        Buka Tab Baru
                    </a>
                </div>

                @if($video['embed_url'])
                    <div class="aspect-video bg-gray-100 dark:bg-gray-950">
                        <iframe src="{{ $video['embed_url'] }}"
                                title="{{ $video['title'] }}"
                                class="h-full w-full border-0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                @else
                    <div class="p-5">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-100">
                            Link video ini tidak bisa diputar langsung di halaman. Gunakan tombol Buka Tab Baru untuk melihatnya.
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
