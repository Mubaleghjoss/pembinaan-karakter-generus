@extends('layouts.auth')

@section('title', 'Login Orang Tua - ' . ($siteSettings['site_title'] ?? 'PKG Presensi'))
@section('auth_accent', '#0d9488')
@section('auth_accent_secondary', '#10b981')
@section('auth_badge', 'Portal Orang Tua')
@section('auth_heading', $siteSettings['site_title'] ?? 'PKG Presensi')
@section('auth_subheading', 'Masuk untuk memantau kehadiran, tugas, dan aktivitas anak dari portal yang seragam dengan sistem utama.')
@section('auth_card_title', 'Login Orang Tua')
@section('auth_card_copy', 'Gunakan akun orang tua untuk memantau progres anak secara langsung.')

@section('auth_mark')
    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
@endsection

@section('content')
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('error') || request('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            {{ session('error') ?? request('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('ortu.login.post') }}" class="space-y-4 sm:space-y-5">
        @csrf

        <div>
            <label for="username" class="form-label">Username</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <input type="text" name="username" id="username" value="{{ old('username') }}" inputmode="numeric" autocomplete="username" autocapitalize="none" spellcheck="false" class="form-input pkg-field-icon-left py-3" placeholder="Default: NIS anak" required autofocus>
            </div>
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8"/>
                    </svg>
                </div>
                <input type="password" name="password" id="password" autocomplete="current-password" class="form-input pkg-field-icon-left pkg-field-icon-right py-3" placeholder="Masukkan password" required>
                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <svg id="eye-icon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" id="remember" class="pkg-check h-4 w-4 rounded border-slate-300">
            <span>Ingat saya</span>
        </label>

        <button type="submit" class="pkg-btn-primary w-full rounded-xl px-4 py-3 text-base font-bold">
            Masuk
        </button>
    </form>

    <div id="biometricSection" class="mt-5" data-biometric-login-section>
        <div class="pkg-auth-divider">
            <span>atau</span>
        </div>
        <button
            type="button"
            id="biometricLoginBtn"
            class="mt-4 flex w-full items-center justify-center gap-3 rounded-xl bg-emerald-600 px-4 py-3 font-semibold text-white shadow-lg shadow-emerald-900/10 transition hover:bg-emerald-700"
            data-biometric-login
            data-options-url="{{ route('ortu.webauthn.login-options') }}"
            data-login-url="{{ route('ortu.webauthn.login') }}"
            data-fallback-redirect="/ortu/dashboard"
            data-unknown-credential-html="Akun ini belum mengaktifkan login sidik jari.<br><br>Silakan login dengan username dan password, lalu aktifkan fitur biometrik di pengaturan."
            data-info-color="#0d9488"
            data-error-color="#0d9488"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
            </svg>
            <span>Login dengan Sidik Jari</span>
        </button>
    </div>

    <div class="mt-4 rounded-2xl border border-teal-200/70 bg-teal-50 px-4 py-3 text-center text-xs text-teal-800 dark:border-teal-900/70 dark:bg-teal-950/40 dark:text-teal-200">
        Login pertama: gunakan NIS anak sebagai username dan password siswa. Setelah masuk, ubah kredensial di pengaturan.
    </div>
@endsection

@section('auth_footer')
    <div class="space-y-2 text-sm">
        <p class="pkg-page-copy">
            <a href="{{ route('siswa.login') }}" class="pkg-link-accent font-semibold">Login Siswa</a>
            <span class="mx-2 text-slate-300 dark:text-slate-600">|</span>
            <a href="{{ route('login') }}" class="pkg-link-accent font-semibold">Login Pamong</a>
        </p>
        <p>
            <a href="{{ route('public.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Beranda
            </a>
        </p>
    </div>
@endsection
