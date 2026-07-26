@extends('layouts.guru')

@section('title', 'Detail Jadwal')

@section('content')
@php
    $session = $assignment->session;
    $latestRequest = $assignment->requests->first();
@endphp
<div class="space-y-5">
    <a href="{{ route('guru.schedule') }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-600">← Kembali ke Jadwal</a>

    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-600 to-teal-800 p-6 text-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-emerald-100">{{ $session->session_date->translatedFormat('F Y') }}</p>
                <h1 class="mt-2 text-2xl font-black">{{ $session->session_date->translatedFormat('l, d F Y') }}</h1>
            </div>
            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black backdrop-blur">{{ $assignment->role === 'main' ? 'Pengajar Utama' : 'Pengajar Cadangan' }}</span>
        </div>
        <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-emerald-100">Rombel</dt><dd class="mt-1 font-black">{{ strtoupper($session->rombel) }}</dd></div>
            <div><dt class="text-emerald-100">Waktu</dt><dd class="mt-1 font-black">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} WIB</dd></div>
            <div><dt class="text-emerald-100">Lokasi</dt><dd class="mt-1 font-black">{{ $session->location ?: 'Belum ditentukan' }}</dd></div>
            <div><dt class="text-emerald-100">Status</dt><dd class="mt-1 font-black">{{ match($assignment->confirmation_status) { 'confirmed' => 'Bersedia', 'declined' => 'Berhalangan', default => 'Menunggu konfirmasi' } }}</dd></div>
        </dl>
    </section>

    <section class="pkg-panel p-5">
        <h2 class="text-lg font-black">Konfirmasi Jadwal</h2>
        <p class="mt-1 text-sm text-gray-500">Status langsung terlihat oleh Admin setelah disimpan.</p>
        <form method="POST" action="{{ route('guru.schedule.confirm', $assignment) }}" class="mt-4 space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-3">
                <label class="pkg-check rounded-xl border border-gray-200 p-3 dark:border-gray-700"><input type="radio" name="status" value="confirmed" @checked(old('status', $assignment->confirmation_status) === 'confirmed') required><span>Bersedia</span></label>
                <label class="pkg-check rounded-xl border border-gray-200 p-3 dark:border-gray-700"><input type="radio" name="status" value="declined" @checked(old('status', $assignment->confirmation_status) === 'declined') required><span>Berhalangan</span></label>
            </div>
            <textarea name="note" rows="3" class="pkg-field w-full" maxlength="500" placeholder="Catatan untuk Admin (opsional)">{{ old('note', $assignment->confirmation_note) }}</textarea>
            <button class="btn-success w-full justify-center">Simpan Konfirmasi</button>
        </form>
    </section>

    <section class="pkg-panel p-5">
        <h2 class="text-lg font-black">Hubungi Admin</h2>
        <p class="mt-1 text-sm text-gray-500">Nomor Admin: {{ $adminWhatsapp ?: 'Belum diatur' }}</p>
        @if($adminWhatsappUrl)
            <a href="{{ $adminWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn-success mt-4 w-full justify-center">WhatsApp Admin</a>
        @else
            <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Admin belum mengatur nomor WhatsApp.</p>
        @endif

        <div class="my-5 border-t border-gray-200 dark:border-gray-700"></div>
        <h3 class="font-black">Ajukan Perubahan</h3>
        <p class="mt-1 text-sm text-gray-500">Permohonan tersimpan di sistem, lalu WhatsApp Admin terbuka dengan pesan yang sudah disiapkan.</p>
        <form method="POST" action="{{ route('guru.schedule.request', $assignment) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="form-label">Jenis permohonan</label>
                <select name="request_type" class="pkg-field w-full" required>
                    <option value="reschedule" @selected(old('request_type') === 'reschedule')>Pengajuan penjadwalan ulang</option>
                    <option value="unable" @selected(old('request_type') === 'unable')>Permohonan maaf tidak bisa mengajar</option>
                </select>
            </div>
            <div>
                <label class="form-label">Keterangan dan alasan</label>
                <textarea name="reason" rows="4" class="pkg-field w-full" minlength="5" maxlength="1000" placeholder="Jelaskan kebutuhan perubahan atau alasan berhalangan." required>{{ old('reason') }}</textarea>
            </div>
            <button class="btn-primary w-full justify-center">Simpan dan Buka WhatsApp</button>
        </form>

        @if($latestRequest)
            <div class="mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900/60 dark:bg-sky-950/30">
                <p class="text-xs font-black uppercase tracking-wide text-sky-700 dark:text-sky-300">Permohonan terakhir #{{ $latestRequest->id }}</p>
                <p class="mt-2 text-sm font-bold">{{ $latestRequest->request_type === 'reschedule' ? 'Penjadwalan ulang' : 'Tidak bisa mengajar' }} · {{ match($latestRequest->status) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Menunggu Admin' } }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $latestRequest->reason }}</p>
                @if($latestRequest->admin_note)<p class="mt-2 text-sm font-semibold">Catatan Admin: {{ $latestRequest->admin_note }}</p>@endif
            </div>
        @endif
    </section>

    <section class="pkg-panel p-5">
        <h2 class="text-lg font-black">Bahan ajar sesi</h2>
        <p class="mt-1 text-sm text-gray-500">Tautan dibuka langsung di Google Drive pada tab baru.</p>
        <div class="mt-4 space-y-3">
            @forelse($session->materials->where('is_active', true) as $material)
                <a href="{{ $material->google_drive_url }}" target="_blank" rel="noopener noreferrer" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-400 dark:border-slate-700">
                    <p class="font-black">{{ $material->title }}</p>
                    @if($material->description)<p class="mt-1 text-sm text-gray-500">{{ $material->description }}</p>@endif
                    <span class="mt-2 inline-block text-sm font-bold text-emerald-600">Buka materi →</span>
                </a>
            @empty
                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-gray-500 dark:bg-slate-900">Belum ada materi yang ditempel pada sesi ini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
