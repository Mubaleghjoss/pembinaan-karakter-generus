@extends('layouts.public')

@section('title', 'Main Pecah Karakter')

@section('content')
<div class="bg-slate-100 py-4 dark:bg-slate-950 sm:py-6" x-data>
    <div class="mx-auto max-w-2xl px-3 sm:px-4">
        <div class="mb-3 flex items-center justify-between">
            <a href="{{ route('arcade.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400">&larr; Keluar</a>
            <div id="matchBanner" class="hidden rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200"></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-4">
            <div class="mb-2 flex items-center justify-between text-sm font-bold text-slate-700 dark:text-slate-200">
                <span>Skor: <span id="score" class="text-emerald-600 dark:text-emerald-400">0</span></span>
                <span>Nyawa: <span id="lives" class="text-rose-600 dark:text-rose-400">3</span></span>
                <span>Combo: <span id="combo" class="text-amber-600 dark:text-amber-400">0</span></span>
            </div>
            <canvas id="game" class="w-full rounded-xl bg-slate-900" style="touch-action:none;"></canvas>
            <p class="mt-2 text-center text-xs text-slate-500 dark:text-slate-400">
                Geser jari / mouse untuk gerakkan papan. Pantulkan bola untuk memecahkan balok karakter.
            </p>
        </div>

        {{-- Overlay start / hasil --}}
        <div id="overlay" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 text-center dark:border-slate-800 dark:bg-slate-900">
            <h2 id="ovTitle" class="text-lg font-black text-slate-900 dark:text-white">Siap Bermain?</h2>
            <p id="ovDesc" class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pecahkan semua balok karakter luhur. Jangan biarkan bola jatuh!</p>
            <div id="ovVs" class="mt-3 hidden text-sm"></div>
            <button id="btnStart" class="btn-primary mt-4 px-6 py-2.5 font-bold">Mulai</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const WORDS = @json($words);
    const SEED = @json($seed);
    const MATCH = @json($matchCode);
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const canPlay = WORDS.length >= 4;

    // Seeded RNG (mulberry32) supaya urutan kata sama untuk kedua pemain PvP.
    function hashSeed(str) { let h = 1779033703 ^ str.length; for (let i=0;i<str.length;i++){h=Math.imul(h^str.charCodeAt(i),3432918353);h=h<<13|h>>>19;} return h>>>0; }
    function mulberry32(a){return function(){a|=0;a=a+0x6D2B79F5|0;let t=Math.imul(a^a>>>15,1|a);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296;};}
    const rng = mulberry32(hashSeed(SEED));
    function shuffled(arr){const a=arr.slice();for(let i=a.length-1;i>0;i--){const j=Math.floor(rng()*(i+1));[a[i],a[j]]=[a[j],a[i]];}return a;}

    const canvas = document.getElementById('game');
    const ctx = canvas.getContext('2d');
    const scoreEl = document.getElementById('score');
    const livesEl = document.getElementById('lives');
    const comboEl = document.getElementById('combo');
    const overlay = document.getElementById('overlay');
    const ovTitle = document.getElementById('ovTitle');
    const ovDesc = document.getElementById('ovDesc');
    const ovVs = document.getElementById('ovVs');
    const btnStart = document.getElementById('btnStart');
    const matchBanner = document.getElementById('matchBanner');

    // Ukuran canvas responsif (rasio 3:4).
    let W = 0, H = 0, DPR = Math.min(window.devicePixelRatio || 1, 2);
    function resize() {
        const cssW = Math.min(canvas.parentElement.clientWidth, 560);
        const cssH = Math.round(cssW * 1.15);
        W = cssW; H = cssH;
        canvas.style.height = cssH + 'px';
        canvas.width = Math.round(cssW * DPR);
        canvas.height = Math.round(cssH * DPR);
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    }
    resize();
    window.addEventListener('resize', () => { resize(); if (!running) drawIdle(); });

    // State game
    let bricks = [], paddle, ball, score = 0, lives = 3, combo = 0, bestCombo = 0, running = false, raf = null, submitted = false;

    const COLORS = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#14b8a6','#ec4899','#6366f1'];

    function buildBricks() {
        bricks = [];
        const words = shuffled(WORDS).slice(0, Math.min(WORDS.length, 24));
        const cols = 3;
        const pad = 6;
        const bw = (W - pad * (cols + 1)) / cols;
        const bh = 30;
        const top = 44;
        words.forEach((w, i) => {
            const c = i % cols, r = Math.floor(i / cols);
            bricks.push({
                x: pad + c * (bw + pad),
                y: top + r * (bh + pad),
                w: bw, h: bh, word: w, alive: true,
                color: COLORS[(r) % COLORS.length],
            });
        });
    }

    function reset() {
        score = 0; lives = 3; combo = 0; bestCombo = 0; submitted = false;
        buildBricks();
        paddle = { w: Math.max(70, W * 0.22), h: 12, x: W / 2 - 40, y: H - 28 };
        resetBall();
        updateHud();
    }
    function resetBall() {
        ball = { x: W / 2, y: paddle.y - 12, r: 8, dx: (rng() < 0.5 ? -1 : 1) * (W * 0.006 + 2.2), dy: -(H * 0.008 + 2.6), stuck: true };
    }
    function updateHud() { scoreEl.textContent = score; livesEl.textContent = lives; comboEl.textContent = combo; }

    // Kontrol papan
    function movePaddleTo(clientX) {
        const rect = canvas.getBoundingClientRect();
        let x = (clientX - rect.left) - paddle.w / 2;
        x = Math.max(0, Math.min(W - paddle.w, x));
        paddle.x = x;
        if (ball.stuck) ball.x = paddle.x + paddle.w / 2;
    }
    canvas.addEventListener('mousemove', e => movePaddleTo(e.clientX));
    canvas.addEventListener('touchmove', e => { if (e.touches[0]) { movePaddleTo(e.touches[0].clientX); e.preventDefault(); } }, { passive: false });
    canvas.addEventListener('touchstart', e => { if (e.touches[0]) movePaddleTo(e.touches[0].clientX); ball.stuck = false; }, { passive: true });
    canvas.addEventListener('mousedown', () => { ball.stuck = false; });

    function drawIdle() {
        ctx.clearRect(0, 0, W, H);
        ctx.fillStyle = '#0f172a'; ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = '#64748b'; ctx.font = 'bold 16px system-ui'; ctx.textAlign = 'center';
        ctx.fillText('Tekan Mulai', W / 2, H / 2);
    }

    function tick() {
        ctx.clearRect(0, 0, W, H);
        ctx.fillStyle = '#0f172a'; ctx.fillRect(0, 0, W, H);

        // gerak bola
        if (!ball.stuck) { ball.x += ball.dx; ball.y += ball.dy; }
        if (ball.x - ball.r < 0) { ball.x = ball.r; ball.dx *= -1; }
        if (ball.x + ball.r > W) { ball.x = W - ball.r; ball.dx *= -1; }
        if (ball.y - ball.r < 0) { ball.y = ball.r; ball.dy *= -1; }

        // jatuh
        if (ball.y - ball.r > H) {
            lives--; combo = 0; updateHud();
            if (lives <= 0) { return endGame(false); }
            ball.stuck = true; ball.x = paddle.x + paddle.w / 2; ball.y = paddle.y - 12; ball.dy = -Math.abs(ball.dy);
        }

        // pantul papan
        if (ball.dy > 0 && ball.y + ball.r >= paddle.y && ball.x >= paddle.x && ball.x <= paddle.x + paddle.w) {
            ball.dy = -Math.abs(ball.dy);
            const hit = (ball.x - (paddle.x + paddle.w / 2)) / (paddle.w / 2);
            ball.dx = hit * (W * 0.008 + 3);
        }

        // tabrak balok
        for (const b of bricks) {
            if (!b.alive) continue;
            if (ball.x > b.x && ball.x < b.x + b.w && ball.y - ball.r < b.y + b.h && ball.y + ball.r > b.y) {
                b.alive = false; ball.dy *= -1;
                combo++; bestCombo = Math.max(bestCombo, combo);
                score += 10 + combo * 2; // combo bonus
                updateHud();
                break;
            }
        }

        // gambar balok
        for (const b of bricks) {
            if (!b.alive) continue;
            ctx.fillStyle = b.color; roundRect(b.x, b.y, b.w, b.h, 6); ctx.fill();
            ctx.fillStyle = '#fff'; ctx.font = 'bold 11px system-ui'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            const label = b.word.length > 12 ? b.word.slice(0, 11) + '…' : b.word;
            ctx.fillText(label, b.x + b.w / 2, b.y + b.h / 2);
        }
        // papan
        ctx.fillStyle = '#e2e8f0'; roundRect(paddle.x, paddle.y, paddle.w, paddle.h, 6); ctx.fill();
        // bola
        ctx.fillStyle = '#fbbf24'; ctx.beginPath(); ctx.arc(ball.x, ball.y, ball.r, 0, Math.PI * 2); ctx.fill();

        // menang bila semua balok habis
        if (bricks.every(b => !b.alive)) { score += lives * 50; return endGame(true); }

        raf = requestAnimationFrame(tick);
    }

    function roundRect(x, y, w, h, r) { ctx.beginPath(); ctx.moveTo(x+r,y); ctx.arcTo(x+w,y,x+w,y+h,r); ctx.arcTo(x+w,y+h,x,y+h,r); ctx.arcTo(x,y+h,x,y,r); ctx.arcTo(x,y,x+w,y,r); ctx.closePath(); }

    function startGame() {
        if (!canPlay) return;
        overlay.classList.add('hidden');
        reset();
        running = true;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(tick);
    }

    async function endGame(won) {
        running = false; cancelAnimationFrame(raf);
        updateHud();
        overlay.classList.remove('hidden');
        ovTitle.textContent = won ? 'Menang! 🎉' : 'Permainan Selesai';
        ovDesc.textContent = 'Skor akhir: ' + score + ' (combo terbaik ' + bestCombo + ')';
        btnStart.textContent = 'Main Lagi';

        if (submitted) return;
        submitted = true;

        if (MATCH) {
            // Submit ke match PvP.
            try {
                const res = await fetch(@json(route('arcade.match.submit', ['match' => '__CODE__'])).replace('__CODE__', MATCH), {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ score }),
                });
                const data = await res.json();
                if (data.success) pollMatch();
            } catch (e) {}
        } else {
            // Submit skor solo.
            try {
                const res = await fetch(@json(route('arcade.score')), {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ score, best_combo: bestCombo }),
                });
                const data = await res.json();
                if (data.saved === false) ovDesc.textContent += ' — login untuk menyimpan skor.';
            } catch (e) {}
        }
    }

    // PvP: tunggu lawan submit lalu tampilkan hasil.
    let pollTimer = null;
    async function pollMatch() {
        ovVs.classList.remove('hidden');
        ovVs.innerHTML = '<span class="text-slate-500">Menunggu lawan menyelesaikan…</span>';
        clearInterval(pollTimer);
        pollTimer = setInterval(async () => {
            try {
                const res = await fetch(@json(route('arcade.match.state', ['match' => '__CODE__'])).replace('__CODE__', MATCH), { headers: { 'Accept': 'application/json' } });
                const s = await res.json();
                if (s.status === 'finished') {
                    clearInterval(pollTimer);
                    const me = score;
                    let verdict = s.winner === 'draw' ? 'SERI' : '';
                    ovVs.innerHTML = '<div class="rounded-lg bg-slate-100 p-3 dark:bg-slate-800">' +
                        '<p class="font-bold text-slate-900 dark:text-white">' + (s.p1_name||'P1') + ': ' + (s.p1_score ?? '-') + '</p>' +
                        '<p class="font-bold text-slate-900 dark:text-white">' + (s.p2_name||'P2') + ': ' + (s.p2_score ?? '-') + '</p>' +
                        (verdict ? '<p class="mt-1 font-black text-amber-600">'+verdict+'</p>' : '') +
                        '</div>';
                }
            } catch (e) {}
        }, 2500);
    }

    btnStart.addEventListener('click', startGame);

    if (MATCH) {
        matchBanner.textContent = 'Duel kode: ' + MATCH;
        matchBanner.classList.remove('hidden');
        ovDesc.textContent = 'Mode tanding! Balok & urutan sama untuk kedua pemain. Skor tertinggi menang.';
    }
    if (!canPlay) { ovTitle.textContent = 'Belum bisa dimainkan'; ovDesc.textContent = 'Bank karakter belum cukup.'; btnStart.disabled = true; }
    drawIdle();
})();
</script>
@endpush
@endsection
