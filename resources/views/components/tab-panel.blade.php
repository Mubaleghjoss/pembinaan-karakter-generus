@props([
    'id' => '',
    'lazy' => false,
    'class' => '',
])

@php
    $panelId = 'panel-' . $id;
@endphp

<div
    x-show="activeTab === '{{ $id }}'"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 transform translate-y-1"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    id="{{ $panelId }}"
    role="tabpanel"
    aria-labelledby="tab-{{ $id }}"
    class="{{ $class }}"
>
    {{ $slot }}
</div>
