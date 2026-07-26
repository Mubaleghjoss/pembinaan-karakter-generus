@extends('layouts.guru')

@section('title', 'Biometrik Guru')

@section('content')
<x-biometric-settings-panel
    :back-url="route('guru.profile')"
    back-label="Kembali ke profil"
    title="Biometrik Guru"
    subtitle="Kelola perangkat untuk login cepat dan aman."
    :credentials="$credentials"
    :valid-credential-count="$validCredentialCount ?? 0"
    :legacy-credential-count="$legacyCredentialCount ?? 0"
    :webauthn-environment="$webauthnEnvironment ?? null"
    :register-options-url="route('webauthn.register-options')"
    :register-url="route('webauthn.register')"
    :destroy-base-url="url('/webauthn')"
    register-success-message="Perangkat berhasil didaftarkan."
    register-error-message="Gagal mendaftarkan perangkat Guru."
    delete-confirm-text="Perangkat ini tidak bisa dipakai login biometrik lagi."
    delete-success-text="Perangkat biometrik berhasil dihapus."
/>
@endsection
