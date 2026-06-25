@extends('layouts.public')

@php
    $initialViewMode = request()->query('view') === '2d' ? '2d' : '3d';
@endphp

@section('title', 'Main RPG Quest - ' . $rpgMap->nama)
@section('og_title', 'Main RPG Quest - ' . $rpgMap->nama)
@section('og_description', $rpgMap->deskripsi ?: 'Coba main RPG Quest PKG Panunggangan langsung dari halaman publik.')

@push('styles')
<style>
    .public-rpg-shell {
        background:
            radial-gradient(circle at top left, rgba(16, 185, 129, 0.18), transparent 28%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.16), transparent 24%),
            linear-gradient(180deg, #eff6ff 0%, #f8fafc 42%, #ffffff 100%);
    }

    .dark .public-rpg-shell {
        background:
            radial-gradient(circle at top left, rgba(16, 185, 129, 0.2), transparent 30%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.16), transparent 26%),
            radial-gradient(circle at bottom, rgba(245, 158, 11, 0.12), transparent 32%),
            linear-gradient(180deg, #020617 0%, #0f172a 52%, #111827 100%);
    }

    .public-rpg-hero {
        background:
            radial-gradient(circle at top left, rgba(16, 185, 129, 0.18), transparent 34%),
            radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.16), transparent 30%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(241, 245, 249, 0.94));
        border: 1px solid rgba(226, 232, 240, 0.9);
        color: #0f172a;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
    }

    .dark .public-rpg-hero {
        background:
            radial-gradient(circle at top left, rgba(16, 185, 129, 0.2), transparent 32%),
            radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.14), transparent 28%),
            linear-gradient(135deg, rgba(2, 6, 23, 0.96), rgba(15, 23, 42, 0.92));
        border-color: rgba(255, 255, 255, 0.08);
        color: #f8fafc;
        box-shadow: 0 28px 78px rgba(0, 0, 0, 0.4);
    }

    .public-rpg-hero-chip {
        background: rgba(255, 255, 255, 0.86);
        color: #0f172a;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 10px 24px rgba(148, 163, 184, 0.18);
    }

    .dark .public-rpg-hero-chip {
        background: rgba(255, 255, 255, 0.1);
        color: #f8fafc;
        border-color: rgba(255, 255, 255, 0.12);
        box-shadow: none;
    }

    .public-rpg-panel {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 20px 50px rgba(148, 163, 184, 0.18);
    }

    .dark .public-rpg-panel {
        background: rgba(15, 23, 42, 0.92);
        border-color: rgba(51, 65, 85, 0.92);
        box-shadow: 0 20px 56px rgba(2, 6, 23, 0.34);
    }

    .public-rpg-secondary-link {
        background: rgba(255, 255, 255, 0.96);
        color: #334155;
        border: 1px solid rgba(203, 213, 225, 0.95);
        box-shadow: 0 8px 22px rgba(148, 163, 184, 0.15);
    }

    .dark .public-rpg-secondary-link {
        background: rgba(15, 23, 42, 0.92);
        color: #e2e8f0;
        border-color: rgba(51, 65, 85, 0.9);
        box-shadow: 0 10px 24px rgba(2, 6, 23, 0.28);
    }

    .public-rpg-status-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .dark .public-rpg-status-panel {
        background: #020617;
        border-color: #1e293b;
    }

    .public-rpg-primary-action {
        background: #0f172a;
        color: #ffffff;
    }

    .public-rpg-primary-action:hover {
        background: #1e293b;
    }

    .dark .public-rpg-primary-action {
        background: #ffffff;
        color: #0f172a;
    }

    .dark .public-rpg-primary-action:hover {
        background: #dcfce7;
    }

    .public-rpg-stage-card {
        padding: clamp(16px, 1.8vw, 24px);
    }

    .public-rpg-play-wrap,
    .public-rpg-game-column {
        min-height: 0;
    }

    .public-rpg-play-wrap {
        width: 100%;
        max-width: 92rem;
    }

    .public-rpg-stage-hud {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.65rem;
        margin-top: 1rem;
    }

    .public-rpg-stage-stat {
        border: 1px solid rgba(226, 232, 240, 0.92);
        border-radius: 18px;
        background: rgba(248, 250, 252, 0.92);
        padding: 0.75rem 0.85rem;
        text-align: center;
    }

    .dark .public-rpg-stage-stat {
        border-color: rgba(51, 65, 85, 0.92);
        background: rgba(2, 6, 23, 0.55);
    }

    .public-rpg-stage-stat > span {
        display: block;
        color: #94a3b8;
        font-size: 0.64rem;
        font-weight: 800;
        line-height: 1;
        text-transform: uppercase;
    }

    .public-rpg-stage-stat strong {
        display: block;
        margin-top: 0.4rem;
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 900;
        line-height: 1.1;
    }

    .dark .public-rpg-stage-stat strong {
        color: #f8fafc;
    }

    .public-rpg-3d-active .public-rpg-shell {
        background: linear-gradient(180deg, #020617 0%, #0f172a 56%, #111827 100%);
    }

    .public-rpg-3d-active #pwa-install-banner {
        display: none !important;
    }

    .public-rpg-3d-active .public-rpg-play-shell {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .public-rpg-3d-active .public-rpg-play-wrap {
        max-width: min(100%, 74rem);
    }

    .public-rpg-3d-active .public-rpg-layout {
        display: block !important;
    }

    .public-rpg-3d-active .public-rpg-info-column,
    .public-rpg-3d-active .public-rpg-guest-dock,
    .public-rpg-3d-active .public-rpg-save-panel {
        display: none !important;
    }

    .public-rpg-3d-active .public-rpg-game-column {
        max-width: 100%;
    }

    .public-rpg-3d-active .public-rpg-stage-card {
        padding: clamp(12px, 1.2vw, 18px);
        border-radius: 28px;
    }

    .public-rpg-3d-active .public-rpg-stage-frame {
        min-height: min(76svh, 760px);
    }

    .public-rpg-3d-active #guest-rpg-3d-scene {
        min-height: min(76svh, 760px);
        margin-top: 0.85rem;
    }

    .public-rpg-stage-frame {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 0;
        padding: 0.1rem;
        border-radius: 20px;
        overflow: hidden;
    }

    .public-rpg-grid-shell {
        display: flex;
        justify-content: center;
        width: 100%;
        padding: clamp(4px, 1vw, 10px);
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(226, 232, 240, 0.88));
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.68);
        overflow: hidden;
    }

    .dark .public-rpg-grid-shell {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(2, 6, 23, 0.92));
        border-color: rgba(51, 65, 85, 0.92);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .public-rpg-grid {
        --public-rpg-grid-size: {{ max(1, (int) $rpgMap->grid_size) }};
        --public-rpg-cell-font: clamp(14px, calc(34vw / var(--public-rpg-grid-size)), 28px);
        display: grid;
        grid-template-columns: repeat({{ $rpgMap->grid_size }}, minmax(0, 1fr));
        grid-template-rows: repeat({{ $rpgMap->grid_size }}, minmax(0, 1fr));
        gap: 1px;
        aspect-ratio: 1;
        width: min(100%, clamp(520px, 54vw, 760px), calc(100svh - 12rem));
        max-width: 720px;
        margin: 0 auto;
        border-radius: 20px;
        overflow: hidden;
        background: rgba(15, 23, 42, 0.12);
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 30px 80px rgba(15, 23, 42, 0.16);
    }

    .dark .public-rpg-grid {
        background: rgba(30, 41, 59, 0.8);
        border-color: rgba(148, 163, 184, 0.24);
        box-shadow: 0 34px 84px rgba(2, 6, 23, 0.45);
    }

    .public-rpg-cell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
        min-height: 0;
        aspect-ratio: 1;
        overflow: hidden;
        border: 0;
        padding: 0;
        cursor: pointer;
        user-select: none;
        transition: transform 0.12s ease, filter 0.12s ease;
        font-size: var(--public-rpg-cell-font);
    }

    .public-rpg-cell:hover {
        filter: brightness(1.04);
    }

    .public-rpg-cell:active {
        transform: scale(0.96);
    }

    .theme-grass { background: #86efac; }
    .theme-grass:nth-child(odd) { background: #6ee7b7; }
    .theme-desert { background: #fde68a; }
    .theme-desert:nth-child(odd) { background: #fcd34d; }
    .theme-castle { background: #cbd5e1; }
    .theme-castle:nth-child(odd) { background: #94a3b8; }
    .theme-forest { background: #4ade80; }
    .theme-forest:nth-child(odd) { background: #22c55e; }
    .theme-snow { background: #dbeafe; }
    .theme-snow:nth-child(odd) { background: #bfdbfe; }

    .public-rpg-cell.obstacle {
        background: #57534e !important;
    }

    .public-rpg-cell.safe {
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.55);
    }

    .cell-layer {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .npc-marker {
        animation: npc-bob 1.6s ease-in-out infinite;
    }

    .npc-answered {
        opacity: 0.35;
        filter: grayscale(1);
    }

    .player-marker {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        filter: drop-shadow(0 8px 14px rgba(15, 23, 42, 0.3));
    }

    .player-marker.shield-aura::after {
        content: '';
        position: absolute;
        inset: -5px;
        border-radius: 999px;
        border: 2px solid rgba(16, 185, 129, 0.95);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.22), 0 0 16px rgba(16, 185, 129, 0.38);
        animation: shield-pulse 1.2s ease-in-out infinite;
    }

    .enemy-marker {
        z-index: 8;
        animation: enemy-float 0.9s ease-in-out infinite;
        filter: drop-shadow(0 8px 16px rgba(239, 68, 68, 0.24));
    }

    .shot-flash {
        z-index: 12;
        color: #f97316;
        font-size: 0.85em;
        font-weight: 900;
        text-shadow: 0 0 14px rgba(251, 146, 60, 0.9);
        animation: shot-burst 0.45s ease-out forwards;
        pointer-events: none;
    }

    .caught-flash {
        animation: caught-flash 0.18s ease 4;
    }

    @keyframes npc-bob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    @keyframes enemy-float {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-3px) scale(1.06); }
    }

    @keyframes caught-flash {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.18; }
    }

    @keyframes shield-pulse {
        0%, 100% { transform: scale(0.96); opacity: 0.95; }
        50% { transform: scale(1.06); opacity: 0.6; }
    }

    @keyframes shot-burst {
        0% { transform: scale(0.4); opacity: 0; }
        35% { transform: scale(1.2); opacity: 1; }
        100% { transform: scale(1.6); opacity: 0; }
    }

    .public-rpg-control-tabs {
        display: inline-flex;
        gap: 6px;
        padding: 6px;
        border-radius: 999px;
        background: rgba(241, 245, 249, 0.92);
        border: 1px solid rgba(226, 232, 240, 0.92);
    }

    .dark .public-rpg-control-tabs {
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(51, 65, 85, 0.92);
    }

    .public-rpg-control-tab {
        border: 0;
        border-radius: 999px;
        padding: 10px 16px;
        background: transparent;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        transition: background 0.12s ease, color 0.12s ease, box-shadow 0.12s ease;
    }

    .dark .public-rpg-control-tab {
        color: #cbd5e1;
    }

    .public-rpg-control-tab.is-active {
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.16);
    }

    .dark .public-rpg-control-tab.is-active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: none;
    }

    .public-rpg-control-area {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
    }

    .public-dpad {
        display: grid;
        grid-template-columns: repeat(3, 58px);
        grid-template-rows: repeat(3, 58px);
        gap: 8px;
    }

    .public-dpad button {
        border: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: white;
        font-size: 22px;
        font-weight: 700;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
        transition: transform 0.12s ease, background 0.12s ease;
    }

    .public-dpad button:active {
        transform: scale(0.94);
    }

    .public-dpad .center {
        background: linear-gradient(135deg, #10b981, #2563eb);
        font-size: 18px;
    }

    .public-rpg-joystick {
        display: none;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .public-rpg-joystick.is-active {
        display: flex;
    }

    .public-rpg-joystick-zone {
        position: relative;
        width: 148px;
        height: 148px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.16) 0%, rgba(59, 130, 246, 0.06) 100%);
        border: 3px solid rgba(16, 185, 129, 0.28);
        touch-action: none;
        user-select: none;
    }

    .dark .public-rpg-joystick-zone {
        background: radial-gradient(circle, rgba(16, 185, 129, 0.18) 0%, rgba(59, 130, 246, 0.08) 100%);
        border-color: rgba(110, 231, 183, 0.3);
    }

    .public-rpg-joystick-thumb {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 52px;
        height: 52px;
        border-radius: 999px;
        background: linear-gradient(135deg, #10b981, #2563eb);
        box-shadow: 0 10px 26px rgba(16, 185, 129, 0.26);
        transform: translate(-50%, -50%);
        transition: box-shadow 0.12s ease;
        cursor: grab;
    }

    .public-rpg-joystick-thumb.is-active {
        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.28);
        cursor: grabbing;
    }

    .public-rpg-joystick-label {
        position: absolute;
        color: rgba(71, 85, 105, 0.72);
        font-size: 12px;
        font-weight: 700;
    }

    .dark .public-rpg-joystick-label {
        color: rgba(203, 213, 225, 0.7);
    }

    .public-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 70;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(15, 23, 42, 0.72);
    }

    .public-modal-backdrop.is-open {
        display: flex;
    }

    .public-modal-card {
        width: 100%;
        max-width: 480px;
        overflow: hidden;
        border-radius: 28px;
        background: white;
        border: 1px solid rgba(226, 232, 240, 0.92);
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.28);
    }

    .dark .public-modal-card {
        background: #020617;
        color: #f8fafc;
        border-color: rgba(51, 65, 85, 0.92);
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.5);
    }

    .public-rpg-modal-body {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .public-rpg-mobile-dpad-overlay {
        display: none;
    }

    .public-rpg-mobile-view-switch {
        display: none;
    }

    @media (min-width: 1024px) {
        .public-rpg-layout {
            grid-template-columns: minmax(18rem, 0.7fr) minmax(0, 1.3fr);
            gap: 2rem;
        }

        .public-rpg-game-column {
            min-width: 0;
        }

        .public-rpg-stage-card {
            border-radius: 34px;
        }

        .public-rpg-grid {
            max-width: 760px;
        }
    }

    @media (max-width: 1024px) {
        .public-rpg-stage-card {
            padding: 14px;
        }

        .public-rpg-grid-shell {
            padding: 4px;
            border-radius: 20px;
        }

        .public-rpg-grid {
            width: 100%;
            max-width: 100%;
            border-radius: 16px;
        }

        .public-rpg-joystick-zone {
            width: 132px;
            height: 132px;
        }
    }

    @media (max-width: 1023px) {
        .public-rpg-play-shell {
            min-height: calc(100svh - 5rem);
            padding-top: 0.35rem;
            padding-bottom: 0.45rem;
        }

        .public-rpg-play-wrap {
            height: 100%;
            padding-inline: 0.4rem;
        }

        .public-rpg-play-wrap > .flex,
        .public-rpg-play-wrap > .lg\:grid {
            height: 100%;
        }

        .public-rpg-game-column {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-height: 0;
        }

        .public-rpg-stage-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            padding: 0.35rem 0.4rem;
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .public-rpg-stage-frame {
            flex: 1;
            min-height: 0;
            margin-top: 0;
        }

        .public-rpg-3d-active .public-rpg-play-shell {
            padding-top: 0.35rem;
            padding-bottom: 0.45rem;
        }

        .public-rpg-3d-active .public-rpg-play-wrap {
            padding-inline: 0.25rem;
        }

        .public-rpg-3d-active .public-rpg-stage-card {
            padding: 0.25rem;
            border-radius: 0;
        }

        .public-rpg-3d-active .public-rpg-stage-frame {
            min-height: calc(100svh - 10.25rem);
            padding: 0;
        }

        .public-rpg-3d-active #guest-rpg-3d-scene {
            flex: 1;
            min-height: calc(100svh - 10.25rem);
            height: auto;
            margin-top: 0;
            border-radius: 1rem;
        }

        .public-rpg-mobile-view-switch {
            display: flex;
            justify-content: center;
            padding: 0.15rem 0 0.4rem;
        }

        .public-rpg-mobile-view-switch .pkg-rpg-view-toggle {
            background: rgba(15, 23, 42, 0.78);
            border-color: rgba(255, 255, 255, 0.18);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.2);
            backdrop-filter: blur(18px);
        }

        .public-rpg-mobile-view-switch .pkg-rpg-view-toggle button {
            min-width: 4.5rem;
            color: rgba(255, 255, 255, 0.78);
        }

        .public-rpg-mobile-view-switch .pkg-rpg-view-toggle button.is-active {
            background: #ffffff;
            color: #0f172a;
        }

        .public-rpg-grid-shell {
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

        .public-rpg-grid {
            width: 100%;
            max-width: 100%;
            max-height: 100%;
            margin-inline: auto;
        }

        .public-rpg-cell {
            min-height: 0;
        }

        .public-rpg-mobile-hide {
            display: none !important;
        }

        .public-rpg-mobile-dpad-overlay {
            display: flex;
            flex: 0 0 auto;
            justify-content: center;
            padding-bottom: calc(0.3rem + env(safe-area-inset-bottom));
        }

        .public-rpg-mobile-dpad-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            width: 100%;
        }

        .public-rpg-mobile-dpad-cluster {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .public-rpg-mobile-dpad-overlay .public-dpad button {
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.24);
            backdrop-filter: blur(18px);
        }

        .public-rpg-mobile-dpad-overlay .public-dpad .center {
            background: rgba(16, 185, 129, 0.78);
        }

        .public-rpg-control-tab {
            padding: 9px 14px;
        }

        .public-rpg-grid-shell + .mt-3 {
            display: none;
        }

        .public-rpg-joystick-zone {
            width: 112px;
            height: 112px;
        }

        .public-dpad {
            grid-template-columns: repeat(3, 48px);
            grid-template-rows: repeat(3, 48px);
            gap: 5px;
        }

        .public-dpad button {
            border-radius: 12px;
            font-size: 18px;
        }

        .public-modal-card {
            max-height: calc(100svh - 0.75rem);
        }

        .public-rpg-modal-body {
            max-height: calc(100svh - 6.75rem);
            padding-bottom: 1rem;
        }
    }

    @media (max-width: 640px) {
        .public-rpg-grid {
            max-width: 100%;
        }

        .public-rpg-control-tab {
            padding: 9px 14px;
        }

        .public-dpad {
            grid-template-columns: repeat(3, 54px);
            grid-template-rows: repeat(3, 54px);
            gap: 6px;
        }
    }
</style>
@endpush

@section('content')
<section class="public-rpg-shell public-rpg-play-shell py-10 sm:py-14">
    <div class="public-rpg-play-wrap max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="public-rpg-layout flex flex-col gap-8 lg:grid lg:items-start">
            <div class="public-rpg-info-column public-rpg-mobile-hide order-2 space-y-6 lg:order-1">
                <a href="{{ route('public.rpg.index') }}" class="public-rpg-secondary-link inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold transition hover:border-slate-300 hover:bg-slate-50 dark:hover:border-slate-600 dark:hover:bg-slate-900">
                    Kembali ke daftar RPG
                </a>

                <div class="public-rpg-hero rounded-[28px] px-6 py-7">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700 dark:text-emerald-300">Mode tamu 3D</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight">{{ $rpgMap->nama }}</h1>
                    <p class="mt-4 max-w-xl text-sm leading-7 text-slate-600 dark:text-slate-300">
                        {{ $rpgMap->deskripsi ?: 'Mainkan arena publik RPG Quest ini. Skor tamu berjalan di halaman ini, dan poin tersimpan saat kamu lanjut lewat akun siswa.' }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="public-rpg-hero-chip rounded-full px-3 py-1 text-xs font-semibold">
                            Grid {{ $rpgMap->grid_size }} x {{ $rpgMap->grid_size }}
                        </span>
                        <span class="public-rpg-hero-chip rounded-full px-3 py-1 text-xs font-semibold">
                            {{ $npcs->count() }} tantangan
                        </span>
                        <span class="public-rpg-hero-chip rounded-full px-3 py-1 text-xs font-semibold">
                            Mode {{ ucfirst($rpgMap->difficulty ?? 'seru') }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="public-rpg-panel rounded-3xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Skor tamu</p>
                        <p id="guest-score" class="mt-3 text-4xl font-black text-slate-900 dark:text-white">0</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Skor akan bertambah saat jawaban kamu benar.
                        </p>
                    </div>
                    <div class="public-rpg-panel rounded-3xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Sedang Bermain</p>
                        <p id="guest-active-players" class="mt-3 text-4xl font-black text-sky-600">{{ (int) ($presence['active_players_count'] ?? 0) }}</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            <span id="guest-active-students">{{ (int) ($presence['active_students_count'] ?? 0) }}</span> siswa dan
                            <span id="guest-active-guests">{{ (int) ($presence['active_guests_count'] ?? 0) }}</span> tamu aktif di map ini.
                        </p>
                    </div>
                    <div class="public-rpg-panel rounded-3xl p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Progress</p>
                        <p class="mt-3 text-4xl font-black text-slate-900 dark:text-white">
                            <span id="guest-answered">0</span>/<span id="guest-total">{{ $npcs->count() }}</span>
                        </p>
                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                            Selesaikan semua tantangan untuk memunculkan pesan hadiah.
                        </p>
                    </div>
                    <div class="public-rpg-panel rounded-3xl p-5 md:col-span-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Petunjuk main</p>
                        <div class="mt-3 grid gap-3 text-sm leading-7 text-slate-600 dark:text-slate-300 md:grid-cols-3">
                            <p>Klik kotak di sebelah pemain atau gunakan tombol arah.</p>
                            <p>Kotak NPC adalah zona aman. Musuh tidak masuk ke sana.</p>
                            <p>Jika kena musuh, kamu kembali ke titik awal dan skor tamu berkurang 5.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="public-rpg-game-column order-1 space-y-5 lg:order-2">
                <div class="public-rpg-panel public-rpg-stage-card rounded-[30px]">
                    <div class="public-rpg-mobile-hide flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Arena 3D</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Frame game memakai mode utama, termasuk saat grid 15x15.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="pkg-rpg-view-toggle" aria-label="Pilih tampilan arena">
                                <button id="guest-view-2d" type="button" data-public-rpg-view="2d" class="{{ $initialViewMode === '2d' ? 'is-active' : '' }}" aria-pressed="{{ $initialViewMode === '2d' ? 'true' : 'false' }}">2D</button>
                                <button id="guest-view-3d" type="button" data-public-rpg-view="3d" class="{{ $initialViewMode === '3d' ? 'is-active' : '' }}" aria-pressed="{{ $initialViewMode === '3d' ? 'true' : 'false' }}">3D</button>
                            </div>
                            <span class="public-rpg-hero-chip rounded-full px-3 py-1 text-xs font-semibold">
                                Mobile mode lebih lega
                            </span>
                        </div>
                    </div>

                    <div class="public-rpg-mobile-view-switch">
                        <div class="pkg-rpg-view-toggle" aria-label="Pilih tampilan arena">
                            <button type="button" data-public-rpg-view="2d" class="{{ $initialViewMode === '2d' ? 'is-active' : '' }}" aria-pressed="{{ $initialViewMode === '2d' ? 'true' : 'false' }}">2D</button>
                            <button type="button" data-public-rpg-view="3d" class="{{ $initialViewMode === '3d' ? 'is-active' : '' }}" aria-pressed="{{ $initialViewMode === '3d' ? 'true' : 'false' }}">3D</button>
                        </div>
                    </div>

                    <div class="public-rpg-stage-hud public-rpg-mobile-hide">
                        <div class="public-rpg-stage-stat">
                            <span>Skor</span>
                            <strong id="guest-stage-score">0</strong>
                        </div>
                        <div class="public-rpg-stage-stat">
                            <span>NPC</span>
                            <strong><span id="guest-stage-answered">0</span>/<span id="guest-stage-total">{{ $npcs->count() }}</span></strong>
                        </div>
                        <div class="public-rpg-stage-stat">
                            <span>Online</span>
                            <strong id="guest-stage-active-players">{{ (int) ($presence['active_players_count'] ?? 0) }}</strong>
                        </div>
                        <div class="public-rpg-stage-stat">
                            <span>Peluru</span>
                            <strong id="guest-stage-ammo">0</strong>
                        </div>
                    </div>

                    <div id="guest-stage-frame" class="public-rpg-stage-frame mt-4">
                        <div
                            id="guest-rpg-3d-scene"
                            data-rpg-3d-scene
                            data-rpg-3d-provider="pkgPublicRpg3dState"
                            data-rpg-3d-controls="pkgPublicRpg3dControls"
                            data-rpg-3d-resettable="true"
                            class="pkg-rpg-3d-scene {{ $initialViewMode === '3d' ? '' : 'is-hidden' }}"
                        ></div>
                        <div id="guest-grid-shell" class="public-rpg-grid-shell {{ $initialViewMode === '3d' ? 'is-hidden' : '' }}">
                            <div id="guest-grid" class="public-rpg-grid">
                                @for ($displayY = $rpgMap->grid_size - 1; $displayY >= 0; $displayY--)
                                    @for ($x = 0; $x < $rpgMap->grid_size; $x++)
                                        <button
                                            type="button"
                                            class="public-rpg-cell theme-{{ $rpgMap->background_theme }}"
                                            data-rpg-cell
                                            data-x="{{ $x }}"
                                            data-y="{{ $displayY }}"
                                        ></button>
                                    @endfor
                                @endfor
                            </div>
                        </div>
                    </div>

                    <div class="public-rpg-mobile-hide mt-3 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 dark:border-slate-700 dark:bg-slate-900">
                            <span>{{ \App\Support\RpgCatalog::pickupIcons()['shield'] }}</span>
                            Pickup tameng auto
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 dark:border-slate-700 dark:bg-slate-900">
                            <span>{{ \App\Support\RpgCatalog::pickupIcons()['ammo'] }}</span>
                            Pickup peluru
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 dark:border-slate-700 dark:bg-slate-900">
                            <span>AUTO</span>
                            Tembakan otomatis
                        </span>
                    </div>
                </div>

                <div class="public-rpg-mobile-dpad-overlay {{ $initialViewMode === '3d' ? 'is-hidden' : '' }}">
                    <div class="public-rpg-mobile-dpad-stack">
                        <div class="public-rpg-mobile-dpad-cluster">
                            <div class="public-dpad">
                                <div></div>
                                <button type="button" data-move="up">&#8593;</button>
                                <div></div>
                                <button type="button" data-move="left">&#8592;</button>
                                <button type="button" class="center">G</button>
                                <button type="button" data-move="right">&#8594;</button>
                                <div></div>
                                <button type="button" data-move="down">&#8595;</button>
                                <div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="public-rpg-guest-dock public-rpg-mobile-hide public-rpg-panel rounded-[28px] p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Main sebagai guest</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Kontrol dipisah dari frame supaya gerak lebih enak di layar kecil.</p>
                        </div>
                        <button id="guest-reset" type="button" class="public-rpg-primary-action inline-flex items-center rounded-full px-4 py-2 text-sm font-bold transition">
                            Reset arena
                        </button>
                    </div>

                    <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="public-rpg-control-area">
                            <div class="public-rpg-control-tabs" role="tablist" aria-label="Pilih kontrol arena">
                                <button type="button" class="public-rpg-control-tab is-active" data-control-mode="dpad">D-Pad</button>
                                <button type="button" class="public-rpg-control-tab" data-control-mode="joystick">Analog</button>
                                <button type="button" id="guest-sound-toggle" class="public-rpg-control-tab is-active">Suara ON</button>
                            </div>

                            <div class="grid w-full max-w-xs grid-cols-3 gap-2">
                                <button type="button" id="guest-action-move" class="rounded-2xl bg-slate-900 px-3 py-2 text-xs font-bold text-white transition dark:bg-white dark:text-slate-950">
                                    Gerak
                                </button>
                                <button type="button" id="guest-action-shoot" class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 transition dark:bg-slate-800 dark:text-slate-300">
                                    Tembak
                                </button>
                                <button type="button" id="guest-use-shield" disabled class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500 transition dark:bg-slate-800 dark:text-slate-300 opacity-80 cursor-default">
                                    Tameng Auto
                                </button>
                            </div>

                            <div class="grid w-full max-w-xs grid-cols-2 gap-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-950/50">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Peluru</p>
                                    <p id="guest-ammo-count" class="mt-2 text-xl font-black text-amber-500">0</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Auto tembak saat musuh lurus 3 blok.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-950/50">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Tameng</p>
                                    <p id="guest-shield-timer" class="mt-2 text-xl font-black text-slate-400">OFF</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Aktif otomatis saat pickup tameng diambil.</p>
                                </div>
                            </div>

                            <div class="w-full max-w-xs rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Objektif</p>
                                <div class="mt-2 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                    <p><span id="guest-objective-answered" class="font-semibold text-emerald-500">0</span>/<span id="guest-objective-total" class="font-semibold">{{ $npcs->count() }}</span> NPC selesai dijawab.</p>
                                    <p>Pickup tameng tersisa <span id="guest-objective-shields" class="font-semibold text-emerald-500">{{ (int) ($rpgMap->shield_pickups_count ?? 1) }}</span> dari <span class="font-semibold">{{ (int) ($rpgMap->shield_pickups_count ?? 1) }}</span>.</p>
                                    <p>Pickup peluru tersisa <span id="guest-objective-ammo" class="font-semibold text-amber-500">{{ (int) ($rpgMap->ammo_pickups_count ?? 2) }}</span> dari <span class="font-semibold">{{ (int) ($rpgMap->ammo_pickups_count ?? 2) }}</span>.</p>
                                    <p>Ambil {{ \App\Support\RpgCatalog::pickupIcons()['shield'] }} untuk tameng otomatis {{ (int) ($rpgMap->shield_duration_seconds ?? 8) }} detik, dan {{ \App\Support\RpgCatalog::pickupIcons()['ammo'] }} untuk {{ (int) ($rpgMap->ammo_per_pickup ?? 3) }} peluru tiap pickup.</p>
                                </div>
                            </div>

                            <div id="guest-dpad" class="public-dpad">
                                <div></div>
                                <button type="button" data-move="up">&#8593;</button>
                                <div></div>
                                <button type="button" data-move="left">&#8592;</button>
                                <button type="button" class="center">G</button>
                                <button type="button" data-move="right">&#8594;</button>
                                <div></div>
                                <button type="button" data-move="down">&#8595;</button>
                                <div></div>
                            </div>

                            <div id="guest-joystick" class="public-rpg-joystick">
                                <div id="guest-joystick-zone" class="public-rpg-joystick-zone">
                                    <span class="public-rpg-joystick-label" style="top: 8px; left: 50%; transform: translateX(-50%);">&#8593;</span>
                                    <span class="public-rpg-joystick-label" style="bottom: 8px; left: 50%; transform: translateX(-50%);">&#8595;</span>
                                    <span class="public-rpg-joystick-label" style="left: 10px; top: 50%; transform: translateY(-50%);">&#8592;</span>
                                    <span class="public-rpg-joystick-label" style="right: 10px; top: 50%; transform: translateY(-50%);">&#8594;</span>
                                    <div id="guest-joystick-thumb" class="public-rpg-joystick-thumb"></div>
                                </div>
                            </div>

                            <p id="guest-control-hint" class="text-xs text-slate-400 dark:text-slate-500">
                                Gunakan keyboard, D-Pad, atau analog untuk bergerak.
                            </p>
                        </div>

                        <div class="public-rpg-status-panel w-full max-w-sm rounded-3xl p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Status</p>
                            <p id="guest-status" class="mt-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                                Mulai dari pojok kiri bawah lalu temui semua NPC.
                            </p>
                            <p id="guest-combat-status" class="mt-2 text-xs leading-6 text-slate-500 dark:text-slate-400">
                                Mode gerak. Amunisi 0, tameng cari pickup.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="public-rpg-save-panel public-rpg-mobile-hide public-rpg-panel rounded-[28px] p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Simpan poin sungguhan</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Kalau mau poin dan hadiah tercatat, lanjutkan lewat akun siswa.</p>
                        </div>
                        @if(Auth::guard('siswa')->check())
                            <a href="{{ route('siswa.rpg.play', $rpgMap) }}" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400">
                                Main versi siswa
                            </a>
                        @else
                            <a href="{{ route('siswa.login') }}" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400">
                                Login siswa
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="guest-guide-modal" class="public-modal-backdrop">
    <div class="public-modal-card">
        <div class="bg-gradient-to-r from-emerald-500 via-cyan-500 to-blue-500 px-6 py-5 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/80">Panduan arena</p>
            <h2 class="mt-2 text-2xl font-black">{{ $rpgMap->nama }}</h2>
            <p class="mt-2 text-sm text-white/85">Mode tamu memakai gameplay yang sama. Untuk menyimpan poin, lanjutkan lewat akun siswa.</p>
        </div>
        <div class="public-rpg-modal-body px-6 py-6">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Target</p>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Jawab {{ $npcs->count() }} NPC dan hindari musuh yang bergerak di arena.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Kontrol</p>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Di mobile cukup pakai D-Pad bawah layar. Fokus utama tetap gerak dan eksplorasi arena.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Pickup</p>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Ambil {{ \App\Support\RpgCatalog::pickupIcons()['shield'] }} untuk tameng otomatis {{ (int) ($rpgMap->shield_duration_seconds ?? 8) }} detik, dan {{ \App\Support\RpgCatalog::pickupIcons()['ammo'] }} untuk {{ (int) ($rpgMap->ammo_per_pickup ?? 3) }} peluru.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Status tamu</p>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Mode tamu ini tidak menyimpan progres. Untuk poin sungguhan, lanjutkan lewat akun siswa.
                    </p>
                </div>
            </div>

            <div class="mt-5 flex gap-3">
                <button id="guest-guide-start" type="button" class="public-rpg-primary-action inline-flex flex-1 items-center justify-center rounded-full px-5 py-3 text-sm font-bold transition">
                    Saya paham, mulai main
                </button>
                <a href="{{ route('public.rpg.index') }}" class="public-rpg-secondary-link inline-flex items-center rounded-full px-5 py-3 text-sm font-bold transition">
                    Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div id="guest-npc-modal" class="public-modal-backdrop">
    <div class="public-modal-card">
        <div class="bg-gradient-to-r from-emerald-500 via-cyan-500 to-blue-500 px-6 py-5 text-white">
            <p id="guest-npc-name" class="text-2xl font-black">NPC</p>
            <p class="mt-1 text-sm text-white/80">Jawab dengan benar untuk menambah skor tamu.</p>
        </div>
        <div class="public-rpg-modal-body px-6 py-6">
            <div class="rounded-2xl bg-slate-50 p-4 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-800">
                <p id="guest-npc-question" class="text-base leading-8"></p>
            </div>

            <div id="guest-choice-list" class="mt-4 space-y-2"></div>

            <div id="guest-answer-result" class="mt-5 hidden rounded-2xl bg-slate-50 p-4 text-center ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p id="guest-answer-title" class="text-xl font-black text-slate-900 dark:text-white"></p>
                <p id="guest-answer-text" class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300"></p>
                <button id="guest-answer-close" type="button" class="public-rpg-primary-action mt-4 inline-flex items-center rounded-full px-5 py-2.5 text-sm font-bold transition">
                    Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>

<div id="guest-completion-modal" class="public-modal-backdrop">
    <div class="public-modal-card">
        <div class="bg-gradient-to-r from-emerald-500 via-lime-500 to-cyan-500 px-6 py-6 text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/80">Quest selesai</p>
            <h2 class="mt-3 text-3xl font-black">Selamat, kamu selesai.</h2>
        </div>
        <div class="px-6 py-6">
            <div class="rounded-3xl bg-slate-50 p-5 text-center ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Skor tamu</p>
                <p id="guest-final-score" class="mt-3 text-5xl font-black text-slate-900 dark:text-white">0</p>
            </div>

            <p class="mt-5 text-base leading-8 text-slate-700 dark:text-slate-300">
                Selamat kamu selesai dan dapat point. Ingin menyimpan point ini untuk mendapatkan hadiah dan lakuin kegiatan seru lainnya, ikuti kegiatan PKG Panunggangan.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @if(Auth::guard('siswa')->check())
                    <a href="{{ route('siswa.rpg.play', $rpgMap) }}" class="public-rpg-primary-action inline-flex items-center rounded-full px-5 py-3 text-sm font-bold transition">
                        Main versi siswa
                    </a>
                @else
                    <a href="{{ route('siswa.login') }}" class="public-rpg-primary-action inline-flex items-center rounded-full px-5 py-3 text-sm font-bold transition">
                        Login siswa untuk simpan poin
                    </a>
                @endif
                <a href="{{ route('public.rpg.index') }}" class="public-rpg-secondary-link inline-flex items-center rounded-full px-5 py-3 text-sm font-bold transition hover:border-emerald-400 hover:text-emerald-700 dark:hover:border-emerald-400 dark:hover:text-emerald-300">
                    Lihat quest lainnya
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var state = {
            gridSize: {{ $rpgMap->grid_size }},
            session: {
                pos_x: 0,
                pos_y: 0,
                answered_npcs: [],
                total_score: 0,
                completed: false
            },
            npcs: @json($npcs->values()),
            obstacles: @json($obstacles),
            enemies: JSON.parse(JSON.stringify(@json($enemies))),
            initialEnemies: JSON.parse(JSON.stringify(@json($enemies))),
            currentNpc: null,
            answerResult: null,
            pendingCompletion: false,
            enemyTimer: null,
            controlMode: 'dpad',
            actionMode: 'move',
            viewMode: @json($initialViewMode),
            joystickActive: false,
            joystickX: 0,
            joystickY: 0,
            joystickMoveTimer: null,
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
            shieldDurationSeconds: {{ (int) ($rpgMap->shield_duration_seconds ?? 8) }},
            ammoPerPickup: {{ (int) ($rpgMap->ammo_per_pickup ?? 3) }},
            shieldPickupCount: {{ (int) ($rpgMap->shield_pickups_count ?? 1) }},
            ammoPickupCount: {{ (int) ($rpgMap->ammo_pickups_count ?? 2) }},
            pickupRespawnSeconds: 8,
            activePlayersCount: {{ (int) ($presence['active_players_count'] ?? 0) }},
            activeStudentsCount: {{ (int) ($presence['active_students_count'] ?? 0) }},
            activeGuestsCount: {{ (int) ($presence['active_guests_count'] ?? 0) }},
            presenceToken: null,
            presenceTimer: null,
            pickupIcons: @json(\App\Support\RpgCatalog::pickupIcons()),
            npcAvatarLookup: @json(\App\Support\RpgCatalog::npcAvatarLookup()),
            enemyAvatarLookup: @json(\App\Support\RpgCatalog::enemyAvatarLookup()),
            isMobileViewport: false,
            resizeHandler: null,
            layoutObserver: null,
            mobileGridPx: null,
        };

        var elements = {
            board: document.getElementById('guest-grid'),
            stageFrame: document.getElementById('guest-stage-frame'),
            gridShell: document.getElementById('guest-grid-shell'),
            threeScene: document.getElementById('guest-rpg-3d-scene'),
            view2dButton: document.getElementById('guest-view-2d'),
            view3dButton: document.getElementById('guest-view-3d'),
            viewModeButtons: Array.prototype.slice.call(document.querySelectorAll('[data-public-rpg-view]')),
            mobileDpadOverlay: document.querySelector('.public-rpg-mobile-dpad-overlay'),
            cells: Array.prototype.slice.call(document.querySelectorAll('[data-rpg-cell]')),
            score: document.getElementById('guest-score'),
            answered: document.getElementById('guest-answered'),
            total: document.getElementById('guest-total'),
            status: document.getElementById('guest-status'),
            guideModal: document.getElementById('guest-guide-modal'),
            guideStart: document.getElementById('guest-guide-start'),
            npcModal: document.getElementById('guest-npc-modal'),
            npcName: document.getElementById('guest-npc-name'),
            npcQuestion: document.getElementById('guest-npc-question'),
            choiceList: document.getElementById('guest-choice-list'),
            answerResult: document.getElementById('guest-answer-result'),
            answerTitle: document.getElementById('guest-answer-title'),
            answerText: document.getElementById('guest-answer-text'),
            answerClose: document.getElementById('guest-answer-close'),
            completionModal: document.getElementById('guest-completion-modal'),
            finalScore: document.getElementById('guest-final-score'),
            resetButton: document.getElementById('guest-reset'),
            actionMove: document.getElementById('guest-action-move'),
            actionShoot: document.getElementById('guest-action-shoot'),
            actionShield: document.getElementById('guest-use-shield'),
            dpadButtons: Array.prototype.slice.call(document.querySelectorAll('[data-move]')),
            controlButtons: Array.prototype.slice.call(document.querySelectorAll('[data-control-mode]')),
            dpadPanel: document.getElementById('guest-dpad'),
            joystickPanel: document.getElementById('guest-joystick'),
            controlHint: document.getElementById('guest-control-hint'),
            combatStatus: document.getElementById('guest-combat-status'),
            ammoCount: document.getElementById('guest-ammo-count'),
            shieldTimer: document.getElementById('guest-shield-timer'),
            objectiveAnswered: document.getElementById('guest-objective-answered'),
            objectiveTotal: document.getElementById('guest-objective-total'),
            objectiveShields: document.getElementById('guest-objective-shields'),
            objectiveAmmo: document.getElementById('guest-objective-ammo'),
            activePlayers: document.getElementById('guest-active-players'),
            activeStudents: document.getElementById('guest-active-students'),
            activeGuests: document.getElementById('guest-active-guests'),
            stageScore: document.getElementById('guest-stage-score'),
            stageAnswered: document.getElementById('guest-stage-answered'),
            stageTotal: document.getElementById('guest-stage-total'),
            stageActivePlayers: document.getElementById('guest-stage-active-players'),
            stageAmmo: document.getElementById('guest-stage-ammo'),
            soundToggle: document.getElementById('guest-sound-toggle'),
            joystickZone: document.getElementById('guest-joystick-zone'),
            joystickThumb: document.getElementById('guest-joystick-thumb'),
        };

        function clone(value) {
            return JSON.parse(JSON.stringify(value || []));
        }

        function resolveStoredViewMode() {
            try {
                var requested = new URLSearchParams(window.location.search).get('view');
                if (requested === '2d') {
                    return '2d';
                }
                return '3d';
            } catch (error) {
                return '3d';
            }
        }

        function isNpcActive(npc) {
            return !!npc && npc.is_active !== false && npc.is_active !== 0 && npc.is_active !== '0';
        }

        function activeNpcs() {
            return (state.npcs || []).filter(isNpcActive);
        }

        var rpg3dLoaderReady = null;

        function waitForRpg3dLoader() {
            if (typeof window.pkgLoadRpg3dScene === 'function') {
                return Promise.resolve(true);
            }

            if (rpg3dLoaderReady) {
                return rpg3dLoaderReady;
            }

            rpg3dLoaderReady = new Promise(function (resolve) {
                var resolved = false;
                var finish = function () {
                    if (resolved) {
                        return;
                    }

                    resolved = true;
                    resolve(typeof window.pkgLoadRpg3dScene === 'function');
                };

                window.addEventListener('pkg:rpg3d-loader-ready', finish, { once: true });
                window.setTimeout(finish, 3000);
            });

            return rpg3dLoaderReady;
        }

        function getThreeState() {
            var npcs = activeNpcs();
            return {
                map: {
                    grid_size: state.gridSize,
                    background_theme: @json($rpgMap->background_theme),
                    difficulty: @json($rpgMap->difficulty ?? 'easy')
                },
                session: state.session,
                character: {
                    avatar_display: resolvePlayerAvatar('\u{1F9D1}\u{200D}\u{1F393}')
                },
                npcs: npcs.map(function (npc) {
                    return Object.assign({}, npc, {
                        avatar_display: resolveNpcAvatar(npc.avatar_display || npc.avatar)
                    });
                }),
                obstacles: state.obstacles,
                enemies: (state.enemies || []).map(function (enemy) {
                    return Object.assign({}, enemy, {
                        avatar: resolveEnemyAvatar(enemy.avatar)
                    });
                }),
                pickups: state.pickups,
                shieldActive: state.shieldActive,
                shieldSecondsLeft: state.shieldSecondsLeft,
                ammo: state.ammo,
                answeredCount: state.session.answered_npcs.length,
                totalNpcs: npcs.length,
                actionMode: state.actionMode,
                npcDialogOpen: !!state.currentNpc && elements.npcModal.classList.contains('is-open'),
                currentNpc: state.currentNpc,
                answerResult: state.answerResult,
                submittingAnswer: false,
                completionOpen: elements.completionModal.classList.contains('is-open'),
                mapName: @json($rpgMap->nama),
                mapListUrl: @json(route('public.rpg.index'))
            };
        }

        async function prepareThreeScene(immersive) {
            if (!elements.threeScene) {
                return null;
            }

            await waitForRpg3dLoader();

            if (typeof window.pkgLoadRpg3dScene !== 'function') {
                return null;
            }

            try {
                var scene = await window.pkgLoadRpg3dScene(elements.threeScene);
                if (scene && typeof scene.minimizeUi === 'function') {
                    scene.minimizeUi();
                }
                if (immersive && scene && typeof scene.enterImmersiveMode === 'function') {
                    scene.enterImmersiveMode();
                }

                return scene;
            } catch (error) {
                console.error('Gagal memuat tampilan 3D RPG publik', error);
                return null;
            }
        }

        function setViewMode(mode, options) {
            state.viewMode = mode === '3d' ? '3d' : '2d';
            var shouldEnterImmersive = !options || options.immersive !== false;

            try {
                window.localStorage.setItem('pkg-rpg-public-view-mode', state.viewMode);
            } catch (error) {
                // ignore localStorage failures
            }

            if (elements.gridShell) {
                elements.gridShell.classList.toggle('is-hidden', state.viewMode !== '2d');
            }
            if (elements.threeScene) {
                elements.threeScene.classList.toggle('is-hidden', state.viewMode !== '3d');
            }
            if (elements.mobileDpadOverlay) {
                elements.mobileDpadOverlay.classList.toggle('is-hidden', state.viewMode !== '2d');
            }
            document.body.classList.toggle('public-rpg-3d-active', state.viewMode === '3d');
            elements.viewModeButtons.forEach(function (button) {
                var isActive = button.dataset.publicRpgView === state.viewMode;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            if (state.viewMode === '3d') {
                prepareThreeScene(shouldEnterImmersive);
            }

            refreshMobileGridSize();
        }

        function guideStorageKey() {
            return 'pkg-rpg-public-guide-map-{{ $rpgMap->id }}';
        }

        function hasSeenGuide() {
            try {
                return window.localStorage.getItem(guideStorageKey()) === '1';
            } catch (error) {
                return false;
            }
        }

        function isGuideOpen() {
            return !!(elements.guideModal && elements.guideModal.classList.contains('is-open'));
        }

        function openGuideModal() {
            if (elements.guideModal) {
                elements.guideModal.classList.add('is-open');
            }
            resetJoystick();
        }

        function dismissGuide() {
            if (elements.guideModal) {
                elements.guideModal.classList.remove('is-open');
            }

            try {
                window.localStorage.setItem(guideStorageKey(), '1');
            } catch (error) {
                // ignore localStorage failures
            }

            refreshMobileGridSize();
        }

        function syncViewportMode() {
            state.isMobileViewport = window.matchMedia('(max-width: 1023px)').matches;

            if (state.isMobileViewport) {
                state.actionMode = 'move';
                state.controlMode = 'dpad';
            } else {
                if (elements.guideModal) {
                    elements.guideModal.classList.remove('is-open');
                }
                state.mobileGridPx = null;
                if (elements.board) {
                    elements.board.style.width = '';
                    elements.board.style.height = '';
                }
            }
        }

        function setupLayoutObserver() {
            if (typeof ResizeObserver === 'undefined' || !elements.stageFrame) {
                return;
            }

            state.layoutObserver = new ResizeObserver(function () {
                refreshMobileGridSize();
            });
            state.layoutObserver.observe(elements.stageFrame);
        }

        function refreshMobileGridSize() {
            if (state.viewMode !== '2d' || !state.isMobileViewport || !elements.stageFrame || !elements.board) {
                return;
            }

            window.requestAnimationFrame(function () {
                var availableWidth = Math.max(0, elements.stageFrame.clientWidth - 10);
                var availableHeight = Math.max(0, elements.stageFrame.clientHeight - 10);
                var nextSize = Math.floor(Math.min(availableWidth, availableHeight));

                if (nextSize > 0) {
                    state.mobileGridPx = nextSize;
                    elements.board.style.width = nextSize + 'px';
                    elements.board.style.height = nextSize + 'px';
                }
            });
        }

        function init() {
            state.viewMode = resolveStoredViewMode();
            window.pkgPublicRpg3dState = getThreeState;
            window.pkgPublicRpg3dControls = {
                move: function (detail) {
                    movePlayer(Number(detail.dx || 0), Number(detail.dy || 0));
                },
                shoot: function (detail) {
                    shootDirection(Number(detail.dx || 0), Number(detail.dy || 0));
                },
                answer: function (detail) {
                    submitAnswer(Number(detail.index || 0));
                },
                closeNpc: function () {
                    closeNpcModal();
                },
                closeCompletion: function () {
                    closeCompletionModal();
                },
                reset: function () {
                    resetDemo();
                },
                view2d: function () {
                    setViewMode('2d');
                }
            };
            setViewMode(state.viewMode, { immersive: false });

            if (elements.threeScene) {
                elements.threeScene.addEventListener('rpg3d:move', function (event) {
                    var detail = event.detail || {};
                    movePlayer(Number(detail.dx || 0), Number(detail.dy || 0));
                });
                elements.threeScene.addEventListener('rpg3d:shoot', function (event) {
                    var detail = event.detail || {};
                    shootDirection(Number(detail.dx || 0), Number(detail.dy || 0));
                });
                elements.threeScene.addEventListener('rpg3d:view2d', function () {
                    setViewMode('2d');
                });
            }
            elements.viewModeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setViewMode(button.dataset.publicRpgView || '2d', { immersive: true });
                });
            });

            syncViewportMode();
            state.resizeHandler = function () {
                syncViewportMode();
                refreshMobileGridSize();
            };
            window.addEventListener('resize', state.resizeHandler);
            window.addEventListener('orientationchange', state.resizeHandler);

            elements.cells.forEach(function (cell) {
                cell.addEventListener('click', function () {
                    var x = Number(cell.dataset.x);
                    var y = Number(cell.dataset.y);
                    handleCellClick(x, y);
                });
            });

            elements.dpadButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var direction = button.dataset.move;
                    if (direction === 'up') {
                        performDirectionalAction(0, 1);
                    } else if (direction === 'down') {
                        performDirectionalAction(0, -1);
                    } else if (direction === 'left') {
                        performDirectionalAction(-1, 0);
                    } else if (direction === 'right') {
                        performDirectionalAction(1, 0);
                    }
                });
            });

            elements.controlButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setControlMode(button.dataset.controlMode || 'dpad');
                });
            });

            if (elements.guideStart) {
                elements.guideStart.addEventListener('click', function () {
                    dismissGuide();
                    if (state.viewMode === '3d') {
                        prepareThreeScene(true);
                    }
                });
            }

            elements.answerClose.addEventListener('click', closeNpcModal);
            elements.resetButton.addEventListener('click', resetDemo);
            elements.actionMove.addEventListener('click', function () { setActionMode('move'); });
            elements.actionShoot.addEventListener('click', function () { setActionMode('shoot'); });
            if (elements.soundToggle) {
                elements.soundToggle.addEventListener('click', toggleSound);
            }
            bindJoystick();
            setControlMode(state.controlMode);
            setActionMode(state.actionMode);
            syncSoundButton();
            state.npcs = (state.npcs || []).map(function (npc) {
                npc.avatar = resolveNpcAvatar(npc.avatar_display || npc.avatar);
                return npc;
            });
            state.enemies = normalizeEnemyRoster(state.enemies);
            state.initialEnemies = clone(state.enemies);
            generatePickups();
            state.presenceToken = getPresenceToken();
            updatePresenceUI();
            syncPresence();
            state.presenceTimer = window.setInterval(syncPresence, 5000);
            setupLayoutObserver();
            window.requestAnimationFrame(refreshMobileGridSize);

            if (state.isMobileViewport && state.viewMode !== '3d' && !hasSeenGuide()) {
                openGuideModal();
            }

            document.addEventListener('keydown', function (event) {
                if (state.viewMode === '3d' || isGuideOpen() || elements.npcModal.classList.contains('is-open') || elements.completionModal.classList.contains('is-open')) {
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    performDirectionalAction(0, 1);
                } else if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    performDirectionalAction(0, -1);
                } else if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    performDirectionalAction(-1, 0);
                } else if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    performDirectionalAction(1, 0);
                }
            });

            renderBoard();
            startEnemyLoop();
        }

        function getPresenceToken() {
            try {
                var storageKey = 'pkg-rpg-guest-token-map-{{ $rpgMap->id }}';
                var existing = window.localStorage.getItem(storageKey);
                if (existing) {
                    return existing;
                }

                var generated = 'guest-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
                window.localStorage.setItem(storageKey, generated);
                return generated;
            } catch (error) {
                return 'guest-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
            }
        }

        function updatePresenceUI() {
            if (elements.activePlayers) {
                elements.activePlayers.textContent = String(state.activePlayersCount);
            }

            if (elements.stageActivePlayers) {
                elements.stageActivePlayers.textContent = String(state.activePlayersCount);
            }

            if (elements.activeStudents) {
                elements.activeStudents.textContent = String(state.activeStudentsCount);
            }

            if (elements.activeGuests) {
                elements.activeGuests.textContent = String(state.activeGuestsCount);
            }
        }

        async function syncPresence() {
            if (!state.presenceToken) {
                return;
            }

            try {
                var response = await fetch("{{ route('public.rpg.presence', $rpgMap) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        guest_token: state.presenceToken
                    })
                });
                var data = await response.json();
                if (data.success) {
                    state.activePlayersCount = Number(data.active_players_count || 0);
                    state.activeStudentsCount = Number(data.active_students_count || 0);
                    state.activeGuestsCount = Number(data.active_guests_count || 0);
                    updatePresenceUI();
                }
            } catch (error) {
                // Presence should fail silently in public guest mode.
            }
        }

        function setActionMode(mode) {
            state.actionMode = mode === 'shoot' ? 'shoot' : 'move';

            elements.actionMove.className = state.actionMode === 'move'
                ? 'rounded-2xl bg-slate-900 px-3 py-2 text-xs font-bold text-white transition dark:bg-white dark:text-slate-950'
                : 'rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 transition dark:bg-slate-800 dark:text-slate-300';

            elements.actionShoot.className = state.actionMode === 'shoot'
                ? 'rounded-2xl bg-rose-600 px-3 py-2 text-xs font-bold text-white transition dark:bg-rose-500'
                : 'rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 transition dark:bg-slate-800 dark:text-slate-300';

            updateCombatStatus();
        }

        function updateCombatStatus() {
            if (!elements.combatStatus) {
                return;
            }

            elements.actionShoot.disabled = state.ammo <= 0;
            elements.actionShield.disabled = state.shields <= 0 || state.shieldActive;
            elements.actionShield.textContent = state.shieldActive ? 'Tameng ON' : 'Tameng Auto';

            if (elements.ammoCount) {
                elements.ammoCount.textContent = String(state.ammo);
            }

            if (elements.shieldTimer) {
                elements.shieldTimer.textContent = state.shieldActive ? (state.shieldSecondsLeft + 'd') : 'OFF';
                elements.shieldTimer.className = 'mt-2 text-xl font-black ' + (state.shieldActive ? 'text-emerald-500' : 'text-slate-400');
            }

            if (elements.objectiveAnswered) {
                elements.objectiveAnswered.textContent = String(state.session.answered_npcs.length);
            }

            if (elements.objectiveTotal) {
                elements.objectiveTotal.textContent = String(activeNpcs().length);
            }

            if (elements.objectiveShields) {
                elements.objectiveShields.textContent = String((state.pickups.shield || []).length);
            }

            if (elements.objectiveAmmo) {
                elements.objectiveAmmo.textContent = String((state.pickups.ammo || []).length);
            }

            if (elements.stageAmmo) {
                elements.stageAmmo.textContent = String(state.ammo);
            }

            elements.combatStatus.textContent =
                'Mode ' + (state.actionMode === 'shoot' ? 'tembak' : 'gerak') +
                '. Amunisi ' + state.ammo +
                ', tameng ' + (state.shieldActive ? 'aktif' : 'cari pickup') +
                ', isi pickup ' + state.ammoPerPickup +
                ', perlindungan aktif ' + (state.shieldActive ? 'ya' : 'tidak') +
                (state.shieldActive ? ' (' + state.shieldSecondsLeft + 'd)' : '') +
                '.';
        }

        function setControlMode(mode) {
            state.controlMode = mode === 'joystick' ? 'joystick' : 'dpad';

            elements.controlButtons.forEach(function (button) {
                button.classList.toggle('is-active', button.dataset.controlMode === state.controlMode);
            });

            if (elements.dpadPanel) {
                elements.dpadPanel.classList.toggle('hidden', state.controlMode !== 'dpad');
            }

            if (elements.joystickPanel) {
                elements.joystickPanel.classList.toggle('is-active', state.controlMode === 'joystick');
            }

            if (elements.controlHint) {
                elements.controlHint.textContent = state.controlMode === 'joystick'
                    ? 'Tarik analog ke arah gerak. Lepas untuk berhenti.'
                    : 'Tap tombol arah atau gunakan keyboard untuk bergerak.';
            }

            if (state.controlMode !== 'joystick') {
                resetJoystick();
            }
        }

        function toggleSound() {
            state.soundEnabled = !state.soundEnabled;
            syncSoundButton();
            setStatus(state.soundEnabled ? 'Suara RPG aktif.' : 'Suara RPG dimatikan.');
        }

        function syncSoundButton() {
            if (elements.soundToggle) {
                elements.soundToggle.textContent = state.soundEnabled ? 'Suara ON' : 'Suara OFF';
                elements.soundToggle.className = state.soundEnabled
                    ? 'public-rpg-control-tab is-active'
                    : 'public-rpg-control-tab';
            }
        }

        function playTone(type) {
            if (!state.soundEnabled) {
                return;
            }

            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            if (!state.audioContext) {
                state.audioContext = new AudioCtx();
            }

            var audio = state.audioContext;
            if (audio.state === 'suspended' && typeof audio.resume === 'function') {
                audio.resume().catch(function () {});
            }

            var oscillator = audio.createOscillator();
            var gain = audio.createGain();
            var presets = {
                pickup: { frequency: 740, duration: 0.08, volume: 0.035 },
                shoot: { frequency: 520, duration: 0.06, volume: 0.04 },
                shield: { frequency: 880, duration: 0.12, volume: 0.04 },
                walk: { frequency: 190, duration: 0.045, volume: 0.018 },
                npc: { frequency: 980, duration: 0.16, volume: 0.035 },
                hit: { frequency: 110, duration: 0.18, volume: 0.045 }
            };
            var config = presets[type] || { frequency: 660, duration: 0.07, volume: 0.03 };

            oscillator.type = ['shoot', 'hit'].indexOf(type) !== -1 ? 'square' : 'sine';
            oscillator.frequency.value = config.frequency;
            gain.gain.value = config.volume;
            oscillator.connect(gain);
            gain.connect(audio.destination);

            var now = audio.currentTime;
            oscillator.start(now);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + config.duration);
            oscillator.stop(now + config.duration);
        }

        function bindJoystick() {
            if (!elements.joystickZone || !elements.joystickThumb) {
                return;
            }

            elements.joystickZone.addEventListener('touchstart', function (event) {
                event.preventDefault();
                state.joystickActive = true;
                updateJoystick(event.touches[0]);
            }, { passive: false });

            elements.joystickZone.addEventListener('touchmove', function (event) {
                if (!state.joystickActive) {
                    return;
                }

                event.preventDefault();
                updateJoystick(event.touches[0]);
            }, { passive: false });

            elements.joystickZone.addEventListener('touchend', resetJoystick);
            elements.joystickZone.addEventListener('touchcancel', resetJoystick);

            elements.joystickZone.addEventListener('mousedown', function (event) {
                event.preventDefault();
                state.joystickActive = true;
                updateJoystick(event);
            });

            window.addEventListener('mousemove', function (event) {
                if (!state.joystickActive) {
                    return;
                }

                event.preventDefault();
                updateJoystick(event);
            });

            window.addEventListener('mouseup', resetJoystick);
        }

        function startEnemyLoop() {
            if (!state.enemies.length) {
                return;
            }

            state.enemyTimer = window.setInterval(function () {
                if (state.session.completed || isGuideOpen() || elements.npcModal.classList.contains('is-open')) {
                    return;
                }

                moveEnemies();
                if (!checkEnemyCatch()) {
                    tryAutoShoot();
                }
                renderBoard();
            }, 250);
        }

        function renderBoard() {
            elements.cells.forEach(function (cell) {
                var x = Number(cell.dataset.x);
                var y = Number(cell.dataset.y);
                var html = '';
                cell.classList.remove('obstacle', 'safe');

                if (isObstacle(x, y)) {
                    cell.classList.add('obstacle');
                    html += '<span class="cell-layer">🧱</span>';
                }

                var pickup = getPickupAt(x, y);
                if (pickup && !(state.session.pos_x === x && state.session.pos_y === y)) {
                    html += '<span class="cell-layer npc-marker ' + (pickup.type === 'shield' ? 'text-emerald-500' : 'text-amber-500') + '">' + escapeHtml(pickup.icon) + '</span>';
                }

                var enemy = getEnemyAt(x, y);
                if (enemy) {
                    html += '<span class="cell-layer enemy-marker">' + escapeHtml(enemy.avatar || '👾') + '</span>';
                }

                var npc = getNpcAt(x, y);
                if (npc) {
                    cell.classList.add('safe');
                    var npcClass = isNpcAnswered(npc.id) ? 'cell-layer npc-marker npc-answered' : 'cell-layer npc-marker';
                    html += '<span class="' + npcClass + '">' + escapeHtml(npc.avatar || '🧙') + '</span>';
                }

                if (state.session.pos_x === x && state.session.pos_y === y) {
                    html += '<span class="cell-layer player-marker ' + (state.shieldActive ? 'shield-aura' : '') + '">🧒</span>';
                }

                if (state.shotFlash && state.shotFlash.x === x && state.shotFlash.y === y) {
                    html += '<span class="cell-layer shot-flash">✦</span>';
                }

                cell.innerHTML = html;

                var npcMarker = cell.querySelector('.npc-marker');
                if (npcMarker && npc) {
                    npcMarker.textContent = resolveNpcAvatar(npc.avatar_display || npc.avatar);
                }

                var enemyMarker = cell.querySelector('.enemy-marker');
                if (enemyMarker && enemy) {
                    enemyMarker.textContent = resolveEnemyAvatar(enemy.avatar);
                }

                var playerMarker = cell.querySelector('.player-marker');
                if (playerMarker) {
                    playerMarker.textContent = resolvePlayerAvatar('🧑‍🎓');
                }
            });

            elements.score.textContent = String(state.session.total_score);
            elements.answered.textContent = String(state.session.answered_npcs.length);
            elements.total.textContent = String(activeNpcs().length);
            if (elements.stageScore) {
                elements.stageScore.textContent = String(state.session.total_score);
            }
            if (elements.stageAnswered) {
                elements.stageAnswered.textContent = String(state.session.answered_npcs.length);
            }
            if (elements.stageTotal) {
                elements.stageTotal.textContent = String(activeNpcs().length);
            }
            updateCombatStatus();
        }

        function handleCellClick(x, y) {
            var dx = x - state.session.pos_x;
            var dy = y - state.session.pos_y;

            if (Math.abs(dx) + Math.abs(dy) !== 1) {
                setStatus('Kamu hanya bisa bergerak ke kotak di sebelah pemain.');
                return;
            }

            performDirectionalAction(dx, dy);
        }

        function performDirectionalAction(dx, dy) {
            if (isGuideOpen()) {
                return;
            }

            if (state.actionMode === 'shoot') {
                shootDirection(dx, dy);
                return;
            }

            movePlayer(dx, dy);
        }

        function movePlayer(dx, dy) {
            if (state.session.completed || isGuideOpen() || elements.npcModal.classList.contains('is-open')) {
                return false;
            }

            var newX = state.session.pos_x + dx;
            var newY = state.session.pos_y + dy;

            if (newX < 0 || newX >= state.gridSize || newY < 0 || newY >= state.gridSize) {
                return false;
            }

            if (isObstacle(newX, newY)) {
                setStatus('Arah ini tertutup. Cari jalan lain.');
                return false;
            }

            state.session.pos_x = newX;
            state.session.pos_y = newY;
            playTone('walk');
            collectPickupAt(newX, newY);

            if (checkEnemyCatch()) {
                renderBoard();
                return true;
            }

            tryAutoShoot();
            renderBoard();

            var npc = getNpcAt(newX, newY);
            if (npc && !isNpcAnswered(npc.id)) {
                openNpcModal(npc);
                return true;
            }

            setStatus('Teruskan langkahmu sampai semua NPC selesai dijawab.');
            return true;
        }

        function moveEnemies() {
            var now = Date.now();
            var playerX = state.session.pos_x;
            var playerY = state.session.pos_y;

            state.enemies.forEach(function (enemy) {
                var moveInterval = getEnemyMoveInterval(enemy);
                if (enemy._nextMoveAt && now < enemy._nextMoveAt) {
                    return;
                }

                enemy._nextMoveAt = now + moveInterval;
                var nextStep = pickEnemyStep(enemy, playerX, playerY);
                if (!nextStep) {
                    return;
                }

                enemy._lastX = enemy.x;
                enemy._lastY = enemy.y;
                enemy.x = nextStep.x;
                enemy.y = nextStep.y;
            });
        }

        function checkEnemyCatch() {
            if (getNpcAt(state.session.pos_x, state.session.pos_y)) {
                return false;
            }

            var caught = state.enemies.some(function (enemy) {
                return enemy.x === state.session.pos_x && enemy.y === state.session.pos_y;
            });

            if (!caught) {
                return false;
            }

            if (state.shieldActive) {
                clearShieldState();
                state.enemies = clone(state.initialEnemies);
                playTone('shield');
                setStatus('Tameng aktif menahan serangan musuh.');
                return true;
            }

            state.session.pos_x = 0;
            state.session.pos_y = 0;
            state.session.total_score = Math.max(0, state.session.total_score - 5);
            state.enemies = clone(state.initialEnemies);
            playTone('hit');

            elements.board.classList.add('caught-flash');
            window.setTimeout(function () {
                elements.board.classList.remove('caught-flash');
            }, 650);

            setStatus('Kena musuh. Kamu kembali ke titik awal dan skor tamu berkurang 5.');
            return true;
        }

        function useShield() {
            if (state.shields <= 0 || state.shieldActive) {
                return;
            }

            state.shields -= 1;
            state.shieldActive = true;
            state.shieldSecondsLeft = state.shieldDurationSeconds;

            if (state.shieldTimer) {
                window.clearInterval(state.shieldTimer);
            }

            state.shieldTimer = window.setInterval(function () {
                state.shieldSecondsLeft = Math.max(0, state.shieldSecondsLeft - 1);

                if (state.shieldSecondsLeft <= 0) {
                    clearShieldState();
                    updateCombatStatus();
                    setStatus('Durasi tameng habis.');
                }
            }, 1000);

            updateCombatStatus();
            setStatus('Tameng aktif selama ' + state.shieldDurationSeconds + ' detik.');
        }

        function shootDirection(dx, dy) {
            if (isGuideOpen()) {
                return;
            }

            if (state.ammo <= 0) {
                setStatus('Amunisi habis. Kembali ke mode gerak.');
                setActionMode('move');
                return;
            }

            state.ammo -= 1;
            var hitIndex = -1;

            for (var step = 1; step < state.gridSize; step++) {
                var targetX = state.session.pos_x + (dx * step);
                var targetY = state.session.pos_y + (dy * step);

                if (
                    targetX < 0 ||
                    targetX >= state.gridSize ||
                    targetY < 0 ||
                    targetY >= state.gridSize ||
                    isObstacle(targetX, targetY)
                ) {
                    break;
                }

                hitIndex = state.enemies.findIndex(function (enemy) {
                    return enemy.x === targetX && enemy.y === targetY;
                });

                if (hitIndex !== -1) {
                    break;
                }
            }

            if (hitIndex !== -1) {
                var defeatedEnemy = Object.assign({}, state.enemies[hitIndex]);
                flashShotAt(defeatedEnemy.x, defeatedEnemy.y);
                state.enemies.splice(hitIndex, 1);
                setStatus('Tembakan kena. Satu musuh berhasil dikalahkan.');
                scheduleEnemyRespawn(defeatedEnemy);
            } else {
                setStatus('Tembakan meleset.');
            }

            setActionMode('move');
            renderBoard();
        }

        function tryAutoShoot() {
            if (state.ammo <= 0 || state.session.completed || isGuideOpen() || elements.npcModal.classList.contains('is-open')) {
                return false;
            }

            var target = findAutoShootTarget(3);
            if (!target) {
                return false;
            }

            state.ammo -= 1;
            var defeatedEnemy = Object.assign({}, state.enemies[target.index]);
            flashShotAt(defeatedEnemy.x, defeatedEnemy.y);
            state.enemies.splice(target.index, 1);
            playTone('shoot');
            scheduleEnemyRespawn(defeatedEnemy);
            setActionMode('move');
            setStatus('Peluru otomatis ditembakkan ke musuh terdekat.');
            return true;
        }

        function findAutoShootTarget(maxRange) {
            var directions = [[1, 0], [-1, 0], [0, 1], [0, -1]];

            for (var i = 0; i < directions.length; i++) {
                var dx = directions[i][0];
                var dy = directions[i][1];

                for (var step = 1; step <= maxRange; step++) {
                    var targetX = state.session.pos_x + (dx * step);
                    var targetY = state.session.pos_y + (dy * step);

                    if (
                        targetX < 0 ||
                        targetX >= state.gridSize ||
                        targetY < 0 ||
                        targetY >= state.gridSize ||
                        isObstacle(targetX, targetY)
                    ) {
                        break;
                    }

                    var enemyIndex = state.enemies.findIndex(function (enemy) {
                        return enemy.x === targetX && enemy.y === targetY;
                    });

                    if (enemyIndex !== -1) {
                        return { index: enemyIndex, x: targetX, y: targetY };
                    }
                }
            }

            return null;
        }

        function scheduleEnemyRespawn(enemy) {
            var respawnTile = findEnemyRespawnTile();
            if (!respawnTile) {
                return;
            }

            window.setTimeout(function () {
                state.enemies.push({
                    x: respawnTile.x,
                    y: respawnTile.y,
                    avatar: enemy.avatar,
                    speed_level: enemy.speed_level || 'normal',
                    intelligence_level: enemy.intelligence_level || 'normal',
                    _lastX: respawnTile.x,
                    _lastY: respawnTile.y,
                    _alerted: false,
                    _alertedUntil: 0,
                    _patrolAxis: enemy._patrolAxis || (Math.random() > 0.5 ? 'horizontal' : 'vertical'),
                    _patrolDirection: Number(enemy._patrolDirection) === -1 ? -1 : 1,
                    _nextMoveAt: Date.now() + getEnemyMoveInterval(enemy)
                });
                renderBoard();
            }, 900);
        }

        function openNpcModal(npc) {
            playTone('npc');
            state.currentNpc = npc;
            state.answerResult = null;
            state.pendingCompletion = false;
            resetJoystick();

            elements.npcName.textContent = npc.nama || 'NPC';
            elements.npcQuestion.textContent = npc.pertanyaan || '';
            elements.choiceList.innerHTML = '';
            elements.answerResult.classList.add('hidden');

            (npc.pilihan_jawaban || []).forEach(function (choice, index) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-200';
                button.textContent = String.fromCharCode(65 + index) + '. ' + choice;
                button.addEventListener('click', function () {
                    submitAnswer(index);
                });
                elements.choiceList.appendChild(button);
            });

            elements.npcModal.classList.add('is-open');
        }

        function submitAnswer(index) {
            if (!state.currentNpc) {
                return;
            }

            var isCorrect = isCorrectAnswer(index, state.currentNpc);
            state.answerResult = {
                correct: isCorrect,
                poin: isCorrect ? Number(state.currentNpc.poin || 10) : 0
            };

            if (isCorrect && !isNpcAnswered(state.currentNpc.id)) {
                state.session.answered_npcs.push(state.currentNpc.id);
                state.session.total_score += Number(state.currentNpc.poin || 10);
            }

            elements.choiceList.innerHTML = '';
            elements.answerResult.classList.remove('hidden');

            if (isCorrect) {
                elements.answerTitle.textContent = 'Jawaban benar';
                elements.answerText.textContent = 'Skor tamu bertambah ' + Number(state.currentNpc.poin || 10) + ' poin. Lanjutkan petualanganmu.';

                if (state.session.answered_npcs.length >= activeNpcs().length) {
                    state.pendingCompletion = true;
                }
            } else {
                elements.answerTitle.textContent = 'Belum tepat';
                elements.answerText.textContent = 'Tidak apa-apa, kamu bisa kembali lagi ke NPC ini dan mencoba ulang.';
            }

            renderBoard();
        }

        function closeNpcModal() {
            elements.npcModal.classList.remove('is-open');
            state.currentNpc = null;
            state.answerResult = null;

            if (state.pendingCompletion) {
                state.pendingCompletion = false;
                openCompletionModal();
                return;
            }

            setStatus('Lanjutkan ke NPC berikutnya untuk menambah skor tamu.');
        }

        function openCompletionModal() {
            state.session.completed = true;
            resetJoystick();
            elements.finalScore.textContent = String(state.session.total_score);
            elements.completionModal.classList.add('is-open');
            setStatus('Quest tamu selesai. Jika ingin poin tersimpan, lanjutkan lewat akun siswa.');
        }

        function closeCompletionModal() {
            elements.completionModal.classList.remove('is-open');
            if (state.viewMode === '3d' && elements.threeScene) {
                elements.threeScene.focus({ preventScroll: true });
            }
        }

        async function resetDemo() {
            const confirmed = await window.showConfirmation('Reset arena tamu ini dan kembali ke titik awal?', {
                title: 'Reset arena RPG',
                confirmText: 'Reset',
                tone: 'warning'
            });
            if (!confirmed) {
                return;
            }

            state.session = {
                pos_x: 0,
                pos_y: 0,
                answered_npcs: [],
                total_score: 0,
                completed: false
            };
            state.currentNpc = null;
            state.pendingCompletion = false;
            state.enemies = clone(state.initialEnemies);
            state.shields = 0;
            state.ammo = 0;
            clearShieldState();
            state.pickupRespawnTimers.forEach(function (timer) {
                window.clearTimeout(timer);
            });
            state.pickupRespawnTimers = [];
            generatePickups();
            resetJoystick();
            setActionMode('move');

            elements.npcModal.classList.remove('is-open');
            elements.completionModal.classList.remove('is-open');

            renderBoard();
            setStatus('Arena direset. Mulai lagi dari titik awal.');
        }

        function isObstacle(x, y) {
            return (state.obstacles || []).some(function (obstacle) {
                return Number(obstacle.x) === x && Number(obstacle.y) === y;
            });
        }

        function getNpcAt(x, y) {
            return activeNpcs().find(function (npc) {
                return Number(npc.pos_x) === x && Number(npc.pos_y) === y;
            }) || null;
        }

        function getEnemyAt(x, y) {
            return (state.enemies || []).find(function (enemy) {
                return Number(enemy.x) === x && Number(enemy.y) === y;
            }) || null;
        }

        function getPickupAt(x, y) {
            var shield = (state.pickups.shield || []).find(function (item) {
                return Number(item.x) === x && Number(item.y) === y;
            });

            if (shield) {
                return { type: 'shield', icon: state.pickupIcons.shield };
            }

            var ammo = (state.pickups.ammo || []).find(function (item) {
                return Number(item.x) === x && Number(item.y) === y;
            });

            if (ammo) {
                return { type: 'ammo', icon: state.pickupIcons.ammo };
            }

            return null;
        }

        function resolveNpcAvatar(avatar) {
            if (!avatar || String(avatar).indexOf('ð') !== -1) {
                return state.npcAvatarLookup.N1 || '🧙';
            }

            return state.npcAvatarLookup[avatar] || avatar;
        }

        function resolveEnemyAvatar(avatar) {
            if (!avatar || String(avatar).indexOf('ð') !== -1) {
                return state.enemyAvatarLookup.EN || '👾';
            }

            return state.enemyAvatarLookup[avatar] || avatar;
        }

        function resolvePlayerAvatar(avatar) {
            if (!avatar || String(avatar).indexOf('ð') !== -1) {
                return '\u{1F9D1}\u{200D}\u{1F393}';
            }

            return avatar;
        }

        function correctAnswerIndex(npc) {
            var choices = Array.isArray(npc?.pilihan_jawaban) ? npc.pilihan_jawaban : [];
            var rawValue = npc?.jawaban_benar;
            var rawText = String(rawValue ?? '').trim();
            var letterIndex = rawText.length === 1 ? rawText.toUpperCase().charCodeAt(0) - 65 : -1;

            if (letterIndex >= 0 && letterIndex < choices.length) {
                return letterIndex;
            }

            var choiceIndex = choices.findIndex(function (choice) {
                return String(choice).trim().toLowerCase() === rawText.toLowerCase();
            });
            if (rawText !== '' && choiceIndex >= 0) {
                return choiceIndex;
            }

            var raw = Number(rawValue);

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
        }

        function isCorrectAnswer(selectedIndex, npc) {
            var selected = Number(selectedIndex);
            var choices = Array.isArray(npc?.pilihan_jawaban) ? npc.pilihan_jawaban : [];
            var raw = Number(npc?.jawaban_benar);

            if (!Number.isFinite(selected)) {
                return false;
            }

            if (selected === correctAnswerIndex(npc)) {
                return true;
            }

            return Number.isFinite(raw) && raw >= 1 && raw <= choices.length && selected === raw - 1;
        }

        function normalizeEnemyRoster(roster) {
            return (roster || []).map(function (enemy) {
                var x = Number(enemy.x || 0);
                var y = Number(enemy.y || 0);

                return {
                    x: x,
                    y: y,
                    avatar: resolveEnemyAvatar(enemy.avatar),
                    speed_level: enemy.speed_level || 'normal',
                    intelligence_level: enemy.intelligence_level || 'normal',
                    _lastX: Number(enemy._lastX ?? x),
                    _lastY: Number(enemy._lastY ?? y),
                    _patrolAxis: ['horizontal', 'vertical'].indexOf(enemy._patrolAxis) !== -1 ? enemy._patrolAxis : (Math.random() > 0.5 ? 'horizontal' : 'vertical'),
                    _patrolDirection: Number(enemy._patrolDirection) === -1 ? -1 : 1,
                    _alertedUntil: Number(enemy._alertedUntil || 0),
                    _nextMoveAt: Number(enemy._nextMoveAt || 0)
                };
            });
        }

        function generatePickups() {
            var walkableTiles = [];

            for (var y = 0; y < state.gridSize; y++) {
                for (var x = 0; x < state.gridSize; x++) {
                    var blocked =
                        isObstacle(x, y) ||
                        !!getNpcAt(x, y) ||
                        !!getEnemyAt(x, y) ||
                        (x === 0 && y === 0);

                    if (!blocked) {
                        walkableTiles.push({ x: x, y: y });
                    }
                }
            }

            var shuffled = shuffleDirections(walkableTiles);

            state.pickups = {
                shield: shuffled.splice(0, Math.max(0, state.shieldPickupCount)),
                ammo: shuffled.splice(0, Math.max(0, state.ammoPickupCount))
            };
        }

        function collectPickupAt(x, y) {
            var shieldIndex = (state.pickups.shield || []).findIndex(function (item) {
                return Number(item.x) === x && Number(item.y) === y;
            });

            if (shieldIndex !== -1) {
                var shieldPickup = Object.assign({}, state.pickups.shield.splice(shieldIndex, 1)[0]);
                activateShield();
                playTone('shield');
                setStatus('Tameng aktif ' + state.shieldDurationSeconds + ' detik.');
                schedulePickupRespawn('shield', shieldPickup);
            }

            var ammoIndex = (state.pickups.ammo || []).findIndex(function (item) {
                return Number(item.x) === x && Number(item.y) === y;
            });

            if (ammoIndex !== -1) {
                var ammoPickup = Object.assign({}, state.pickups.ammo.splice(ammoIndex, 1)[0]);
                state.ammo += state.ammoPerPickup;
                playTone('pickup');
                setStatus('Kamu mendapatkan ' + state.ammoPerPickup + ' peluru.');
                tryAutoShoot();
                schedulePickupRespawn('ammo', ammoPickup);
            }
        }

        function schedulePickupRespawn(type, pickup) {
            if (!pickup || ['shield', 'ammo'].indexOf(type) === -1) {
                return;
            }

            var timer = window.setTimeout(function () {
                var list = state.pickups[type] || [];
                var x = Number(pickup.x);
                var y = Number(pickup.y);
                var alreadyExists = list.some(function (item) {
                    return Number(item.x) === x && Number(item.y) === y;
                });

                if (!alreadyExists && !isObstacle(x, y) && !getNpcAt(x, y)) {
                    list.push({ x: x, y: y });
                    state.pickups[type] = list;
                    renderBoard();
                }

                state.pickupRespawnTimers = state.pickupRespawnTimers.filter(function (item) {
                    return item !== timer;
                });
            }, Math.max(3, Number(state.pickupRespawnSeconds || 8)) * 1000);

            state.pickupRespawnTimers.push(timer);
        }

        function clearShieldState() {
            state.shieldActive = false;
            state.shieldSecondsLeft = 0;

            if (state.shieldTimer) {
                window.clearInterval(state.shieldTimer);
                state.shieldTimer = null;
            }
        }

        function flashShotAt(x, y) {
            state.shotFlash = { x: x, y: y, at: Date.now() };
            renderBoard();

            window.setTimeout(function () {
                if (state.shotFlash && state.shotFlash.x === x && state.shotFlash.y === y) {
                    state.shotFlash = null;
                    renderBoard();
                }
            }, 420);
        }

        function activateShield() {
            state.shieldActive = true;
            state.shieldSecondsLeft = state.shieldDurationSeconds;

            if (state.shieldTimer) {
                window.clearInterval(state.shieldTimer);
            }

            state.shieldTimer = window.setInterval(function () {
                state.shieldSecondsLeft = Math.max(0, state.shieldSecondsLeft - 1);

                if (state.shieldSecondsLeft <= 0) {
                    clearShieldState();
                    updateCombatStatus();
                    renderBoard();
                    setStatus('Durasi tameng habis.');
                }
            }, 1000);

            updateCombatStatus();
            renderBoard();
        }

        function findEnemyRespawnTile() {
            var candidates = [];

            for (var y = 0; y < state.gridSize; y++) {
                for (var x = 0; x < state.gridSize; x++) {
                    var blocked =
                        isObstacle(x, y) ||
                        !!getNpcAt(x, y) ||
                        !!getEnemyAt(x, y) ||
                        (state.session.pos_x === x && state.session.pos_y === y) ||
                        (x === 0 && y === 0);

                    if (!blocked) {
                        candidates.push({ x: x, y: y });
                    }
                }
            }

            if (!candidates.length) {
                return null;
            }

            return candidates[Math.floor(Math.random() * candidates.length)];
        }

        function getEnemyMoveInterval(enemy) {
            var baseByDifficulty = { easy: 1900, medium: 1350, hard: 950 };
            var speedFactor = { slow: 1.45, normal: 1.08, fast: 0.82 };
            var base = baseByDifficulty[@json($rpgMap->difficulty ?? 'easy')] || baseByDifficulty.easy;
            var patrolFactor = enemy._alerted ? 1 : 1.24;
            var jitter = 0.94 + Math.random() * 0.12;

            return Math.round(base * (speedFactor[enemy.speed_level || 'normal'] || 1.08) * patrolFactor * jitter);
        }

        function pickEnemyStep(enemy, playerX, playerY) {
            var intelligence = enemy.intelligence_level || 'normal';
            var alerted = isEnemyAlerted(enemy, playerX, playerY, Date.now());

            if (!alerted) {
                return pickEnemyPatrolStep(enemy);
            }

            var randomChance = { low: 0.34, normal: 0.18, high: 0.08 }[intelligence] || 0.18;
            var chaseDirections = buildEnemyChaseDirections(enemy, playerX, playerY, intelligence);
            var wanderDirections = shuffleDirections([[0, 1], [0, -1], [1, 0], [-1, 0]]);
            var directions = [];

            if (Math.random() < randomChance) {
                directions = wanderDirections.concat(chaseDirections);
            } else {
                directions = chaseDirections.concat(wanderDirections);
            }

            for (var index = 0; index < directions.length; index++) {
                var direction = directions[index];
                var nextX = enemy.x + direction[0];
                var nextY = enemy.y + direction[1];
                if (canEnemyMoveTo(nextX, nextY, enemy)) {
                    return { x: nextX, y: nextY };
                }
            }

            return null;
        }

        function isEnemyAlerted(enemy, playerX, playerY, now) {
            var distance = Math.abs(playerX - enemy.x) + Math.abs(playerY - enemy.y);
            var baseRadius = { low: 3, normal: 5, high: 7 }[enemy.intelligence_level || 'normal'] || 5;
            var difficultyBonus = { easy: 0, medium: 1, hard: 1 }[@json($rpgMap->difficulty ?? 'easy')] || 0;
            var speedBonus = enemy.speed_level === 'fast' ? 1 : 0;
            var radius = baseRadius + difficultyBonus + speedBonus;

            if (distance <= radius) {
                var memory = { low: 900, normal: 1600, high: 2400 }[enemy.intelligence_level || 'normal'] || 1600;
                enemy._alertedUntil = now + memory;
                enemy._alerted = true;
                return true;
            }

            enemy._alerted = Number(enemy._alertedUntil || 0) > now;
            return enemy._alerted;
        }

        function buildEnemyChaseDirections(enemy, playerX, playerY, intelligence) {
            var directions = [[1, 0], [-1, 0], [0, 1], [0, -1]]
                .map(function (direction) {
                    var nextX = enemy.x + direction[0];
                    var nextY = enemy.y + direction[1];
                    var score = Math.abs(playerX - nextX) + Math.abs(playerY - nextY);

                    if (nextX === enemy._lastX && nextY === enemy._lastY) {
                        score += 0.65;
                    }

                    return { direction: direction, score: score };
                })
                .sort(function (a, b) {
                    return a.score - b.score;
                })
                .map(function (item) {
                    return item.direction;
                });

            if (intelligence === 'high') {
                return directions;
            }

            return directions.slice(0, 2).concat(shuffleDirections(directions.slice(2)));
        }

        function pickEnemyPatrolStep(enemy) {
            var direction = Number(enemy._patrolDirection) === -1 ? -1 : 1;
            var primary = enemy._patrolAxis === 'vertical'
                ? [[0, direction], [1, 0], [-1, 0], [0, -direction]]
                : [[direction, 0], [0, 1], [0, -1], [-direction, 0]];
            var directions = Math.random() < 0.18
                ? shuffleDirections(primary.slice(0, 3)).concat(primary.slice(3))
                : primary;

            for (var index = 0; index < directions.length; index++) {
                var step = directions[index];
                var nextX = enemy.x + step[0];
                var nextY = enemy.y + step[1];

                if (canEnemyMoveTo(nextX, nextY, enemy)) {
                    var reversed = enemy._patrolAxis === 'vertical'
                        ? step[1] === -direction
                        : step[0] === -direction;

                    if (reversed) {
                        enemy._patrolDirection = -direction;
                    }

                    return { x: nextX, y: nextY };
                }
            }

            enemy._patrolDirection = -direction;
            return null;
        }

        function canEnemyMoveTo(x, y, currentEnemy) {
            if (x < 0 || x >= state.gridSize || y < 0 || y >= state.gridSize) {
                return false;
            }

            if (isObstacle(x, y) || getNpcAt(x, y)) {
                return false;
            }

            return !state.enemies.some(function (enemy) {
                return enemy !== currentEnemy && enemy.x === x && enemy.y === y;
            });
        }

        function shuffleDirections(directions) {
            return directions.slice().sort(function () {
                return Math.random() - 0.5;
            });
        }

        function isNpcAnswered(npcId) {
            return state.session.answered_npcs.indexOf(npcId) !== -1;
        }

        function setStatus(message) {
            elements.status.textContent = message;
        }

        function updateJoystick(point) {
            if (!elements.joystickZone || !elements.joystickThumb) {
                return;
            }

            var rect = elements.joystickZone.getBoundingClientRect();
            var centerX = rect.left + rect.width / 2;
            var centerY = rect.top + rect.height / 2;
            var dx = (point.clientX || point.pageX) - centerX;
            var dy = (point.clientY || point.pageY) - centerY;
            var distance = Math.sqrt(dx * dx + dy * dy);
            var maxDistance = rect.width * 0.3;

            if (distance > maxDistance) {
                dx = (dx / distance) * maxDistance;
                dy = (dy / distance) * maxDistance;
            }

            state.joystickX = dx;
            state.joystickY = dy;
            elements.joystickThumb.classList.add('is-active');
            elements.joystickThumb.style.transform = 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px))';

            if (!state.joystickMoveTimer && distance > 16) {
                executeJoystickMove(dx, dy);
                state.joystickMoveTimer = window.setInterval(function () {
                    if (!state.joystickActive) {
                        return;
                    }

                    executeJoystickMove(state.joystickX, state.joystickY);
                }, 170);
            }
        }

        function executeJoystickMove(dx, dy) {
            if (Math.max(Math.abs(dx), Math.abs(dy)) < 12) {
                return;
            }

            if (Math.abs(dx) > Math.abs(dy)) {
                performDirectionalAction(dx > 0 ? 1 : -1, 0);
                return;
            }

            performDirectionalAction(0, dy > 0 ? -1 : 1);
        }

        function resetJoystick() {
            state.joystickActive = false;
            state.joystickX = 0;
            state.joystickY = 0;

            if (state.joystickMoveTimer) {
                window.clearInterval(state.joystickMoveTimer);
                state.joystickMoveTimer = null;
            }

            if (elements.joystickThumb) {
                elements.joystickThumb.classList.remove('is-active');
                elements.joystickThumb.style.transform = 'translate(-50%, -50%)';
            }
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        init();
    })();
</script>
@endpush

