@props([
    'title',
    'description' => null,
    'open' => false,
    'sectionId' => null,
    'compact' => false,
])

<section
    x-data="{ sectionOpen: @js((bool) $open) }"
    @if($sectionId) @pkg:open-section.window="if ($event.detail.id === @js($sectionId)) sectionOpen = true" @endif
    {{ $attributes->class(['pkg-panel overflow-hidden']) }}
    data-collapsible-section
>
    <button
        type="button"
        class="flex w-full items-center justify-between gap-3 text-left {{ $compact ? 'px-4 py-3' : 'px-4 py-4 sm:px-6' }}"
        @click="sectionOpen = !sectionOpen"
        :aria-expanded="String(sectionOpen)"
    >
        <span class="min-w-0">
            <span class="block font-bold text-gray-900 dark:text-white {{ $compact ? 'text-sm sm:text-base' : 'text-base sm:text-lg' }}">{{ $title }}</span>
            @if($description)
                <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400 sm:text-sm">{{ $description }}</span>
            @endif
        </span>
        <span class="inline-flex shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition dark:bg-slate-800 dark:text-slate-300 {{ $compact ? 'h-8 w-8' : 'h-9 w-9' }}" :class="sectionOpen ? 'rotate-180' : ''" aria-hidden="true">
            <svg class="{{ $compact ? 'h-4 w-4' : 'h-5 w-5' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
            </svg>
        </span>
    </button>

    <div
        x-show="sectionOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="border-t border-gray-200 dark:border-gray-700 {{ $compact ? 'p-4' : 'p-4 sm:p-6' }}"
    >
        {{ $slot }}
    </div>
</section>
