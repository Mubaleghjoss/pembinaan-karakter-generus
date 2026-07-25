@extends('layouts.public')

@section('title', 'Konfirmasi Jadwal Mengajar')

@section('content')
<main class="mx-auto flex min-h-[75vh] max-w-xl items-center px-4 py-10 sm:px-6">
    <section class="pkg-panel-lg w-full p-6 sm:p-8">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-600 dark:text-emerald-400">Konfirmasi Penugasan</p>
        <h1 class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ $assignment->teacher->name }}</h1>

        @if(session('success'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errors->first() }}</div>
        @endif

        <dl class="mt-6 grid gap-4 rounded-2xl bg-gray-50 p-5 dark:bg-gray-800/70 sm:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase tracking-wide text-gray-500">Tanggal</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $assignment->session->session_date->translatedFormat('l, d F Y') }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-gray-500">Waktu</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ substr($assignment->session->start_time, 0, 5) }}–{{ substr($assignment->session->end_time, 0, 5) }} WIB</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-gray-500">Rombel</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ strtoupper($assignment->session->rombel) }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-gray-500">Peran</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $assignment->role === 'main' ? 'Pengajar Utama' : 'Pengajar Cadangan' }}</dd></div>
        </dl>

        <p class="mt-5 text-sm text-gray-600 dark:text-gray-300">Status saat ini: <strong>{{ match($assignment->confirmation_status) { 'confirmed' => 'Bersedia', 'declined' => 'Berhalangan', default => 'Menunggu konfirmasi' } }}</strong></p>

        <form method="POST" action="{{ route('public.teacher-confirmation.store', $token) }}" class="mt-6 space-y-5">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="pkg-check"><input type="radio" name="status" value="confirmed" @checked(old('status', $assignment->confirmation_status) === 'confirmed') required><span>Bersedia hadir</span></label>
                <label class="pkg-check"><input type="radio" name="status" value="declined" @checked(old('status', $assignment->confirmation_status) === 'declined') required><span>Berhalangan</span></label>
            </div>
            <div>
                <label for="note" class="form-label">Catatan untuk admin (opsional)</label>
                <textarea id="note" name="note" rows="3" class="pkg-field w-full" maxlength="500">{{ old('note', $assignment->confirmation_note) }}</textarea>
            </div>
            <button type="submit" class="btn-success min-h-12 w-full justify-center">Simpan Konfirmasi</button>
        </form>
    </section>
</main>
@endsection
