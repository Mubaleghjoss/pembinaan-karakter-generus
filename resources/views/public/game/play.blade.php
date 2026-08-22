@extends('layouts.public')

@section('title', ($mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata') . ' (Coba)')

@section('content')
<div class="bg-slate-50 py-8 dark:bg-slate-950 sm:py-10" x-data="guestGame()">
    <div class="mx-auto max-w-2xl px-4 sm:px-6">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-black text-gray-900 dark:text-white">{{ $mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata' }} <span class="text-sm font-medium text-gray-400">(Mode Coba)</span></h1>
            <a href="{{ route('public.rpg.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400">Keluar</a>
        </div>

        {{-- Progress --}}
        <div class="mb-4 flex items-center gap-2">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-full bg-blue-600 transition-all" :style="`width: ${(idx/total)*100}%`"></div>
            </div>
            <span class="text-xs font-bold text-gray-500" x-text="`${Math.min(idx+1,total)}/${total}`"></span>
        </div>

        {{-- Kartu soal --}}
        <div x-show="!finished" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <template x-if="mode==='rangkai'">
                <div>
                    <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">Petunjuk</p>
                    <p class="mb-4 text-gray-800 dark:text-gray-200" x-text="q().clue"></p>
                    <p class="mb-2 text-xs text-gray-500">Panjang jawaban: <span x-text="(q().word_lengths||[]).join(' + ')"></span> huruf</p>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <template x-for="(ltr,i) in scramble" :key="i">
                            <button type="button" @click="pick(i)" :disabled="used.includes(i)"
                                class="flex h-10 w-10 items-center justify-center rounded-lg border-2 border-blue-200 bg-blue-50 text-lg font-bold text-blue-800 disabled:opacity-30 dark:border-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                                x-text="ltr"></button>
                        </template>
                    </div>
                    <input type="text" x-model="typed" placeholder="Ketik / klik huruf jawaban..." class="mb-3 w-full rounded-lg border-2 border-dashed border-gray-300 bg-transparent p-2 text-lg font-bold text-gray-900 outline-none dark:border-gray-600 dark:text-white">
                    <div class="flex gap-2">
                        <button type="button" @click="clearInput()" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Hapus</button>
                        <button type="button" @click="submitRound()" class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white hover:bg-blue-700">Jawab</button>
                    </div>
                </div>
            </template>

            <template x-if="mode==='tebak'">
                <div>
                    <p class="mb-1 text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Studi Kasus</p>
                    <p class="mb-4 text-gray-800 dark:text-gray-200" x-text="q().prompt"></p>
                    <div class="grid gap-2">
                        <template x-for="(opt,i) in q().options" :key="i">
                            <button type="button" @click="choose(opt)"
                                :class="chosen===opt ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/40' : 'border-gray-200 dark:border-gray-600'"
                                class="rounded-lg border-2 px-4 py-3 text-left text-sm font-semibold text-gray-800 hover:border-emerald-400 dark:text-gray-200"
                                x-text="opt"></button>
                        </template>
                    </div>
                    <button type="button" @click="submitRound()" :disabled="!chosen" class="mt-4 w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-40">Jawab</button>
                </div>
            </template>

            <p x-show="feedback" x-cloak class="mt-3 text-center text-sm font-bold" :class="lastCorrect ? 'text-emerald-600' : 'text-rose-600'" x-text="feedback"></p>
        </div>

        {{-- Hasil --}}
        <div x-show="finished" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-lg font-black text-gray-900 dark:text-white" x-text="`Benar ${correct}/${total}`"></p>
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Ini mode coba, poin belum dihitung.</p>
                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">Login sebagai siswa untuk mengumpulkan poin, duel, dan naik peringkat.</p>
                <a href="{{ route('siswa.login') }}" class="mt-3 inline-block rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white hover:bg-amber-700">Login Siswa</a>
            </div>
            <div class="mt-4 flex justify-center gap-2">
                <a href="{{ route('public.game.play', $mode) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Coba Lagi</a>
                <a href="{{ route('public.rpg.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Menu Game</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function guestGame() {
        return {
            mode: @json($mode),
            questions: @json($questions),
            idx: 0,
            total: {{ count($questions) }},
            typed: '', used: [], scramble: [], chosen: null,
            correct: 0, finished: false, feedback: '', lastCorrect: false,
            init() { this.loadRound(); },
            q() { return this.questions[this.idx] || {}; },
            canonical(s) { return (s||'').toString().toLowerCase().replace(/\s+/g,' ').trim(); },
            loadRound() {
                if (this.mode === 'rangkai') { this.scramble = (this.q().scrambled || '').split(''); this.used = []; this.typed = ''; }
                this.chosen = null; this.feedback = '';
            },
            pick(i) { if (!this.used.includes(i)) { this.typed += this.scramble[i]; this.used.push(i); } },
            clearInput() { this.typed = ''; this.used = []; },
            choose(opt) { this.chosen = opt; },
            submitRound() {
                const ans = this.mode === 'tebak' ? (this.chosen || '') : this.typed.trim();
                if (!ans) return;
                const ok = this.canonical(ans) === this.canonical(this.q().answer);
                this.lastCorrect = ok;
                if (ok) this.correct++;
                this.feedback = ok ? 'Benar!' : ('Kurang tepat. Jawaban: ' + this.q().answer);
                setTimeout(() => {
                    if (this.idx + 1 < this.total) { this.idx++; this.loadRound(); }
                    else { this.finished = true; }
                }, 1200);
            }
        };
    }
</script>
@endpush
@endsection
