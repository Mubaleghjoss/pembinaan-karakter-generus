@extends('layouts.app')

@section('title', 'Pengaturan Website')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8" x-data="settingsTabs('{{ $tab }}')">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Pengaturan Website</h1>
            <p class="pkg-page-subheading">Kelola tampilan, identitas, popup, dan konfigurasi inti website.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @include('settings.partials.admin-tabs', ['settingsTabClient' => true])

    <div x-show="activeSettingsTab === 'general'" x-cloak>
        @include('settings.partials.general')
    </div>
    <div x-show="activeSettingsTab === 'id_card'" x-cloak>
        @include('settings.partials.id-card')
    </div>
    <div x-show="activeSettingsTab === 'theme'" x-cloak>
        @include('settings.partials.theme')
    </div>
    <div x-show="activeSettingsTab === 'kelas'" x-cloak>
        @include('settings.partials.kelas')
    </div>
    <div x-show="activeSettingsTab === 'permissions'" x-cloak>
        @include('settings.partials.permissions')
    </div>
    <div x-show="activeSettingsTab === 'share_info'" x-cloak>
        @include('settings.partials.share-info')
    </div>
    <div x-show="activeSettingsTab === 'face_attendance'" x-cloak>
        @include('settings.partials.face-attendance')
    </div>
    <div x-show="activeSettingsTab === 'popup'" x-cloak>
        @include('settings.partials.popup')
    </div>
    <div x-show="activeSettingsTab === 'registration'" x-cloak>
        @include('settings.partials.registration-access')
    </div>
</div>

@push('scripts')
<script>
    function settingsTabs(initialTab) {
        const validTabs = ['general', 'id_card', 'theme', 'kelas', 'permissions', 'share_info', 'face_attendance', 'popup', 'registration'];

        return {
            activeSettingsTab: validTabs.includes(initialTab) ? initialTab : 'general',

            switchSettingsTab(tab) {
                if (!validTabs.includes(tab)) {
                    return;
                }

                this.activeSettingsTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.pushState({ settingsTab: tab }, '', url);
            },

            init() {
                window.addEventListener('popstate', () => {
                    const tab = new URLSearchParams(window.location.search).get('tab') || 'general';
                    this.activeSettingsTab = validTabs.includes(tab) ? tab : 'general';
                });
            }
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const themePreview = document.getElementById('theme-live-preview');
        const idCardPreview = document.getElementById('id-card-live-preview');
        const idCardTitle = document.getElementById('id-card-preview-title');
        const idCardSubtitle = document.getElementById('id-card-preview-subtitle');
        const idCardFooter = document.getElementById('id-card-preview-footer');
        const previewModeButtons = document.querySelectorAll('[data-theme-preview-mode]');
        const themePresetButtons = document.querySelectorAll('[data-theme-preset]');
        const colorInputs = document.querySelectorAll('input[type="color"]');
        const cssVarMap = {
            primary_color: '--color-primary',
            secondary_color: '--color-secondary',
            accent_color: '--color-accent',
            success_color: '--color-success',
            warning_color: '--color-warning',
            danger_color: '--color-danger',
            dark_color: '--color-dark',
            light_color: '--color-light',
            sidebar_color: '--color-sidebar',
            topbar_color: '--color-topbar',
        };
        const themePresets = {
            'emerald-ocean': {
                primary_color: '#0F766E',
                secondary_color: '#0369A1',
                accent_color: '#F59E0B',
                success_color: '#10B981',
                warning_color: '#F59E0B',
                danger_color: '#EF4444',
                dark_color: '#020617',
                light_color: '#F8FAFC',
                sidebar_color: '#FFFFFF',
                topbar_color: '#F8FAFC',
            },
            'sunrise-warm': {
                primary_color: '#C2410C',
                secondary_color: '#EA580C',
                accent_color: '#FACC15',
                success_color: '#16A34A',
                warning_color: '#F59E0B',
                danger_color: '#DC2626',
                dark_color: '#1C1917',
                light_color: '#FFF7ED',
                sidebar_color: '#FFFAF5',
                topbar_color: '#FFEDD5',
            },
            'midnight-gold': {
                primary_color: '#1D4ED8',
                secondary_color: '#1E3A8A',
                accent_color: '#D4A017',
                success_color: '#059669',
                warning_color: '#FBBF24',
                danger_color: '#EF4444',
                dark_color: '#020617',
                light_color: '#F8FAFC',
                sidebar_color: '#E0F2FE',
                topbar_color: '#DBEAFE',
            },
            'clean-slate': {
                primary_color: '#334155',
                secondary_color: '#475569',
                accent_color: '#0EA5E9',
                success_color: '#10B981',
                warning_color: '#F59E0B',
                danger_color: '#EF4444',
                dark_color: '#111827',
                light_color: '#F8FAFC',
                sidebar_color: '#FFFFFF',
                topbar_color: '#F1F5F9',
            },
        };

        const updateHexDisplay = (input) => {
            const colorField = input.closest('div');
            const hexDisplay = colorField?.parentElement?.querySelector('.hex-value');
            if (hexDisplay) {
                hexDisplay.textContent = input.value;
            }
        };

        const updateColorSwatch = (input) => {
            const previewId = input.dataset.preview;
            if (!previewId) {
                return;
            }

            const swatch = input.closest('form')?.querySelector(`[id="${previewId}"]`) || document.getElementById(previewId);
            if (swatch) {
                swatch.style.backgroundColor = input.value;
            }
        };

        const updateThemePreviewVar = (input) => {
            const scopedThemePreview = input.closest('form')?.querySelector('#theme-live-preview');
            if (!scopedThemePreview) {
                return;
            }

            const cssVar = cssVarMap[input.name];
            if (cssVar) {
                scopedThemePreview.style.setProperty(cssVar, input.value);
            }
        };

        const refreshColorInput = (input) => {
            updateColorSwatch(input);
            updateHexDisplay(input);
            updateThemePreviewVar(input);

            if (idCardPreview && input.name === 'card_color') {
                idCardPreview.style.background = `linear-gradient(135deg, ${input.value} 0%, ${input.value}99 100%)`;
            }
        };

        const applyThemePreviewMode = (mode) => {
            if (!themePreview) {
                return;
            }

            themePreview.classList.toggle('dark', mode === 'dark');

            previewModeButtons.forEach((button) => {
                const isActive = button.dataset.themePreviewMode === mode;
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-secondary', !isActive);
            });
        };

        applyThemePreviewMode('light');

        colorInputs.forEach((input) => {
            input.addEventListener('input', (event) => {
                refreshColorInput(event.target);
            });
        });

        const bindTextPreview = (inputId, targetEl) => {
            const input = document.getElementById(inputId);
            if (!input || !targetEl) {
                return;
            }

            input.addEventListener('input', () => {
                targetEl.textContent = input.value.trim() || input.placeholder;
            });
        };

        bindTextPreview('card_title', idCardTitle);
        bindTextPreview('card_subtitle', idCardSubtitle);
        bindTextPreview('card_footer_text', idCardFooter);

        previewModeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                applyThemePreviewMode(button.dataset.themePreviewMode);
            });
        });

        themePresetButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const preset = themePresets[button.dataset.themePreset];
                if (!preset) {
                    return;
                }

                Object.entries(preset).forEach(([name, value]) => {
                    const input = button.closest('form')?.querySelector(`input[name="${name}"]`);
                    if (!input) {
                        return;
                    }

                    input.value = value;
                    refreshColorInput(input);
                });
            });
        });
    });
</script>
@endpush
@endsection
