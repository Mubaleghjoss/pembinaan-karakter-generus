@extends('layouts.siswa')

@section('title', 'Boss Online')

@section('content')
<div class="mx-auto w-full max-w-2xl px-4 py-6 sm:px-6" x-data="bossArena()">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-black text-gray-900 dark:text-white">Boss Online</h1>
        <a href="{{ route('siswa.game.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400">Keluar</a>
    </div>

    @if(! $boss)
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <span class="text-5xl">🐉</span>
            <p class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Belum ada Boss aktif</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tunggu admin memulai pertarungan Boss. Kalahkan bersama teman-teman untuk poin!</p>
            <a href="{{ route('siswa.game.index') }}" class="mt-5 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Kembali ke Menu Game</a>
        </div>
    @else
        {{-- Boss card + HP bar --}}
        <div class="mb-4 rounded-2xl border-2 border-rose-300 bg-gradient-to-br from-rose-50 to-red-100 p-5 text-center dark:border-rose-800 dark:from-rose-950/50 dark:to-red-950/40">
            <span class="text-5xl">👹</span>
            <h2 class="mt-2 text-lg font-black text-rose-800 dark:text-rose-200">{{ $boss->nama }}</h2>
            @if($boss->deskripsi)<p class="text-xs text-rose-600 dark:text-rose-300">{{ $boss->deskripsi }}</p>@endif
            <div class="mt-3">
                <div class="h-4 w-full overflow-hidden rounded-full bg-rose-200 dark:bg-rose-900/50">
                    <div class="h-full bg-gradient-to-r from-red-500 to-rose-600 transition-all duration-500" :style="`width: ${hpPercent}%`"></div>
                </div>
                <p class="mt-1 text-xs font-bold text-rose-700 dark:text-rose-300"><span x-text="currentHp"></span> / {{ $boss->max_hp }} HP</p>
            </div>
        </div>

        {{-- Arena serang --}}
        <div x-show="!defeated" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="mb-3 text-center text-xs font-bold uppercase tracking-wide text-rose-600 dark:text-rose-400">Jawab benar untuk menyerang! (-10 HP)</p>
            <template x-if="mode==='rangkai'">
                <div>
                    <p class="mb-3 text-gray-800 dark:text-gray-200" x-text="q().clue"></p>
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
                        <button type="button" @click="attack()" :disabled="locked" class="flex-1 rounded-lg bg-rose-600 px-3 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-40">Serang!</button>
                    </div>
                </div>
            </template>
            <template x-if="mode==='tebak'">
                <div>
                    <p class="mb-3 text-gray-800 dark:text-gray-200" x-text="q().prompt"></p>
                    <div class="grid gap-2">
                        <template x-for="(opt,i) in q().options" :key="i">
                            <button type="button" @click="chosen=opt"
                                :class="chosen===opt ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/40' : 'border-gray-200 dark:border-gray-600'"
                                class="rounded-lg border-2 px-4 py-3 text-left text-sm font-semibold text-gray-800 hover:border-rose-400 dark:text-gray-200"
                                x-text="opt"></button>
                        </template>
                    </div>
                    <button type="button" @click="attack()" :disabled="!chosen || locked" class="mt-4 w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-40">Serang!</button>
                </div>
            </template>
            <p x-show="feedback" x-cloak class="mt-3 text-center text-sm font-bold" :class="lastCorrect ? 'text-emerald-600' : 'text-gray-500'" x-text="feedback"></p>
            <p class="mt-2 text-center text-xs text-gray-500">Damage kamu: <span class="font-bold text-rose-600" x-text="myDamage"></span></p>
        </div>

        {{-- Boss kalah --}}
        <div x-show="defeated" x-cloak class="rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-6 text-center dark:border-emerald-800 dark:bg-emerald-900/30">
            <span class="text-5xl">🎉</span>
            <p class="mt-3 text-xl font-black text-emerald-700 dark:text-emerald-300">Boss Dikalahkan!</p>
            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">Poin dibagikan ke semua kontributor. Cek Poin & Peringkat.</p>
            <a href="{{ route('siswa.gamification.dashboard') }}" class="mt-4 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Lihat Peringkat</a>
        </div>

        {{-- Leaderboard kontribusi --}}
        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="mb-2 text-sm font-bold text-gray-900 dark:text-white">Kontributor Terbanyak</p>
            <div class="space-y-1">
                @forelse($topHitters as $i => $hit)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700 dark:text-gray-300">{{ $i + 1 }}. {{ $hit->siswa->nama ?? 'Siswa' }}</span>
                        <span class="font-bold text-rose-600">{{ $hit->damage }} dmg</span>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">Belum ada serangan. Jadilah yang pertama!</p>
                @endforelse
            </div>
        </div>
    @endif
</div>

@if($boss)
@push('scripts')
<script>
    function bossArena() {
        return {
            mode: @json($boss->mode),
            bossId: {{ $boss->id }},
            questions: @json($questions),
            idx: 0,
            total: {{ count($questions) }},
            typed: '', used: [], scramble: [], chosen: null,
            currentHp: {{ max(0, $boss->current_hp) }},
            hpPercent: {{ $boss->hpPercent() }},
            myDamage: {{ (int) ($myHit->damage ?? 0) }},
            locked: false, defeated: {{ $boss->status === 'defeated' ? 'true' : 'false' }},
            feedback: '', lastCorrect: false,
            _poll: null,
            init() {
                this.loadRound();
                // Poll HP boss tiap 3s agar sinkron dengan pemain lain.
                this._poll = setInterval(() => this.syncHp(), 3000);
            },
            q() { return this.questions[this.idx] || {}; },
            loadRound() {
                if (this.mode === 'rangkai') { this.scramble = (this.q().scrambled || '').split(''); this.used = []; this.typed = ''; }
                this.chosen = null; this.feedback = ''; this.locked = false;
            },
            nextRound() {
                this.idx = (this.idx + 1) % this.total;
                this.loadRound();
            },
            pick(i) { if (!this.used.includes(i)) { this.typed += this.scramble[i]; this.used.push(i); } },
            clearInput() { this.typed = ''; this.used = []; },
            async syncHp() {
                if (this.defeated) return;
                try {
                    const res = await fetch('{{ url('siswa/game/boss') }}/' + this.bossId + '/state', {headers:{'Accept':'application/json'}});
                    const d = await res.json();
                    this.currentHp = d.current_hp; this.hpPercent = d.hp_percent;
                    if (d.status === 'defeated') { this.defeated = true; clearInterval(this._poll); }
                } catch(e) {}
            },
            async attack() {
                if (this.locked) return;
                const ans = this.mode === 'tebak' ? (this.chosen || '') : this.typed.trim();
                if (!ans) return;
                this.locked = true;
                try {
                    const res = await fetch('{{ url('siswa/game/boss') }}/' + this.bossId + '/attack', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                        body: JSON.stringify({round: this.idx, answer: ans})
                    });
                    const d = await res.json();
                    if (!d.success) { this.feedback = d.message || 'Gagal'; this.locked = false; return; }
                    this.lastCorrect = d.correct;
                    this.currentHp = d.current_hp; this.hpPercent = d.hp_percent;
                    if (d.correct) { this.myDamage += d.damage; this.feedback = 'Kena! -' + d.damage + ' HP'; }
                    else { this.feedback = 'Meleset! Jawaban: ' + d.answer_key; }
                    if (d.defeated) { this.defeated = true; clearInterval(this._poll); return; }
                    setTimeout(() => this.nextRound(), 1000);
                } catch(e) { this.feedback = 'Koneksi bermasalah.'; this.locked = false; }
            }
        };
    }
</script>
@endpush
@endif
@endsection
