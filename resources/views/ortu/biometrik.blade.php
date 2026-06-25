@extends('layouts.ortu')

@section('title', 'Biometrik')

@section('content')
    <x-biometric-settings-panel
        :back-url="route('ortu.dashboard')"
        back-label="Kembali ke dashboard orang tua"
        title="Biometrik"
        subtitle="Kelola perangkat login orang tua."
        :credentials="$credentials"
        :valid-credential-count="$validCredentialCount ?? 0"
        :legacy-credential-count="$legacyCredentialCount ?? 0"
        :webauthn-environment="$webauthnEnvironment ?? null"
        :register-options-url="route('ortu.webauthn.register-options')"
        :register-url="route('ortu.webauthn.register')"
        :destroy-base-url="url('/ortu/webauthn')"
        register-success-message="Perangkat berhasil didaftarkan."
        register-error-message="Gagal mendaftarkan perangkat orang tua."
        delete-confirm-text="Perangkat ini tidak bisa dipakai login biometrik lagi."
        delete-success-text="Perangkat biometrik berhasil dihapus."
    />
@endsection
