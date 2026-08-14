@php($completed = collect($khatam['completed_surahs'] ?? []))
<section class="pkg-panel-lg space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><h2 class="text-lg font-bold">Peta Khatam dan Referensi 114 Surat</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PDF baru dipakai sebagai referensi dan checklist manual. Progres digital lama tetap tersimpan pada siklus {{ $khatam['cycle']?->cycle_number ?? 1 }}.</p></div>
        @if($downloadUrl ?? null)<a href="{{ $downloadUrl }}" class="btn-primary min-h-11 shrink-0 justify-center">Unduh Referensi A4</a>@endif
    </div>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="pkg-card-soft p-3"><p class="text-xs text-gray-500 dark:text-gray-400">Surat selesai</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ $khatam['completed_count'] ?? 0 }}<span class="text-sm font-normal">/114</span></p></div>
        <div class="pkg-card-soft p-3"><p class="text-xs text-gray-500 dark:text-gray-400">Progres</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ $khatam['percentage'] ?? 0 }}%</p></div>
        <div class="pkg-card-soft col-span-2 p-3"><p class="text-xs text-gray-500 dark:text-gray-400">Posisi aktif</p><p class="mt-1 font-bold">@if($khatam['active_surah']){{ $khatam['active_surah'] }}. {{ \App\Support\QuranCatalog::name($khatam['active_surah']) }} ayat {{ $khatam['active_ayah'] }}@else Belum dicatat @endif</p></div>
    </div>
    <div class="h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-valuenow="{{ $khatam['completed_count'] ?? 0 }}" aria-valuemin="0" aria-valuemax="114"><div class="h-full rounded-full bg-emerald-500" style="width:{{ $khatam['percentage'] ?? 0 }}%"></div></div>
    <details class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
        <summary class="min-h-11 cursor-pointer py-2 font-semibold">Lihat rincian 114 surat</summary>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @for($number=1;$number<=114;$number++)
                <div class="flex min-h-11 items-center gap-2 rounded-lg border px-3 py-2 {{ $completed->contains($number) ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30' : 'border-gray-200 dark:border-gray-700' }}">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $completed->contains($number) ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-400' }}" aria-hidden="true">@if($completed->contains($number))<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 12 4 4L19 6" /></svg>@endif</span>
                    <span class="min-w-0 text-sm"><strong>{{ $number }}.</strong> {{ \App\Support\QuranCatalog::name($number) }} <span class="text-xs text-gray-500">({{ \App\Support\QuranCatalog::ayahCount($number) }})</span></span>
                </div>
            @endfor
        </div>
    </details>
    @if(($cycleHistory ?? collect())->isNotEmpty())
        <div><h3 class="font-semibold">Riwayat siklus</h3><div class="mt-2 flex flex-wrap gap-2">@foreach($cycleHistory as $cycle)<span class="rounded-full border border-gray-200 px-3 py-1.5 text-xs dark:border-gray-700">Siklus {{ $cycle->cycle_number }} &middot; {{ $cycle->status === 'completed' ? 'Selesai '.$cycle->completed_at?->format('d/m/Y') : 'Berjalan' }}</span>@endforeach</div></div>
    @endif
    @if(($correctionRoute ?? null) && $khatam['cycle'])
        <details class="rounded-xl border border-gray-200 p-3 dark:border-gray-700"><summary class="min-h-11 cursor-pointer py-2 font-semibold">Koreksi manual oleh Pamong/Admin</summary>
            <form method="POST" action="{{ $correctionRoute }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')<input type="hidden" name="cycle_id" value="{{ $khatam['cycle']->id }}">
                <label><span class="mb-1 block text-xs font-semibold">Surat</span><select class="pkg-field min-h-11" name="surah_number" required>@foreach(\App\Support\QuranCatalog::options() as $number => $label)<option value="{{ $number }}">{{ $label }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-semibold">Kondisi</span><select class="pkg-field min-h-11" name="state" required><option value="completed">Tandai selesai</option><option value="active">Atur ayat aktif</option><option value="reset">Kosongkan progres surat</option></select></label>
                <label><span class="mb-1 block text-xs font-semibold">Ayat aktif</span><input class="pkg-field min-h-11" type="number" inputmode="numeric" min="0" max="286" name="last_ayah" placeholder="Diisi untuk kondisi aktif"></label>
                <label><span class="mb-1 block text-xs font-semibold">Alasan koreksi</span><input class="pkg-field min-h-11" name="reason" minlength="3" maxlength="1000" required placeholder="Contoh: salah baca tanda scan"></label>
                <button class="btn-primary min-h-11 justify-center sm:col-span-2">Simpan Koreksi</button>
            </form>
        </details>
    @endif
</section>
