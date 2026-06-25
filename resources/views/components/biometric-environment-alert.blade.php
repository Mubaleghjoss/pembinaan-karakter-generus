@php
    $webauthnEnvironment = $webauthnEnvironment ?? null;
    $warnings = $webauthnEnvironment['warnings'] ?? [];
@endphp

@if(!empty($warnings))
    <div class="pkg-card-soft mb-6 border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        <div class="flex items-start gap-3">
            <span class="pkg-status-badge pkg-status-warning shrink-0">Periksa host</span>
            <div class="space-y-1">
                <p>{{ $warnings[0] }}</p>
                @if(count($warnings) > 1)
                    <p class="text-xs text-amber-700/90 dark:text-amber-200/90">Ada {{ count($warnings) }} catatan environment yang perlu dicek.</p>
                @endif
                <p class="text-xs text-amber-700/90 dark:text-amber-200/90">
                    Host: <strong>{{ $webauthnEnvironment['current_origin'] ?? '-' }}</strong>
                    @if(!empty($webauthnEnvironment['app_url']))
                        | APP_URL: <strong>{{ $webauthnEnvironment['app_url'] }}</strong>
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif
