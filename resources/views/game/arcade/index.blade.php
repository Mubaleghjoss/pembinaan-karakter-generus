@extends('layouts.public')

@section('title', 'Pecah Karakter - Game Arcade')

@section('content')
<div class="bg-slate-50 py-8 dark:bg-slate-950 sm:py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="pkg-page-header mb-6">
            <div>
                <h1 class="pkg-page-heading">Pecah Karakter</h1>
                <p class="pkg-page-subheading">Pecahkan balok berisi 29 karakter luhur. Main solo, lawan AI, atau tantang teman lewat kode.</p>
            </div>
            <div class="pkg-page-actions">
                @if($player)
                    <a href="{{ route('arcade.play') }}" class="btn-primary px-5 py-2.5 font-bold">Main Solo</a>
                @else
                    <a href="{{ route('arcade.play') }}" class="btn-primary px-5 py-2.5 font-bold">Coba Sekarang</a>
                @endif
            </div>
        </div>

        @if(session('error'))
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">{{ session('error') }}</div>
        @endif

        {{-- Info pemain --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 sm:p-5">
            @if($player)
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Kamu login sebagai
                    <span class="font-bold text-slate-900 dark:text-white">{{ $player[2] }}</span>
                    <span class="ml-1 inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $player[0] === 'staff' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                        {{ $player[0] === 'staff' ? 'Staff' : 'Siswa' }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Skor kamu masuk papan peringkat {{ $player[0] === 'staff' ? 'Staff (terpisah dari siswa)' : 'Siswa' }}.
                    Duel via kode tidak memengaruhi poin gamifikasi siswa.
                </p>
            @else
                <p class="text-sm text-slate-600 dark:text-slate-300">Kamu bisa mencoba tanpa login. <span class="font-semibold">Login</span> untuk menyimpan skor ke papan peringkat.</p>
            @endif
        </div>

        {{-- PvP via kode --}}
        @if($player)
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="font-bold text-slate-900 dark:text-white">Tantang Teman (PvP)</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Buat kode lalu bagikan. Siswa & pamong bisa saling tanding.</p>
                <button type="button" id="btnCreate" class="btn-primary mt-3 w-full justify-center px-4 py-2.5 font-bold">Buat Kode Tanding</button>
                <div id="createResult" class="mt-3 hidden rounded-lg bg-emerald-50 p-3 text-center dark:bg-emerald-900/30">
                    <p class="text-xs text-emerald-700 dark:text-emerald-300">Kode tanding kamu:</p>
                    <p id="createCode" class="text-2xl font-black tracking-widest text-emerald-800 dark:text-emerald-200"></p>
                    <a id="createStart" href="#" class="btn-success mt-2 inline-flex px-4 py-2 text-sm font-bold">Mulai &amp; Tunggu Lawan</a>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="font-bold text-slate-900 dark:text-white">Gabung dengan Kode</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Masukkan kode dari temanmu.</p>
                <div class="mt-3 flex gap-2">
                    <input type="text" id="joinCode" maxlength="6" placeholder="KODE" class="pkg-field w-full text-center text-lg font-black uppercase tracking-widest">
                    <button type="button" id="btnJoin" class="btn-primary shrink-0 px-4 py-2.5 font-bold">Gabung</button>
                </div>
                <p id="joinError" class="mt-2 hidden text-xs font-semibold text-red-600 dark:text-red-400"></p>
            </div>
        </div>
        @endif

        {{-- Leaderboard terpisah --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-3 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">S</span>
                    Papan Siswa
                </h2>
                @forelse($topSiswa as $i => $row)
                    <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0 dark:border-slate-800">
                        <span class="truncate"><span class="mr-2 font-bold text-slate-400">{{ $i + 1 }}.</span>{{ $row['name'] }}</span>
                        <span class="font-black text-emerald-600 dark:text-emerald-300">{{ number_format($row['score']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada skor.</p>
                @endforelse
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-3 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-purple-100 text-xs text-purple-700 dark:bg-purple-900/40 dark:text-purple-200">P</span>
                    Papan Staff (Admin &amp; Pamong)
                </h2>
                @forelse($topStaff as $i => $row)
                    <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0 dark:border-slate-800">
                        <span class="truncate"><span class="mr-2 font-bold text-slate-400">{{ $i + 1 }}.</span>{{ $row['name'] }}</span>
                        <span class="font-black text-purple-600 dark:text-purple-300">{{ number_format($row['score']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada skor.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($player)
@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const btnCreate = document.getElementById('btnCreate');
    const btnJoin = document.getElementById('btnJoin');

    btnCreate?.addEventListener('click', async () => {
        btnCreate.disabled = true;
        try {
            const res = await fetch(@json(route('arcade.match.create')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('createCode').textContent = data.code;
                document.getElementById('createResult').classList.remove('hidden');
                const url = new URL(@json(route('arcade.play')), window.location.origin);
                url.searchParams.set('match', data.code);
                url.searchParams.set('seed', data.seed);
                document.getElementById('createStart').href = url.toString();
            }
        } finally { btnCreate.disabled = false; }
    });

    btnJoin?.addEventListener('click', async () => {
        const code = document.getElementById('joinCode').value.trim().toUpperCase();
        const err = document.getElementById('joinError');
        err.classList.add('hidden');
        if (code.length < 4) { err.textContent = 'Masukkan kode yang valid.'; err.classList.remove('hidden'); return; }
        btnJoin.disabled = true;
        try {
            const res = await fetch(@json(route('arcade.match.join')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ code }),
            });
            const data = await res.json();
            if (data.success) {
                const url = new URL(@json(route('arcade.play')), window.location.origin);
                url.searchParams.set('match', code);
                url.searchParams.set('seed', data.seed);
                window.location.href = url.toString();
            } else {
                err.textContent = data.message || 'Gagal bergabung.'; err.classList.remove('hidden');
            }
        } finally { btnJoin.disabled = false; }
    });
})();
</script>
@endpush
@endif
@endsection
