@props([
    'subscribeUrl',
    'unsubscribeUrl',
    'badgeCount' => 0,
])

<div
    data-pwa-push-control
    data-subscribe-url="{{ $subscribeUrl }}"
    data-unsubscribe-url="{{ $unsubscribeUrl }}"
    data-vapid-public-key="{{ config('webpush.vapid.public_key') }}"
    data-badge-count="{{ (int) $badgeCount }}"
    data-state="inactive"
>
    <button
        type="button"
        data-pwa-push-button
        class="pkg-pwa-push-button"
        aria-pressed="false"
        title="Aktifkan notifikasi tugas"
    >
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span data-pwa-push-label class="hidden text-xs font-semibold sm:inline">Aktifkan notifikasi</span>
        <span class="pkg-pwa-push-dot" aria-hidden="true"></span>
    </button>
</div>
