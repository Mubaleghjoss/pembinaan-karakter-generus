@extends('layouts.auth')

@section('title', 'Login Pamong atau Guru - ' . ($siteSettings['site_title'] ?? 'PKG Presensi'))
@section('auth_accent', '#0f766e')
@section('auth_accent_secondary', '#0369a1')
@section('auth_card_title', 'Login Pamong atau Guru')
@section('auth_card_copy', 'Masuk dengan akun Admin, Pamong, atau Guru menggunakan username, nomor HP, atau email terdaftar.')
@section('auth_public_navigation', 'false')

@section('content')
    @include('auth.partials.role-switcher', ['activeRole' => 'staff'])

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('error') || request('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            {{ session('error') ?? request('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-3.5 sm:space-y-4">
        @csrf

        <div>
            <label for="login" class="form-label">Username / No HP / Email</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input id="login" name="login" type="text" required value="{{ old('login') }}" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus class="form-input pkg-field-icon-left py-2.5" placeholder="Username, nomor HP, atau email">
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
                <input id="password" name="password" type="password" required autocomplete="current-password" class="form-input pkg-field-icon-left pkg-field-icon-right py-2.5" placeholder="Masukkan password">
                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    <svg id="eye-icon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
            <input id="remember" name="remember" type="checkbox" class="pkg-check h-4 w-4 rounded border-slate-300">
            <span>Ingat saya</span>
        </label>

        <button type="submit" class="pkg-btn-primary w-full rounded-xl px-4 py-2.5 text-base font-bold">
            Masuk
        </button>
    </form>

    <div id="biometricSection" class="mt-4" data-biometric-login-section>
        <div class="pkg-auth-divider">
            <span>atau</span>
        </div>
        <button
            type="button"
            id="biometricLoginBtn"
            class="mt-3 flex w-full items-center justify-center gap-3 rounded-xl bg-emerald-600 px-4 py-2.5 font-semibold text-white shadow-lg shadow-emerald-900/10 transition hover:bg-emerald-700"
            data-biometric-login
            data-options-url="{{ route('webauthn.login-options') }}"
            data-login-url="{{ route('webauthn.login') }}"
            data-fallback-redirect="/dashboard"
            data-unknown-credential-html="Akun ini belum mengaktifkan login sidik jari.<br><br>Silakan login dengan username dan password, lalu aktifkan fitur biometrik di pengaturan."
            data-info-color="#0f766e"
            data-error-color="#0f766e"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
            </svg>
            <span>Login dengan Sidik Jari</span>
        </button>
    </div>
@endsection

@push('scripts')
    <script>
        async function refreshCsrfToken() {
            try {
                const response = await fetch('/csrf-token');
                const data = await response.json();
                if (data.token) {
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                    document.querySelector('input[name="_token"]').value = data.token;
                }
            } catch (error) {
                console.error('Failed to refresh CSRF token:', error);
            }
        }

        refreshCsrfToken();
        setInterval(refreshCsrfToken, 600000);

        const loginForm = document.querySelector('form');
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;

            submitButton.disabled = true;
            submitButton.textContent = 'Memproses...';

            await refreshCsrfToken();

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    redirect: 'follow'
                });

                if (response.redirected || response.url !== window.location.href) {
                    window.location.href = response.url;
                    return;
                }

                if (response.ok) {
                    const data = await response.text();
                    if (data.includes('dashboard') || response.url.includes('dashboard')) {
                        window.location.href = '/dashboard';
                    } else {
                        window.location.reload();
                    }
                } else if (response.status === 419) {
                    await refreshCsrfToken();
                    form.submit();
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                    form.submit();
                }
            } catch (error) {
                console.error('Login submission error:', error);
                submitButton.disabled = false;
                submitButton.textContent = originalText;
                form.submit();
            }
        });
    </script>
@endpush
