@php
    $iconClass = $iconClass ?? 'h-5 w-5';
@endphp

@switch($icon ?? '')
    @case('home')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 11l9-8 9 8v9a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1v-9z"/></svg>
        @break
    @case('calendar')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3v4m8-4v4M4 9h16M5 5h14a1 1 0 011 1v14H4V6a1 1 0 011-1z"/></svg>
        @break
    @case('check')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @break
    @case('book')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a3 3 0 013-3h5v18H7a3 3 0 00-3 2V5zm16 0a3 3 0 00-3-3h-5v18h5a3 3 0 013 2V5z"/></svg>
        @break
    @case('chat')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A7.25 7.25 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        @break
    @case('journal')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4h6a2 2 0 012 2v14H7V6a2 2 0 012-2zm1-2h4v4h-4V2zm0 8h4m-4 4h4"/></svg>
        @break
    @case('attendance')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12H4V7a2 2 0 012-2zm3 10l2 2 4-4"/></svg>
        @break
    @case('game')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8h8a5 5 0 014.8 6.4l-1 3.2a2 2 0 01-3.3.8L14 16h-4l-2.5 2.4a2 2 0 01-3.3-.8l-1-3.2A5 5 0 018 8zm0 3v4m-2-2h4m6-1h.01M18 14h.01"/></svg>
        @break
    @case('rpg')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 4.5-2.9 7.7-7 9-4.1-1.3-7-4.5-7-9V7l7-4zm0 5v8m-3-4h6"/></svg>
        @break
    @case('user')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0"/></svg>
        @break
    @case('users')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87m-2-11.26a4 4 0 010 7.75"/></svg>
        @break
    @case('card')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18v12H3V6zm0 4h18M7 14h4"/></svg>
        @break
    @case('print')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8V3h10v5M7 17H5a2 2 0 01-2-2v-4a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2h-2m-10-4h10v8H7v-8z"/></svg>
        @break
    @case('fingerprint')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a2 2 0 012 2c0 3-.6 5.4-2 8m-4-1c1.3-2.2 2-4.6 2-7a2 2 0 014 0m3.5 5.5c.3-1.7.5-3.5.5-5.5a6 6 0 00-12 0c0 1.2-.1 2.4-.4 3.5M4 12a8 8 0 0116 0"/></svg>
        @break
    @case('settings')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6zm7.4-3a7.7 7.7 0 00-.1-1l2-1.5-2-3.4-2.4 1a8 8 0 00-1.7-1L15 3.5h-4L10.6 6a8 8 0 00-1.7 1l-2.4-1-2 3.4 2 1.5a7.7 7.7 0 000 2L4.5 14.5l2 3.4 2.4-1a8 8 0 001.7 1l.4 2.6h4l.4-2.6a8 8 0 001.7-1l2.4 1 2-3.4-2-1.5c.1-.3.1-.7.1-1z"/></svg>
        @break
    @case('export')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16"/></svg>
        @break
    @case('database')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6c0-1.66 3.58-3 8-3s8 1.34 8 3-3.58 3-8 3-8-1.34-8-3zm0 0v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6m-16 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/></svg>
        @break
    @case('report')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3h9l3 3v15H6V3zm8 0v4h4M9 12h6m-6 4h6"/></svg>
        @break
    @case('qr')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm11 0h2v2h-2v-2zm3 0h2v5h-2m-4-1h3v2h-3v-2z"/></svg>
        @break
    @case('news')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h11v14H5a2 2 0 01-2-2V7a2 2 0 012-2zm11 3h3a2 2 0 012 2v7a2 2 0 01-2 2h-3M8 9h5m-5 4h5m-5 3h3"/></svg>
        @break
    @case('presentation')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v12H4V4zm8 12v5m-4 0h8M8 12l3-3 2 2 3-4"/></svg>
        @break
    @case('globe')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2 0 3.5-4 3.5-9S14 3 12 3 8.5 7 8.5 12 10 21 12 21zM3 12h18"/></svg>
        @break
    @case('more')
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg>
        @break
    @default
        <svg class="{{ $iconClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke-width="2"/></svg>
@endswitch
