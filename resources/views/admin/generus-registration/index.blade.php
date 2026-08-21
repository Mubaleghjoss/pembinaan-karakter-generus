@extends('layouts.app')

@section('title', 'Daftar Ulang Generus')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="pkg-page-header">
        <div>
            <h1 class="pkg-page-heading">Daftar Ulang Generus</h1>
            <p class="pkg-page-subheading">Bagikan tautan daftar-ulang ke Orang Tua, pantau status tanda tangan surat pernyataan, dan unduh dokumennya.</p>
        </div>
    </div>

    @include('settings.partials.admin-tabs', ['tab' => 'daftarulang'])

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Generus</p>
            <p class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $totalCount }}</p>
        </div>
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Sudah Tanda Tangan</p>
            <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $signedCount }}</p>
        </div>
        <div class="pkg-card-soft rounded-2xl p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300">Belum</p>
            <p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-300">{{ $totalCount - $signedCount }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.generus-registration.index') }}" class="mb-5 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama Generus, Orang Tua, atau NIS..." class="pkg-field w-full max-w-md">
        <button type="submit" class="btn-primary px-5 py-2.5 font-bold">Cari</button>
        @if($search !== '')
            <a href="{{ route('admin.generus-registration.index') }}" class="btn-secondary px-5 py-2.5 font-bold">Reset</a>
        @endif
    </form>

    <div class="pkg-panel-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3">Generus</th>
                        <th class="px-4 py-3">WA Generus</th>
                        <th class="px-4 py-3">Orang Tua</th>
                        <th class="px-4 py-3">WA Orang Tua</th>
                        <th class="px-4 py-3">Status Surat</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($rows as $row)
                        @php $s = $row['siswa']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $s->nama }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">NIS {{ $s->nis }}@if($s->kelompok) · {{ \App\Models\Siswa::kelompokOptions()[$s->kelompok] ?? $s->kelompok }}@endif</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($row['student_wa'])
                                    <a href="{{ $row['student_wa'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-green-600 hover:text-green-700 dark:text-green-400">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ $s->phone }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-800 dark:text-gray-200">{{ $s->nama_wali ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($row['parent_wa'])
                                    <a href="{{ $row['parent_wa'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-green-600 hover:text-green-700 dark:text-green-400">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ $s->phone_wali }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($row['signed'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/></svg>
                                        Sudah TTD
                                    </span>
                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ optional($row['registration']->statement_accepted_at)->translatedFormat('d M Y') }}</p>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/50 dark:text-amber-200">Belum</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="btn-secondary !px-2.5 !py-1.5 text-xs" data-copy-link="{{ $row['direct_url'] }}">Salin Link</button>
                                    @php
                                        $waTarget = $row['parent_wa'] ?: $row['student_wa'];
                                        $waMsg = rawurlencode("Assalamu'alaikum. Mohon daftar ulang / lengkapi biodata dan tanda tangan surat pernyataan untuk ananda {$s->nama} melalui tautan berikut:\n{$row['direct_url']}\nTerima kasih.");
                                    @endphp
                                    @if($waTarget)
                                        <a href="{{ $waTarget }}?text={{ $waMsg }}" target="_blank" rel="noopener" class="btn-success !px-2.5 !py-1.5 text-xs">Kirim WA</a>
                                    @endif
                                    @if($row['preview_url'])
                                        <a href="{{ $row['preview_url'] }}" target="_blank" rel="noopener" class="btn-primary !px-2.5 !py-1.5 text-xs">Lihat Surat</a>
                                        <a href="{{ $row['download_url'] }}" class="btn-secondary !px-2.5 !py-1.5 text-xs">Unduh</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Tidak ada data Generus yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy-link]');
        if (!button) return;
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            const original = button.textContent;
            button.textContent = 'Tersalin';
            window.setTimeout(() => { button.textContent = original; }, 1500);
        } catch (e) {
            window.prompt('Salin tautan:', button.dataset.copyLink);
        }
    });
</script>
@endpush
@endsection
