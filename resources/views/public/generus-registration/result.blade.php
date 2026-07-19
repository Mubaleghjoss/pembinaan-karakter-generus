@extends('layouts.public')

@section('title', 'Pendaftaran Berhasil - ' . ($theme->app_name ?? 'PKG'))

@section('content')
<div class="py-8 sm:py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <section class="pkg-panel-lg overflow-hidden">
            <div class="bg-emerald-600 px-5 py-6 text-center text-white sm:px-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                </div>
                <h1 class="mt-3 text-2xl font-black">{{ $isNewAccount ? 'Pendaftaran Berhasil' : 'Biodata Berhasil Diperbarui' }}</h1>
                <p class="mt-1 text-sm text-emerald-50">{{ $isNewAccount ? 'Akun Generus dan Orang Tua sudah dibuat.' : 'Surat pernyataan terbaru sudah tersimpan.' }}</p>
            </div>

            <div class="space-y-6 p-5 sm:p-8">
                @if($isNewAccount)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                    Simpan informasi akun ini. Untuk keamanan, segera ganti password setelah login pertama.
                </div>
                @endif

                @if($isNewAccount)
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="pkg-card-soft rounded-2xl p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-300">Akun Generus</p>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div><dt class="text-gray-500 dark:text-gray-400">Login</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $registration->siswa->nis }}</dd></div>
                            <div><dt class="text-gray-500 dark:text-gray-400">Password awal</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $registration->siswa->nis }}</dd></div>
                        </dl>
                        <a href="{{ route('siswa.login') }}" class="pkg-btn-primary mt-4 inline-flex px-4 py-2 text-sm">Login Siswa</a>
                    </div>
                    <div class="pkg-card-soft rounded-2xl p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-teal-600 dark:text-teal-300">Akun Orang Tua</p>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div><dt class="text-gray-500 dark:text-gray-400">Username</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $registration->siswa->ortu_username }}</dd></div>
                            <div><dt class="text-gray-500 dark:text-gray-400">Password awal</dt><dd class="font-bold text-gray-900 dark:text-white">{{ $registration->siswa->nis }}</dd></div>
                        </dl>
                        <a href="{{ route('ortu.login') }}" class="btn-success mt-4 inline-flex px-4 py-2 text-sm">Login Orang Tua</a>
                    </div>
                </div>
                @else
                <div class="pkg-card-soft rounded-2xl p-4 text-sm text-gray-700 dark:text-gray-200">
                    Biodata akun siswa dan orang tua sudah diperbarui. Username dan password lama tetap sama.
                </div>
                @endif

                <a href="{{ route('public.generus-registration.short.pdf', ['registration' => $registration, 'downloadToken' => $downloadToken]) }}" class="btn-secondary flex w-full items-center justify-center gap-2 px-5 py-3 font-bold">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12-4-4m4 4 4-4M5 20h14"/></svg>
                    Unduh PDF Surat Pernyataan
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
