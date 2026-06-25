@extends('layouts.siswa')

@section('title', 'Biometrik')

@section('content')
    <x-biometric-settings-panel
        :back-url="route('siswa.dashboard')"
        back-label="Kembali ke dashboard siswa"
        title="Biometrik"
        subtitle="Kelola perangkat login siswa."
        :credentials="$credentials"
        :valid-credential-count="$validCredentialCount ?? 0"
        :legacy-credential-count="$legacyCredentialCount ?? 0"
        :webauthn-environment="$webauthnEnvironment ?? null"
        :register-options-url="route('siswa.webauthn.register-options')"
        :register-url="route('siswa.webauthn.register')"
        :destroy-base-url="url('/siswa/webauthn')"
        register-success-message="Perangkat berhasil didaftarkan."
        register-error-message="Gagal mendaftarkan perangkat siswa."
        delete-confirm-text="Perangkat ini tidak bisa dipakai login biometrik lagi."
        delete-success-text="Perangkat biometrik berhasil dihapus."
    />
@endsection
