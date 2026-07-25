@extends('layouts.public')

@section('title', 'Formulir Terkirim')

@section('content')
<main class="mx-auto flex min-h-[75vh] max-w-xl items-center px-4 py-10 sm:px-6">
    <section class="pkg-panel-lg w-full p-6 text-center sm:p-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/>
            </svg>
        </div>
        <h1 class="mt-5 text-2xl font-black text-gray-900 dark:text-white">Terima kasih</h1>
        <p class="mt-3 leading-7 text-gray-600 dark:text-gray-300">Formulir kesediaan Anda sudah tersimpan. Admin akan menghubungi melalui WhatsApp saat jadwal disusun.</p>
        <a href="{{ route('public.index') }}" class="btn-secondary mt-6 justify-center">Kembali ke Beranda</a>
    </section>
</main>
@endsection
