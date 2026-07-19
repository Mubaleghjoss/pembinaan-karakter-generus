@extends('layouts.public')

@section('title', 'Akses Pendaftaran PKG - ' . ($theme->app_name ?? 'PKG'))

@section('content')
<div class="flex min-h-[calc(100svh-5rem)] items-center py-8 sm:py-12">
    <div class="mx-auto w-full max-w-lg px-4 sm:px-6">
        <section class="pkg-panel-lg overflow-hidden">
            <div class="bg-emerald-700 px-5 py-7 text-center text-white sm:px-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                </div>
                <h1 class="mt-4 text-2xl font-black">Pendaftaran Generus PKG</h1>
                <p class="mt-2 text-sm text-emerald-50">Masukkan kode akses yang diberikan oleh pengurus.</p>
            </div>

            <form method="POST" action="{{ route('public.generus-registration.short.unlock') }}" class="space-y-5 p-5 sm:p-8">
                @csrf
                <div>
                    <label for="access_code" class="form-label">Kode Akses</label>
                    <input
                        id="access_code"
                        name="access_code"
                        type="text"
                        value="{{ old('access_code') }}"
                        class="pkg-field w-full text-center text-xl font-black uppercase tracking-[0.25em]"
                        minlength="6"
                        maxlength="32"
                        autocomplete="one-time-code"
                        autocapitalize="characters"
                        spellcheck="false"
                        autofocus
                        required>
                    @error('access_code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-success min-h-12 w-full px-5 py-3 font-bold">Buka Formulir</button>
                <p class="text-center text-xs leading-5 text-gray-500 dark:text-gray-400">Kode tidak tersimpan di browser. Sesi formulir berakhir otomatis setelah 60 menit.</p>
            </form>
        </section>
    </div>
</div>
@endsection
