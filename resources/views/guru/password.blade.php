@extends('layouts.guru')

@section('title', $firstLogin ? 'Buat Password Baru' : 'Ubah Password')

@section('content')
<div class="mx-auto max-w-lg">
    <section class="pkg-panel p-5 sm:p-7">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2H6a2 2 0 00-2 2v7a2 2 0 002 2zm2-11V7a4 4 0 118 0v3"/></svg></div>
        <h1 class="mt-5 text-2xl font-black">{{ $firstLogin ? 'Amankan akun Anda' : 'Ubah password' }}</h1>
        <p class="mt-2 text-sm leading-6 text-gray-500">{{ $firstLogin ? 'Password awal hanya untuk satu kali masuk. Buat password pribadi sebelum membuka Portal Guru.' : 'Gunakan password yang kuat dan tidak dibagikan kepada orang lain.' }}</p>
        <form method="POST" action="{{ $firstLogin ? route('guru.password.initial.update') : route('guru.password.update') }}" class="mt-6 space-y-4">
            @csrf @method('PUT')
            @unless($firstLogin)<div><label class="form-label">Password saat ini</label><input type="password" name="current_password" class="pkg-field w-full" required autocomplete="current-password"></div>@endunless
            <div><label class="form-label">Password baru</label><input type="password" name="password" class="pkg-field w-full" required autocomplete="new-password"></div>
            <div><label class="form-label">Ulangi password baru</label><input type="password" name="password_confirmation" class="pkg-field w-full" required autocomplete="new-password"></div>
            <button class="btn-primary w-full justify-center">{{ $firstLogin ? 'Simpan dan Buka Portal' : 'Simpan Password' }}</button>
        </form>
    </section>
</div>
@endsection
