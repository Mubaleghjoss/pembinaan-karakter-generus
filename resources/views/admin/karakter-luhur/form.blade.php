@extends('layouts.app')

@section('title', $mode === 'create' ? 'Tambah Karakter' : 'Edit Karakter')

@section('content')
@php
    $action = $mode === 'create'
        ? route('admin.karakter-luhur.store')
        : route('admin.karakter-luhur.update', $item);
    $dq = $item->dalil_quran ?: [];
    $dh = $item->dalil_hadits ?: [];
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 py-8" x-data="karakterForm()">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">{{ $mode === 'create' ? 'Tambah Karakter Luhur' : 'Edit: '.$item->nama }}</h1>
            <p class="pkg-page-subheading">Isi definisi, dalil, dan terutama <b>studi kasus</b> (1 baris = 1 skenario) untuk bahan game.</p>
        </div>
        <a href="{{ route('admin.karakter-luhur.index') }}" class="btn-secondary px-5 py-2.5 font-bold">Kembali</a>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="pkg-panel-lg p-4 sm:p-6 space-y-4">
            <div class="grid gap-4 sm:grid-cols-4">
                <div>
                    <label class="pkg-label">Nomor</label>
                    <input type="number" name="nomor" value="{{ old('nomor', $item->nomor) }}" class="pkg-field w-full" min="1" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="pkg-label">Nama Karakter (jawaban game)</label>
                    <input type="text" name="nama" value="{{ old('nama', $item->nama) }}" class="pkg-field w-full" required>
                </div>
                <div>
                    <label class="pkg-label">Slug (opsional)</label>
                    <input type="text" name="slug" value="{{ old('slug', $item->slug) }}" class="pkg-field w-full" placeholder="otomatis">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="pkg-label">Nama Arab</label>
                    <input type="text" name="nama_arab" value="{{ old('nama_arab', $item->nama_arab) }}" class="pkg-field w-full" dir="rtl" lang="ar">
                </div>
                <div>
                    <label class="pkg-label">Kategori</label>
                    <input type="text" name="kategori" value="{{ old('kategori', $item->kategori) }}" class="pkg-field w-full" placeholder="mis. 6 Thabiat Luhur">
                </div>
                <div>
                    <label class="pkg-label">Ringkas (label pendek)</label>
                    <input type="text" name="ringkas" value="{{ old('ringkas', $item->ringkas) }}" class="pkg-field w-full">
                </div>
            </div>
            <div>
                <label class="pkg-label">Deskripsi singkat</label>
                <textarea name="deskripsi" rows="2" class="pkg-field w-full">{{ old('deskripsi', $item->deskripsi) }}</textarea>
            </div>
            <div>
                <label class="pkg-label">Definisi (dipakai sebagai petunjuk Rangkai Kata)</label>
                <textarea name="definisi" rows="3" class="pkg-field w-full">{{ old('definisi', $item->definisi) }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) class="rounded">
                <span class="text-sm font-medium">Aktif (dipakai di game)</span>
            </label>
        </div>

        <div class="pkg-panel-lg p-4 sm:p-6 space-y-4">
            <h2 class="font-bold text-gray-900 dark:text-white">Bahan Game (1 baris = 1 item)</h2>
            <div>
                <label class="pkg-label">Studi Kasus (skenario kehidupan anak SMA)</label>
                <textarea name="studi_kasus_text" rows="5" class="pkg-field w-full" placeholder="Satu skenario per baris...">{{ old('studi_kasus_text', implode("\n", (array) ($item->studi_kasus ?? []))) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="pkg-label">Hikmah</label>
                    <textarea name="hikmah_text" rows="3" class="pkg-field w-full">{{ old('hikmah_text', implode("\n", (array) ($item->hikmah ?? []))) }}</textarea>
                </div>
                <div>
                    <label class="pkg-label">Tips Amal</label>
                    <textarea name="tips_amal_text" rows="3" class="pkg-field w-full">{{ old('tips_amal_text', implode("\n", (array) ($item->tips_amal ?? []))) }}</textarea>
                </div>
            </div>
        </div>

        <div class="pkg-panel-lg p-4 sm:p-6 space-y-4">
            <h2 class="font-bold text-gray-900 dark:text-white">Dalil Al-Qur'an</h2>
            <template x-for="(row, i) in quran" :key="'q'+i">
                <div class="grid gap-2 sm:grid-cols-12 items-start border-b border-gray-100 dark:border-gray-800 pb-3">
                    <input type="text" :name="'dalil_quran_arab[]'" x-model="row.arab" class="pkg-field sm:col-span-4" placeholder="Arab" dir="rtl" lang="ar">
                    <input type="text" :name="'dalil_quran_terjemahan[]'" x-model="row.terjemahan" class="pkg-field sm:col-span-5" placeholder="Terjemahan">
                    <input type="text" :name="'dalil_quran_sumber[]'" x-model="row.sumber" class="pkg-field sm:col-span-2" placeholder="Sumber">
                    <button type="button" @click="quran.splice(i,1)" class="btn-danger !px-2 !py-1 text-xs sm:col-span-1">×</button>
                </div>
            </template>
            <button type="button" @click="quran.push({arab:'',terjemahan:'',sumber:''})" class="btn-secondary !px-3 !py-1.5 text-xs">+ Baris Dalil Qur'an</button>
        </div>

        <div class="pkg-panel-lg p-4 sm:p-6 space-y-4">
            <h2 class="font-bold text-gray-900 dark:text-white">Dalil Hadits</h2>
            <template x-for="(row, i) in hadits" :key="'h'+i">
                <div class="grid gap-2 sm:grid-cols-12 items-start border-b border-gray-100 dark:border-gray-800 pb-3">
                    <input type="text" :name="'dalil_hadits_arab[]'" x-model="row.arab" class="pkg-field sm:col-span-4" placeholder="Arab" dir="rtl" lang="ar">
                    <input type="text" :name="'dalil_hadits_terjemahan[]'" x-model="row.terjemahan" class="pkg-field sm:col-span-5" placeholder="Terjemahan">
                    <input type="text" :name="'dalil_hadits_sumber[]'" x-model="row.sumber" class="pkg-field sm:col-span-2" placeholder="Sumber">
                    <button type="button" @click="hadits.splice(i,1)" class="btn-danger !px-2 !py-1 text-xs sm:col-span-1">×</button>
                </div>
            </template>
            <button type="button" @click="hadits.push({arab:'',terjemahan:'',sumber:''})" class="btn-secondary !px-3 !py-1.5 text-xs">+ Baris Dalil Hadits</button>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary px-6 py-2.5 font-bold">{{ $mode === 'create' ? 'Simpan Karakter' : 'Perbarui' }}</button>
            <a href="{{ route('admin.karakter-luhur.index') }}" class="btn-secondary px-6 py-2.5 font-bold">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function karakterForm() {
        return {
            quran: @json($dq ?: [['arab'=>'','terjemahan'=>'','sumber'=>'']]),
            hadits: @json($dh ?: [['arab'=>'','terjemahan'=>'','sumber'=>'']]),
        };
    }
</script>
@endpush
@endsection
