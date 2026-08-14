@extends($isPublic ? 'layouts.public' : ($isStudent ? 'layouts.siswa' : 'layouts.app'))

@section('title', 'Konfirmasi Peta Khatam')

@section('content')
@php
    $imageRoute = match (true) {
        $isPublic => route('public.quran.scan.image', $scan),
        $isStudent => route('siswa.quran.scan.image', $scan),
        default => route('quran.scan.image', $scan),
    };
    $formRoute = match (true) {
        $isPublic => route('public.quran.scan.confirm.store', $scan),
        $isStudent => route('siswa.quran.scan.confirm.store', $scan),
        default => route('quran.scan.confirm.store', $scan),
    };
    $suggestion = $scan->metadata['ocr_suggestion'] ?? [];
    $detected = collect(old('completed_surahs', $suggestion['completed_surahs'] ?? []))->map(fn ($n) => (int) $n)->unique()->sort()->values();
    $ambiguous = collect(old('ambiguous_surahs', $suggestion['ambiguous_surahs'] ?? []))->map(fn ($n) => (int) $n)->unique()->sort()->values();
    $baseline = collect($khatam['completed_surahs']);
    $newDetected = $detected->diff($baseline)->values();
@endphp
<div class="mx-auto max-w-6xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
    <div class="pkg-page-header">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $scan->siswa->nama }} &middot; Siklus {{ $scan->sheet->cycle->cycle_number }}</p>
            <h1 class="pkg-page-heading">Periksa Peta Khatam</h1>
            <p class="pkg-page-subheading">Hanya perubahan baru yang ditampilkan. Progres terverifikasi tidak dapat berkurang dari hasil scan.</p>
        </div>
    </div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $errors->first() }}</div>@endif

    <div class="grid gap-5 lg:grid-cols-[minmax(300px,0.85fr)_minmax(0,1.15fr)]">
        <aside class="pkg-panel-lg lg:sticky lg:top-24 lg:self-start">
            <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-bold">Foto Peta Khatam</h2><a href="{{ $imageRoute }}?original=1" target="_blank" rel="noopener" class="btn-secondary min-h-11">Buka Foto</a></div>
            <img src="{{ $scan->processed_path ? $imageRoute : $imageRoute.'?original=1' }}" alt="Foto Peta Khatam yang diunggah" class="mt-3 max-h-[65vh] w-full rounded-xl border border-gray-200 object-contain dark:border-gray-700">
        </aside>

        <form method="POST" action="{{ $formRoute }}" class="space-y-4">@csrf
            <input type="hidden" name="ocr_suggestion" value="{{ json_encode($suggestion) }}">
            <section class="pkg-card-soft p-4">
                <p class="font-bold">{{ $newDetected->count() }} surat baru terdeteksi</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $ambiguous->count() }} tanda meragukan perlu diperiksa. Hapus centang bila lingkaran sebenarnya belum diisi.</p>
            </section>

            <section class="pkg-panel-lg">
                <h2 class="font-bold">Surat selesai yang baru</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @forelse($newDetected as $number)
                        <label class="flex min-h-11 items-center gap-3 rounded-xl border border-gray-200 px-3 py-2 dark:border-gray-700">
                            <input class="pkg-check" type="checkbox" name="completed_surahs[]" value="{{ $number }}" checked>
                            <span><strong>{{ $number }}. {{ $catalog::name($number) }}</strong><span class="block text-xs text-gray-500">{{ $catalog::ayahCount($number) }} ayat</span></span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada tanda baru yang terbaca otomatis.</p>
                    @endforelse
                </div>

                @if($ambiguous->isNotEmpty())
                    <h3 class="mt-5 font-semibold">Tanda meragukan</h3>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach($ambiguous->diff($baseline) as $number)
                            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 dark:border-amber-800 dark:bg-amber-950/30">
                                <input class="pkg-check" type="checkbox" name="completed_surahs[]" value="{{ $number }}">
                                <span>{{ $number }}. {{ $catalog::name($number) }} <span class="block text-xs">Centang hanya bila benar-benar hitam.</span></span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <details class="mt-5 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                    <summary class="min-h-11 cursor-pointer py-2 font-semibold">Tambahkan surat yang tidak terbaca</summary>
                    <div class="mt-3 grid max-h-72 gap-2 overflow-y-auto sm:grid-cols-2">
                        @for($number=1;$number<=114;$number++)
                            @continue($baseline->contains($number) || $newDetected->contains($number) || $ambiguous->contains($number))
                            <label class="flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 px-3 dark:border-gray-700"><input class="pkg-check" type="checkbox" name="completed_surahs[]" value="{{ $number }}"><span>{{ $number }}. {{ $catalog::name($number) }}</span></label>
                        @endfor
                    </div>
                </details>
            </section>

            <section class="pkg-panel-lg">
                <h2 class="font-bold">Posisi bacaan aktif</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Opsional. Posisi yang lebih rendah tidak akan menimpa progres lama.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label><span class="mb-1 block text-xs font-semibold">Surat aktif</span><select class="pkg-field min-h-11" name="active_surah"><option value="">Tidak diisi</option>@foreach($catalog::options() as $number => $label)<option value="{{ $number }}" @selected((string) old('active_surah', $suggestion['active_surah'] ?? '') === (string) $number)>{{ $label }}</option>@endforeach</select></label>
                    <label><span class="mb-1 block text-xs font-semibold">Ayat terakhir</span><input class="pkg-field min-h-11" type="number" inputmode="numeric" min="1" max="286" name="active_ayah" value="{{ old('active_ayah', $suggestion['active_ayah'] ?? '') }}"></label>
                    <label><span class="mb-1 block text-xs font-semibold">Tanggal pembaruan</span><input class="pkg-field min-h-11" type="date" max="{{ now()->toDateString() }}" name="marked_on" value="{{ old('marked_on', $suggestion['marked_on'] ?? now()->toDateString()) }}"></label>
                </div>
            </section>

            <button class="btn-primary min-h-12 w-full justify-center">{{ ($isStudent || $isPublic) ? 'Kirim untuk Verifikasi Pamong' : 'Simpan sebagai Terverifikasi' }}</button>
        </form>
    </div>
</div>
@endsection
