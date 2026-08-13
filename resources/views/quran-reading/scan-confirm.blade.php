@extends($isPublic ? 'layouts.public' : ($isStudent ? 'layouts.siswa' : 'layouts.app'))

@section('title', 'Konfirmasi Hasil Scan')

@section('content')
@php
    $suggestedRows = collect($scan->metadata['ocr_suggestion'] ?? [])->keyBy('row_number');
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
@endphp
<div class="mx-auto max-w-6xl space-y-5 px-4 py-4 sm:px-6 sm:py-6">
    <div class="pkg-page-header">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $scan->siswa->nama }} · {{ $scan->siswa->kelas?->nama ?? 'Tanpa kelas' }}</p>
            <h1 class="pkg-page-heading">Konfirmasi hasil scan</h1>
            <p class="pkg-page-subheading">Cocokkan semua angka dengan foto. Warna kuning berarti hasil pembacaan perlu perhatian lebih.</p>
        </div>
    </div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $errors->first() }}</div>@endif
    <div class="grid gap-5 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.2fr)]">
        <aside class="pkg-panel-lg lg:sticky lg:top-24 lg:self-start">
            <div class="flex items-center justify-between gap-3"><h2 class="font-bold">Foto yang sudah diluruskan</h2><a href="{{ $imageRoute }}?original=1" target="_blank" class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Lihat asli</a></div>
            <img src="{{ $imageRoute }}" alt="Foto lembar bacaan yang diunggah" class="mt-3 max-h-[70vh] w-full rounded-xl border border-gray-200 object-contain dark:border-gray-700">
            <div class="mt-3 flex flex-wrap gap-2 text-xs"><span class="rounded-full border border-emerald-300 px-2.5 py-1">Hijau: cukup jelas</span><span class="rounded-full border border-amber-300 px-2.5 py-1">Kuning: periksa ulang</span></div>
        </aside>
        <form method="POST" action="{{ $formRoute }}" class="space-y-3">@csrf
            @for($i = 1; $i <= 12; $i++)
                @php
                    $suggestion = $suggestedRows->get($i, []);
                    $hasSuggestion = collect($suggestion)->only(['reading_date','page_start','page_end','surah_start','ayah_start','surah_end','ayah_end'])->filter(fn($value) => $value !== null && $value !== '')->isNotEmpty();
                    $confidence = $suggestion['confidence'] ?? [];
                    $confidenceClass = function (string $field) use ($confidence) {
                        $score = (int) ($confidence[$field] ?? 0);
                        return $score >= 85 ? 'pkg-quran-confidence-high' : ($score >= 60 ? 'pkg-quran-confidence-medium' : '');
                    };
                @endphp
                <fieldset class="pkg-panel p-4" x-data="{ used: {{ ($hasSuggestion || $i === 1) ? 'true' : 'false' }} }">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <legend class="font-bold">Baris {{ $i }}</legend>
                        <label class="flex min-h-11 cursor-pointer items-center gap-2"><input type="checkbox" class="pkg-check" x-model="used"><span class="text-sm">Baris terisi</span></label>
                    </div>
                    <input type="hidden" name="rows[{{ $i }}][row_number]" value="{{ $i }}" :disabled="!used">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="col-span-2 sm:col-span-1"><span class="mb-1 block text-xs font-semibold">Tanggal</span><input type="date" name="rows[{{ $i }}][reading_date]" value="{{ old("rows.$i.reading_date", $suggestion['reading_date'] ?? '') }}" class="pkg-field min-h-11 {{ $confidenceClass('reading_date') }}" :disabled="!used" :required="used"></label>
                        <label><span class="mb-1 block text-xs font-semibold">Hal. awal</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][page_start]" value="{{ old("rows.$i.page_start", $suggestion['page_start'] ?? '') }}" min="1" max="1000" class="pkg-field min-h-11 {{ $confidenceClass('page_start') }}" :disabled="!used" :required="used"></label>
                        <label><span class="mb-1 block text-xs font-semibold">Hal. akhir</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][page_end]" value="{{ old("rows.$i.page_end", $suggestion['page_end'] ?? '') }}" min="1" max="1000" class="pkg-field min-h-11 {{ $confidenceClass('page_end') }}" :disabled="!used" :required="used"></label>
                        <label><span class="mb-1 block text-xs font-semibold">Surat awal</span><select name="rows[{{ $i }}][surah_start]" class="pkg-field min-h-11 {{ $confidenceClass('surah_start') }}" :disabled="!used" :required="used"><option value="">Pilih</option>@foreach($surahOptions as $number=>$label)<option value="{{ $number }}" @selected((string) old("rows.$i.surah_start", $suggestion['surah_start'] ?? '') === (string) $number)>{{ $label }}</option>@endforeach</select></label>
                        <label><span class="mb-1 block text-xs font-semibold">Ayat awal</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][ayah_start]" value="{{ old("rows.$i.ayah_start", $suggestion['ayah_start'] ?? '') }}" min="1" max="286" class="pkg-field min-h-11 {{ $confidenceClass('ayah_start') }}" :disabled="!used" :required="used"></label>
                        <label><span class="mb-1 block text-xs font-semibold">Surat akhir</span><select name="rows[{{ $i }}][surah_end]" class="pkg-field min-h-11 {{ $confidenceClass('surah_end') }}" :disabled="!used" :required="used"><option value="">Pilih</option>@foreach($surahOptions as $number=>$label)<option value="{{ $number }}" @selected((string) old("rows.$i.surah_end", $suggestion['surah_end'] ?? '') === (string) $number)>{{ $label }}</option>@endforeach</select></label>
                        <label><span class="mb-1 block text-xs font-semibold">Ayat akhir</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][ayah_end]" value="{{ old("rows.$i.ayah_end", $suggestion['ayah_end'] ?? '') }}" min="1" max="286" class="pkg-field min-h-11 {{ $confidenceClass('ayah_end') }}" :disabled="!used" :required="used"></label>
                        <label class="col-span-2 sm:col-span-4"><span class="mb-1 block text-xs font-semibold">Catatan</span><input name="rows[{{ $i }}][notes]" value="{{ old("rows.$i.notes", $suggestion['notes'] ?? '') }}" maxlength="1000" class="pkg-field min-h-11" :disabled="!used"></label>
                    </div>
                </fieldset>
            @endfor
            <button class="btn-primary min-h-12 w-full justify-center">{{ ($isStudent || $isPublic) ? 'Kirim untuk Verifikasi Pamong' : 'Simpan sebagai Terverifikasi' }}</button>
        </form>
    </div>
</div>
@endsection
