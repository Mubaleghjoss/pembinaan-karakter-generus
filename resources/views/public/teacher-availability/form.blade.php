@extends('layouts.public')

@section('title', 'Formulir Kesediaan MT/MS')

@section('content')
<main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12" x-data="teacherAvailabilityForm()">
    <header class="pkg-page-header">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-600 dark:text-emerald-400">Program Tambahan Keilmuan</p>
            <h1 class="pkg-page-heading mt-2">Formulir Kesediaan MT/MS</h1>
            <p class="pkg-page-subheading">PKG Desa Panunggangan</p>
        </div>
    </header>

    <section class="pkg-card mb-6 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Pengantar</h2>
        <div class="mt-3 space-y-3 leading-7 text-gray-600 dark:text-gray-300">
            <p>Dalam rangka meningkatkan keilmuan generus, PKG Desa Panunggangan akan mengadakan kegiatan belajar tambahan setiap Senin, Selasa, dan Jumat malam pukul 20.00–21.30 WIB.</p>
            <p>Kegiatan terdiri dari ngaji makna Al-Qur'an, ngaji makna Al-Hadits, hafalan, dan praktik. Bahan ajar serta panduan penyampaian akan disiapkan oleh admin.</p>
            <p>Setiap rombel didampingi satu pengajar utama dan satu pengajar cadangan. Jadwal dibuat bergiliran, adil, dan disesuaikan dengan kesediaan masing-masing.</p>
        </div>
    </section>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">
            <p class="font-bold">Periksa kembali formulir:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.teacher-availability.store') }}" class="space-y-6">
        @csrf
        <section class="pkg-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Data Diri</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="form-label">Nama lengkap</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="pkg-field w-full" maxlength="160" required>
                    <p class="mt-2 text-sm font-medium text-amber-700 dark:text-amber-300">Pastikan tidak typo dan tuliskan nama lengkap agar tidak tertukar dengan pengajar lain.</p>
                </div>
                <div>
                    <label for="kelompok" class="form-label">Kelompok</label>
                    <select id="kelompok" name="kelompok" class="pkg-field w-full" required>
                        <option value="">Pilih kelompok</option>
                        @foreach($groups as $value => $label)
                            <option value="{{ $value }}" @selected(old('kelompok') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="whatsapp" class="form-label">Nomor WhatsApp aktif</label>
                    <input id="whatsapp" name="whatsapp" type="tel" inputmode="tel" value="{{ old('whatsapp') }}" class="pkg-field w-full" placeholder="Contoh: 081234567890" maxlength="24" required>
                </div>
            </div>
        </section>

        <section class="pkg-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kesediaan Mengajar</h2>
            <div class="mt-5 space-y-6">
                <fieldset>
                    <legend class="form-label">Kesediaan berpartisipasi</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach([
                            'main_backup' => 'Siap menjadi pengajar utama dan cadangan',
                            'main' => 'Siap menjadi pengajar utama',
                            'backup' => 'Siap menjadi pengajar cadangan',
                            'as_needed' => 'Siap membantu sesuai kebutuhan',
                            'unavailable' => 'Saat ini belum memungkinkan',
                        ] as $value => $label)
                            <label class="pkg-check">
                                <input type="radio" name="participation_role" value="{{ $value }}" x-model="participation" @checked(old('participation_role') === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div x-show="participation !== 'unavailable'" x-cloak class="space-y-6">
                    <fieldset>
                        <legend class="form-label">Rombel yang siap didampingi</legend>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach($rombels as $value => $label)
                                <label class="pkg-check"><input type="checkbox" name="rombels[]" value="{{ $value }}" @checked(in_array($value, old('rombels', []), true))><span>{{ $label }}</span></label>
                            @endforeach
                            <button type="button" @click="selectAllRombels()" class="btn-secondary justify-center text-sm">Semua rombel</button>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="form-label">Malam yang memungkinkan dan urutan prioritas</legend>
                        <div class="space-y-3">
                            @foreach($nights as $value => $label)
                                <div class="grid items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 sm:grid-cols-[1fr_180px]">
                                    <label class="pkg-check border-0 p-0">
                                        <input type="checkbox" name="available_nights[]" value="{{ $value }}" x-model="nights" @checked(in_array($value, old('available_nights', []), true))>
                                        <span>{{ $label }}</span>
                                    </label>
                                    <select name="night_priorities[{{ $value }}]" class="pkg-field w-full" :disabled="!nights.includes('{{ $value }}')">
                                        <option value="">Pilih urutan</option>
                                        <option value="1" @selected(old("night_priorities.$value") == 1)>Pilihan pertama</option>
                                        <option value="2" @selected(old("night_priorities.$value") == 2)>Pilihan kedua</option>
                                        <option value="3" @selected(old("night_priorities.$value") == 3)>Pilihan ketiga</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <div>
                        <label for="monthly_limit" class="form-label">Jumlah maksimal penugasan dalam satu bulan</label>
                        <select id="monthly_limit" name="monthly_limit" class="pkg-field w-full" :required="participation !== 'unavailable'">
                            <option value="">Pilih batas</option>
                            <option value="1" @selected(old('monthly_limit') === '1')>1 kali</option>
                            <option value="2" @selected(old('monthly_limit') === '2')>2 kali</option>
                            <option value="3" @selected(old('monthly_limit') === '3')>3 kali</option>
                            <option value="4_plus" @selected(old('monthly_limit') === '4_plus')>4 kali atau lebih</option>
                        </select>
                    </div>

                    <fieldset>
                        <legend class="form-label">Kemampuan atau materi yang paling dikuasai</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach([
                                'quran' => "Makna Al-Qur'an",
                                'hadith' => 'Makna Al-Hadits',
                                'memorization' => 'Hafalan',
                                'practice' => 'Praktik',
                                'class_support' => 'Pendampingan dan pengondisian kelas',
                                'all_materials' => 'Bersedia mempelajari seluruh materi',
                            ] as $value => $label)
                                <label class="pkg-check"><input type="checkbox" name="competencies[]" value="{{ $value }}" @checked(in_array($value, old('competencies', []), true))><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="material_readiness" class="form-label">Kesiapan mempelajari bahan ajar</label>
                            <select id="material_readiness" name="material_readiness" class="pkg-field w-full">
                                <option value="">Pilih jawaban</option>
                                <option value="ready" @selected(old('material_readiness') === 'ready')>Bersedia</option>
                                <option value="needs_support" @selected(old('material_readiness') === 'needs_support')>Perlu pendampingan</option>
                            </select>
                        </div>
                        <div>
                            <label for="backup_contact_preference" class="form-label">Kesediaan dihubungi sebagai cadangan</label>
                            <select id="backup_contact_preference" name="backup_contact_preference" class="pkg-field w-full">
                                <option value="">Pilih jawaban</option>
                                <option value="ready" @selected(old('backup_contact_preference') === 'ready')>Bersedia</option>
                                <option value="one_day_notice" @selected(old('backup_contact_preference') === 'one_day_notice')>Bersedia jika dikabari minimal satu hari</option>
                                <option value="unavailable" @selected(old('backup_contact_preference') === 'unavailable')>Belum memungkinkan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="constraints" class="form-label">Kendala atau waktu yang perlu diperhatikan admin</label>
                    <textarea id="constraints" name="constraints" rows="4" class="pkg-field w-full" maxlength="1000" :required="participation === 'unavailable'">{{ old('constraints') }}</textarea>
                </div>
            </div>
        </section>

        <section class="pkg-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Pernyataan Kesediaan</h2>
            <p class="mt-3 leading-7 text-gray-600 dark:text-gray-300">Saya bersedia ikut membantu Program Tambahan Keilmuan PKG Desa Panunggangan sesuai kemampuan dan waktu yang saya pilih. Saya siap mempelajari bahan ajar yang diberikan, menjalankan jadwal dengan amanah, serta segera memberikan informasi kepada admin apabila berhalangan hadir.</p>
            <label class="pkg-check mt-5">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                <span>Saya menyetujui pernyataan di atas.</span>
            </label>
        </section>

        <button type="submit" class="btn-success min-h-12 w-full justify-center text-base">Kirim Formulir Kesediaan</button>
    </form>
</main>

@push('scripts')
<script>
function teacherAvailabilityForm() {
    return {
        participation: @json(old('participation_role', '')),
        nights: @json(old('available_nights', [])),
        selectAllRombels() {
            document.querySelectorAll('input[name="rombels[]"]').forEach((checkbox) => {
                checkbox.checked = true;
            });
        },
    };
}
</script>
@endpush
@endsection
