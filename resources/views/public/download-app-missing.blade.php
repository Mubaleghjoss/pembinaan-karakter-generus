@extends('layouts.public')

@section('title', 'Berkas aplikasi belum tersedia')

@section('content')
    <section class="bg-slate-50 py-10 dark:bg-slate-950">
        <div class="mx-auto max-w-3xl px-4">
            <div class="rounded-2xl bg-white p-6 shadow-sm dark:bg-slate-900">
                <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">
                    Berkas aplikasi belum tersedia
                </h1>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Rilis APK belum diunggah ke server. Silakan hubungi pengurus PKG.
                </p>
                <a href="{{ route('public.download-app') }}"
                   class="mt-6 inline-flex min-h-[48px] items-center rounded-xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-100">
                    Kembali
                </a>
            </div>
        </div>
    </section>
@endsection
