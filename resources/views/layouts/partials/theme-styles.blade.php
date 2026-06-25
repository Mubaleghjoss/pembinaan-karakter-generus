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
