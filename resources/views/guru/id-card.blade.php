@extends('layouts.guru')

@section('title', 'Kartu ID Guru')

@section('content')
<div class="mx-auto max-w-lg space-y-4">
    <header><p class="text-sm font-bold text-emerald-600">Identitas presensi</p><h1 class="mt-1 text-2xl font-black">Kartu ID Guru</h1></header>
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-700 to-slate-900 p-6 text-white shadow-2xl">
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white/15 text-xl font-black">@if(auth()->user()->avatar_path)<img src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="" class="h-full w-full object-cover">@else{{ strtoupper(substr($profile->name, 0, 1)) }}@endif</div>
            <div class="min-w-0"><p class="truncate text-xl font-black">{{ $profile->name }}</p><p class="mt-1 text-sm text-emerald-100">Guru · {{ $profile->kelompokLabel() }}</p></div>
        </div>
        <div class="mx-auto mt-6 w-fit rounded-2xl bg-white p-4">
            <img src="{{ $qrData['qr_image_base64'] }}" alt="QR ID Guru" width="220" height="220" class="h-52 w-52">
        </div>
        <p class="mt-4 text-center text-xs text-emerald-100">Tunjukkan QR ini pada perangkat presensi PKG.</p>
    </section>
</div>
@endsection
