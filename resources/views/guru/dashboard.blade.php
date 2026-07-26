@extends('layouts.guru')

@section('title', 'Beranda Guru')

@section('content')
<div class="space-y-5">
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-600 to-teal-800 p-6 text-white shadow-xl shadow-emerald-900/15">
        <p class="text-sm font-semibold text-emerald-100">{{ now()->translatedFormat('l, d F Y') }}</p>
        <h1 class="mt-2 text-2xl font-black sm:text-3xl">Assalamu'alaikum, {{ $profile->publicDisplayName() }}</h1>
        <p class="mt-2 max-w-xl text-sm leading-6 text-emerald-50">Jadwal, bahan ajar, dan kesediaan mengajar tersedia dalam satu portal.</p>
    </section>

    @if($nextAssignment)
        @php
            $session = $nextAssignment->session;
        @endphp
        <section class="pkg-panel p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Jadwal terdekat</p><h2 class="mt-1 text-xl font-black text-gray-900 dark:text-white">{{ $session->session_date->translatedFormat('l, d F Y') }}</h2></div>
                <span class="rounded-full px-3 py-1 text-xs font-black {{ $nextAssignment->role === 'main' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">{{ $nextAssignment->role === 'main' ? 'Utama' : 'Cadangan' }}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="pkg-card-soft p-3"><p class="text-xs text-gray-500">Rombel</p><p class="mt-1 font-black">{{ strtoupper($session->rombel) }}</p></div>
                <div class="pkg-card-soft p-3"><p class="text-xs text-gray-500">Waktu</p><p class="mt-1 font-black">{{ substr($session->start_time, 0, 5) }} WIB</p></div>
                <div class="pkg-card-soft p-3"><p class="text-xs text-gray-500">Lokasi</p><p class="mt-1 font-black">{{ $session->location ?: 'Belum ditentukan' }}</p></div>
                <div class="pkg-card-soft p-3"><p class="text-xs text-gray-500">Konfirmasi</p><p class="mt-1 font-black">{{ match($nextAssignment->confirmation_status) { 'confirmed' => 'Bersedia', 'declined' => 'Berhalangan', default => 'Menunggu' } }}</p></div>
            </div>
            <a href="{{ route('guru.schedule.show', $nextAssignment) }}" class="btn-primary mt-4 w-full justify-center">Lihat Detail dan Materi</a>
        </section>
    @else
        <section class="pkg-empty-state"><h2 class="pkg-empty-title">Belum ada jadwal terbit</h2><p class="pkg-empty-copy">Jadwal berikutnya akan muncul setelah diterbitkan Admin.</p></section>
    @endif

    <section>
        <div class="mb-3 flex items-center justify-between"><h2 class="text-lg font-black">Tugas bulan ini</h2><a href="{{ route('guru.schedule') }}" class="text-sm font-bold text-emerald-600">Semua jadwal</a></div>
        <div class="grid grid-cols-3 gap-3">
            @foreach([['Total', $monthStats['total']], ['Utama', $monthStats['main']], ['Cadangan', $monthStats['backup']]] as [$label, $value])
                <div class="pkg-card p-4 text-center"><p class="text-2xl font-black">{{ $value }}</p><p class="mt-1 text-xs font-semibold text-gray-500">{{ $label }}</p></div>
            @endforeach
        </div>
    </section>

    <section class="pkg-panel p-5">
        <div class="flex items-center justify-between gap-3"><div><h2 class="font-black">Kesediaan saat ini</h2><p class="mt-1 text-sm text-gray-500">{{ \App\Models\TeacherProfile::PARTICIPATION_ROLES[$profile->participation_role] ?? '-' }} · Maks. {{ $profile->monthly_limit ?: '4+' }} kali/bulan</p></div><a href="{{ route('guru.profile') }}#kesediaan" class="btn-secondary !px-3 !py-2 text-sm">Ubah</a></div>
        <div class="mt-3 flex flex-wrap gap-2">@foreach($profile->rombels ?? [] as $rombel)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold dark:bg-slate-800">{{ strtoupper($rombel) }}</span>@endforeach @foreach($profile->available_nights ?? [] as $night)<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ \App\Models\TeacherProfile::NIGHTS[$night] ?? $night }}</span>@endforeach</div>
    </section>
</div>
@endsection
