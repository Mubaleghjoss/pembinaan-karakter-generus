@extends('layouts.public')

@section('title', 'Pendataan Guru MT/MS')

@section('content')
<main class="mx-auto flex min-h-[75vh] max-w-xl items-center px-4 py-10 sm:px-6">
    <section class="pkg-panel-lg w-full p-6 sm:p-8">
        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4"/>
            </svg>
        </div>
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-600 dark:text-emerald-400">Formulir Privat</p>
        <h1 class="mt-2 text-2xl font-black text-gray-900 dark:text-white">Pendataan Guru MT/MS</h1>
        <p class="mt-3 leading-7 text-gray-600 dark:text-gray-300">Masukkan kode akses yang dibagikan pengurus untuk membuka formulir kesediaan Program Tambahan Keilmuan PKG.</p>

        @if($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('public.teacher-availability.unlock') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="access_code" class="form-label">Kode Akses</label>
                <input id="access_code" name="access_code" type="text" class="pkg-field w-full uppercase tracking-[0.18em]" minlength="6" maxlength="32" autocomplete="one-time-code" required autofocus>
            </div>
            <button type="submit" class="btn-primary min-h-12 w-full justify-center">Buka Formulir</button>
        </form>
    </section>
</main>
@endsection
