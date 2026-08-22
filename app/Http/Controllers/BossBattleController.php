<?php

namespace App\Http\Controllers;

use App\Models\BossBattle;
use App\Models\BossHit;
use App\Models\Siswa;
use App\Services\GamificationService;
use App\Services\KarakterGameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Boss Online — satu "sifat buruk" ber-HP besar dikeroyok banyak siswa.
 * Tiap jawaban benar mengurangi HP boss (damage kolektif).
 * Hemat hosting: polling ringan (SELECT 1 row), damage ditulis atomik via DB.
 */
class BossBattleController extends Controller
{
    public function __construct(
        private readonly KarakterGameService $gameService,
        private readonly GamificationService $gamification
    ) {}

    private function siswa(): Siswa
    {
        return Auth::guard('siswa')->user();
    }

    // ===================== SISWA =====================

    /**
     * Arena boss: tampilkan boss aktif (kalau ada).
     */
    public function arena()
    {
        $boss = BossBattle::where('status', 'active')->where('current_hp', '>', 0)->latest('id')->first();

        $questions = [];
        $topHitters = collect();
        $myHit = null;

        if ($boss) {
            $questions = $this->gameService->buildQuestions($boss->mode, 8);
            $topHitters = BossHit::where('boss_battle_id', $boss->id)
                ->with('siswa:id,nama')
                ->orderByDesc('damage')
                ->limit(10)
                ->get();
            $myHit = BossHit::where('boss_battle_id', $boss->id)
                ->where('siswa_id', $this->siswa()->id)
                ->first();
        }

        // Kirim soal tanpa jawaban.
        $clientQuestions = array_map(function ($q) use ($boss) {
            if (! $boss) {
                return $q;
            }
            if ($boss->mode === 'tebak') {
                return ['prompt' => $q['prompt'], 'options' => $q['options']];
            }
            return [
                'clue' => $q['clue'],
                'scrambled' => $q['scrambled'],
                'word_lengths' => $q['word_lengths'],
                'hint_arab' => $q['hint_arab'] ?? null,
            ];
        }, $questions);

        // Simpan kunci jawaban di sesi agar penilaian di server.
        if ($boss) {
            session(['boss_questions.'.$boss->id => $questions]);
        }

        return view('siswa.game.boss', [
            'boss' => $boss,
            'questions' => $clientQuestions,
            'topHitters' => $topHitters,
            'myHit' => $myHit,
        ]);
    }

    /**
     * Serang boss: nilai satu jawaban, kurangi HP kolektif bila benar.
     */
    public function attack(Request $request, BossBattle $boss)
    {
        if (! $boss->isActive()) {
            return response()->json(['success' => false, 'message' => 'Boss sudah dikalahkan atau tidak aktif.'], 422);
        }

        $round = (int) $request->input('round');
        $answer = trim((string) $request->input('answer', ''));

        $questions = session('boss_questions.'.$boss->id, []);
        if (! isset($questions[$round])) {
            return response()->json(['success' => false, 'message' => 'Soal tidak valid.'], 422);
        }

        $q = $questions[$round];
        $correct = $boss->mode === 'tebak'
            ? $this->gameService->checkTebak($q['answer'], $answer)
            : $this->gameService->checkRangkai($q['answer'], $answer);

        $damage = 0;
        if ($correct) {
            $damage = 10; // damage per jawaban benar
            // Kurangi HP boss secara atomik (aman untuk banyak user bersamaan).
            DB::transaction(function () use ($boss, $damage) {
                DB::table('boss_battles')
                    ->where('id', $boss->id)
                    ->where('current_hp', '>', 0)
                    ->decrement('current_hp', $damage);

                $hit = BossHit::firstOrCreate(
                    ['boss_battle_id' => $boss->id, 'siswa_id' => $this->siswa()->id],
                    ['damage' => 0, 'correct_count' => 0]
                );
                $hit->increment('damage', $damage);
                $hit->increment('correct_count', 1);
            });
        }

        $boss->refresh();
        $justDefeated = false;

        if ($boss->current_hp <= 0 && $boss->status === 'active') {
            $boss->update(['status' => 'defeated', 'current_hp' => 0]);
            $justDefeated = true;
            $this->awardParticipants($boss);
        }

        return response()->json([
            'success' => true,
            'correct' => $correct,
            'damage' => $damage,
            'answer_key' => $q['answer'],
            'current_hp' => max(0, $boss->current_hp),
            'max_hp' => $boss->max_hp,
            'hp_percent' => $boss->hpPercent(),
            'defeated' => $boss->status === 'defeated',
            'just_defeated' => $justDefeated,
        ]);
    }

    /**
     * Polling ringan status boss + HP.
     */
    public function state(BossBattle $boss)
    {
        return response()->json([
            'status' => $boss->status,
            'current_hp' => max(0, $boss->current_hp),
            'max_hp' => $boss->max_hp,
            'hp_percent' => $boss->hpPercent(),
        ]);
    }

    /**
     * Saat boss kalah: beri poin ke semua kontributor (sekali saja).
     * Top 3 dapat bonus.
     */
    private function awardParticipants(BossBattle $boss): void
    {
        $hits = BossHit::where('boss_battle_id', $boss->id)
            ->where('points_awarded', false)
            ->orderByDesc('damage')
            ->get();

        $rank = 0;
        foreach ($hits as $hit) {
            $rank++;
            $bonus = match (true) {
                $rank === 1 => 20,
                $rank === 2 => 15,
                $rank === 3 => 12,
                default => 8,
            };
            $siswa = $hit->siswa;
            if ($siswa) {
                $this->gamification->awardGamePoints(
                    $siswa,
                    $bonus,
                    "Mengalahkan Boss \"{$boss->nama}\" (peringkat #{$rank}, {$hit->correct_count} jawaban benar)",
                    $boss
                );
            }
            $hit->update(['points_awarded' => true]);
        }
    }

    // ===================== ADMIN =====================

    public function adminIndex()
    {
        $battles = BossBattle::withCount('hits')->latest('id')->limit(30)->get();

        return view('admin.boss.index', ['battles' => $battles]);
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:300'],
            'mode' => ['required', 'in:tebak,rangkai'],
            'max_hp' => ['required', 'integer', 'min:50', 'max:100000'],
        ]);

        // Nonaktifkan boss aktif lain agar hanya 1 arena berjalan.
        BossBattle::where('status', 'active')->update(['status' => 'ended']);

        BossBattle::create([
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'mode' => $data['mode'],
            'max_hp' => $data['max_hp'],
            'current_hp' => $data['max_hp'],
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.boss.index')->with('success', 'Boss baru dimulai!');
    }

    public function adminEnd(BossBattle $boss)
    {
        if ($boss->status === 'active') {
            $boss->update(['status' => 'ended']);
        }

        return redirect()->route('admin.boss.index')->with('success', 'Boss dihentikan.');
    }
}
