@extends('layouts.siswa')

@section('title', 'Game - ' . $rpgMap->nama)

@push('styles')
<style>
    .rpg-page-shell {
        max-width: 1120px;
    }

    .rpg-stage-card,
    .rpg-hud-card,
    .rpg-online-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
    }

    .dark .rpg-stage-card,
    .dark .rpg-hud-card,
    .dark .rpg-online-card {
        background: rgba(15, 23, 42, 0.94);
        border-color: rgba(51, 65, 85, 0.9);
        box-shadow: 0 20px 52px rgba(2, 6, 23, 0.3);
    }

    .rpg-grid-shell {
        display: flex;
        justify-content: center;
        padding: clamp(10px, 2vw, 18px);
        border-radius: 28px;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(226, 232, 240, 0.9));
        border: 1px solid rgba(226, 232, 240, 0.92);
    }

    .dark .rpg-grid-shell {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(2, 6, 23, 0.94));
        border-color: rgba(51, 65, 85, 0.92);
    }

    .rpg-grid {
        --rpg-grid-size: {{ max(1, (int) $rpgMap->grid_size) }};
        --rpg-cell-min: clamp(18px, calc(94vw / var(--rpg-grid-size)), 40px);
        --rpg-cell-font: clamp(10px, calc(190px / var(--rpg-grid-size)), 22px);
        display: grid;
        grid-template-columns: repeat({{ $rpgMap->grid_size }}, 1fr);
        grid-template-rows: repeat({{ $rpgMap->grid_size }}, 1fr);
        gap: 1px;
        aspect-ratio: 1;
        width: min(100%, 720px);
        max-width: min(100vw - 1.5rem, 720px);
        margin: 0 auto;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 22px;
        overflow: hidden;
        border: 2px solid rgba(15, 23, 42, 0.12);
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
    }

    .dark .rpg-grid {
        background: rgba(30, 41, 59, 0.84);
        border-color: rgba(148, 163, 184, 0.22);
        box-shadow: 0 28px 76px rgba(2, 6, 23, 0.42);
    }

    .rpg-cell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: var(--rpg-cell-min);
        font-size: var(--rpg-cell-font);
        transition: background-color 0.15s;
        user-select: none;
    }

    .theme-grass .rpg-cell { background: #86efac; }
    .theme-grass .rpg-cell:nth-child(odd) { background: #6ee7b7; }
    .theme-desert .rpg-cell { background: #fde68a; }
    .theme-desert .rpg-cell:nth-child(odd) { background: #fcd34d; }
    .theme-castle .rpg-cell { background: #94a3b8; }
    .theme-castle .rpg-cell:nth-child(odd) { background: #7c8ba0; }
    .theme-forest .rpg-cell { background: #22c55e; }
    .theme-forest .rpg-cell:nth-child(odd) { background: #16a34a; }
    .theme-snow .rpg-cell { background: #e0f2fe; }
    .theme-snow .rpg-cell:nth-child(odd) { background: #bae6fd; }

    .rpg-cell.obstacle { background: #44403c !important; }
    .rpg-cell.obstacle .wall-icon { font-size: 0.9em; opacity: 0.8; }
    .rpg-cell .cell-content { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 2; }
    .rpg-cell .npc-marker { animation: npc-bob 1.5s ease-in-out infinite; cursor: pointer; }
    .rpg-cell .npc-answered { opacity: 0.4; filter: grayscale(1); }
    .rpg-cell .player-marker { position: relative; display: inline-flex; align-items: center; justify-content: center; z-index: 10; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); animation: player-bounce 0.3s ease; }
    .rpg-cell .player-marker.shield-aura::after {
        content: '';
        position: absolute;
        inset: -5px;
        border-radius: 999px;
        border: 2px solid rgba(16, 185, 129, 0.95);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.22), 0 0 16px rgba(16, 185, 129, 0.38);
        animation: shield-pulse 1.2s ease-in-out infinite;
    }
    .rpg-cell .other-player { opacity: 0.5; z-index: 5; font-size: 0.85em; }
    .rpg-cell .enemy-marker { z-index: 8; animation: enemy-float 0.8s ease-in-out infinite; filter: drop-shadow(0 2px 6px rgba(239,68,68,0.5)); }
    .rpg-cell .shot-flash {
        z-index: 12;
        color: #f97316;
        font-size: 0.85em;
        font-weight: 900;
        text-shadow: 0 0 14px rgba(251, 146, 60, 0.9);
        animation: shot-burst 0.45s ease-out forwards;
        pointer-events: none;
    }

    @keyframes npc-bob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
    @keyframes player-bounce { 0% { transform: scale(1.3); } 100% { transform: scale(1); } }
    @keyframes enemy-float { 0%,100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-2px) scale(1.1); } }
    @keyframes shield-pulse { 0%, 100% { transform: scale(0.96); opacity: 0.95; } 50% { transform: scale(1.06); opacity: 0.6; } }
    @keyframes shot-burst { 0% { transform: scale(0.4); opacity: 0; } 35% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(1.6); opacity: 0; } }
    @keyframes caught-flash { 0%,100% { opacity: 1; } 50% { opacity: 0.2; } }
    .caught-effect { animation: caught-flash 0.15s ease 4; }

    .rpg-control-tabs {
        display: inline-flex;
        gap: 6px;
        padding: 6px;
        border-radius: 999px;
        background: rgba(241, 245, 249, 0.92);
        border: 1px solid rgba(226, 232, 240, 0.92);
    }

    .dark .rpg-control-tabs {
        background: rgba(2, 6, 23, 0.9);
        border-color: rgba(51, 65, 85, 0.9);
    }

    .rpg-control-tab {
        border-radius: 999px;
        padding: 10px 16px;
    }

    .rpg-mobile-only {
        display: none;
    }

    .rpg-controls-dock {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .rpg-controls-summary {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .rpg-mobile-control-pad {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
    }

    .dark .rpg-mobile-control-pad {
        background: rgba(15, 23, 42, 0.94);
        border-color: rgba(51, 65, 85, 0.9);
        box-shadow: 0 20px 52px rgba(2, 6, 23, 0.3);
    }

    .rpg-quick-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
    }

    .rpg-quick-stat {
        border-radius: 18px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: rgba(248, 250, 252, 0.92);
        padding: 0.7rem 0.85rem;
    }

    .dark .rpg-quick-stat {
        border-color: rgba(51, 65, 85, 0.92);
        background: rgba(2, 6, 23, 0.55);
    }

    .rpg-mobile-dpad-overlay {
        display: none;
    }

    .rpg-mobile-view-switch {
        display: none;
    }

    .rpg-mobile-reset-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(251, 146, 60, 0.42);
        background: rgba(255, 247, 237, 0.96);
        color: #9a3412;
        padding: 0.72rem 0.9rem;
        font-size: 0.75rem;
        font-weight: 900;
        box-shadow: 0 12px 28px rgba(154, 52, 18, 0.14);
        backdrop-filter: blur(18px);
    }

    .dark .rpg-mobile-reset-btn {
        border-color: rgba(251, 146, 60, 0.32);
        background: rgba(67, 20, 7, 0.8);
        color: #fed7aa;
    }

    .rpg-stage-copy {
        max-width: 32rem;
    }

    .dpad-container {
        display: grid;
        grid-template-columns: repeat(3, 56px);
        grid-template-rows: repeat(3, 56px);
        gap: 8px;
    }

    .dpad-btn {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
        transition: all 0.1s;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: white;
        border: none;
        box-shadow: 0 8px 20px rgba(79,70,229,0.28);
    }

    .dpad-btn:active { transform: scale(0.92); }
    .dpad-center { background: linear-gradient(135deg, #312e81, #3730a3); border-radius: 50%; }

    .joystick-zone {
        width: 148px;
        height: 148px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(79,70,229,0.15) 0%, rgba(79,70,229,0.05) 100%);
        border: 3px solid rgba(79,70,229,0.3);
        position: relative;
        touch-action: none;
    }

    .joystick-thumb {
        width: 52px;
        height: 52px;
        border-radius: 999px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        box-shadow: 0 6px 16px rgba(79,70,229,0.4);
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        transition: box-shadow 0.1s;
        cursor: grab;
    }

    .joystick-thumb.active { box-shadow: 0 8px 22px rgba(79,70,229,0.56); cursor: grabbing; }
    .joystick-direction { position: absolute; font-size: 12px; color: rgba(79,70,229,0.5); font-weight: 700; }

    .npc-dialog {
        animation: dialog-appear 0.3s ease;
        display: flex;
        flex-direction: column;
        max-height: min(92vh, 92svh);
    }

    .npc-dialog-body {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    @keyframes dialog-appear {
        0% { transform: scale(0.8) translateY(20px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }

    .choice-btn { transition: all 0.2s; }
    .choice-btn:hover { transform: translateX(4px); }
    .choice-btn.correct { background: #10b981 !important; color: white !important; border-color: #10b981 !important; }
    .choice-btn.wrong { background: #ef4444 !important; color: white !important; border-color: #ef4444 !important; }

    .confetti { animation: confetti-fall 2s ease-in forwards; position: absolute; font-size: 24px; }
    @keyframes confetti-fall {
        0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
        100% { transform: translateY(300px) rotate(720deg); opacity: 0; }
    }

    .dark .theme-grass .rpg-cell { background: #166534; }
    .dark .theme-grass .rpg-cell:nth-child(odd) { background: #15803d; }
    .dark .theme-desert .rpg-cell { background: #92400e; }
    .dark .theme-desert .rpg-cell:nth-child(odd) { background: #a16207; }
    .dark .theme-castle .rpg-cell { background: #374151; }
    .dark .theme-castle .rpg-cell:nth-child(odd) { background: #4b5563; }
    .dark .theme-forest .rpg-cell { background: #14532d; }
    .dark .theme-forest .rpg-cell:nth-child(odd) { background: #166534; }
    .dark .theme-snow .rpg-cell { background: #1e3a5f; }
    .dark .theme-snow .rpg-cell:nth-child(odd) { background: #1e40af; }

    @media (max-width: 1023px) {
        body {
            overflow: hidden;
        }

        main {
            overflow: hidden !important;
        }

        .pkg-topbar {
            display: none !important;
        }

        .rpg-page-shell {
            max-width: 100%;
            height: 100svh;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            padding-inline: 0.35rem;
            padding-top: 0.1rem;
            padding-bottom: 0.45rem;
            --mobile-grid-bottom-space: calc(9.75rem + env(safe-area-inset-bottom));
        }

        .rpg-page-shell > .grid {
            flex: 1;
            min-height: 0;
            width: 100%;
        }

        .rpg-page-shell > .grid > div:first-child {
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .rpg-stage-card {
            background: transparent;
            border: none;
            box-shadow: none;
            flex: 1;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 0.35rem 0.4rem;
        }

        .rpg-mobile-only {
            display: flex;
        }

        .rpg-mobile-view-switch {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 0.15rem 0 0.4rem;
        }

        .rpg-mobile-view-switch .pkg-rpg-view-toggle {
            background: rgba(15, 23, 42, 0.78);
            border-color: rgba(255, 255, 255, 0.18);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.2);
            backdrop-filter: blur(18px);
        }

        .rpg-mobile-view-switch .pkg-rpg-view-toggle button {
            min-width: 4.5rem;
            color: rgba(255, 255, 255, 0.78);
        }

        .rpg-mobile-view-switch .pkg-rpg-view-toggle button.is-active {
            background: #ffffff;
            color: #0f172a;
        }

        .rpg-desktop-only {
            display: none !important;
        }

        .rpg-grid-shell {
            padding: 6px;
            border-radius: 22px;
        }

        .rpg-grid {
            max-width: calc(100vw - 1.6rem);
            border-radius: 18px;
        }

        .rpg-controls-summary {
            display: none;
        }

        .rpg-controls-dock {
            display: none;
        }

        .joystick-zone {
            width: 112px;
            height: 112px;
        }

        .dpad-container {
            grid-template-columns: repeat(3, 48px);
            grid-template-rows: repeat(3, 48px);
            gap: 5px;
        }

        .dpad-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            font-size: 18px;
        }

        .rpg-mobile-fullscreen-hide {
            display: none !important;
        }

        .rpg-stage-copy {
            display: none;
        }

        .rpg-stage-frame {
            position: relative;
            flex: 1;
            min-height: 0;
            margin-top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.1rem;
            border-radius: 20px;
            overflow: hidden;
        }

        .rpg-grid-shell {
            width: 100%;
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            box-shadow: none;
            padding-inline: 0.2rem;
            overflow: hidden;
        }

        .rpg-stage-frame .rpg-grid-shell {
            margin-top: 0 !important;
        }

        .rpg-mobile-dpad-overlay {
            display: flex;
            position: static;
            flex: 0 0 auto;
            justify-content: center;
            padding-bottom: calc(0.3rem + env(safe-area-inset-bottom));
        }

        .rpg-mobile-dpad-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            width: 100%;
        }

        .rpg-mobile-dpad-cluster {
            pointer-events: auto;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .rpg-mobile-dpad-overlay .dpad-btn {
            background: rgba(79, 70, 229, 0.76);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 14px 32px rgba(79, 70, 229, 0.26);
            backdrop-filter: blur(18px);
        }

        .rpg-mobile-dpad-overlay .dpad-center {
            background: rgba(30, 41, 59, 0.82);
        }

        .rpg-grid {
            width: min(calc(100vw - 1.6rem), calc(100svh - var(--mobile-grid-bottom-space)));
            max-height: 100%;
            max-width: 100%;
            --rpg-cell-min: 0px;
            margin-inline: auto;
        }

        .rpg-cell {
            min-height: 0;
        }

        .npc-dialog {
            max-height: calc(100svh - 0.75rem);
            width: min(100%, 30rem);
        }

        .npc-dialog-body {
            max-height: calc(100svh - 6.75rem);
            padding-bottom: 1rem;
        }

        .rpg-online-card {
            display: none !important;
        }
    }

    @media (min-width: 1024px) {
        .rpg-controls-dock {
            position: sticky;
            top: 5rem;
            padding: 1rem;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        .dark .rpg-controls-dock {
            background: rgba(15, 23, 42, 0.94);
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow: 0 20px 52px rgba(2, 6, 23, 0.3);
        }

        .rpg-mobile-control-pad {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .rpg-desktop-only {
            display: block;
        }
    }
</style>
@endpush

@section('content')
<div class="rpg-page-shell p-3 lg:p-6 mx-auto" x-data="rpgGame()" x-init="init()" @keydown.window="handleKey($event)">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
        <div class="space-y-4">
            <div class="rpg-stage-card rounded-[28px] p-4 sm:p-5">
                <div class="rpg-mobile-fullscreen-hide flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <a href="{{ route('siswa.rpg.index') }}" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500 dark:text-indigo-300">Game</p>
                            <h1 class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $rpgMap->nama }}</h1>
                            <p class="mt-1 hidden text-sm text-slate-500 dark:text-slate-400 sm:block">{{ $rpgMap->deskripsi }}</p>
                        </div>
                    </div>
                    <button x-show="hasResettableProgress()" x-cloak @click="resetGame()" class="btn-secondary text-sm !rounded-full !px-4 !py-2">
                        Reset poin
                    </button>
                </div>

                <div class="rpg-mobile-fullscreen-hide mt-4 grid grid-cols-4 gap-2 sm:gap-3">
                    <div class="rpg-hud-card rounded-2xl p-2 sm:p-3 text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Skor</p>
                        <p class="mt-1 sm:mt-2 text-lg sm:text-2xl font-black text-indigo-600" x-text="session.total_score">0</p>
                    </div>
                    <div class="rpg-hud-card rounded-2xl p-2 sm:p-3 text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Tertangkap</p>
                        <p class="mt-1 sm:mt-2 text-lg sm:text-2xl font-black text-red-500" x-text="catchCount">0</p>
                    </div>
                    <div class="rpg-hud-card rounded-2xl p-2 sm:p-3 text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">NPC</p>
                        <p class="mt-1 sm:mt-2 text-lg sm:text-2xl font-black text-emerald-600"><span x-text="answeredCount">0</span>/<span x-text="totalNpcs">0</span></p>
                    </div>
                    <div class="rpg-hud-card rounded-2xl p-2 sm:p-3 text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Online</p>
                        <p class="mt-1 sm:mt-2 text-lg sm:text-2xl font-black text-sky-600"><span x-text="activePlayersCount">1</span></p>
                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500"><span x-text="activeGuestsCount">0</span> guest demo</p>
                    </div>
                </div>

                <div class="rpg-mobile-fullscreen-hide mt-4 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-500" :style="'width:' + (totalNpcs > 0 ? (answeredCount / totalNpcs * 100) : 0) + '%'"> </div>
                </div>

                <div class="rpg-mobile-view-switch">
                    <div class="pkg-rpg-view-toggle" aria-label="Pilih tampilan arena">
                        <button type="button" @click="setViewMode('2d')" :class="viewMode === '2d' ? 'is-active' : ''" :aria-pressed="(viewMode === '2d').toString()">2D</button>
                        <button type="button" @click="setViewMode('3d')" :class="viewMode === '3d' ? 'is-active' : ''" :aria-pressed="(viewMode === '3d').toString()">3D</button>
                    </div>
                    <button x-show="hasResettableProgress()" x-cloak type="button" @click="resetGame()" class="rpg-mobile-reset-btn">
                        Reset poin
                    </button>
                </div>

                <div x-ref="stageFrame" class="rpg-stage-frame mt-5 rounded-[28px] border border-slate-200/80 bg-white/70 p-3 dark:border-slate-700/80 dark:bg-slate-950/40">
                    <div class="rpg-mobile-fullscreen-hide flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Arena permainan</p>
                            <p class="rpg-stage-copy text-sm text-slate-500 dark:text-slate-400">Grid besar tetap diprioritaskan terlihat penuh di mobile.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="pkg-rpg-view-toggle" aria-label="Pilih tampilan arena">
                                <button type="button" @click="setViewMode('2d')" :class="viewMode === '2d' ? 'is-active' : ''" :aria-pressed="(viewMode === '2d').toString()">2D</button>
                                <button type="button" @click="setViewMode('3d')" :class="viewMode === '3d' ? 'is-active' : ''" :aria-pressed="(viewMode === '3d').toString()">3D</button>
                            </div>
                            <button x-show="hasResettableProgress()" x-cloak type="button" @click="resetGame()" class="btn-secondary text-xs !rounded-full !px-3 !py-1.5">
                                Reset poin
                            </button>
                            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                Grid {{ $rpgMap->grid_size }} x {{ $rpgMap->grid_size }}
                            </span>
                        </div>
                    </div>

                    <div
                        x-show="viewMode === '3d'"
                        x-cloak
                        id="siswa-rpg-3d-scene"
                        data-rpg-3d-scene
                        data-rpg-3d-provider="pkgSiswaRpg3dState"
                        data-rpg-3d-controls="pkgSiswaRpg3dControls"
                        data-rpg-3d-resettable="true"
                        class="pkg-rpg-3d-scene mt-4"
                    ></div>

                    <div x-show="viewMode === '2d'" x-ref="gridShell" class="rpg-grid-shell mt-4">
                        <div class="rpg-grid theme-{{ $rpgMap->background_theme }}" id="gameGrid" :class="{'caught-effect': caughtFlash}" :style="mobileGridStyle()">
                            <template x-for="displayY in displayRows()" :key="'row-'+displayY">
                                <template x-for="x in gridSize" :key="'cell-'+x+'-'+displayY">
                                    <div class="rpg-cell" :class="{ 'obstacle': isObstacle(x-1, displayY) }" @click="moveToCell(x-1, displayY)">
                                        <template x-if="isObstacle(x-1, displayY)">
                                            <span class="wall-icon">#</span>
                                        </template>

                                        <div x-show="session.pos_x === (x-1) && session.pos_y === displayY" class="cell-content">
                                            <span class="player-marker" :class="{'shield-aura': shieldActive}" x-text="resolvePlayerAvatar(character.avatar_display || character.avatar)" :key="session.pos_x + '-' + session.pos_y"></span>
                                        </div>

                                        <template x-for="npc in getNpcsAt(x-1, displayY)" :key="'npc-'+npc.id">
                                            <div class="cell-content" x-show="!(session.pos_x === (x-1) && session.pos_y === displayY)">
                                                <span class="npc-marker" :class="{'npc-answered': isNpcAnswered(npc.id)}" x-text="resolveNpcAvatar(npc.avatar_display || npc.avatar)"></span>
                                            </div>
                                        </template>

                                        <template x-for="pickup in getPickupsAt(x-1, displayY)" :key="'pickup-'+pickup.type+'-'+x+'-'+displayY">
                                            <div class="cell-content" x-show="!(session.pos_x === (x-1) && session.pos_y === displayY)">
                                                <span class="npc-marker" :class="pickup.type === 'shield' ? 'text-emerald-500' : 'text-amber-500'" x-text="pickup.icon"></span>
                                            </div>
                                        </template>

                                        <template x-for="(enemy, ei) in getEnemiesAt(x-1, displayY)" :key="'en-'+ei">
                                            <div class="cell-content">
                                                <span class="enemy-marker" x-text="resolveEnemyAvatar(enemy.avatar)"></span>
                                            </div>
                                        </template>

                                        <template x-if="shotFlash && shotFlash.x === (x-1) && shotFlash.y === displayY">
                                            <div class="cell-content">
                                                <span class="shot-flash">*</span>
                                            </div>
                                        </template>

                                        <template x-for="(op, oi) in getPlayersAt(x-1, displayY)" :key="'op-'+oi">
                                            <div class="cell-content">
                                                <span class="other-player" x-text="resolvePlayerAvatar(op.avatar)" :title="op.nama"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>

                    <div class="rpg-desktop-only mt-3 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 dark:border-slate-700 dark:bg-slate-900">
                            <span x-text="pickupIcons.shield"></span>
                            Pickup tameng auto
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 dark:border-slate-700 dark:bg-slate-900">
                            <span x-text="pickupIcons.ammo"></span>
                            Pickup peluru
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 dark:border-slate-700 dark:bg-slate-900">
                            <span>AUTO</span>
                            Tembakan otomatis
                        </span>
                    </div>
                </div>
            </div>

            <div x-show="onlinePlayers.length > 0" class="rpg-online-card rounded-[24px] p-4">
                <h4 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Pemain online</h4>
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="(op, oi) in onlinePlayers" :key="'online-'+oi">
                        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm dark:bg-slate-800">
                            <span x-text="resolvePlayerAvatar(op.avatar)"></span>
                            <span class="font-medium text-slate-700 dark:text-slate-200" x-text="op.nama"></span>
                            <span class="text-xs text-slate-400" x-text="'(' + op.pos_x + ',' + op.pos_y + ')'"> </span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="rpg-controls-dock">
            <div class="rpg-controls-summary">
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Kontrol gerak</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Info permainan tetap di halaman, tombol gerak tetap mudah dijangkau di mobile.</p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-950/50">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Peluru</p>
                        <p class="mt-2 text-xl font-black text-amber-500" x-text="ammo"></p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Auto tembak saat musuh lurus 3 blok.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-950/50">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Tameng</p>
                        <p class="mt-2 text-xl font-black" :class="shieldActive ? 'text-emerald-500' : 'text-slate-400'" x-text="shieldActive ? shieldSecondsLeft + 'd' : 'OFF'"></p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Aktif otomatis saat pickup tameng diambil.</p>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Objektif</p>
                    <div class="mt-2 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        <p><span class="font-semibold text-emerald-500" x-text="answeredCount"></span>/<span class="font-semibold" x-text="totalNpcs"></span> NPC selesai dijawab.</p>
                        <p>Pickup tameng tersisa <span class="font-semibold text-emerald-500" x-text="pickups.shield.length"></span> dari <span class="font-semibold" x-text="shieldPickupCount"></span>.</p>
                        <p>Pickup peluru tersisa <span class="font-semibold text-amber-500" x-text="pickups.ammo.length"></span> dari <span class="font-semibold" x-text="ammoPickupCount"></span>.</p>
                        <p>Ambil <span class="font-semibold" x-text="pickupIcons.shield"></span> untuk tameng otomatis <span class="font-semibold" x-text="shieldDurationSeconds"></span> detik, dan <span class="font-semibold" x-text="pickupIcons.ammo"></span> untuk <span class="font-semibold" x-text="ammoPerPickup"></span> peluru tiap pickup.</p>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Tips</p>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Mode <span class="font-semibold" x-text="actionMode === 'shoot' ? 'Tembak' : 'Gerak'"></span>.
                        Amunisi <span class="font-semibold" x-text="ammo"></span>,
                        tameng <span class="font-semibold" x-text="shieldActive ? 'aktif' : 'cari pickup'"></span>,
                        isi pickup <span class="font-semibold" x-text="ammoPerPickup"></span>,
                        perlindungan aktif <span class="font-semibold" x-text="shieldActive ? 'ya' : 'tidak'"></span>
                        <span x-show="shieldActive">(<span class="font-semibold" x-text="shieldSecondsLeft"></span>d)</span>.
                    </p>
                </div>
            </div>

            <div class="rpg-mobile-control-pad space-y-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white lg:hidden">Kontrol cepat</p>
                    <p class="mt-1 hidden text-xs text-slate-500 dark:text-slate-400 lg:block">Gunakan dock ini untuk gerak dan serang tanpa menutup informasi misi.</p>
                </div>

                <div class="rpg-control-tabs">
                    <button @click="controlMode = 'dpad'" :class="controlMode === 'dpad' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-300'" class="rpg-control-tab text-xs font-semibold transition-all">D-Pad</button>
                    <button @click="controlMode = 'joystick'" :class="controlMode === 'joystick' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-300'" class="rpg-control-tab text-xs font-semibold transition-all">Analog</button>
                    <button @click="toggleSound()" :class="soundEnabled ? 'bg-emerald-600 text-white dark:bg-emerald-500' : 'text-slate-500 dark:text-slate-300'" class="rpg-control-tab text-xs font-semibold transition-all" x-text="soundEnabled ? 'Suara ON' : 'Suara OFF'"></button>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <button @click="actionMode = 'move'" :class="actionMode === 'move' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" class="rounded-2xl px-3 py-2 text-xs font-bold transition-all">
                        Gerak
                    </button>
                    <button @click="actionMode = 'shoot'" :disabled="ammo <= 0" :class="actionMode === 'shoot' ? 'bg-rose-600 text-white dark:bg-rose-500 dark:text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" class="rounded-2xl px-3 py-2 text-xs font-bold transition-all disabled:opacity-50">
                        Tembak
                    </button>
                    <button type="button" disabled :class="shieldActive ? 'bg-emerald-600 text-white dark:bg-emerald-500' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300'" class="rounded-2xl px-3 py-2 text-xs font-bold transition-all opacity-80 cursor-default">
                        <span x-text="shieldActive ? 'Tameng ON' : 'Tameng Auto'"></span>
                    </button>
                </div>

                <div class="rpg-quick-stats rpg-desktop-only">
                    <div class="rpg-quick-stat">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Peluru</p>
                        <p class="mt-1 text-sm font-black text-amber-500" x-text="ammo"></p>
                    </div>
                    <div class="rpg-quick-stat">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Tameng</p>
                        <p class="mt-1 text-sm font-black" :class="shieldActive ? 'text-emerald-500' : 'text-slate-400'" x-text="shieldActive ? shieldSecondsLeft + 'd' : 'OFF'"></p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-4">
                    <div x-show="controlMode === 'dpad'" class="dpad-container">
                        <div></div>
                        <button class="dpad-btn" @click="performDirectionalAction(0, 1)" @touchstart.prevent="performDirectionalAction(0, 1)">&#8593;</button>
                        <div></div>
                        <button class="dpad-btn" @click="performDirectionalAction(-1, 0)" @touchstart.prevent="performDirectionalAction(-1, 0)">&#8592;</button>
                        <button class="dpad-center dpad-btn" disabled>
                            <span x-text="resolvePlayerAvatar(character.avatar_display || character.avatar)" class="text-sm"></span>
                        </button>
                        <button class="dpad-btn" @click="performDirectionalAction(1, 0)" @touchstart.prevent="performDirectionalAction(1, 0)">&#8594;</button>
                        <div></div>
                        <button class="dpad-btn" @click="performDirectionalAction(0, -1)" @touchstart.prevent="performDirectionalAction(0, -1)">&#8595;</button>
                        <div></div>
                    </div>

                    <div x-show="controlMode === 'joystick'" class="relative flex items-center justify-center">
                        <div class="joystick-zone" id="joystickZone"
                            @touchstart.prevent="joystickStart($event)"
                            @touchmove.prevent="joystickMove($event)"
                            @touchend.prevent="joystickEnd()"
                            @mousedown.prevent="joystickMouseStart($event)"
                            @mousemove.prevent="joystickMouseMove($event)"
                            @mouseup.prevent="joystickEnd()"
                            @mouseleave.prevent="joystickEnd()">
                            <span class="joystick-direction" style="top: 6px; left: 50%; transform: translateX(-50%);">&#8593;</span>
                            <span class="joystick-direction" style="bottom: 6px; left: 50%; transform: translateX(-50%);">&#8595;</span>
                            <span class="joystick-direction" style="left: 10px; top: 50%; transform: translateY(-50%);">&#8592;</span>
                            <span class="joystick-direction" style="right: 10px; top: 50%; transform: translateY(-50%);">&#8594;</span>
                            <div class="joystick-thumb" id="joystickThumb" :class="{'active': joystickActive}" :style="'transform: translate(calc(-50% + ' + joystickX + 'px), calc(-50% + ' + joystickY + 'px))'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showGuideModal" x-cloak class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4 bg-slate-950/70" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="pkg-modal w-full max-w-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 via-sky-500 to-emerald-500 p-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/80">Panduan arena</p>
                <h2 class="mt-2 text-2xl font-black">{{ $rpgMap->nama }}</h2>
                <p class="mt-2 text-sm text-white/85">Baca cepat, lalu langsung main dengan frame yang lebih lega di mobile.</p>
            </div>

            <div class="space-y-4 p-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Target</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Jawab <span class="font-semibold text-emerald-500" x-text="totalNpcs"></span> NPC dan hindari musuh yang bergerak mengikuti tempo map.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Kontrol</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Di mobile cukup pakai D-Pad transparan di bawah layar. Fokus utama tetap gerak dan eksplorasi arena, sementara peluru ditembakkan otomatis saat musuh lurus 3 blok.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Pickup</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Ambil <span class="font-semibold" x-text="pickupIcons.shield"></span> untuk tameng otomatis <span class="font-semibold" x-text="shieldDurationSeconds"></span> detik, dan <span class="font-semibold" x-text="pickupIcons.ammo"></span> untuk <span class="font-semibold" x-text="ammoPerPickup"></span> peluru.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Info cepat</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Pickup tameng <span class="font-semibold text-emerald-500" x-text="shieldPickupCount"></span>, pickup peluru <span class="font-semibold text-amber-500" x-text="ammoPickupCount"></span>, auto tembak aktif saat musuh lurus 3 blok.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/80 p-4 text-sm leading-7 text-slate-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-slate-200">
                    Setelah modal ini ditutup, arena diprioritaskan penuh di mobile. Tombol <span class="font-semibold">Panduan</span> tetap tersedia kalau mau lihat petunjuk lagi.
                </div>

                <div class="flex gap-3">
                    <button @click="dismissGuide()" class="flex-1 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Saya paham, mulai main
                    </button>
                    <a href="{{ route('siswa.rpg.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900">
                        Pilih map lain
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="viewMode === '2d' && isMobileViewport && !showGuideModal && !showNpcDialog && !showCompletion"
        x-cloak
        class="rpg-mobile-dpad-overlay"
    >
        <div class="rpg-mobile-dpad-stack">
            <div class="rpg-mobile-dpad-cluster">
                <div class="dpad-container">
                    <div></div>
                    <button class="dpad-btn" @click="performDirectionalAction(0, 1)" @touchstart.prevent="performDirectionalAction(0, 1)">&#8593;</button>
                    <div></div>
                    <button class="dpad-btn" @click="performDirectionalAction(-1, 0)" @touchstart.prevent="performDirectionalAction(-1, 0)">&#8592;</button>
                    <button class="dpad-center dpad-btn" disabled>
                        <span x-text="resolvePlayerAvatar(character.avatar_display || character.avatar)" class="text-sm"></span>
                    </button>
                    <button class="dpad-btn" @click="performDirectionalAction(1, 0)" @touchstart.prevent="performDirectionalAction(1, 0)">&#8594;</button>
                    <div></div>
                    <button class="dpad-btn" @click="performDirectionalAction(0, -1)" @touchstart.prevent="performDirectionalAction(0, -1)">&#8595;</button>
                    <div></div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showNpcDialog && viewMode !== '3d'" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/60" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="npc-dialog pkg-modal w-full max-w-md overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 flex items-center gap-3">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl backdrop-blur-sm">
                    <span x-text="resolveNpcAvatar(currentNpc?.avatar_display || currentNpc?.avatar)"></span>
                </div>
                <div class="text-white">
                    <h3 class="font-bold text-lg" x-text="currentNpc?.nama"></h3>
                    <p class="text-white/70 text-sm"><span x-text="currentNpc?.poin"></span> poin</p>
                </div>
            </div>

            <div class="npc-dialog-body p-5">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 mb-4 border-l-4 border-indigo-500">
                    <p class="text-gray-800 dark:text-gray-200 font-medium" x-text="currentNpc?.pertanyaan"></p>
                </div>

                <div class="space-y-2" x-show="!answerResult">
                    <template x-for="(choice, idx) in currentNpc?.pilihan_jawaban" :key="idx">
                        <button @click="submitAnswer(idx)" :disabled="submittingAnswer" class="choice-btn w-full text-left p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-medium disabled:opacity-50">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-bold mr-2" x-text="['A','B','C','D'][idx]"></span>
                            <span x-text="choice"></span>
                        </button>
                    </template>
                </div>

                <div x-show="answerResult" class="text-center py-4">
                    <template x-if="answerResult?.correct">
                        <div>
                            <h4 class="text-xl font-bold text-green-600 mt-2">Benar</h4>
                            <p class="text-gray-500 mt-1">+<span x-text="answerResult?.poin"></span> poin</p>
                        </div>
                    </template>
                    <template x-if="answerResult && !answerResult?.correct">
                        <div>
                            <h4 class="text-xl font-bold text-red-500 mt-2">Kurang tepat</h4>
                            <p class="text-gray-500 mt-1">Kamu bisa coba lagi nanti.</p>
                            <p class="text-xs text-gray-400 mt-1">Kunjungi NPC ini lagi untuk menjawab ulang.</p>
                        </div>
                    </template>
                    <button @click="closeDialog()" class="mt-4 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors">
                        <span x-text="answerResult?.correct ? 'Lanjutkan' : 'Coba lagi nanti'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showCompletion && viewMode !== '3d'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" x-transition>
        <div class="pkg-modal w-full max-w-sm p-6 text-center relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-3">Game selesai</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2">{{ $rpgMap->nama }}</p>

                <div class="my-6 p-4 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total skor</p>
                    <p class="text-4xl font-bold text-indigo-600" x-text="session.total_score"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">dari <span x-text="totalNpcs"></span> pertanyaan</p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('siswa.rpg.index') }}" class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-100 dark:hover:bg-gray-700">
                        Peta lain
                    </a>
                    <button @click="resetGame()" class="flex-1 px-4 py-2.5 border border-orange-300 bg-orange-50 text-orange-700 rounded-xl font-medium hover:bg-orange-100 dark:border-orange-900/60 dark:bg-orange-950/30 dark:text-orange-200">
                        Main ulang
                    </button>
                    <button @click="closeCompletion()" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700">
                        Lihat peta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function rpgGame() {
    return {
        gridSize: {{ $rpgMap->grid_size }},
        session: @json($session),
        character: @json($character),
        npcs: @json($npcs),
        obstacles: @json($obstacles),
        onlinePlayers: [],
        activePlayersCount: 1,
        activeGuestsCount: 0,
        activeStudentsCount: 1,
        controlMode: 'dpad',
        actionMode: 'move',
        viewMode: '2d',
        difficulty: '{{ $rpgMap->difficulty ?? "easy" }}',
        npcAvatarLookup: @json(\App\Support\RpgCatalog::npcAvatarLookup()),
        enemyAvatarLookup: @json(\App\Support\RpgCatalog::enemyAvatarLookup()),
        pickupIcons: @json(\App\Support\RpgCatalog::pickupIcons()),
        shieldDurationSeconds: {{ (int) ($rpgMap->shield_duration_seconds ?? 8) }},
        ammoPerPickup: {{ (int) ($rpgMap->ammo_per_pickup ?? 3) }},
        shieldPickupCount: {{ (int) ($rpgMap->shield_pickups_count ?? 1) }},
        ammoPickupCount: {{ (int) ($rpgMap->ammo_pickups_count ?? 2) }},
        pickupRespawnSeconds: 8,
        
        // Enemies
        enemies: JSON.parse(JSON.stringify(@json($enemies))),
        enemyInitial: @json($enemies),
        enemyTimer: null,
        caughtFlash: false,
        catchCount: 0,
        shields: 0,
        shieldActive: false,
        shieldSecondsLeft: 0,
        shieldTimer: null,
        ammo: 0,
        pickups: { shield: [], ammo: [] },
        pickupRespawnTimers: [],
        shotFlash: null,
        soundEnabled: true,
        audioContext: null,

        // NPC dialog
        showNpcDialog: false,
        currentNpc: null,
        answerResult: null,
        submittingAnswer: false,

        // Completion
        showCompletion: false,
        showGuideModal: false,
        isMobileViewport: false,
        resizeHandler: null,
        layoutObserver: null,
        mobileGridPx: null,

        // Joystick
        joystickActive: false,
        joystickX: 0,
        joystickY: 0,
        joystickTimer: null,
        joystickMoveTimer: null,

        // Movement
        pollTimer: null,
        _lastMoveTime: 0,

        get answeredCount() {
            return (this.session.answered_npcs || []).length;
        },
        get totalNpcs() {
            return this.activeNpcs().length;
        },
        isNpcActive(npc) {
            return !!npc && npc.is_active !== false && npc.is_active !== 0 && npc.is_active !== '0';
        },
        activeNpcs() {
            return (this.npcs || []).filter(n => this.isNpcActive(n));
        },
        displayRows() {
            return Array.from({ length: this.gridSize }, (_, index) => this.gridSize - 1 - index);
        },

        mobileGridStyle() {
            if (!this.isMobileViewport || !this.mobileGridPx) {
                return '';
            }

            return `width:${this.mobileGridPx}px;max-width:100%;max-height:100%;`;
        },

        init() {
            this.viewMode = this.resolveStoredViewMode();
            window.pkgSiswaRpg3dState = () => this.getThreeState();
            window.pkgSiswaRpg3dControls = {
                move: ({ dx, dy }) => this.movePlayer(Number(dx || 0), Number(dy || 0)),
                shoot: ({ dx, dy }) => this.shootDirection(Number(dx || 0), Number(dy || 0)),
                answer: ({ index }) => this.submitAnswer(Number(index || 0)),
                closeNpc: () => this.closeDialog(),
                view2d: () => this.setViewMode('2d'),
                reset: () => this.resetGame(),
            };
            this.bindThreeControls();
            if (this.viewMode === '3d') {
                this.$nextTick(() => this.prepareThreeScene());
            }
            this.syncViewportMode();
            this.resizeHandler = () => {
                this.syncViewportMode();
                this.refreshMobileGridSize();
            };
            window.addEventListener('resize', this.resizeHandler);
            window.addEventListener('orientationchange', this.resizeHandler);

            this.enemies = (this.enemies || []).map(enemy => this.normalizeEnemy(enemy));
            this.enemyInitial = JSON.parse(JSON.stringify(this.enemies));
            this.generatePickups();
            this.pollTimer = setInterval(() => this.pollState(), 3000);
            this.startEnemyAI();
            this.setupLayoutObserver();
            requestAnimationFrame(() => this.refreshMobileGridSize());

            if (this.isMobileViewport && !this.hasSeenGuide()) {
                this.showGuideModal = true;
            }

            if (this.session.completed_at) {
                this.showCompletion = true;
            }
        },

        destroy() {
            if (this.pollTimer) clearInterval(this.pollTimer);
            if (this.joystickTimer) clearInterval(this.joystickTimer);
            if (this.enemyTimer) clearInterval(this.enemyTimer);
            if (this.shieldTimer) clearInterval(this.shieldTimer);
            this.pickupRespawnTimers.forEach(timer => clearTimeout(timer));
            this.pickupRespawnTimers = [];
            if (this.resizeHandler) {
                window.removeEventListener('resize', this.resizeHandler);
                window.removeEventListener('orientationchange', this.resizeHandler);
            }
            if (this.layoutObserver) {
                this.layoutObserver.disconnect();
                this.layoutObserver = null;
            }
            if (window.pkgSiswaRpg3dState) {
                delete window.pkgSiswaRpg3dState;
            }
            if (window.pkgSiswaRpg3dControls) {
                delete window.pkgSiswaRpg3dControls;
            }
        },

        resolveStoredViewMode() {
            try {
                return localStorage.getItem('pkg-rpg-view-mode') === '3d' ? '3d' : '2d';
            } catch (error) {
                return '2d';
            }
        },

        setViewMode(mode) {
            this.viewMode = mode === '3d' ? '3d' : '2d';

            try {
                localStorage.setItem('pkg-rpg-view-mode', this.viewMode);
            } catch (error) {
                // ignore localStorage failures
            }

            if (this.viewMode === '3d') {
                this.$nextTick(() => this.prepareThreeScene({ immersive: true }));
            }

            requestAnimationFrame(() => this.refreshMobileGridSize());
        },

        async prepareThreeScene({ immersive = false } = {}) {
            const scene = document.getElementById('siswa-rpg-3d-scene');

            if (!scene || typeof window.pkgLoadRpg3dScene !== 'function') {
                return null;
            }

            try {
                const instance = await window.pkgLoadRpg3dScene(scene);
                if (instance?.minimizeUi) {
                    instance.minimizeUi();
                }
                if (immersive && instance?.enterImmersiveMode) {
                    instance.enterImmersiveMode();
                }

                return instance;
            } catch (error) {
                console.error('Gagal memuat tampilan 3D RPG', error);
                return null;
            }
        },

        bindThreeControls() {
            const scene = document.getElementById('siswa-rpg-3d-scene');
            if (!scene) {
                return;
            }

            scene.addEventListener('rpg3d:move', (event) => {
                const detail = event.detail || {};
                this.movePlayer(Number(detail.dx || 0), Number(detail.dy || 0));
            });

            scene.addEventListener('rpg3d:shoot', (event) => {
                const detail = event.detail || {};
                this.shootDirection(Number(detail.dx || 0), Number(detail.dy || 0));
            });

            scene.addEventListener('rpg3d:view2d', () => this.setViewMode('2d'));
        },

        getThreeState() {
            return {
                map: {
                    grid_size: this.gridSize,
                    background_theme: '{{ $rpgMap->background_theme }}',
                    difficulty: this.difficulty,
                },
                session: this.session,
                character: {
                    ...this.character,
                    avatar_display: this.resolvePlayerAvatar(this.character.avatar_display || this.character.avatar),
                },
                npcs: this.activeNpcs().map(npc => ({
                    ...npc,
                    avatar_display: this.resolveNpcAvatar(npc.avatar_display || npc.avatar),
                })),
                obstacles: this.obstacles,
                enemies: this.enemies.map(enemy => ({
                    ...enemy,
                    avatar: this.resolveEnemyAvatar(enemy.avatar),
                })),
                pickups: this.pickups,
                onlinePlayers: (this.onlinePlayers || []).map(player => ({
                    ...player,
                    avatar_display: this.resolvePlayerAvatar(player.avatar_display || player.avatar),
                })),
                shieldActive: this.shieldActive,
                shieldSecondsLeft: this.shieldSecondsLeft,
                ammo: this.ammo,
                answeredCount: this.answeredCount,
                totalNpcs: this.totalNpcs,
                actionMode: this.actionMode,
                npcDialogOpen: this.showNpcDialog,
                currentNpc: this.currentNpc,
                answerResult: this.answerResult,
                submittingAnswer: this.submittingAnswer,
                completionOpen: this.showCompletion,
                mapName: @json($rpgMap->nama),
                mapListUrl: @json(route('siswa.rpg.index')),
            };
        },

        focusThreeScene() {
            if (this.viewMode !== '3d') return;

            this.$nextTick(() => {
                const scene = this.$el.querySelector('[data-rpg-3d-scene]');
                if (scene) {
                    scene.focus({ preventScroll: true });
                }
            });
        },

        closeCompletion() {
            this.showCompletion = false;
            this.focusThreeScene();
        },

        hasResettableProgress() {
            return !!this.session?.completed_at
                || Number(this.session?.total_score || 0) > 0
                || (this.session?.answered_npcs || []).length > 0;
        },

        guideStorageKey() {
            return 'pkg-rpg-siswa-guide-map-{{ $rpgMap->id }}';
        },

        syncViewportMode() {
            this.isMobileViewport = window.matchMedia('(max-width: 1023px)').matches;
            if (this.isMobileViewport) {
                this.actionMode = 'move';
                this.controlMode = 'dpad';
            }
            if (!this.isMobileViewport) {
                this.showGuideModal = false;
                this.mobileGridPx = null;
            }
        },

        setupLayoutObserver() {
            if (typeof ResizeObserver === 'undefined') {
                return;
            }

            requestAnimationFrame(() => {
                if (!this.$refs?.gridShell) {
                    return;
                }

                this.layoutObserver = new ResizeObserver(() => this.refreshMobileGridSize());
                this.layoutObserver.observe(this.$refs.gridShell);
            });
        },

        refreshMobileGridSize() {
            if (!this.isMobileViewport) {
                this.mobileGridPx = null;
                return;
            }

            requestAnimationFrame(() => {
                const gridShell = this.$refs?.gridShell;
                if (!gridShell) {
                    return;
                }

                const availableWidth = Math.max(0, gridShell.clientWidth - 12);
                const availableHeight = Math.max(0, gridShell.clientHeight - 12);
                const nextSize = Math.floor(Math.min(availableWidth, availableHeight, window.innerWidth - 20));

                if (nextSize > 0) {
                    this.mobileGridPx = nextSize;
                }
            });
        },

        hasSeenGuide() {
            try {
                return window.localStorage.getItem(this.guideStorageKey()) === '1';
            } catch (error) {
                return false;
            }
        },

        openGuide() {
            this.showGuideModal = true;
            this.joystickEnd();
        },

        dismissGuide() {
            this.showGuideModal = false;

            try {
                window.localStorage.setItem(this.guideStorageKey(), '1');
            } catch (error) {
                // ignore localStorage failures
            }

            this.refreshMobileGridSize();
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            this.notifyPlayer(this.soundEnabled ? 'Suara game aktif.' : 'Suara game dimatikan.', 'info');
        },

        notifyPlayer(message, tone = 'info') {
            if (this.isMobileViewport) {
                return;
            }

            if (typeof window.showNotification === 'function') {
                window.showNotification(message, tone);
            }
        },

        playTone(type = 'pickup') {
            if (!this.soundEnabled) return;

            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;

            if (!this.audioContext) {
                this.audioContext = new AudioCtx();
            }

            const audio = this.audioContext;
            if (audio.state === 'suspended' && typeof audio.resume === 'function') {
                audio.resume().catch(() => null);
            }

            const oscillator = audio.createOscillator();
            const gain = audio.createGain();
            const config = {
                pickup: { frequency: 740, duration: 0.08, volume: 0.035 },
                shoot: { frequency: 520, duration: 0.06, volume: 0.04 },
                shield: { frequency: 880, duration: 0.12, volume: 0.04 },
                walk: { frequency: 190, duration: 0.045, volume: 0.018 },
                npc: { frequency: 980, duration: 0.16, volume: 0.035 },
                hit: { frequency: 110, duration: 0.18, volume: 0.045 },
            }[type] || { frequency: 660, duration: 0.07, volume: 0.03 };

            oscillator.type = ['shoot', 'hit'].includes(type) ? 'square' : 'sine';
            oscillator.frequency.value = config.frequency;
            gain.gain.value = config.volume;
            oscillator.connect(gain);
            gain.connect(audio.destination);

            const now = audio.currentTime;
            oscillator.start(now);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + config.duration);
            oscillator.stop(now + config.duration);
        },

        // ===== ENEMY AI =====
        startEnemyAI() {
            if (!this.enemies || this.enemies.length === 0) return;
            const speed = 250;
            this.enemyTimer = setInterval(() => {
                if (this.showNpcDialog || this.showCompletion || this.showGuideModal) return;
                // Player is safe on NPC tile
                if (this.isNpcTile(this.session.pos_x, this.session.pos_y)) return;
                this.moveEnemies();
                if (!this.checkEnemyCatch()) {
                    this.tryAutoShoot();
                }
            }, speed);
        },

        moveEnemies() {
            const now = Date.now();
            const px = this.session.pos_x, py = this.session.pos_y;
            this.enemies.forEach(enemy => {
                const moveInterval = this.getEnemyMoveInterval(enemy);
                if (enemy._nextMoveAt && now < enemy._nextMoveAt) {
                    return;
                }

                const nextMove = this.pickEnemyStep(enemy, px, py);
                enemy._nextMoveAt = now + moveInterval;

                if (nextMove) {
                    enemy._lastX = enemy.x;
                    enemy._lastY = enemy.y;
                    enemy.x = nextMove.x;
                    enemy.y = nextMove.y;
                }
            });
        },

        checkEnemyCatch() {
            const px = this.session.pos_x, py = this.session.pos_y;
            // Player is safe on NPC tiles
            if (this.isNpcTile(px, py)) return false;
            if (this.showNpcDialog) return false;
            if (this.enemies.some(e => e.x === px && e.y === py)) {
                if (this.shieldActive) {
                    this.clearShieldState();
                    this.enemies = JSON.parse(JSON.stringify(this.enemyInitial));
                    this.playTone('shield');
                    this.notifyPlayer('Tameng menyerap serangan musuh.', 'success');
                    return true;
                }

                this.caughtFlash = true;
                setTimeout(() => { this.caughtFlash = false; }, 600);
                this.session.pos_x = 0;
                this.session.pos_y = 0;
                this.session.total_score = Math.max(0, this.session.total_score - 5);
                this.enemies = JSON.parse(JSON.stringify(this.enemyInitial));
                this.playTone('hit');
                
                this.catchCount++;

                fetch("{{ route('siswa.rpg.move', $rpgMap) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ pos_x: 0, pos_y: 0 })
                }).catch(e => console.error(e));

                this.notifyPlayer('Kamu tertangkap musuh. Kembali ke titik awal.', 'error');
                
                return true;
            }
            return false;
        },

        getEnemiesAt(x, y) {
            return (this.enemies || []).filter(e => e.x === x && e.y === y);
        },

        getPickupsAt(x, y) {
            const pickups = [];

            if ((this.pickups.shield || []).some(item => item.x === x && item.y === y)) {
                pickups.push({ type: 'shield', icon: this.pickupIcons.shield });
            }

            if ((this.pickups.ammo || []).some(item => item.x === x && item.y === y)) {
                pickups.push({ type: 'ammo', icon: this.pickupIcons.ammo });
            }

            return pickups;
        },

        resolveNpcAvatar(avatar) {
            if (!avatar || String(avatar).includes('ð')) return this.npcAvatarLookup.N1 || '🧙';
            return this.npcAvatarLookup[avatar] || avatar;
        },

        resolveEnemyAvatar(avatar) {
            if (!avatar || String(avatar).includes('ð')) return this.enemyAvatarLookup.EN || '👾';
            return this.enemyAvatarLookup[avatar] || avatar;
        },

        resolvePlayerAvatar(avatar) {
            if (!avatar || String(avatar).includes('ð')) return '🧑‍🎓';
            return avatar;
        },

        correctAnswerIndex(npc) {
            const choices = Array.isArray(npc?.pilihan_jawaban) ? npc.pilihan_jawaban : [];
            const rawValue = npc?.jawaban_benar;
            const rawText = String(rawValue ?? '').trim();
            const letterIndex = rawText.length === 1 ? rawText.toUpperCase().charCodeAt(0) - 65 : -1;

            if (letterIndex >= 0 && letterIndex < choices.length) {
                return letterIndex;
            }

            const choiceIndex = choices.findIndex(choice => String(choice).trim().toLowerCase() === rawText.toLowerCase());
            if (rawText !== '' && choiceIndex >= 0) {
                return choiceIndex;
            }

            const raw = Number(rawValue);

            if (!Number.isFinite(raw)) {
                return -1;
            }

            if (raw >= 0 && raw < choices.length) {
                return raw;
            }

            if (raw >= 1 && raw <= choices.length) {
                return raw - 1;
            }

            return raw;
        },

        isCorrectAnswer(selectedIndex, npc) {
            const selected = Number(selectedIndex);
            const choices = Array.isArray(npc?.pilihan_jawaban) ? npc.pilihan_jawaban : [];
            const raw = Number(npc?.jawaban_benar);

            if (!Number.isFinite(selected)) return false;
            if (selected === this.correctAnswerIndex(npc)) return true;

            return Number.isFinite(raw) && raw >= 1 && raw <= choices.length && selected === raw - 1;
        },

        normalizeEnemy(enemy) {
            const x = Number(enemy?.x ?? 0);
            const y = Number(enemy?.y ?? 0);

            return {
                x,
                y,
                avatar: enemy?.avatar,
                speed_level: ['slow', 'normal', 'fast'].includes(enemy?.speed_level) ? enemy.speed_level : 'normal',
                intelligence_level: ['low', 'normal', 'high'].includes(enemy?.intelligence_level) ? enemy.intelligence_level : 'normal',
                _lastX: Number(enemy?._lastX ?? x),
                _lastY: Number(enemy?._lastY ?? y),
                _patrolAxis: ['horizontal', 'vertical'].includes(enemy?._patrolAxis) ? enemy._patrolAxis : (Math.random() > 0.5 ? 'horizontal' : 'vertical'),
                _patrolDirection: Number(enemy?._patrolDirection) === -1 ? -1 : 1,
                _alertedUntil: Number(enemy?._alertedUntil || 0),
                _nextMoveAt: Number(enemy?._nextMoveAt || 0),
            };
        },

        generatePickups() {
            const walkableTiles = [];

            for (let y = 0; y < this.gridSize; y++) {
                for (let x = 0; x < this.gridSize; x++) {
                    const blocked =
                        this.isObstacle(x, y) ||
                        this.isNpcTile(x, y) ||
                        this.enemies.some(enemy => enemy.x === x && enemy.y === y) ||
                        (x === 0 && y === 0);

                    if (!blocked) {
                        walkableTiles.push({ x, y });
                    }
                }
            }

            const shuffled = this.shuffleDirections(walkableTiles);

            this.pickups = {
                shield: shuffled.splice(0, Math.max(0, this.shieldPickupCount)),
                ammo: shuffled.splice(0, Math.max(0, this.ammoPickupCount)),
            };
        },

        collectPickupAt(x, y) {
            const shieldIndex = (this.pickups.shield || []).findIndex(item => item.x === x && item.y === y);
            if (shieldIndex !== -1) {
                const pickup = { ...this.pickups.shield.splice(shieldIndex, 1)[0] };
                this.activateShield();
                this.playTone('shield');
                this.notifyPlayer(`Tameng aktif ${this.shieldDurationSeconds} detik.`, 'success');
                this.schedulePickupRespawn('shield', pickup);
            }

            const ammoIndex = (this.pickups.ammo || []).findIndex(item => item.x === x && item.y === y);
            if (ammoIndex !== -1) {
                const pickup = { ...this.pickups.ammo.splice(ammoIndex, 1)[0] };
                this.ammo += this.ammoPerPickup;
                this.playTone('pickup');
                this.notifyPlayer(`Kamu mendapat ${this.ammoPerPickup} peluru.`, 'success');
                this.tryAutoShoot();
                this.schedulePickupRespawn('ammo', pickup);
            }
        },

        schedulePickupRespawn(type, pickup) {
            if (!pickup || !['shield', 'ammo'].includes(type)) return;

            const timer = setTimeout(() => {
                const list = this.pickups[type] || [];
                const x = Number(pickup.x);
                const y = Number(pickup.y);
                const alreadyExists = list.some(item => Number(item.x) === x && Number(item.y) === y);

                if (!alreadyExists && !this.isObstacle(x, y) && !this.isNpcTile(x, y)) {
                    list.push({ x, y });
                    this.pickups[type] = list;
                }

                this.pickupRespawnTimers = this.pickupRespawnTimers.filter(item => item !== timer);
            }, Math.max(3, Number(this.pickupRespawnSeconds || 8)) * 1000);

            this.pickupRespawnTimers.push(timer);
        },

        clearShieldState() {
            this.shieldActive = false;
            this.shieldSecondsLeft = 0;

            if (this.shieldTimer) {
                clearInterval(this.shieldTimer);
                this.shieldTimer = null;
            }
        },

        flashShotAt(x, y) {
            this.shotFlash = { x, y, at: Date.now() };
            setTimeout(() => {
                if (this.shotFlash && this.shotFlash.x === x && this.shotFlash.y === y) {
                    this.shotFlash = null;
                }
            }, 420);
        },

        activateShield() {
            this.shieldActive = true;
            this.shieldSecondsLeft = this.shieldDurationSeconds;

            if (this.shieldTimer) {
                clearInterval(this.shieldTimer);
            }

            this.shieldTimer = setInterval(() => {
                this.shieldSecondsLeft = Math.max(0, this.shieldSecondsLeft - 1);

                if (this.shieldSecondsLeft <= 0) {
                    this.clearShieldState();
                    this.notifyPlayer('Durasi tameng habis.', 'warning');
                }
            }, 1000);
        },

        useShield() {
            if (this.shields <= 0 || this.shieldActive) return;
            this.shields--;
            this.activateShield();
            this.notifyPlayer(`Tameng aktif selama ${this.shieldDurationSeconds} detik.`, 'success');
        },

        performDirectionalAction(dx, dy) {
            if (this.showGuideModal) return;

            if (this.actionMode === 'shoot') {
                this.shootDirection(dx, dy);
                return;
            }

            this.movePlayer(dx, dy);
        },

        shootDirection(dx, dy) {
            if (this.showGuideModal) return;

            if (this.ammo <= 0) {
                this.notifyPlayer('Amunisi habis.', 'warning');
                this.actionMode = 'move';
                return;
            }

            this.ammo--;
            let targetIndex = -1;

            for (let step = 1; step < this.gridSize; step++) {
                const tx = this.session.pos_x + (dx * step);
                const ty = this.session.pos_y + (dy * step);

                if (tx < 0 || tx >= this.gridSize || ty < 0 || ty >= this.gridSize || this.isObstacle(tx, ty)) {
                    break;
                }

                targetIndex = this.enemies.findIndex(enemy => enemy.x === tx && enemy.y === ty);
                if (targetIndex !== -1) {
                    break;
                }
            }

            if (targetIndex !== -1) {
                const defeatedEnemy = { ...this.enemies[targetIndex] };
                this.flashShotAt(defeatedEnemy.x, defeatedEnemy.y);
                this.enemies.splice(targetIndex, 1);
                this.notifyPlayer('Musuh berhasil dikalahkan.', 'success');
                this.scheduleEnemyRespawn(defeatedEnemy);
            } else {
                this.notifyPlayer('Tembakan meleset.', 'warning');
            }

            this.actionMode = 'move';
        },

        tryAutoShoot() {
            if (this.ammo <= 0 || this.showNpcDialog || this.showCompletion || this.showGuideModal) return false;

            const target = this.findAutoShootTarget(3);
            if (!target) return false;

            this.ammo--;
            const defeatedEnemy = { ...this.enemies[target.index] };
            this.flashShotAt(defeatedEnemy.x, defeatedEnemy.y);
            this.enemies.splice(target.index, 1);
            this.playTone('shoot');
            this.scheduleEnemyRespawn(defeatedEnemy);
            this.actionMode = 'move';
            this.notifyPlayer('Peluru otomatis ditembakkan ke musuh terdekat.', 'success');
            return true;
        },

        findAutoShootTarget(maxRange = 3) {
            const directions = [[1, 0], [-1, 0], [0, 1], [0, -1]];

            for (const [dx, dy] of directions) {
                for (let step = 1; step <= maxRange; step++) {
                    const tx = this.session.pos_x + (dx * step);
                    const ty = this.session.pos_y + (dy * step);

                    if (tx < 0 || tx >= this.gridSize || ty < 0 || ty >= this.gridSize || this.isObstacle(tx, ty)) {
                        break;
                    }

                    const index = this.enemies.findIndex(enemy => enemy.x === tx && enemy.y === ty);
                    if (index !== -1) {
                        return { index, x: tx, y: ty };
                    }
                }
            }

            return null;
        },

        scheduleEnemyRespawn(enemy) {
            const respawnTarget = this.findEnemyRespawnTile();
            if (!respawnTarget) return;

            setTimeout(() => {
                this.enemies.push({
                    ...enemy,
                    x: respawnTarget.x,
                    y: respawnTarget.y,
                    _lastX: respawnTarget.x,
                    _lastY: respawnTarget.y,
                    _alerted: false,
                    _alertedUntil: 0,
                    _nextMoveAt: Date.now() + this.getEnemyMoveInterval(enemy),
                });
            }, 900);
        },

        findEnemyRespawnTile() {
            const candidates = [];

            for (let y = 0; y < this.gridSize; y++) {
                for (let x = 0; x < this.gridSize; x++) {
                    const blocked =
                        this.isObstacle(x, y) ||
                        this.isNpcTile(x, y) ||
                        this.enemies.some(enemy => enemy.x === x && enemy.y === y) ||
                        (this.session.pos_x === x && this.session.pos_y === y) ||
                        (x === 0 && y === 0);

                    if (!blocked) {
                        candidates.push({ x, y });
                    }
                }
            }

            if (!candidates.length) return null;

            return candidates[Math.floor(Math.random() * candidates.length)];
        },

        getEnemyMoveInterval(enemy) {
            const base = { easy: 1900, medium: 1350, hard: 950 }[this.difficulty] || 1350;
            const speedFactor = { slow: 1.45, normal: 1.08, fast: 0.82 }[enemy.speed_level || 'normal'] || 1.08;
            const patrolFactor = enemy._alerted ? 1 : 1.24;
            const jitter = 0.94 + Math.random() * 0.12;
            return Math.round(base * speedFactor * patrolFactor * jitter);
        },

        pickEnemyStep(enemy, playerX, playerY) {
            const intelligence = enemy.intelligence_level || 'normal';
            const now = Date.now();
            const alerted = this.isEnemyAlerted(enemy, playerX, playerY, now);

            if (!alerted) {
                return this.pickEnemyPatrolStep(enemy);
            }

            const randomChance = { low: 0.34, normal: 0.18, high: 0.08 }[intelligence] || 0.18;
            const chaseDirections = this.buildEnemyChaseDirections(enemy, playerX, playerY, intelligence);
            const wanderDirections = this.shuffleDirections([[0,1],[0,-1],[1,0],[-1,0]]);
            let directions = [];

            if (Math.random() < randomChance) {
                directions = wanderDirections.concat(chaseDirections);
            } else {
                directions = chaseDirections.concat(wanderDirections);
            }

            for (const [dx, dy] of directions) {
                const nx = enemy.x + dx;
                const ny = enemy.y + dy;
                if (this.canEnemyMoveTo(nx, ny, enemy)) {
                    return { x: nx, y: ny };
                }
            }

            return null;
        },

        isEnemyAlerted(enemy, playerX, playerY, now) {
            const distance = Math.abs(playerX - enemy.x) + Math.abs(playerY - enemy.y);
            const baseRadius = { low: 3, normal: 5, high: 7 }[enemy.intelligence_level || 'normal'] || 5;
            const difficultyBonus = { easy: 0, medium: 1, hard: 1 }[this.difficulty] || 0;
            const speedBonus = enemy.speed_level === 'fast' ? 1 : 0;
            const radius = baseRadius + difficultyBonus + speedBonus;

            if (distance <= radius) {
                const memory = { low: 900, normal: 1600, high: 2400 }[enemy.intelligence_level || 'normal'] || 1600;
                enemy._alertedUntil = now + memory;
                enemy._alerted = true;
                return true;
            }

            enemy._alerted = Number(enemy._alertedUntil || 0) > now;
            return enemy._alerted;
        },

        buildEnemyChaseDirections(enemy, playerX, playerY, intelligence) {
            const directions = [[1,0],[-1,0],[0,1],[0,-1]]
                .map(([dx, dy]) => {
                    const nx = enemy.x + dx;
                    const ny = enemy.y + dy;
                    let score = Math.abs(playerX - nx) + Math.abs(playerY - ny);

                    if (nx === enemy._lastX && ny === enemy._lastY) {
                        score += 0.65;
                    }

                    return { direction: [dx, dy], score };
                })
                .sort((a, b) => a.score - b.score)
                .map(item => item.direction);

            if (intelligence === 'high') {
                return directions;
            }

            return directions.slice(0, 2).concat(this.shuffleDirections(directions.slice(2)));
        },

        pickEnemyPatrolStep(enemy) {
            const direction = Number(enemy._patrolDirection) === -1 ? -1 : 1;
            const primary = enemy._patrolAxis === 'vertical'
                ? [[0, direction], [1, 0], [-1, 0], [0, -direction]]
                : [[direction, 0], [0, 1], [0, -1], [-direction, 0]];
            const directions = Math.random() < 0.18
                ? this.shuffleDirections(primary.slice(0, 3)).concat(primary.slice(3))
                : primary;

            for (const [dx, dy] of directions) {
                const nx = enemy.x + dx;
                const ny = enemy.y + dy;

                if (this.canEnemyMoveTo(nx, ny, enemy)) {
                    const reversed = enemy._patrolAxis === 'vertical'
                        ? dy === -direction
                        : dx === -direction;

                    if (reversed) {
                        enemy._patrolDirection = -direction;
                    }

                    return { x: nx, y: ny };
                }
            }

            enemy._patrolDirection = -direction;
            return null;
        },

        shuffleDirections(directions) {
            return directions
                .slice()
                .sort(() => Math.random() - 0.5);
        },

        canEnemyMoveTo(x, y, currentEnemy) {
            if (x < 0 || x >= this.gridSize || y < 0 || y >= this.gridSize) return false;
            if (this.isObstacle(x, y) || this.isNpcTile(x, y)) return false;
            return !this.enemies.some(enemy => enemy !== currentEnemy && enemy.x === x && enemy.y === y);
        },

        isNpcTile(x, y) {
            return this.activeNpcs().some(n => n.pos_x === x && n.pos_y === y);
        },

        // ===== RESET GAME =====
        async resetGame() {
            const message = 'Reset progres dan poin dari map ini? NPC yang sudah benar akan bisa dijawab ulang.';
            const confirmed = typeof window.showConfirmation === 'function'
                ? await window.showConfirmation(message, {
                    title: 'Reset poin',
                    confirmText: 'Reset',
                    tone: 'warning'
                })
                : window.confirm(message);
            if (!confirmed) return;
            try {
                const res = await fetch("{{ route('siswa.rpg.reset', $rpgMap) }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) location.reload();
                else this.notifyPlayer(data.message || 'Gagal reset game', 'error');
            } catch (e) { this.notifyPlayer(e.message || 'Terjadi kesalahan saat reset game', 'error'); }
        },

        // ===== MOVEMENT =====
        handleKey(e) {
            if (this.viewMode === '3d') return;
            if (this.showNpcDialog || this.showCompletion || this.showGuideModal) return;
            const keyMap = { ArrowUp: [0,1], ArrowDown: [0,-1], ArrowLeft: [-1,0], ArrowRight: [1,0] };
            if (keyMap[e.key]) {
                e.preventDefault();
                this.performDirectionalAction(keyMap[e.key][0], keyMap[e.key][1]);
            }
        },

        moveToCell(x, y) {
            if (this.showNpcDialog || this.showCompletion || this.showGuideModal) return;
            // Only allow moving to adjacent cells
            const dx = x - this.session.pos_x;
            const dy = y - this.session.pos_y;
            if (Math.abs(dx) + Math.abs(dy) === 1) {
                this.performDirectionalAction(dx, dy);
            }
        },

        movePlayer(dx, dy) {
            if (this.showNpcDialog || this.showGuideModal) return false;
            
            // Throttle: 100ms between moves
            const now = Date.now();
            if (now - (this._lastMoveTime || 0) < 100) return false;
            this._lastMoveTime = now;

            const newX = this.session.pos_x + dx;
            const newY = this.session.pos_y + dy;

            // Bounds check
            if (newX < 0 || newX >= this.gridSize || newY < 0 || newY >= this.gridSize) return false;
            // Obstacle check
            if (this.isObstacle(newX, newY)) return false;

            this.session.pos_x = newX;
            this.session.pos_y = newY;
            this.playTone('walk');
            this.collectPickupAt(newX, newY);
            
            // If caught during this move, checkEnemyCatch already resets position and sends fetch.
            if (this.checkEnemyCatch()) return true;

            this.tryAutoShoot();

            // Instant NPC detection (client-side, no delay!)
            const answeredIds = this.session.answered_npcs || [];
            const npcHere = this.activeNpcs().find(n =>
                n.pos_x === newX && n.pos_y === newY && !answeredIds.includes(n.id)
            );
            if (npcHere) {
                this.playTone('npc');
                this.currentNpc = {
                    id: npcHere.id,
                    nama: npcHere.nama,
                    avatar: npcHere.avatar,
                    avatar_display: npcHere.avatar_display || this.resolveNpcAvatar(npcHere.avatar),
                    pertanyaan: npcHere.pertanyaan,
                    pilihan_jawaban: npcHere.pilihan_jawaban,
                    jawaban_benar: npcHere.jawaban_benar,
                    poin: npcHere.poin,
                };
                this.answerResult = null;
                this.showNpcDialog = true;
            }

            // Fire-and-forget server sync (position only, non-blocking)
            fetch("{{ route('siswa.rpg.move', $rpgMap) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pos_x: newX, pos_y: newY })
            })
            .catch(e => console.error('Move error:', e));

            return true;
        },

        // ===== ANALOG JOYSTICK =====
        joystickStart(e) {
            this.joystickActive = true;
            this.updateJoystickPosition(e.touches[0]);
        },
        joystickMove(e) {
            if (!this.joystickActive) return;
            this.updateJoystickPosition(e.touches[0]);
        },
        joystickMouseStart(e) {
            this.joystickActive = true;
            this.updateJoystickPosition(e);
        },
        joystickMouseMove(e) {
            if (!this.joystickActive) return;
            this.updateJoystickPosition(e);
        },
        joystickEnd() {
            this.joystickActive = false;
            this.joystickX = 0;
            this.joystickY = 0;
            if (this.joystickMoveTimer) {
                clearInterval(this.joystickMoveTimer);
                this.joystickMoveTimer = null;
            }
        },

        updateJoystickPosition(point) {
            const zone = document.getElementById('joystickZone');
            const rect = zone.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            let dx = (point.clientX || point.pageX) - centerX;
            let dy = (point.clientY || point.pageY) - centerY;
            
            // Clamp to circle
            const maxDist = 45;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist > maxDist) {
                dx = (dx / dist) * maxDist;
                dy = (dy / dist) * maxDist;
            }
            
            this.joystickX = dx;
            this.joystickY = dy;

            // Determine direction and move (with debounce)
            const threshold = 20;
            if (dist > threshold && !this.joystickMoveTimer) {
                this.executeJoystickMove(dx, dy);
                this.joystickMoveTimer = setInterval(() => {
                    if (this.joystickActive) {
                        this.executeJoystickMove(this.joystickX, this.joystickY);
                    }
                }, 180);
            }
        },

        executeJoystickMove(dx, dy) {
            const absDx = Math.abs(dx);
            const absDy = Math.abs(dy);
            const threshold = 15;
            
            if (Math.max(absDx, absDy) < threshold) return;
            
            if (absDx > absDy) {
                        this.performDirectionalAction(dx > 0 ? 1 : -1, 0);
            } else {
                // Invert Y: joystick up (dy<0) = move up = +1
                this.performDirectionalAction(0, dy > 0 ? -1 : 1);
            }
        },

        // ===== NPC INTERACTION =====
        async submitAnswer(idx) {
            if (this.submittingAnswer || !this.currentNpc) return;
            this.submittingAnswer = true;

            // Instant client-side answer check (no delay!)
            const npcData = this.npcs.find(n => Number(n.id) === Number(this.currentNpc.id));
            const correctIndex = this.correctAnswerIndex(npcData || this.currentNpc);
            const selectedIndex = Number(idx);
            const isCorrect = !!npcData && this.isCorrectAnswer(selectedIndex, npcData || this.currentNpc);
            
            // Show result immediately
            this.answerResult = {
                correct: isCorrect,
                poin: isCorrect ? (this.currentNpc.poin || 10) : 0,
                jawaban_benar: correctIndex,
                total_score: this.session.total_score + (isCorrect ? (this.currentNpc.poin || 10) : 0),
            };

            // Update local state instantly
            if (isCorrect) {
                this.session.total_score += (this.currentNpc.poin || 10);
                if (!this.session.answered_npcs) this.session.answered_npcs = [];
                if (!this.session.answered_npcs.includes(this.currentNpc.id)) {
                    this.session.answered_npcs.push(this.currentNpc.id);
                }

                // Check completion locally
                const totalActive = this.totalNpcs;
                if (this.session.answered_npcs.length >= totalActive) {
                    this.session.completed_at = true;
                    setTimeout(() => {
                        this.showCompletion = true;
                    }, 1500);
                }
            }

            // Sync to server in background (fire-and-forget)
            fetch("{{ route('siswa.rpg.answer', $rpgMap) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    npc_id: this.currentNpc.id,
                    jawaban: idx
                })
            })
            .then(res => res.json())
            .then(data => {
                if (typeof data.correct !== 'undefined') {
                    const serverCorrect = !!data.correct;
                    if (serverCorrect !== this.answerResult.correct) {
                        this.answerResult.correct = serverCorrect;
                        this.answerResult.poin = Number(data.poin || 0);

                        if (serverCorrect && !this.session.answered_npcs.includes(this.currentNpc.id)) {
                            this.session.answered_npcs.push(this.currentNpc.id);
                        } else if (!serverCorrect) {
                            this.session.answered_npcs = (this.session.answered_npcs || []).filter(id => Number(id) !== Number(this.currentNpc.id));
                        }
                    }
                }

                // Sync server score (in case of discrepancy)
                if (data.total_score !== undefined) {
                    this.session.total_score = data.total_score;
                }
            })
            .catch(e => console.error('Answer sync error:', e));

            this.submittingAnswer = false;
        },

        closeDialog() {
            this.showNpcDialog = false;
            this.currentNpc = null;
            this.answerResult = null;
            this.focusThreeScene();
        },

        // ===== HELPERS =====
        isObstacle(x, y) {
            return (this.obstacles || []).some(o => o.x === x && o.y === y);
        },

        getNpcsAt(x, y) {
            return this.activeNpcs().filter(n => n.pos_x === x && n.pos_y === y);
        },

        getPlayersAt(x, y) {
            return this.onlinePlayers.filter(p => p.pos_x === x && p.pos_y === y);
        },

        isNpcAnswered(npcId) {
            return (this.session.answered_npcs || []).includes(npcId);
        },

        // ===== MULTIPLAYER POLLING =====
        async pollState() {
            try {
                const res = await fetch("{{ route('siswa.rpg.state', $rpgMap) }}", {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.onlinePlayers = data.online_players || [];
                    this.activePlayersCount = Number(data.active_players_count || (this.onlinePlayers.length + 1));
                    this.activeGuestsCount = Number(data.active_guests_count || 0);
                    this.activeStudentsCount = Number(data.active_students_count || (this.onlinePlayers.length + 1));
                }
            } catch (e) {
                // Silent fail for polling
            }
        }
    }
}
</script>
@endsection


