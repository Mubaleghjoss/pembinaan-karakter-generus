@extends('layouts.siswa')

@section('title', 'Duel ' . ($duel->mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata'))

@section('content')
<div class="mx-auto w-full max-w-2xl px-4 py-6 sm:px-6" x-data="duelGame()">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-black text-gray-900 dark:text-white">Duel {{ $duel->mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata' }}</h1>
        <a href="{{ route('siswa.game.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400">Keluar</a>
    </div>

    {{-- Ruang tunggu PvP (menunggu lawan gabung) --}}
    @if($duel->opponent_type === 'pvp' && $duel->status === 'waiting' && $isP1)
    <div x-show="waiting" class="rounded-2xl border-2 border-indigo-200 bg-indigo-50 p-6 text-center dark:border-indigo-800 dark:bg-indigo-900/30">
        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-200">Bagikan kode ini ke temanmu:</p>
        <p class="my-3 text-4xl font-black tracking-widest text-indigo-800 dark:text-indigo-100">{{ $duel->join_code }}</p>
        <p class="text-xs text-indigo-500 dark:text-indigo-300">Menunggu lawan bergabung...</p>
        <div class="mt-3 flex justify-center">
            <span class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-indigo-300 border-t-indigo-600"></span>
        </div>
    </div>
    @endif

    <div x-show="!waiting">
    {{-- Skor --}}
    <div class="mb-4 grid grid-cols-2 gap-3">
        <div class="rounded-xl border-2 border-blue-200 bg-blue-50 p-3 text-center dark:border-blue-800 dark:bg-blue-900/30">
            <p class="text-xs font-bold text-blue-600 dark:text-blue-300">Kamu</p>
            <p class="text-2xl font-black text-blue-800 dark:text-blue-200" x-text="p1"></p>
        </div>
        <div class="rounded-xl border-2 border-rose-200 bg-rose-50 p-3 text-center dark:border-rose-800 dark:bg-rose-900/30">
            <p class="text-xs font-bold text-rose-600 dark:text-rose-300">{{ $duel->opponent_type === 'ai' ? 'AI ('.$duel->ai_difficulty.')' : 'Lawan' }}</p>
            <p class="text-2xl font-black text-rose-800 dark:text-rose-200" x-text="p2"></p>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-2">
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div class="h-full bg-emerald-600 transition-all" :style="`width: ${(idx/total)*100}%`"></div>
        </div>
        <span class="text-xs font-bold text-gray-500" x-text="`Ronde ${Math.min(idx+1,total)}/${total}`"></span>
        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300" x-text="`${(timer/1000).toFixed(1)}s`"></span>
    </div>

    {{-- Soal --}}
    <div x-show="!finished" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <template x-if="mode==='rangkai'">
            <div>
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">Petunjuk</p>
                <p class="mb-4 text-gray-800 dark:text-gray-200" x-text="q().clue"></p>
                <p class="mb-2 text-xs text-gray-500">Panjang: <span x-text="(q().word_lengths||[]).join(' + ')"></span> huruf</p>
                <div class="mb-3 flex flex-wrap gap-2">
                    <template x-for="(ltr,i) in scramble" :key="i">
                        <button type="button" @click="pick(i)" :disabled="used.includes(i)"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border-2 border-blue-200 bg-blue-50 text-lg font-bold text-blue-800 disabled:opacity-30 dark:border-blue-800 dark:bg-blue-900/40 dark:text-blue-200"
                            x-text="ltr"></button>
                    </template>
                </div>
                <input type="text" x-model="typed" placeholder="Jawaban..." class="mb-3 w-full rounded-lg border-2 border-dashed border-gray-300 bg-transparent p-2 text-lg font-bold text-gray-900 outline-none dark:border-gray-600 dark:text-white">
                <div class="flex gap-2">
                    <button type="button" @click="clearInput()" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Hapus</button>
                    <button type="button" @click="submitRound()" :disabled="locked" class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-40">Kirim Jawaban</button>
                </div>
            </div>
        </template>

        <template x-if="mode==='tebak'">
            <div>
                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Studi Kasus</p>
                <p class="mb-4 text-gray-800 dark:text-gray-200" x-text="q().prompt"></p>
                <div class="grid gap-2">
                    <template x-for="(opt,i) in q().options" :key="i">
                        <button type="button" @click="chosen=opt"
                            :class="chosen===opt ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/40' : 'border-gray-200 dark:border-gray-600'"
                            class="rounded-lg border-2 px-4 py-3 text-left text-sm font-semibold text-gray-800 hover:border-emerald-400 dark:text-gray-200"
                            x-text="opt"></button>
                    </template>
                </div>
                <button type="button" @click="submitRound()" :disabled="!chosen || locked" class="mt-4 w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-40">Kirim Jawaban</button>
            </div>
        </template>

        <p x-show="feedback" x-cloak class="mt-3 text-center text-sm font-bold" :class="lastCorrect ? 'text-emerald-600' : 'text-rose-600'" x-text="feedback"></p>
    </div>

    {{-- Hasil akhir --}}
    <div x-show="finished" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-2xl font-black" :class="outcomeClass" x-text="outcomeText"></p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="`Skor akhir ${p1} - ${p2}`"></p>
        <p class="mt-2 text-lg font-bold text-blue-600 dark:text-blue-400" x-show="pointsText" x-text="pointsText"></p>
        <div class="mt-5 flex justify-center gap-2">
            <a href="{{ route('siswa.game.index') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Menu Game</a>
        </div>
    </div>
    </div>{{-- /x-show !waiting --}}
</div>

@push('scripts')
<script>
    function duelGame() {
        return {
            mode: @json($duel->mode),
            duelId: {{ $duel->id }},
            questions: @json($questions),
            opponentType: @json($duel->opponent_type),
            isP1: @json($isP1),
            waiting: @json($duel->opponent_type === 'pvp' && $duel->status === 'waiting' && $isP1),
            idx: 0,
            total: {{ count($questions) }},
            typed: '',
            used: [],
            scramble: [],
            chosen: null,
            p1: {{ $duel->p1_score }},
            p2: {{ $duel->p2_score }},
            locked: false,
            finished: false,
            feedback: '',
            lastCorrect: false,
            outcomeText: '',
            outcomeClass: '',
            pointsText: '',
            timer: 0,
            _t0: 0,
            _interval: null,
            _pollInterval: null,
            init() {
                if (this.waiting) { this.startWaitingPoll(); }
                else { this.loadRound(); }
            },
            startWaitingPoll() {
                // Poll ringan tiap 2.5s: cek apakah lawan sudah gabung (status active).
                this._pollInterval = setInterval(async () => {
                    try {
                        const res = await fetch('{{ url('siswa/game/duel') }}/' + this.duelId + '/state', {headers:{'Accept':'application/json'}});
                        const data = await res.json();
                        if (data.status === 'active') {
                            clearInterval(this._pollInterval);
                            this.waiting = false;
                            this.loadRound();
                        }
                    } catch(e) {}
                }, 2500);
            },
            q() { return this.questions[this.idx] || {}; },
            loadRound() {
                if (this.mode === 'rangkai') {
                    this.scramble = (this.q().scrambled || '').split('');
                    this.used = []; this.typed = '';
                }
                this.chosen = null;
                this.feedback = '';
                this.locked = false;
                this._t0 = Date.now();
                this.timer = 0;
                clearInterval(this._interval);
                this._interval = setInterval(() => { this.timer = Date.now() - this._t0; }, 100);
            },
            pick(i) { if (!this.used.includes(i)) { this.typed += this.scramble[i]; this.used.push(i); } },
            clearInput() { this.typed = ''; this.used = []; },
            async submitRound() {
                if (this.locked) return;
                const ans = this.mode === 'tebak' ? (this.chosen || '') : this.typed.trim();
                if (!ans) return;
                this.locked = true;
                clearInterval(this._interval);
                const ms = Date.now() - this._t0;
                try {
                    const res = await fetch('{{ url('siswa/game/duel') }}/' + this.duelId + '/answer', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                        body: JSON.stringify({round: this.idx, answer: ans, ms: ms})
                    });
                    const data = await res.json();
                    if (!data.success) { this.feedback = data.message || 'Gagal'; this.locked = false; return; }
                    this.lastCorrect = data.round_correct;
                    this.feedback = data.round_correct ? 'Benar!' : ('Kurang tepat. Jawaban: ' + data.answer_key);
                    this.p1 = data.p1_score; this.p2 = data.p2_score;
                    setTimeout(() => {
                        if (data.finished) { this.showResult(data.result); }
                        else if (this.idx + 1 < this.total) { this.idx++; this.loadRound(); }
                        else { this.finished = true; }
                    }, 1200);
                } catch(e) { this.feedback = 'Koneksi bermasalah.'; this.locked = false; }
            },
            showResult(result) {
                this.finished = true;
                if (!result) return;
                if (result.winner === 'p1') { this.outcomeText = 'MENANG!'; this.outcomeClass = 'text-emerald-600'; }
                else if (result.winner === 'draw') { this.outcomeText = 'SERI'; this.outcomeClass = 'text-amber-600'; }
                else { this.outcomeText = 'KALAH'; this.outcomeClass = 'text-rose-600'; }
                if (result.p1_points) this.pointsText = '+' + result.p1_points + ' poin';
            }
        };
    }
</script>
@endpush
@endsection
