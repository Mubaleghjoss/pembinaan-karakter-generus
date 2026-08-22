@extends('layouts.siswa')

@section('title', 'Game 29 Karakter')

@section('content')
<div class="mx-auto w-full max-w-3xl px-4 py-6 sm:px-6">
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-900 dark:text-white">Arena 29 Karakter Luhur</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Main sambil belajar. Menang duel dapat +10 poin, seri +1, kalah tetap +3. Poin masuk ke peringkat kamu.</p>
    </div>

    @if(session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">{{ session('error') }}</div>
    @endif

    @if($charCount < 4)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
            Bank karakter belum cukup ({{ $charCount }} aktif). Minta admin melengkapi Bank 29 Karakter dulu.
        </div>
    @else
    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Rangkai Kata --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/></svg>
                </span>
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-white">Rangkai Kata</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Susun huruf jadi nama karakter dari petunjuk.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('siswa.game.solo', 'rangkai') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Latihan Solo</a>
                <button type="button" onclick="openDuel('rangkai')" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Duel vs AI</button>
            </div>
        </div>

        {{-- Tebak Karakter --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-white">Tebak Karakter</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih karakter yang tepat dari studi kasus.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('siswa.game.solo', 'tebak') }}" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">Latihan Solo</a>
                <button type="button" onclick="openDuel('tebak')" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Duel vs AI</button>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Modal pilih kesulitan AI --}}
<div id="duelModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-5 dark:bg-gray-800">
        <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Duel vs AI</h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Pilih tingkat kesulitan lawan.</p>
        <form id="duelForm" method="POST">
            @csrf
            <div class="grid grid-cols-3 gap-2">
                <button name="difficulty" value="easy" class="rounded-lg border border-gray-200 py-2 text-sm font-semibold hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Mudah</button>
                <button name="difficulty" value="medium" class="rounded-lg border border-gray-200 py-2 text-sm font-semibold hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Sedang</button>
                <button name="difficulty" value="hard" class="rounded-lg border border-gray-200 py-2 text-sm font-semibold hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">Sulit</button>
            </div>
        </form>
        <button type="button" onclick="document.getElementById('duelModal').classList.add('hidden')" class="mt-4 w-full rounded-lg bg-gray-100 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Batal</button>
    </div>
</div>

@push('scripts')
<script>
    function openDuel(mode) {
        const form = document.getElementById('duelForm');
        form.action = '{{ url('siswa/game/duel/ai') }}/' + mode;
        const modal = document.getElementById('duelModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
</script>
@endpush
@endsection
