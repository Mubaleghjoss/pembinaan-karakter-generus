@php($item = $entry ?? null)
<div class="pkg-filter-grid">
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Tanggal baca</span>
        <input type="date" name="reading_date" value="{{ old('reading_date', $item?->reading_date?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="pkg-field min-h-11" required>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Halaman awal</span>
        <input type="number" inputmode="numeric" name="page_start" value="{{ old('page_start', $item?->page_start) }}" min="1" max="1000" class="pkg-field min-h-11" required>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Halaman akhir</span>
        <input type="number" inputmode="numeric" name="page_end" value="{{ old('page_end', $item?->page_end) }}" min="1" max="1000" class="pkg-field min-h-11" required>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Surat awal</span>
        <select name="surah_start" class="pkg-field min-h-11" required>
            <option value="">Pilih surat</option>
            @foreach($surahOptions as $number => $label)
                <option value="{{ $number }}" @selected((int) old('surah_start', $item?->surah_start) === $number)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Ayat awal</span>
        <input type="number" inputmode="numeric" name="ayah_start" value="{{ old('ayah_start', $item?->ayah_start) }}" min="1" max="286" class="pkg-field min-h-11" required>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Surat akhir</span>
        <select name="surah_end" class="pkg-field min-h-11" required>
            <option value="">Pilih surat</option>
            @foreach($surahOptions as $number => $label)
                <option value="{{ $number }}" @selected((int) old('surah_end', $item?->surah_end) === $number)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Ayat akhir</span>
        <input type="number" inputmode="numeric" name="ayah_end" value="{{ old('ayah_end', $item?->ayah_end) }}" min="1" max="286" class="pkg-field min-h-11" required>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-sm font-medium">Label mushaf <span class="font-normal text-gray-500">(opsional)</span></span>
        <input type="text" name="mushaf_label" value="{{ old('mushaf_label', $item?->mushaf_label) }}" maxlength="100" placeholder="Contoh: Mushaf Madinah" class="pkg-field min-h-11">
    </label>
</div>
<label class="mt-4 block">
    <span class="mb-1.5 block text-sm font-medium">Catatan <span class="font-normal text-gray-500">(opsional)</span></span>
    <textarea name="notes" rows="3" maxlength="1000" class="pkg-field" placeholder="Tajwid, kelancaran, atau catatan pendampingan">{{ old('notes', $item?->notes) }}</textarea>
</label>
