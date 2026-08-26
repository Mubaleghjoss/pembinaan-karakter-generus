@props([
    'user',
    'maxDuty' => 2,
    'size' => 'sm',
    'showRole' => true,
])
@php
    $dutyBadges = method_exists($user, 'dutyRoleBadges') ? $user->dutyRoleBadges() : [];
    $shown = array_slice($dutyBadges, 0, $maxDuty);
    $hidden = array_slice($dutyBadges, $maxDuty);
    $sizeClasses = $size === 'xs'
        ? 'px-1.5 py-0.5 text-[10px]'
        : 'px-2 py-0.5 text-[11px]';
@endphp
<span class="inline-flex flex-wrap items-center gap-1 align-middle">
    @if($showRole)
        <span class="rounded-full font-bold uppercase tracking-wide {{ $sizeClasses }} {{ $user->roleBadgeClasses() }}">
            {{ method_exists($user, 'operationalRoleLabel') ? $user->operationalRoleLabel() : ($user->role->display_name ?? 'Pengguna') }}
        </span>
    @endif

    @foreach($shown as $badge)
        <span class="rounded-full font-semibold {{ $sizeClasses }} {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
    @endforeach

    @if(count($hidden) > 0)
        <span class="rounded-full bg-gray-100 font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300 {{ $sizeClasses }}"
              title="{{ collect($hidden)->pluck('label')->implode(', ') }}">
            +{{ count($hidden) }}
        </span>
    @endif
</span>
