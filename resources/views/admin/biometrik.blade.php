@extends('layouts.app')

@section('title', 'Biometrik')

@section('content')
    <x-biometric-settings-panel
        :back-url="route('dashboard')"
        back-label="Kembali ke dashboard"
        title="Biometrik Pamong"
        subtitle="Kelola perangkat login pamong."
        :credentials="$credentials"
        :valid-credential-count="$validCredentialCount ?? 0"
        :legacy-credential-count="$legacyCredentialCount ?? 0"
        :webauthn-environment="$webauthnEnvironment ?? null"
        :register-options-url="route('webauthn.register-options')"
        :register-url="route('webauthn.register')"
        :destroy-base-url="url('/webauthn')"
        register-success-message="Perangkat berhasil didaftarkan."
        register-error-message="Gagal mendaftarkan perangkat pamong."
        delete-confirm-text="Perangkat ini tidak bisa dipakai login biometrik lagi."
        delete-success-text="Perangkat biometrik berhasil dihapus."
    />
@endsection
