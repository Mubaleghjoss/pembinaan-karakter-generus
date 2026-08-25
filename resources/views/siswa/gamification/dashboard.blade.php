@extends('layouts.siswa')

@section('title', 'Prestasi')

@section('content')
@php
    $level = $stats['current_level'] ?? null;
    $nextLevel = $stats['next_level'] ?? null;
    $totalPoints = $stats['points']->total_points ?? 0;
    $earnedBadges = collect($allBadges)->where('earned', true);
    $lockedBadges = collect($allBadges)->where('earned', false);

    // Kumpulkan penghargaan (sertifikat/pin/piagam dll) yang punya template siap unduh.
    $rewards = [];
    foreach ($reachedLevels as $reachedLevel) {
        foreach (($reachedLevel->benefits ?? []) as $benefit) {
            $lower = strtolower($benefit);
            $type = null;
            if (str_contains($lower, 'sertifikat')) { $type = 'sertifikat'; }
            elseif (str_contains($lower, 'pin')) { $type = 'pin'; }
            elseif (str_contains($lower, 'nominasi')) { $type = 'nominasi'; }
            elseif (str_contains($lower, 'piagam')) { $type = 'piagam'; }
            elseif (str_contains($lower, 'apresiasi')) { $type = 'apresiasi'; }
            elseif (str_contains($lower, 'piala')) { $type = 'piala'; }

            $ready = false;
            if ($type) {
                $tmpl = $reachedLevel->rewardTemplates->where('reward_type', $type)->first();
                $ready = $tmpl && $tmpl->hasTemplate();
            }

            $rewards[] = [
                'level' => $reachedLevel,
                'benefit' => $benefit,
                'type' => $type,
                'ready' => $ready,
            ];
        }
    }
    $readyRewards = collect($rewards)->where('ready', true);
@endphp
<div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 sm:py-6">
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Prestasi</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ringkasan poin, peringkat, pin penghargaan, dan sertifikatmu dalam satu halaman.</p>
    </div>

    @if(!empty($stats['last_period_reset']))
    <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-100">
        <p class="font-semibold">
            {{ number_format($stats['last_period_reset']['points']) }} poin sebelumnya sudah terekam
            @if(!empty($stats['last_period_reset']['period_name'])) di periode {{ $stats['last_period_reset']['period_name'] }} @endif.
        </p>
        <p class="mt-1">{{ $stats['last_period_reset']['message'] ?? 'Semangat kumpulkan poin lagi.' }}</p>
    </div>
    @endif

    {{-- Ringkasan utama --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Level saat ini</p>
                <p class="mt-0.5 text-lg font-black text-gray-900 dark:text-white">
                    Level {{ $level->level ?? 1 }} · {{ $level->nama ?? 'Pemula' }}
                </p>
                @if(!empty($stats['active_period']))
                    <p class="mt-0.5 text-[11px] font-semibold text-gray-500 dark:text-gray-400">Periode: {{ $stats['active_period']['name'] }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-300">{{ number_format($totalPoints) }}</p>
                <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Total poin</p>
            </div>
        </div>

        @if($nextLevel)
            <div class="mt-3">
                <div class="mb-1 flex justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>Menuju {{ $nextLevel->nama }}</span>
                    <span class="font-semibold">{{ $stats['points_to_next'] }} poin lagi</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $stats['progress_to_next'] }}%"></div>
                </div>
            </div>
        @else
            <p class="mt-3 rounded-lg bg-amber-50 p-2.5 text-center text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">Level maksimum tercapai. Luar biasa!</p>
        @endif

        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach([
                ['Peringkat', '#'.$stats['rank'], 'text-emerald-700 dark:text-emerald-300', 'bg-emerald-50 dark:bg-emerald-900/25'],
                ['Pin Diraih', $stats['total_badges'], 'text-amber-700 dark:text-amber-300', 'bg-amber-50 dark:bg-amber-900/25'],
                ['Streak Hadir', $stats['attendance_streak'], 'text-sky-700 dark:text-sky-300', 'bg-sky-50 dark:bg-sky-900/25'],
                ['Streak Tugas', $stats['character_streak'], 'text-purple-700 dark:text-purple-300', 'bg-purple-50 dark:bg-purple-900/25'],
            ] as [$label, $value, $tone, $bg])
                <div class="rounded-xl {{ $bg }} p-2.5 text-center">
                    <p class="text-lg font-black {{ $tone }}">{{ $value }}</p>
                    <p class="text-[11px] font-semibold text-gray-600 dark:text-gray-400">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/60">
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($stats['points']->attendance_points ?? 0) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Poin Kehadiran</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/60">
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($stats['points']->character_points ?? 0) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Poin Tugas PKG</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800/60">
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($stats['points']->bonus_points ?? 0) }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">Poin Bonus</p>
            </div>
        </div>
    </div>

    {{-- Sertifikat & penghargaan --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Sertifikat &amp; Penghargaan</h2>
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                {{ $reachedLevels->count() }} level tercapai · {{ $readyRewards->count() }} siap diunduh
            </span>
        </div>

        @if(count($rewards) === 0)
            <p class="mt-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                Belum ada penghargaan. Terus kerjakan Tugas PKG dan rajin hadir untuk naik level.
            </p>
        @else
            <div class="mt-3 space-y-2">
                @foreach($rewards as $reward)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $reward['benefit'] }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">Level {{ $reward['level']->level }} · {{ $reward['level']->nama }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if($reward['ready'])
                                <a href="{{ route('siswa.gamification.certificate.download', ['level' => $reward['level']->id, 'format' => 'png', 'type' => $reward['type']]) }}&view=1"
                                   target="_blank" rel="noopener"
                                   class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-[11px] font-bold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Lihat</a>
                                <a href="{{ route('siswa.gamification.certificate.download', ['level' => $reward['level']->id, 'format' => 'png', 'type' => $reward['type']]) }}"
                                   class="rounded-lg bg-indigo-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-indigo-700">PNG</a>
                                <a href="{{ route('siswa.gamification.certificate.download', ['level' => $reward['level']->id, 'format' => 'pdf', 'type' => $reward['type']]) }}"
                                   class="rounded-lg bg-rose-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-rose-700">PDF</a>
                            @elseif($reward['type'])
                                <span class="rounded-lg bg-amber-100 px-2.5 py-1.5 text-[11px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">Sedang diproses</span>
                            @else
                                <span class="rounded-lg bg-emerald-100 px-2.5 py-1.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">Tercapai</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Pin penghargaan --}}
    <div class="pkg-panel mb-4 p-4 sm:p-5" x-data="{ showLocked: false }">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Pin Penghargaan</h2>
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                {{ $earnedBadges->count() }} dari {{ count($allBadges) }} pin
            </span>
        </div>

        @if($earnedBadges->count() > 0)
            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach($earnedBadges as $data)
                    @php $badge = $data['badge']; @endphp
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-lg" style="background-color: {{ $badge->warna }}25">{{ $badge->icon_url }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ $badge->nama }}</p>
                                <p class="text-[10px] text-gray-600 dark:text-gray-400">
                                    {{ $data['earned_at'] ? \Carbon\Carbon::parse($data['earned_at'])->translatedFormat('d M Y') : 'Tercapai' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-3 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                Belum ada pin yang diraih. Kerjakan Tugas PKG dan rajin hadir untuk membuka pin pertamamu.
            </p>
        @endif

        @if($lockedBadges->count() > 0)
            <button type="button" @click="showLocked = !showLocked"
                    class="mt-3 text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">
                <span x-text="showLocked ? 'Sembunyikan' : 'Lihat {{ $lockedBadges->count() }} pin yang belum diraih'"></span>
            </button>
            <div x-show="showLocked" x-cloak class="mt-3 space-y-2">
                @foreach($lockedBadges as $data)
                    @php
                        $badge = $data['badge'];
                        $remaining = max(0, ($data['target'] ?? 0) - ($data['current'] ?? 0));
                    @endphp
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-2">
                            <p class="min-w-0 truncate text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $badge->nama }}</p>
                            <span class="shrink-0 text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ $data['current'] }}/{{ $data['target'] }}</span>
                        </div>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ $data['progress'] }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $data['current'] >= $data['target'] ? 'Syarat sudah tercapai, menunggu proses.' : $remaining . ' lagi untuk membuka' }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Peringkat ringkas --}}
    <div class="pkg-panel p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Peringkat Teratas</h2>
            <a href="{{ route('siswa.gamification.history') }}" class="text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">Riwayat poin →</a>
        </div>
        <div class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($leaderboard as $index => $item)
                <div class="flex items-center gap-3 py-2.5 {{ ($item['siswa_id'] ?? null) === auth()->guard('siswa')->id() ? 'rounded-lg bg-indigo-50 px-2 dark:bg-indigo-900/30' : '' }}">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                        {{ $index === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200' :
                          ($index === 1 ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' :
                          ($index === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300')) }}">
                        {{ $index + 1 }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $item['siswa']['nama'] ?? 'Generus' }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Level {{ $item['level'] }}</p>
                    </div>
                    <p class="shrink-0 text-sm font-bold text-indigo-600 dark:text-indigo-300">{{ number_format($item['total_points']) }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Peringkat hanya penyemangat. Fokus utamamu tetap mengerjakan Tugas PKG dan rutin membaca Al-Qur'an.
        </p>
    </div>
</div>
@endsection
