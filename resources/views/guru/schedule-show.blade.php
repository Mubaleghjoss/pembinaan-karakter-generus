@extends('layouts.guru')

@section('title', 'Detail Jadwal')

@section('content')
@php
    $session = $assignment->session;
@endphp
<div class="space-y-5">
    <a href="{{ route('guru.schedule') }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-600">← Kembali ke Jadwal</a>
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-600 to-teal-800 p-6 text-white">
        <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-semibold text-emerald-100">{{ $session->session_date->translatedFormat('F Y') }}</p><h1 class="mt-2 text-2xl font-black">{{ $session->session_date->translatedFormat('l, d F Y') }}</h1></div><span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black backdrop-blur">{{ $assignment->role === 'main' ? 'Pengajar Utama' : 'Pengajar Cadangan' }}</span></div>
        <dl class="mt-6 grid grid-cols-2 gap-4 text-sm"><div><dt class="text-emerald-100">Rombel</dt><dd class="mt-1 font-black">{{ strtoupper($session->rombel) }}</dd></div><div><dt class="text-emerald-100">Waktu</dt><dd class="mt-1 font-black">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} WIB</dd></div><div><dt class="text-emerald-100">Lokasi</dt><dd class="mt-1 font-black">{{ $session->location ?: 'Belum ditentukan' }}</dd></div><div><dt class="text-emerald-100">Status</dt><dd class="mt-1 font-black">{{ match($assignment->confirmation_status) { 'confirmed' => 'Bersedia', 'declined' => 'Berhalangan', default => 'Menunggu konfirmasi' } }}</dd></div></dl>
    </section>
    <section class="pkg-panel p-5"><h2 class="text-lg font-black">Bahan ajar sesi</h2><p class="mt-1 text-sm text-gray-500">Tautan dibuka langsung di Google Drive pada tab baru.</p><div class="mt-4 space-y-3">@forelse($session->materials->where('is_active', true) as $material)<a href="{{ $material->google_drive_url }}" target="_blank" rel="noopener noreferrer" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-400 dark:border-slate-700"><p class="font-black">{{ $material->title }}</p>@if($material->description)<p class="mt-1 text-sm text-gray-500">{{ $material->description }}</p>@endif<span class="mt-2 inline-block text-sm font-bold text-emerald-600">Buka materi →</span></a>@empty<p class="rounded-2xl bg-slate-50 p-4 text-sm text-gray-500 dark:bg-slate-900">Belum ada materi yang ditempel pada sesi ini.</p>@endforelse</div></section>
    <section class="pkg-card-soft p-4"><p class="text-sm font-bold">Konfirmasi tetap melalui tautan WhatsApp</p><p class="mt-1 text-sm text-gray-500">Portal menampilkan status secara read-only. Gunakan tautan dari Admin untuk menyatakan bersedia atau berhalangan.</p>@if($assignment->confirmation_note)<p class="mt-3 text-sm">Catatan: {{ $assignment->confirmation_note }}</p>@endif</section>
</div>
@endsection
