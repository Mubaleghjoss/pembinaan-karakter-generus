@extends('layouts.app')

@section('title', 'Pustaka Materi Guru')

@section('content')
<div class="space-y-6">
    <header class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Pustaka Materi Guru</h1>
            <p class="pkg-page-subheading">Kelola tautan bahan ajar Google Drive yang dapat dipakai ulang pada jadwal Guru.</p>
        </div>
        <div class="pkg-page-actions">
            <a href="{{ route('teacher-planning.index') }}" class="btn-secondary">Kembali ke Jadwal</a>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errors->first() }}</div>
    @endif

    <section class="pkg-panel p-5">
        <h2 class="text-lg font-black text-gray-900 dark:text-white">Tambah Materi</h2>
        <form method="POST" action="{{ route('teacher-materials.store') }}" class="mt-4 grid gap-4 lg:grid-cols-2">
            @csrf
            <div><label class="form-label">Judul</label><input name="title" value="{{ old('title') }}" class="pkg-field w-full" required maxlength="180"></div>
            <div><label class="form-label">Tautan Google Drive/Docs</label><input name="google_drive_url" type="url" value="{{ old('google_drive_url') }}" class="pkg-field w-full" placeholder="https://drive.google.com/..." required></div>
            <div class="lg:col-span-2"><label class="form-label">Keterangan</label><textarea name="description" class="pkg-field w-full" rows="3" maxlength="1000">{{ old('description') }}</textarea></div>
            <fieldset class="lg:col-span-2">
                <legend class="form-label">Target rombel</legend>
                <div class="flex flex-wrap gap-4">
                    @foreach($rombels as $value => $label)
                        <label class="pkg-check"><input type="checkbox" name="rombels[]" value="{{ $value }}" @checked(in_array($value, old('rombels', []), true))><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-500">Kosongkan bila materi dapat dipakai semua rombel.</p>
            </fieldset>
            <label class="pkg-check lg:col-span-2"><input type="checkbox" name="is_active" value="1" checked><span>Materi aktif</span></label>
            <button class="btn-primary justify-center lg:col-span-2">Simpan Materi</button>
        </form>
    </section>

    <section class="pkg-panel overflow-hidden">
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Daftar Materi</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($materials as $material)
                <details class="p-4 sm:p-5">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $material->title }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ collect($material->rombelLabels())->join(', ') ?: 'Semua rombel' }} · {{ $material->sessions_count }} sesi · {{ $material->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-600">Edit</span>
                    </summary>
                    <form method="POST" action="{{ route('teacher-materials.update', $material) }}" class="mt-5 grid gap-4 border-t border-gray-200 pt-5 dark:border-gray-700 lg:grid-cols-2">
                        @csrf @method('PUT')
                        <div><label class="form-label">Judul</label><input name="title" value="{{ $material->title }}" class="pkg-field w-full" required maxlength="180"></div>
                        <div><label class="form-label">Tautan</label><input name="google_drive_url" type="url" value="{{ $material->google_drive_url }}" class="pkg-field w-full" required></div>
                        <div class="lg:col-span-2"><label class="form-label">Keterangan</label><textarea name="description" class="pkg-field w-full" rows="3">{{ $material->description }}</textarea></div>
                        <fieldset class="lg:col-span-2"><legend class="form-label">Target rombel</legend><div class="flex flex-wrap gap-4">@foreach($rombels as $value => $label)<label class="pkg-check"><input type="checkbox" name="rombels[]" value="{{ $value }}" @checked(in_array($value, $material->rombels ?? [], true))><span>{{ $label }}</span></label>@endforeach</div></fieldset>
                        <label class="pkg-check lg:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($material->is_active)><span>Materi aktif</span></label>
                        <button class="btn-primary justify-center lg:col-span-2">Simpan Perubahan</button>
                    </form>
                    <form method="POST" action="{{ route('teacher-materials.destroy', $material) }}" class="mt-4" data-confirm="Hapus materi {{ $material->title }}? Lampiran pada sesi juga akan dilepas.">
                        @csrf @method('DELETE')
                        <button class="btn-danger w-full justify-center">Hapus Materi</button>
                    </form>
                </details>
            @empty
                <div class="pkg-empty-state"><h3 class="pkg-empty-title">Belum ada materi Guru</h3><p class="pkg-empty-copy">Tambahkan tautan bahan ajar Google Drive dari formulir di atas.</p></div>
            @endforelse
        </div>
        @if($materials->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $materials->links() }}</div>@endif
    </section>
</div>
@endsection
