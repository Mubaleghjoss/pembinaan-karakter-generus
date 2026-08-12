@extends($isStudent ? 'layouts.siswa' : 'layouts.app')

@section('title', 'Konfirmasi Hasil Scan')

@section('content')
<div class="mx-auto max-w-6xl space-y-5 {{ $isStudent ? '' : 'px-4 py-4 sm:px-6 sm:py-6' }}">
    @php
        $suggestedRows = collect(json_decode($scan->metadata['ocr_suggestion'] ?? '[]', true) ?: [])->keyBy('row_number');
    @endphp
    <div class="pkg-page-header"><div><h1 class="pkg-page-heading">Konfirmasi hasil scan</h1><p class="pkg-page-subheading">Pilih baris yang terisi dan cocokkan semua angka dengan foto. Sistem tidak menyimpan dugaan OCR secara diam-diam.</p></div></div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif
    <div class="grid gap-5 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.2fr)]">
        <aside class="pkg-panel-lg lg:sticky lg:top-24 lg:self-start"><h2 class="font-bold">Foto sumber</h2><img src="{{ $isStudent ? route('siswa.quran.scan.image', $scan) : route('quran.scan.image', [$scan->siswa, $scan]) }}" alt="Foto lembar bacaan yang diunggah" class="mt-3 max-h-[70vh] w-full rounded-xl border border-gray-200 object-contain dark:border-gray-700"></aside>
        <form method="POST" action="{{ $isStudent ? route('siswa.quran.scan.confirm.store', $scan) : route('quran.scan.confirm.store', [$scan->siswa, $scan]) }}" class="space-y-3">@csrf
            @for($i = 1; $i <= 12; $i++)
                @php($suggestion = $suggestedRows->get($i, []))
                <fieldset class="pkg-panel p-4" x-data="{ used: {{ $i === 1 ? 'true' : 'false' }} }">
                    <div class="mb-3 flex items-center justify-between"><legend class="font-bold">Baris {{ $i }}</legend><label class="flex min-h-11 cursor-pointer items-center gap-2"><input type="checkbox" class="pkg-check" x-model="used"><span class="text-sm">Baris terisi</span></label></div>
                    <input type="hidden" name="rows[{{ $i }}][row_number]" value="{{ $i }}" :disabled="!used">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="col-span-2 sm:col-span-1"><span class="mb-1 block text-xs font-semibold">Tanggal</span><input type="date" name="rows[{{ $i }}][reading_date]" value="{{ $suggestion['reading_date'] ?? '' }}" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif></label>
                        <label><span class="mb-1 block text-xs font-semibold">Hal. awal</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][page_start]" value="{{ $suggestion['page_start'] ?? '' }}" min="1" max="1000" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif></label>
                        <label><span class="mb-1 block text-xs font-semibold">Hal. akhir</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][page_end]" value="{{ $suggestion['page_end'] ?? '' }}" min="1" max="1000" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif></label>
                        <label><span class="mb-1 block text-xs font-semibold">Surat awal</span><select name="rows[{{ $i }}][surah_start]" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif><option value="">Pilih</option>@foreach($surahOptions as $number=>$label)<option value="{{ $number }}">{{ $label }}</option>@endforeach</select></label>
                        <label><span class="mb-1 block text-xs font-semibold">Ayat awal</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][ayah_start]" min="1" max="286" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif></label>
                        <label><span class="mb-1 block text-xs font-semibold">Surat akhir</span><select name="rows[{{ $i }}][surah_end]" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif><option value="">Pilih</option>@foreach($surahOptions as $number=>$label)<option value="{{ $number }}">{{ $label }}</option>@endforeach</select></label>
                        <label><span class="mb-1 block text-xs font-semibold">Ayat akhir</span><input type="number" inputmode="numeric" name="rows[{{ $i }}][ayah_end]" min="1" max="286" class="pkg-field min-h-11" :disabled="!used" @if($i===1) required @endif></label>
                        <label class="col-span-2 sm:col-span-4"><span class="mb-1 block text-xs font-semibold">Catatan</span><input name="rows[{{ $i }}][notes]" maxlength="1000" class="pkg-field min-h-11" :disabled="!used"></label>
                    </div>
                </fieldset>
            @endfor
            <button class="btn-primary min-h-12 w-full justify-center">{{ $isStudent ? 'Kirim untuk Verifikasi Pamong' : 'Simpan sebagai Terverifikasi' }}</button>
        </form>
    </div>
</div>
@endsection
