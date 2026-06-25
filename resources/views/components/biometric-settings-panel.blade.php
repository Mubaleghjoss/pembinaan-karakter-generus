@props([
    'backUrl',
    'backLabel' => 'Kembali',
    'title' => 'Biometrik',
    'subtitle' => 'Kelola perangkat login.',
    'credentials',
    'validCredentialCount' => 0,
    'legacyCredentialCount' => 0,
    'webauthnEnvironment' => null,
    'registerOptionsUrl',
    'registerUrl',
    'destroyBaseUrl',
    'registerSuccessMessage' => 'Perangkat berhasil didaftarkan.',
    'registerErrorMessage' => 'Gagal mendaftarkan perangkat.',
    'deleteConfirmText' => 'Perangkat ini tidak bisa dipakai login biometrik lagi.',
    'deleteSuccessText' => 'Perangkat biometrik berhasil dihapus.',
])

<div class="py-6">
    <div class="mx-auto max-w-2xl px-4" data-biometric-settings>
        <div class="pkg-page-header mb-6">
            <div class="flex items-start gap-3">
                <a href="{{ $backUrl }}" class="mt-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="{{ $backLabel }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="pkg-page-heading">{{ $title }}</h1>
                    <p class="pkg-page-subheading">{{ $subtitle }}</p>
                </div>
            </div>
        </div>

        <div class="pkg-card-soft mb-4 flex items-center justify-between gap-3 px-4 py-3">
            <div class="text-sm text-slate-600 dark:text-slate-300">
                <span class="font-semibold text-slate-900 dark:text-white">{{ $validCredentialCount }}</span> aktif
                <span class="mx-2 text-slate-300 dark:text-slate-600">/</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ $credentials->count() }}</span> total
            </div>
            @if($legacyCredentialCount > 0)
                <span class="pkg-status-badge pkg-status-warning">{{ $legacyCredentialCount }} legacy</span>
            @else
                <span class="pkg-status-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Siap dipakai</span>
            @endif
        </div>

        @if($legacyCredentialCount > 0)
            <div class="pkg-card-soft mb-4 border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                Hapus {{ $legacyCredentialCount }} perangkat lama, lalu daftar ulang di perangkat ini.
            </div>
        @endif

        @include('components.biometric-environment-alert', ['webauthnEnvironment' => $webauthnEnvironment])

        <div class="pkg-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Perangkat</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">Tap untuk hapus perangkat yang tidak dipakai.</span>
            </div>

            @if($credentials->count() > 0)
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($credentials as $credential)
                        @php($isLegacy = blank($credential->credential_public_key))
                        <div class="flex items-center justify-between gap-4 px-5 py-4" id="cred-{{ $credential->id }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $credential->device_name ?: 'Perangkat' }}</p>
                                    <span class="pkg-status-badge {{ $isLegacy ? 'pkg-status-warning' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                        {{ $isLegacy ? 'Legacy' : 'Aktif' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Ditambah {{ $credential->created_at->diffForHumans() }}
                                    @if($credential->last_used_at)
                                        | Dipakai {{ $credential->last_used_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <button
                                type="button"
                                class="btn-danger !px-3 !py-2 text-xs"
                                data-biometric-delete
                                data-credential-id="{{ $credential->id }}"
                                data-delete-url="{{ $destroyBaseUrl }}/{{ $credential->id }}"
                                data-row-selector="#cred-{{ $credential->id }}"
                                data-confirm-text="{{ $deleteConfirmText }}"
                                data-success-text="{{ $deleteSuccessText }}"
                            >
                                Hapus
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pkg-empty-state px-5 py-10">
                    <div class="pkg-empty-icon">HP</div>
                    <h3 class="pkg-empty-title">Belum ada perangkat</h3>
                    <p class="pkg-empty-copy">Tambahkan biometrik di perangkat yang sedang dipakai.</p>
                </div>
            @endif
        </div>

        <div class="mt-5 hidden" data-biometric-unsupported>
            <div class="pkg-card-soft border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                Browser atau perangkat ini belum mendukung biometrik.
            </div>
        </div>

        <div class="mt-5" data-biometric-add-section>
            <button
                type="button"
                id="addBiometricBtn"
                class="btn-primary w-full !justify-center"
                data-biometric-add
                data-options-url="{{ $registerOptionsUrl }}"
                data-register-url="{{ $registerUrl }}"
                data-success-message="{{ $registerSuccessMessage }}"
                data-error-message="{{ $registerErrorMessage }}"
            >
                Tambah perangkat
            </button>
        </div>
    </div>
</div>
