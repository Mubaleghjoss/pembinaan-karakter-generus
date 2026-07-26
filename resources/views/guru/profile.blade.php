@extends('layouts.guru')

@section('title', 'Profil Guru')

@section('content')
<div class="space-y-5">
    <header><p class="text-sm font-bold text-emerald-600">Akun dan kesediaan</p><h1 class="mt-1 text-2xl font-black">Profil Saya</h1><p class="mt-1 text-sm text-gray-500">Perbarui informasi pribadi dan pilihan untuk jadwal berikutnya.</p></header>

    <section class="pkg-panel p-5">
        <div class="flex items-center gap-4">
            <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-emerald-600 text-2xl font-black text-white">
                @if(auth()->user()->avatar_path)<img src="{{ Storage::url(auth()->user()->avatar_path) }}" alt="{{ $profile->name }}" class="h-full w-full object-cover">@else{{ strtoupper(substr($profile->name, 0, 1)) }}@endif
            </div>
            <div class="min-w-0"><h2 class="truncate text-xl font-black">{{ $profile->name }}</h2><p class="mt-1 text-sm text-gray-500">{{ $profile->kelompokLabel() }}</p><p class="mt-1 truncate text-xs font-semibold text-emerald-600">{{ auth()->user()->username }}</p></div>
        </div>
        <form method="POST" action="{{ route('guru.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf @method('PUT')
            <div><label class="form-label">Nama lengkap</label><input name="name" value="{{ old('name', $profile->name) }}" class="pkg-field w-full" required></div>
            <div><label class="form-label">Email</label><input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" class="pkg-field w-full" placeholder="Opsional"></div>
            <div><label class="form-label">Nomor WhatsApp</label><input name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" class="pkg-field w-full" inputmode="tel" required></div>
            <div><label class="form-label">Kelompok</label><input value="{{ $profile->kelompokLabel() }}" class="pkg-field w-full bg-slate-100 dark:bg-slate-800" readonly><p class="mt-1 text-xs text-gray-500">Perubahan kelompok dilakukan oleh Admin.</p></div>
            <div><label class="form-label">Foto profil</label><input name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="pkg-field w-full"><p class="mt-1 text-xs text-gray-500">JPG, PNG, atau WebP. Maksimal 2 MB.</p></div>
            <button class="btn-primary w-full justify-center">Simpan Profil</button>
        </form>
    </section>

    <section id="kesediaan" class="pkg-panel scroll-mt-24 p-5">
        <h2 class="text-lg font-black">Kesediaan Mengajar</h2>
        <p class="mt-1 rounded-xl bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">Perubahan ini dipakai saat Admin membuat atau menghitung ulang jadwal berikutnya. Penugasan yang sudah ada tidak berubah.</p>
        <form method="POST" action="{{ route('guru.availability.update') }}" class="mt-5 space-y-5">
            @csrf @method('PUT')
            <div><label class="form-label">Peran yang bersedia dijalankan</label><select name="participation_role" class="pkg-field w-full" required>@foreach($participationRoles as $value => $label)<option value="{{ $value }}" @selected(old('participation_role', $profile->participation_role) === $value)>{{ $label }}</option>@endforeach</select></div>

            <fieldset><legend class="form-label">Rombel yang siap didampingi</legend><div class="grid gap-2 sm:grid-cols-3">@foreach($rombels as $value => $label)<label class="pkg-check rounded-xl border border-slate-200 p-3 dark:border-slate-700"><input type="checkbox" name="rombels[]" value="{{ $value }}" @checked(in_array($value, old('rombels', $profile->rombels ?? []), true))><span class="font-bold">{{ $label }}</span></label>@endforeach</div></fieldset>

            <fieldset>
                <legend class="form-label">Malam yang memungkinkan dan urutan prioritas</legend>
                <p class="mb-3 text-xs leading-5 text-gray-500">Urutan membantu Admin memilih malam yang paling sesuai saat tersedia lebih dari satu pilihan. Angka 1 adalah yang paling diutamakan.</p>
                <div class="space-y-2">
                    @foreach($nights as $value => $label)
                        <div class="grid grid-cols-[1fr_6rem] items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <label class="pkg-check"><input type="checkbox" name="available_nights[]" value="{{ $value }}" @checked(in_array($value, old('available_nights', $profile->available_nights ?? []), true))><span class="font-bold">{{ $label }}</span></label>
                            <select name="night_priorities[{{ $value }}]" class="pkg-field w-full !py-2 text-sm" aria-label="Prioritas {{ $label }}"><option value="">Urutan</option>@foreach([1,2,3] as $priority)<option value="{{ $priority }}" @selected((int) old("night_priorities.$value", ($profile->night_priorities ?? [])[$value] ?? 0) === $priority)>{{ $priority }}</option>@endforeach</select>
                        </div>
                    @endforeach
                </div>
            </fieldset>

            <div><label class="form-label">Jumlah maksimal penugasan per bulan</label><select name="monthly_limit" class="pkg-field w-full"><option value="">4 kali atau lebih</option>@foreach([1,2,3] as $limit)<option value="{{ $limit }}" @selected((string) old('monthly_limit', $profile->monthly_limit) === (string) $limit)>{{ $limit }} kali</option>@endforeach</select></div>

            <fieldset><legend class="form-label">Kemampuan atau materi yang dikuasai</legend><div class="grid gap-2 sm:grid-cols-2">@foreach($competencies as $value => $label)<label class="pkg-check rounded-xl border border-slate-200 p-3 dark:border-slate-700"><input type="checkbox" name="competencies[]" value="{{ $value }}" @checked(in_array($value, old('competencies', $profile->competencies ?? []), true))><span>{{ $label }}</span></label>@endforeach</div></fieldset>

            <div><label class="form-label">Kesiapan mempelajari bahan ajar</label><select name="material_readiness" class="pkg-field w-full" required><option value="ready" @selected(old('material_readiness', $profile->material_readiness) === 'ready')>Bersedia</option><option value="needs_support" @selected(old('material_readiness', $profile->material_readiness) === 'needs_support')>Perlu pendampingan</option></select></div>
            <div><label class="form-label">Kesediaan dihubungi sebagai cadangan</label><select name="backup_contact_preference" class="pkg-field w-full" required><option value="ready" @selected(old('backup_contact_preference', $profile->backup_contact_preference) === 'ready')>Bersedia</option><option value="one_day_notice" @selected(old('backup_contact_preference', $profile->backup_contact_preference) === 'one_day_notice')>Bersedia bila dikabari minimal satu hari</option><option value="unavailable" @selected(old('backup_contact_preference', $profile->backup_contact_preference) === 'unavailable')>Belum memungkinkan</option></select></div>
            <div><label class="form-label">Kendala atau waktu yang perlu diperhatikan</label><textarea name="constraints" class="pkg-field w-full" rows="4" maxlength="1000">{{ old('constraints', $profile->constraints) }}</textarea></div>
            <button class="btn-primary w-full justify-center">Simpan Kesediaan</button>
        </form>
    </section>

    <section class="pkg-panel p-5">
        <h2 class="text-lg font-black">Dokumen dan keamanan</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('guru.statement') }}" class="btn-secondary w-full justify-center">Unduh Surat Kesediaan</a>
            <a href="{{ route('guru.password.edit') }}" class="btn-secondary w-full justify-center">Ubah Password</a>
        </div>
    </section>
</div>
@endsection
