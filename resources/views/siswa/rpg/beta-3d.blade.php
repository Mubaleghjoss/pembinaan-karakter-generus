@extends('layouts.siswa')

@section('title', 'Petualangan 3D')

@push('styles')
<style>
    .rpg-beta-shell {
        max-width: 1280px;
    }

    .rpg-beta-stage {
        position: relative;
        min-height: min(74svh, 720px);
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: #0f172a;
        box-shadow: 0 28px 72px rgba(15, 23, 42, 0.2);
    }

    .rpg-beta-stage canvas,
    .rpg-beta-canvas {
        display: block;
        width: 100%;
        height: 100%;
    }

    .rpg-beta-canvas {
        position: absolute;
        inset: 0;
    }

    .rpg-beta-panel,
    .rpg-beta-status,
    .rpg-beta-minimap,
    .rpg-beta-actions {
        position: absolute;
        z-index: 2;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(15, 23, 42, 0.72);
        color: #f8fafc;
        backdrop-filter: blur(16px);
    }

    .rpg-beta-panel {
        top: 0.85rem;
        left: 0.85rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        max-width: calc(100% - 1.7rem);
        border-radius: 1rem;
        padding: 0.5rem;
    }

    .rpg-beta-stat {
        min-width: 5rem;
        padding: 0.35rem 0.55rem;
    }

    .rpg-beta-stat span,
    .rpg-beta-status span {
        display: block;
        color: rgba(226, 232, 240, 0.74);
        font-size: 0.64rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .rpg-beta-stat strong,
    .rpg-beta-status strong {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.92rem;
        line-height: 1.2;
    }

    .rpg-beta-actions {
        top: 0.85rem;
        right: 0.85rem;
        display: flex;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.25rem;
    }

    .rpg-beta-actions button {
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        padding: 0.55rem 0.8rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
    }

    .rpg-beta-actions button:hover {
        background: rgba(255, 255, 255, 0.22);
    }

    .rpg-beta-stage.is-ui-hidden .rpg-beta-panel,
    .rpg-beta-stage.is-ui-hidden .rpg-beta-status,
    .rpg-beta-stage.is-ui-hidden .rpg-beta-minimap,
    .rpg-beta-stage.is-ui-hidden .rpg-beta-controls,
    .rpg-beta-stage.is-ui-hidden .rpg-beta-orientation-hint {
        display: none;
    }

    .rpg-beta-stage.is-ui-hidden .rpg-beta-actions button:not([data-beta-action="ui-toggle"]) {
        display: none;
    }

    .rpg-beta-stage.is-ui-hidden .rpg-beta-actions {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(15, 23, 42, 0.58);
    }

    .rpg-beta-crosshair {
        pointer-events: none;
        position: absolute;
        top: 50%;
        left: 50%;
        z-index: 2;
        width: 1.15rem;
        height: 1.15rem;
        transform: translate(-50%, -50%);
        opacity: 0.74;
    }

    .rpg-beta-crosshair::before,
    .rpg-beta-crosshair::after {
        position: absolute;
        content: "";
        background: rgba(255, 255, 255, 0.86);
        box-shadow: 0 0 8px rgba(15, 23, 42, 0.42);
    }

    .rpg-beta-crosshair::before {
        top: 50%;
        left: 0;
        width: 100%;
        height: 2px;
        transform: translateY(-50%);
    }

    .rpg-beta-crosshair::after {
        top: 0;
        left: 50%;
        width: 2px;
        height: 100%;
        transform: translateX(-50%);
    }

    .rpg-beta-orientation-hint[hidden] {
        display: none;
    }

    .rpg-beta-orientation-hint {
        position: absolute;
        top: 50%;
        left: 50%;
        z-index: 4;
        width: min(18rem, calc(100% - 2rem));
        transform: translate(-50%, -50%);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.76);
        color: #ffffff;
        padding: 0.85rem 1rem;
        text-align: center;
        box-shadow: 0 18px 50px rgba(2, 6, 23, 0.28);
        backdrop-filter: blur(16px);
        pointer-events: none;
    }

    .rpg-beta-orientation-hint strong,
    .rpg-beta-orientation-hint span {
        display: block;
    }

    .rpg-beta-orientation-hint strong {
        font-size: 0.95rem;
        font-weight: 900;
    }

    .rpg-beta-orientation-hint span {
        margin-top: 0.25rem;
        color: rgba(226, 232, 240, 0.82);
        font-size: 0.76rem;
        font-weight: 700;
    }

    .rpg-beta-status {
        left: 0.85rem;
        bottom: 0.85rem;
        max-width: min(22rem, calc(100% - 1.7rem));
        border-radius: 1rem;
        padding: 0.7rem 0.85rem;
    }

    .rpg-beta-minimap {
        right: 0.85rem;
        bottom: 0.85rem;
        display: grid;
        width: min(10rem, 25vw);
        aspect-ratio: 1;
        gap: 1px;
        border-radius: 1rem;
        padding: 0.45rem;
    }

    .rpg-beta-minimap span {
        min-width: 0;
        min-height: 0;
        border-radius: 0.12rem;
        background: rgba(226, 232, 240, 0.34);
    }

    .rpg-beta-minimap .is-wall {
        background: rgba(15, 23, 42, 0.95);
    }

    .rpg-beta-minimap .is-player {
        background: #38bdf8;
        box-shadow: 0 0 0 1px #ffffff, 0 0 8px rgba(56, 189, 248, 0.75);
    }

    .rpg-beta-minimap .is-online {
        background: rgba(56, 189, 248, 0.5);
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.58);
    }

    .rpg-beta-minimap .is-npc {
        background: #2563eb;
    }

    .rpg-beta-minimap .is-npc-done {
        background: rgba(37, 99, 235, 0.35);
    }

    .rpg-beta-minimap .is-enemy {
        background: #ef4444;
    }

    .rpg-beta-minimap .is-pickup {
        background: #f59e0b;
    }

    .rpg-beta-shield-vignette[hidden],
    .rpg-beta-dialog[hidden] {
        display: none;
    }

    .rpg-beta-shield-vignette {
        pointer-events: none;
        position: absolute;
        inset: 0;
        z-index: 1;
        box-shadow: inset 0 0 0 8px rgba(16, 185, 129, 0.34), inset 0 0 80px rgba(16, 185, 129, 0.22);
    }

    .rpg-beta-dialog {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(0.75rem, 3vw, 1.5rem);
        background: rgba(2, 6, 23, 0.58);
        backdrop-filter: blur(6px);
    }

    .rpg-beta-dialog-backdrop {
        display: flex;
        width: min(31rem, 100%);
        max-height: calc(100svh - 1.5rem);
    }

    .rpg-beta-dialog-card {
        display: flex;
        width: 100%;
        max-height: min(42rem, calc(100svh - 1.5rem));
        overflow: hidden;
        flex-direction: column;
        border-radius: 1.25rem;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(248, 250, 252, 0.97);
        color: #0f172a;
        box-shadow: 0 24px 70px rgba(2, 6, 23, 0.38);
    }

    .dark .rpg-beta-dialog-card {
        background: rgba(15, 23, 42, 0.97);
        color: #f8fafc;
    }

    .rpg-beta-dialog-card header {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: linear-gradient(135deg, #2563eb, #0891b2);
        color: #ffffff;
    }

    .rpg-beta-dialog-card header > span {
        display: inline-flex;
        height: 3rem;
        width: 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.9rem;
        background: rgba(255, 255, 255, 0.18);
        font-size: 1.35rem;
        font-weight: 900;
    }

    .rpg-beta-dialog-card header strong,
    .rpg-beta-dialog-card header small {
        display: block;
    }

    .rpg-beta-dialog-card header small {
        margin-top: 0.15rem;
        color: rgba(255, 255, 255, 0.78);
        font-weight: 700;
    }

    .rpg-beta-dialog-body {
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }

    .rpg-beta-question {
        margin: 1rem;
        border-left: 4px solid #2563eb;
        border-radius: 0.95rem;
        background: rgba(37, 99, 235, 0.09);
        padding: 0.9rem 1rem;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .rpg-beta-choice-list {
        display: grid;
        gap: 0.5rem;
        padding: 0 1rem 1rem;
    }

    .rpg-beta-choice {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 0.65rem;
        border-radius: 0.95rem;
        border: 1px solid rgba(148, 163, 184, 0.46);
        background: #ffffff;
        padding: 0.78rem 0.9rem;
        color: #1e293b;
        text-align: left;
        font-size: 0.86rem;
        font-weight: 800;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .dark .rpg-beta-choice {
        border-color: rgba(51, 65, 85, 0.9);
        background: rgba(2, 6, 23, 0.78);
        color: #e2e8f0;
    }

    .rpg-beta-choice span {
        display: inline-flex;
        height: 1.75rem;
        min-width: 1.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.6rem;
        background: rgba(37, 99, 235, 0.12);
        color: #2563eb;
        font-size: 0.78rem;
        font-weight: 900;
    }

    .rpg-beta-answer-result {
        margin: 0 1rem 1rem;
        border-radius: 1rem;
        background: rgba(241, 245, 249, 0.92);
        padding: 1rem;
        text-align: center;
    }

    .dark .rpg-beta-answer-result {
        background: rgba(2, 6, 23, 0.68);
    }

    .rpg-beta-answer-result strong {
        display: block;
        font-size: 1.15rem;
    }

    .rpg-beta-answer-result.is-correct strong {
        color: #16a34a;
    }

    .rpg-beta-answer-result.is-wrong strong {
        color: #ef4444;
    }

    .rpg-beta-answer-result p,
    .rpg-beta-empty-question {
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .rpg-beta-answer-result button {
        margin-top: 0.9rem;
        border: 0;
        border-radius: 999px;
        background: #2563eb;
        color: #ffffff;
        padding: 0.68rem 1.2rem;
        font-size: 0.86rem;
        font-weight: 900;
    }

    .rpg-beta-controls {
        pointer-events: none;
        position: absolute;
        right: 1rem;
        bottom: 1rem;
        left: 1rem;
        z-index: 3;
        display: none;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
    }

    .rpg-beta-pad {
        pointer-events: auto;
    }

    .rpg-beta-pad button {
        display: inline-flex;
        height: 3.25rem;
        width: 3.25rem;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.74);
        color: #ffffff;
        font-size: 1.3rem;
        font-weight: 900;
        box-shadow: 0 14px 34px rgba(2, 6, 23, 0.24);
        backdrop-filter: blur(16px);
        touch-action: none;
    }

    .rpg-beta-pad button:active {
        transform: scale(0.95);
        background: rgba(14, 165, 233, 0.82);
    }

    .rpg-beta-move-pad {
        display: grid;
        grid-template-columns: repeat(3, 3.25rem);
        grid-template-rows: repeat(3, 3.25rem);
        gap: 0.35rem;
    }

    .rpg-beta-turn-pad {
        display: grid;
        grid-template-columns: 3.35rem;
        gap: 0.65rem;
    }

    .rpg-beta-turn-pad button {
        height: 3.35rem;
        width: 3.35rem;
        border-radius: 999px;
        background: rgba(5, 150, 105, 0.8);
    }

    .rpg-beta-pad-core {
        display: inline-flex;
        height: 3.25rem;
        width: 3.25rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(16px);
    }

    .rpg-beta-license {
        font-size: 0.75rem;
        color: var(--pkg-text-muted, #64748b);
    }

    @media (max-width: 768px) {
        .rpg-beta-shell {
            padding: 0;
        }

        .rpg-beta-stage {
            min-height: calc(100svh - 9rem);
            border-radius: 0;
            border-inline: 0;
        }

        .rpg-beta-panel {
            max-width: calc(100% - 7.5rem);
        }

        .rpg-beta-stat {
            min-width: 3.75rem;
            padding: 0.28rem 0.42rem;
        }

        .rpg-beta-stat span {
            font-size: 0.56rem;
        }

        .rpg-beta-stat strong {
            font-size: 0.78rem;
        }

        .rpg-beta-status {
            right: 0.75rem;
            bottom: calc(8.75rem + env(safe-area-inset-bottom));
            left: 0.75rem;
            max-width: none;
            padding: 0.58rem 0.7rem;
        }

        .rpg-beta-minimap {
            display: none;
        }

        .rpg-beta-controls {
            bottom: calc(0.9rem + env(safe-area-inset-bottom));
            display: flex;
        }

        .rpg-beta-dialog {
            align-items: flex-end;
            padding: 0.5rem;
        }

        .rpg-beta-dialog-backdrop,
        .rpg-beta-dialog-card {
            max-height: calc(100svh - 1rem);
        }

        .rpg-beta-dialog-card header {
            padding: 0.8rem;
        }

        .rpg-beta-question {
            margin: 0.75rem;
            padding: 0.75rem;
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .rpg-beta-choice-list {
            padding: 0 0.75rem 0.75rem;
        }

        .rpg-beta-choice {
            padding: 0.68rem 0.75rem;
            font-size: 0.82rem;
        }
    }

    @media (max-height: 520px) and (orientation: landscape) {
        .rpg-beta-shell {
            padding: 0;
        }

        .rpg-beta-stage {
            min-height: 100svh;
            border-radius: 0;
            border: 0;
        }

        .rpg-beta-panel {
            top: calc(0.45rem + env(safe-area-inset-top));
            left: calc(0.45rem + env(safe-area-inset-left));
            max-width: calc(100% - 7rem - env(safe-area-inset-left) - env(safe-area-inset-right));
            gap: 0.28rem;
            border-radius: 0.75rem;
            padding: 0.3rem;
        }

        .rpg-beta-stat {
            min-width: 3.4rem;
            padding: 0.2rem 0.32rem;
        }

        .rpg-beta-stat span {
            font-size: 0.48rem;
            letter-spacing: 0.08em;
        }

        .rpg-beta-stat strong {
            font-size: 0.68rem;
        }

        .rpg-beta-actions {
            top: calc(0.45rem + env(safe-area-inset-top));
            right: calc(0.45rem + env(safe-area-inset-right));
            padding: 0.18rem;
        }

        .rpg-beta-actions button {
            padding: 0.46rem 0.58rem;
            font-size: 0.65rem;
        }

        .rpg-beta-status {
            right: auto;
            bottom: calc(0.45rem + env(safe-area-inset-bottom));
            left: calc(0.45rem + env(safe-area-inset-left));
            max-width: min(18rem, 42vw);
            border-radius: 0.8rem;
            padding: 0.45rem 0.55rem;
        }

        .rpg-beta-status span {
            font-size: 0.5rem;
            letter-spacing: 0.08em;
        }

        .rpg-beta-status strong {
            font-size: 0.7rem;
        }

        .rpg-beta-controls {
            right: calc(0.45rem + env(safe-area-inset-right));
            bottom: calc(0.45rem + env(safe-area-inset-bottom));
            left: auto;
            width: min(20rem, 45vw);
            gap: 0.45rem;
        }

        .rpg-beta-move-pad {
            grid-template-columns: repeat(3, 2.45rem);
            grid-template-rows: repeat(3, 2.45rem);
            gap: 0.24rem;
        }

        .rpg-beta-turn-pad {
            grid-template-columns: 2.5rem;
            gap: 0.35rem;
        }

        .rpg-beta-pad button,
        .rpg-beta-pad-core,
        .rpg-beta-turn-pad button {
            width: 2.45rem;
            height: 2.45rem;
            border-radius: 0.72rem;
            font-size: 1rem;
        }

        .rpg-beta-dialog {
            align-items: stretch;
            padding: calc(0.35rem + env(safe-area-inset-top)) calc(0.45rem + env(safe-area-inset-right)) calc(0.35rem + env(safe-area-inset-bottom)) calc(0.45rem + env(safe-area-inset-left));
        }

        .rpg-beta-dialog-backdrop {
            width: min(46rem, 100%);
            max-height: none;
            margin: auto;
        }

        .rpg-beta-dialog-card {
            max-height: none;
            height: calc(100svh - 0.7rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));
            border-radius: 0.85rem;
        }

        .rpg-beta-dialog-card header {
            gap: 0.55rem;
            padding: 0.55rem 0.7rem;
        }

        .rpg-beta-dialog-card header > span {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.7rem;
            font-size: 1.05rem;
        }

        .rpg-beta-dialog-card header strong {
            font-size: 0.86rem;
        }

        .rpg-beta-dialog-card header small {
            font-size: 0.68rem;
        }

        .rpg-beta-dialog-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-bottom: 0.1rem;
        }

        .rpg-beta-question {
            margin: 0.55rem 0.65rem;
            padding: 0.58rem 0.68rem;
            border-radius: 0.72rem;
            font-size: 0.78rem;
            line-height: 1.35;
        }

        .rpg-beta-choice-list {
            gap: 0.38rem;
            padding: 0 0.65rem 0.65rem;
        }

        .rpg-beta-choice {
            min-height: 2.35rem;
            border-radius: 0.72rem;
            padding: 0.48rem 0.58rem;
            font-size: 0.74rem;
            line-height: 1.28;
        }

        .rpg-beta-choice span {
            width: 1.45rem;
            height: 1.45rem;
            min-width: 1.45rem;
            border-radius: 0.48rem;
            font-size: 0.68rem;
        }

        .rpg-beta-answer-result {
            margin: 0 0.65rem 0.65rem;
            border-radius: 0.75rem;
            padding: 0.7rem;
        }

        .rpg-beta-answer-result strong {
            font-size: 0.94rem;
        }

        .rpg-beta-answer-result p,
        .rpg-beta-empty-question {
            font-size: 0.74rem;
        }

        .rpg-beta-answer-result button {
            margin-top: 0.55rem;
            padding: 0.52rem 0.9rem;
            font-size: 0.76rem;
        }
    }
</style>
@endpush

@push('scripts')
@vite(['resources/js/rpg-beta-3d.js'])
@endpush

@section('content')
<div class="rpg-beta-shell space-y-4 p-4 lg:p-6 mx-auto">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Petualangan 3D</h1>
            <p class="pkg-page-subheading">Mode first-person 3D. Pilih peta, temui NPC 29 karakter, dan jawab pertanyaan untuk poin.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('siswa.rpg.index') }}" class="btn-secondary">Kembali</a>
        </div>
    </div>

    <form method="GET" action="{{ route('siswa.rpg.beta-3d') }}" class="pkg-filter-bar">
        <div class="pkg-filter-grid">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Sumber map</span>
                <select name="map" class="pkg-field" onchange="this.form.submit()">
                    @forelse($maps as $map)
                        <option value="{{ $map->id }}" @selected(optional($selectedMap)->id === $map->id)>
                            {{ $map->nama }} - {{ $map->grid_size }} x {{ $map->grid_size }}
                        </option>
                    @empty
                        <option value="">Belum ada map aktif</option>
                    @endforelse
                </select>
            </label>
        </div>
    </form>

    <div class="pkg-panel p-0 sm:p-3">
        <div
            class="rpg-beta-stage"
            data-rpg-beta-3d
            data-map-source="rpg-beta-map-payload"
            data-wall-model="{{ asset('vendor/rpg-beta/3d-maze/models/wall.glb') }}"
            data-arrow-model="{{ asset('vendor/rpg-beta/3d-maze/models/arrow.glb') }}"
        ></div>
    </div>

    <p class="rpg-beta-license">
        Sebagian aset GLB beta berasal dari chrisraff/3d-maze dan disimpan dengan lisensi MIT di folder vendor beta.
    </p>

    <script type="application/json" id="rpg-beta-map-payload">@json($betaMapPayload)</script>
</div>
@endsection
