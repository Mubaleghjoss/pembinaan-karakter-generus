@extends('layouts.siswa')

@section('title', $mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata')

@section('content')
<div class="mx-auto w-full max-w-2xl px-4 py-6 sm:px-6" x-data="soloGame()">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-black text-gray-900 dark:text-white">{{ $mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata' }} <span class="text-sm font-medium text-gray-400">(Latihan)</span></h1>
        <a href="{{ route('siswa.game.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400">Keluar</a>
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
                <div class="mb-3 min-h-[3rem] rounded-lg border-2 border-dashed border-gray-300 p-2 dark:border-gray-600">
                    <input type="text" x-model="typed" placeholder="Ketik / klik huruf jawaban..." class="w-full bg-transparent text-lg font-bold text-gray-900 outline-none dark:text-white">
                </div>
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
    </div>

    {{-- Hasil --}}
    <div x-show="finished" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-lg font-black text-gray-900 dark:text-white" x-text="`Benar ${result.correct}/${result.total}`"></p>
        <p class="mt-1 text-sm" :class="result.passed ? 'text-emerald-600' : 'text-amber-600'"
           x-text="result.passed ? `Lulus! +${result.points_awarded} poin` : 'Belum lulus (perlu 60% benar). Coba lagi!'"></p>
        <div class="mt-5 flex justify-center gap-2">
            <a href="{{ route('siswa.game.solo', $mode) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Main Lagi</a>
            <a href="{{ route('siswa.game.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Menu Game</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function soloGame() {
        return {
            mode: @json($mode),
            token: @json($token),
            questions: @json($questions),
            idx: 0,
            total: {{ count($questions) }},
            answers: [],
            typed: '',
            used: [],
            scramble: [],
            chosen: null,
            finished: false,
            result: {},
            init() { this.loadScramble(); },
            q() { return this.questions[this.idx] || {}; },
            loadScramble() {
                if (this.mode === 'rangkai') {
                    this.scramble = (this.q().scrambled || '').split('');
                    this.used = [];
                    this.typed = '';
                }
                this.chosen = null;
            },
            pick(i) { if (!this.used.includes(i)) { this.typed += this.scramble[i]; this.used.push(i); } },
            clearInput() { this.typed = ''; this.used = []; },
            choose(opt) { this.chosen = opt; },
            submitRound() {
                const ans = this.mode === 'tebak' ? (this.chosen || '') : this.typed.trim();
                if (!ans) return;
                this.answers[this.idx] = ans;
                if (this.idx + 1 < this.total) { this.idx++; this.loadScramble(); }
                else { this.finish(); }
            },
            async finish() {
                try {
                    const res = await fetch('{{ url('siswa/game/solo') }}/' + this.mode + '/submit', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                        body: JSON.stringify({token: this.token, answers: this.answers})
                    });
                    this.result = await res.json();
                    this.finished = true;
                } catch(e) { alert('Gagal menyimpan hasil. Coba lagi.'); }
            }
        };
    }
</script>
@endpush
@endsection
