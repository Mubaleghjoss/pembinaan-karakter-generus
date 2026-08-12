@php
    $statusMap = [
        'pending' => ['Menunggu verifikasi', 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200'],
        'verified' => ['Terverifikasi', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'],
        'rejected' => ['Ditolak', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$status] ?? [ucfirst($status), 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'];
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
