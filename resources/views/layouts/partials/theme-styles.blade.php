<style>
    :root {
        @isset($currentTheme)
            @foreach($currentTheme->getCssVariables() as $key => $value)
        {{ $key }}: {{ $value }};
            @endforeach
        @endisset
    }

    [x-cloak] {
        display: none !important;
    }

    :root {
        --pkg-brand: var(--color-primary, #0f766e);
        --pkg-brand-secondary: var(--color-secondary, #0369a1);
        --pkg-accent: var(--color-accent, #f59e0b);
        --pkg-bg-top: color-mix(in srgb, var(--color-light, #f8fafc) 80%, #ffffff);
        --pkg-bg-base: var(--color-light, #f8fafc);
        --pkg-bg-bottom: color-mix(in srgb, var(--color-light, #f8fafc) 92%, #ffffff);
        --pkg-shell: rgba(255, 255, 255, 0.84);
        --pkg-shell-strong: rgba(255, 255, 255, 0.92);
        --pkg-surface: rgba(255, 255, 255, 0.96);
        --pkg-surface-muted: color-mix(in srgb, var(--color-light, #f8fafc) 92%, #e2e8f0);
        --pkg-surface-soft: rgba(240, 253, 250, 0.82);
        --pkg-border: rgba(148, 163, 184, 0.24);
        --pkg-border-strong: rgba(148, 163, 184, 0.36);
        --pkg-text: #0f172a;
        --pkg-text-muted: #475569;
        --pkg-heading: #020617;
        --pkg-shadow-soft: 0 18px 46px rgba(15, 23, 42, 0.08);
        --pkg-shadow-md: 0 24px 64px rgba(15, 23, 42, 0.12);
        --pkg-shadow-lg: 0 32px 90px rgba(15, 23, 42, 0.16);
        --pkg-sidebar-surface: color-mix(in srgb, var(--color-sidebar, #ffffff) 88%, #ffffff);
        --pkg-topbar-surface: color-mix(in srgb, var(--color-topbar, #ffffff) 90%, #ffffff);
        --pkg-public-nav-bg: color-mix(in srgb, var(--pkg-shell-strong) 82%, rgba(255, 255, 255, 0.88));
        --pkg-public-nav-text: #0f172a;
        --pkg-public-nav-muted: #475569;
        --pkg-home-hero-bg:
            radial-gradient(circle at top left, color-mix(in srgb, var(--pkg-brand) 15%, transparent), transparent 26%),
            radial-gradient(circle at 85% 12%, color-mix(in srgb, var(--pkg-brand-secondary) 12%, transparent), transparent 24%),
            linear-gradient(135deg, color-mix(in srgb, var(--pkg-shell-strong) 94%, #ffffff), color-mix(in srgb, var(--pkg-surface) 96%, #ffffff));
        --pkg-home-hero-text: #0f172a;
        --pkg-home-hero-muted: #334155;
        --pkg-home-hero-badge-bg: rgba(255, 255, 255, 0.78);
        --pkg-home-hero-stat-bg: rgba(255, 255, 255, 0.72);
        --pkg-home-hero-stat-border: rgba(148, 163, 184, 0.22);
        --pkg-motion-fast: 160ms;
        --pkg-motion-base: 240ms;
        --pkg-motion-slow: 320ms;
        --pkg-motion-enter: cubic-bezier(0.2, 0, 0, 1);
        --pkg-motion-emphasized: cubic-bezier(0.05, 0.7, 0.1, 1);
        --pkg-reveal-distance-y: 18px;
        --pkg-reveal-distance-x: 20px;
        --pkg-reveal-scale: 0.985;
    }

    html {
        color-scheme: light;
    }

    html.dark {
        color-scheme: dark;
        --pkg-bg-top: color-mix(in srgb, var(--color-dark, #020617) 92%, #000000);
        --pkg-bg-base: color-mix(in srgb, var(--color-dark, #020617) 84%, #0f172a);
        --pkg-bg-bottom: color-mix(in srgb, var(--color-dark, #020617) 74%, #111827);
        --pkg-shell: rgba(2, 6, 23, 0.78);
        --pkg-shell-strong: rgba(15, 23, 42, 0.9);
        --pkg-surface: color-mix(in srgb, var(--color-dark, #020617) 86%, rgba(15, 23, 42, 0.94));
        --pkg-surface-muted: color-mix(in srgb, var(--color-dark, #020617) 90%, #0b1120);
        --pkg-surface-soft: rgba(15, 23, 42, 0.86);
        --pkg-border: rgba(71, 85, 105, 0.5);
        --pkg-border-strong: rgba(100, 116, 139, 0.65);
        --pkg-text: #e2e8f0;
        --pkg-text-muted: #94a3b8;
        --pkg-heading: #f8fafc;
        --pkg-shadow-soft: 0 22px 52px rgba(2, 6, 23, 0.28);
        --pkg-shadow-md: 0 26px 72px rgba(2, 6, 23, 0.4);
        --pkg-shadow-lg: 0 34px 96px rgba(0, 0, 0, 0.5);
        --pkg-sidebar-surface: color-mix(in srgb, var(--color-sidebar, #ffffff) 18%, var(--color-dark, #020617));
        --pkg-topbar-surface: color-mix(in srgb, var(--color-topbar, #ffffff) 16%, var(--color-dark, #020617));
        --pkg-public-nav-bg: color-mix(in srgb, var(--color-dark, #020617) 84%, rgba(15, 23, 42, 0.88));
        --pkg-public-nav-text: #f8fafc;
        --pkg-public-nav-muted: #cbd5e1;
        --pkg-home-hero-bg:
            radial-gradient(circle at top left, color-mix(in srgb, var(--pkg-brand) 26%, transparent), transparent 26%),
            radial-gradient(circle at 85% 12%, color-mix(in srgb, var(--pkg-brand-secondary) 20%, transparent), transparent 24%),
            linear-gradient(135deg, color-mix(in srgb, var(--pkg-shell-strong) 92%, rgba(15, 23, 42, 0.96)), color-mix(in srgb, var(--pkg-surface) 94%, rgba(2, 6, 23, 0.94)));
        --pkg-home-hero-text: #f8fafc;
        --pkg-home-hero-muted: #dbeafe;
        --pkg-home-hero-badge-bg: rgba(15, 23, 42, 0.44);
        --pkg-home-hero-stat-bg: rgba(15, 23, 42, 0.4);
        --pkg-home-hero-stat-border: rgba(255, 255, 255, 0.12);
    }

    .theme-preview-scope {
        --pkg-brand: var(--color-primary, #0f766e);
        --pkg-brand-secondary: var(--color-secondary, #0369a1);
        --pkg-accent: var(--color-accent, #f59e0b);
        --pkg-bg-top: color-mix(in srgb, var(--color-light, #f8fafc) 80%, #ffffff);
        --pkg-bg-base: var(--color-light, #f8fafc);
        --pkg-bg-bottom: color-mix(in srgb, var(--color-light, #f8fafc) 92%, #ffffff);
        --pkg-shell: rgba(255, 255, 255, 0.84);
        --pkg-shell-strong: rgba(255, 255, 255, 0.92);
        --pkg-surface: rgba(255, 255, 255, 0.96);
        --pkg-surface-muted: color-mix(in srgb, var(--color-light, #f8fafc) 92%, #e2e8f0);
        --pkg-surface-soft: rgba(240, 253, 250, 0.82);
        --pkg-border: rgba(148, 163, 184, 0.24);
        --pkg-border-strong: rgba(148, 163, 184, 0.36);
        --pkg-text: #0f172a;
        --pkg-text-muted: #475569;
        --pkg-heading: #020617;
        --pkg-shadow-soft: 0 18px 46px rgba(15, 23, 42, 0.08);
        --pkg-shadow-md: 0 24px 64px rgba(15, 23, 42, 0.12);
        --pkg-shadow-lg: 0 32px 90px rgba(15, 23, 42, 0.16);
        --pkg-sidebar-surface: color-mix(in srgb, var(--color-sidebar, #ffffff) 88%, #ffffff);
        --pkg-topbar-surface: color-mix(in srgb, var(--color-topbar, #ffffff) 90%, #ffffff);
    }

    .theme-preview-scope.dark {
        --pkg-bg-top: color-mix(in srgb, var(--color-dark, #020617) 92%, #000000);
        --pkg-bg-base: color-mix(in srgb, var(--color-dark, #020617) 84%, #0f172a);
        --pkg-bg-bottom: color-mix(in srgb, var(--color-dark, #020617) 74%, #111827);
        --pkg-shell: rgba(2, 6, 23, 0.78);
        --pkg-shell-strong: rgba(15, 23, 42, 0.9);
        --pkg-surface: color-mix(in srgb, var(--color-dark, #020617) 86%, rgba(15, 23, 42, 0.94));
        --pkg-surface-muted: color-mix(in srgb, var(--color-dark, #020617) 90%, #0b1120);
        --pkg-surface-soft: rgba(15, 23, 42, 0.86);
        --pkg-border: rgba(71, 85, 105, 0.5);
        --pkg-border-strong: rgba(100, 116, 139, 0.65);
        --pkg-text: #e2e8f0;
        --pkg-text-muted: #94a3b8;
        --pkg-heading: #f8fafc;
        --pkg-shadow-soft: 0 22px 52px rgba(2, 6, 23, 0.28);
        --pkg-shadow-md: 0 26px 72px rgba(2, 6, 23, 0.4);
        --pkg-shadow-lg: 0 34px 96px rgba(0, 0, 0, 0.5);
        --pkg-sidebar-surface: color-mix(in srgb, var(--color-sidebar, #ffffff) 18%, var(--color-dark, #020617));
        --pkg-topbar-surface: color-mix(in srgb, var(--color-topbar, #ffffff) 16%, var(--color-dark, #020617));
    }

    body {
        font-family: 'Inter', sans-serif;
        color: var(--pkg-text);
        background:
            radial-gradient(circle at top left, rgba(13, 148, 136, 0.14), transparent 24%),
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.1), transparent 20%),
            linear-gradient(180deg, var(--pkg-bg-top) 0%, var(--pkg-bg-base) 42%, var(--pkg-bg-bottom) 100%);
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .pkg-shell-card {
        background: var(--pkg-shell);
        backdrop-filter: blur(16px);
        border-color: var(--pkg-border) !important;
        box-shadow: var(--pkg-shadow-soft);
    }

    .pkg-sidebar {
        background: var(--pkg-sidebar-surface);
        backdrop-filter: blur(16px);
        border-color: var(--pkg-border) !important;
        box-shadow: var(--pkg-shadow-soft);
    }

    .pkg-topbar {
        background: var(--pkg-topbar-surface);
        backdrop-filter: blur(16px);
        border-color: var(--pkg-border) !important;
        box-shadow: var(--pkg-shadow-soft);
    }

    .pkg-surface {
        background: var(--pkg-surface);
        border: 1px solid var(--pkg-border);
        box-shadow: var(--pkg-shadow-soft);
    }

    .pkg-surface-muted {
        background: var(--pkg-surface-muted);
        border: 1px solid var(--pkg-border);
    }

    .pkg-page-title {
        color: var(--pkg-heading);
    }

    .pkg-page-copy {
        color: var(--pkg-text-muted);
    }

    .pkg-link-accent {
        color: var(--pkg-brand);
        transition: color 0.2s ease;
    }

    .pkg-link-accent:hover {
        color: var(--pkg-brand-secondary);
    }

    .pkg-btn-primary {
        background: linear-gradient(135deg, var(--pkg-brand) 0%, var(--pkg-brand-secondary) 100%);
        color: #ffffff;
        box-shadow: 0 16px 36px rgba(13, 148, 136, 0.2);
        transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
    }

    .pkg-btn-primary:hover {
        filter: brightness(1.04);
        transform: translateY(-1px);
    }

    .pkg-btn-secondary {
        background: var(--pkg-surface);
        color: var(--pkg-text);
        border: 1px solid var(--pkg-border-strong);
        box-shadow: var(--pkg-shadow-soft);
        transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    .pkg-btn-secondary:hover {
        background: var(--pkg-surface-muted);
        border-color: var(--pkg-brand);
        transform: translateY(-1px);
    }

    .pkg-chip {
        background: var(--pkg-surface-soft);
        border: 1px solid var(--pkg-border);
        color: var(--pkg-text-muted);
    }

    .pkg-prose,
    .pkg-prose p,
    .pkg-prose li {
        color: var(--pkg-text-muted);
    }

    .pkg-prose h1,
    .pkg-prose h2,
    .pkg-prose h3,
    .pkg-prose h4,
    .pkg-prose strong {
        color: var(--pkg-heading);
    }

    .pkg-prose a {
        color: var(--pkg-brand);
    }

    .pkg-prose blockquote {
        border-left: 4px solid var(--pkg-brand);
        color: var(--pkg-text);
    }

    .pkg-soft-ring {
        border: 1px solid var(--pkg-border);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    .pkg-panel-gradient {
        background:
            radial-gradient(circle at top left, rgba(13, 148, 136, 0.16), transparent 34%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.14), transparent 30%),
            linear-gradient(135deg, var(--pkg-shell-strong), var(--pkg-surface));
    }

    .pkg-public-nav-theme {
        background: var(--pkg-public-nav-bg);
        color: var(--pkg-public-nav-text);
        border: 1px solid color-mix(in srgb, var(--pkg-border) 88%, transparent);
        box-shadow: var(--pkg-shadow-soft);
    }

    .pkg-public-nav-link {
        color: var(--pkg-public-nav-text);
    }

    .pkg-public-nav-link:hover {
        color: var(--pkg-brand);
        background: color-mix(in srgb, var(--pkg-brand, #0f766e) 10%, transparent);
    }

    .pkg-public-nav-link.is-active {
        color: var(--pkg-brand);
        background: color-mix(in srgb, var(--pkg-brand, #0f766e) 14%, transparent);
        font-weight: 700;
    }

    .pkg-public-nav-sep {
        width: 1px;
        height: 1.5rem;
        background: color-mix(in srgb, var(--pkg-border) 80%, transparent);
    }

    .pkg-public-nav-login {
        color: #fff;
        background: linear-gradient(135deg, var(--pkg-brand, #0f766e), color-mix(in srgb, var(--pkg-brand, #0f766e) 60%, #0ea5e9));
        box-shadow: 0 8px 20px color-mix(in srgb, var(--pkg-brand, #0f766e) 30%, transparent);
    }

    .pkg-public-nav-login:hover {
        filter: brightness(1.06);
        transform: translateY(-1px);
    }

    .pkg-public-login-menu {
        background: var(--pkg-surface, #fff);
        border: 1px solid color-mix(in srgb, var(--pkg-border) 88%, transparent);
        z-index: 60;
    }

    .pkg-public-login-item {
        color: var(--pkg-public-nav-text);
    }

    .pkg-public-login-item:hover {
        background: color-mix(in srgb, var(--pkg-brand, #0f766e) 12%, transparent);
        color: var(--pkg-brand);
    }

    .pkg-theme-toggle:hover {
        background: color-mix(in srgb, var(--pkg-brand, #0f766e) 12%, transparent);
        color: var(--pkg-brand);
    }

    .pkg-public-nav-copy {
        color: var(--pkg-public-nav-muted);
    }

    .pkg-home-hero {
        background: var(--pkg-home-hero-bg);
        color: var(--pkg-home-hero-text);
    }

    .pkg-home-hero h1,
    .pkg-home-hero .pkg-home-hero-title {
        color: var(--pkg-home-hero-text);
    }

    .pkg-home-hero-badge {
        background: var(--pkg-home-hero-badge-bg);
        color: var(--pkg-home-hero-text);
        border-color: color-mix(in srgb, var(--pkg-home-hero-stat-border) 88%, transparent);
    }

    .pkg-home-hero-copy {
        color: var(--pkg-home-hero-muted);
    }

    .pkg-home-hero-stat {
        background: var(--pkg-home-hero-stat-bg);
        border-color: var(--pkg-home-hero-stat-border);
        color: var(--pkg-home-hero-text);
    }

    .pkg-home-hero-stat-label {
        color: color-mix(in srgb, var(--pkg-home-hero-muted) 86%, transparent);
    }

    .pkg-home-hero-stat-copy {
        color: var(--pkg-home-hero-muted);
    }

    .pkg-sidebar .nav-item,
    .pkg-topbar button,
    .pkg-topbar a {
        transition:
            background-color 0.24s ease,
            color 0.24s ease,
            border-color 0.24s ease,
            transform 0.24s ease,
            box-shadow 0.24s ease;
    }

    .pkg-sidebar .nav-item {
        position: relative;
        border: 1px solid transparent;
    }

    .pkg-sidebar .nav-item:hover {
        transform: translateX(2px);
        border-color: color-mix(in srgb, var(--pkg-border, rgba(148, 163, 184, 0.24)) 92%, transparent);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
    }

    .pkg-sidebar .nav-item.bg-blue-50,
    .pkg-sidebar .nav-item.dark\:bg-blue-900\/30 {
        border-color: color-mix(in srgb, var(--pkg-brand, #0f766e) 28%, transparent);
        box-shadow: 0 16px 32px rgba(13, 148, 136, 0.12);
    }

    .pkg-topbar button:hover,
    .pkg-topbar a:hover {
        transform: translateY(-1px);
    }

    main {
        position: relative;
    }

    main::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 12rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), transparent);
        pointer-events: none;
    }

    .pkg-sidebar .bg-blue-50 {
        background-color: rgba(13, 148, 136, 0.08) !important;
    }

    .dark .pkg-sidebar .dark\:bg-blue-900\/30 {
        background-color: rgba(6, 78, 59, 0.48) !important;
    }

    .pkg-sidebar .text-blue-700 {
        color: #0f766e !important;
    }

    .dark .pkg-sidebar .dark\:text-blue-300 {
        color: #6ee7b7 !important;
    }

    .pkg-sidebar .bg-blue-600 {
        background-color: #0f766e !important;
    }
</style>
