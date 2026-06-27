{{-- Biometric Prompt Component --}}
{{-- Works for siswa, admin/pamong, and ortu --}}

@php
    $popupConfig = \App\Support\PopupManager::config('biometric_prompt');
    $biometricPopupEnabled = $popupConfig['enabled'];
    $biometricPopupRequired = $popupConfig['required'];

    $biometricUser = $biometricUser ?? null;
    $biometricUserType = $biometricUserType ?? null;
    $biometricRegisterUrl = $biometricRegisterUrl ?? null;
    $biometricDismissUrl = $biometricDismissUrl ?? null;
    $biometricSettingsUrl = $biometricSettingsUrl ?? null;
    $biometricSettingsRouteName = $biometricSettingsRouteName ?? null;
    $biometricStatus = $biometricStatus ?? 'inactive';
    $biometricHasCredential = (bool) ($biometricHasCredential ?? false) || $biometricStatus === \App\Support\BiometricStatus::ACTIVE;
    $biometricLegacyCredentialCount = (int) ($biometricLegacyCredentialCount ?? 0);

    $isLegacyBiometricState = $biometricStatus === 'legacy';
    $promptTitle = $isLegacyBiometricState ? 'Daftarkan Ulang Biometrik' : 'Aktifkan Login Biometrik';
    $promptCopy = $isLegacyBiometricState
        ? 'Perangkat biometrik lama terdeteksi. Supaya login tetap aman, hapus perangkat lama lalu daftarkan ulang biometrik di browser atau HP yang sedang dipakai.'
        : 'Gunakan sidik jari atau face unlock supaya proses login lebih cepat dan tidak perlu mengetik ulang akun setiap saat.';
    $primaryActionLabel = $isLegacyBiometricState ? 'Daftarkan Ulang Sekarang' : 'Aktifkan Sekarang';
@endphp

@if($biometricPopupEnabled && $biometricUser && !($profileAssignmentPending ?? false))
    @php
        $dismissedForSession = !$biometricPopupRequired && session('biometric_prompt_dismissed');
        $skipOnCurrentPage = $biometricPopupRequired
            && $biometricSettingsRouteName
            && request()->routeIs($biometricSettingsRouteName);
    @endphp

    @if(!$biometricHasCredential && !$dismissedForSession && !$skipOnCurrentPage)
        <div
            id="biometricPrompt"
            class="pkg-biometric-prompt"
            data-biometric-prompt
            data-register-options-url="{{ $biometricRegisterUrl }}"
            data-register-url="{{ str_replace('register-options', 'register', $biometricRegisterUrl ?? '') }}"
            data-dismiss-url="{{ $biometricDismissUrl }}"
            data-success-message="Login biometrik sudah aktif. Selanjutnya kamu bisa masuk lebih cepat."
            aria-hidden="true"
        >
            <div class="pkg-modal pkg-biometric-prompt__dialog">
                <div class="pkg-biometric-prompt__icon">
                    <svg fill="none" stroke="white" viewBox="0 0 24 24" class="h-8 w-8">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                    </svg>
                </div>
                <h2 class="pkg-biometric-prompt__title">{{ $promptTitle }}</h2>
                <p class="pkg-biometric-prompt__copy">
                    {{ $promptCopy }}
                </p>

                @if($isLegacyBiometricState && $biometricLegacyCredentialCount > 0)
                    <div class="pkg-biometric-prompt__note">
                        Ada {{ $biometricLegacyCredentialCount }} perangkat biometrik format lama yang tidak lagi dihitung aktif.
                    </div>
                @endif

                @if($biometricPopupRequired)
                    <div class="pkg-biometric-prompt__note">
                        Pengaturan ini sedang ditandai <strong>wajib</strong>. Selesaikan {{ $isLegacyBiometricState ? 'daftar ulang' : 'aktivasi' }} biometrik atau buka halaman biometrik untuk melihat petunjuk perangkat.
                    </div>
                @endif

                <div class="pkg-biometric-prompt__actions">
                    <button type="button" class="btn-primary w-full" data-biometric-action="register">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="h-5 w-5 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                        </svg>
                        {{ $primaryActionLabel }}
                    </button>

                    @if($biometricPopupRequired)
                        <a href="{{ $biometricSettingsUrl }}" class="btn-secondary w-full">
                            Buka Pengaturan Biometrik
                        </a>
                    @else
                        <button type="button" class="btn-secondary w-full" data-biometric-action="dismiss">
                            Nanti saja
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endif
