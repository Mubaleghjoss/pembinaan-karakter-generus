<?php

namespace App\Http\Controllers;

use App\Models\GameDuel;
use App\Models\Siswa;
use App\Services\GamificationService;
use App\Services\KarakterGameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Game 29 Karakter Luhur — mode Rangkai Kata & Tebak Karakter.
 * Solo (latihan) + Duel vs AI (siapa cepat) + Duel PvP (polling ringan).
 *
 * Desain hemat hosting:
 * - 1 baris DB per duel (state JSON), tidak ada tabel ronde terpisah.
 * - PvP pakai polling HTTP hemat: endpoint state hanya SELECT 1 row by id.
 * - Poin hanya ditulis sekali saat duel selesai.
 */
class KarakterGameController extends Controller
{
    public function __construct(
        private readonly KarakterGameService $gameService,
        private readonly GamificationService $gamification
    ) {}

    private function siswa(): Siswa
    {
        return Auth::guard('siswa')->user();
    }

    /**
     * Lobби game: pilih mode.
     */
    public function index()
    {
        $charCount = $this->gameService->activeCharacters()->count();

        return view('siswa.game.index', [
            'charCount' => $charCount,
        ]);
    }

    /**
     * Mode solo (latihan) — soal dikirim ke frontend, dinilai di server saat submit.
     */
    public function solo(Request $request, string $mode)
    {
        $mode = $mode === 'tebak' ? 'tebak' : 'rangkai';
        $questions = $this->gameService->buildQuestions($mode, 5);

        if (empty($questions)) {
            return redirect()->route('siswa.game.index')
                ->with('error', 'Bank karakter belum cukup (minimal 4 karakter aktif).');
        }

        // Simpan kunci jawaban di sesi (jangan kirim answer ke klien untuk solo berpoin).
        $token = Str::random(20);
        $request->session()->put("game_solo.$token", [
            'mode' => $mode,
            'questions' => $questions,
            'started_at' => now()->timestamp,
        ]);

        // Kirim versi tanpa jawaban ke view.
        $clientQuestions = array_map(function ($q) use ($mode) {
            if ($mode === 'tebak') {
                return ['prompt' => $q['prompt'], 'options' => $q['options']];
            }
            return [
                'clue' => $q['clue'],
                'scrambled' => $q['scrambled'],
                'word_lengths' => $q['word_lengths'],
                'hint_arab' => $q['hint_arab'] ?? null,
            ];
        }, $questions);

        return view('siswa.game.solo', [
            'mode' => $mode,
            'token' => $token,
            'questions' => $clientQuestions,
        ]);
    }

    /**
     * Nilai jawaban solo, beri poin bila lulus (>= 60% benar).
     */
    public function soloSubmit(Request $request, string $mode)
    {
        $token = (string) $request->input('token');
        $answers = (array) $request->input('answers', []);
        $session = $request->session()->pull("game_solo.$token");

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Sesi game kedaluwarsa, ulangi.'], 422);
        }

        $questions = $session['questions'];
        $mode = $session['mode'];
        $correct = 0;

        foreach ($questions as $i => $q) {
            $ua = trim((string) ($answers[$i] ?? ''));
            $ok = $mode === 'tebak'
                ? $this->gameService->checkTebak($q['answer'], $ua)
                : $this->gameService->checkRangkai($q['answer'], $ua);
            if ($ok) {
                $correct++;
            }
        }

        $total = count($questions);
        $passed = $total > 0 && ($correct / $total) >= 0.6;

        $pointsAwarded = 0;
        if ($passed) {
            // Solo: poin kecil agar tetap ada insentif, tidak menyaingi duel.
            $pointsAwarded = 5;
            $this->gamification->awardGamePoints(
                $this->siswa(),
                $pointsAwarded,
                "Latihan game ".($mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata')." ({$correct}/{$total} benar)"
            );
        }

        return response()->json([
            'success' => true,
            'correct' => $correct,
            'total' => $total,
            'passed' => $passed,
            'points_awarded' => $pointsAwarded,
            'answers_key' => array_map(fn ($q) => $q['answer'], $questions),
        ]);
    }

    // ===================== DUEL =====================

    /**
     * Buat duel vs AI.
     */
    public function createAiDuel(Request $request, string $mode)
    {
        $mode = $mode === 'tebak' ? 'tebak' : 'rangkai';
        $difficulty = in_array($request->input('difficulty'), ['easy', 'medium', 'hard'], true)
            ? $request->input('difficulty') : 'medium';

        $questions = $this->gameService->buildQuestions($mode, 5);
        if (empty($questions)) {
            return redirect()->route('siswa.game.index')
                ->with('error', 'Bank karakter belum cukup untuk duel.');
        }

        $duel = GameDuel::create([
            'mode' => $mode,
            'opponent_type' => 'ai',
            'status' => 'active',
            'total_rounds' => count($questions),
            'p1_siswa_id' => $this->siswa()->id,
            'ai_difficulty' => $difficulty,
            'questions' => $questions,
            'p1_answers' => [],
            'p2_answers' => [],
            'last_activity_at' => now(),
        ]);

        return redirect()->route('siswa.game.duel.show', $duel);
    }

    public function showDuel(GameDuel $duel)
    {
        $this->authorizeDuel($duel);

        // Kirim soal tanpa jawaban.
        $clientQuestions = array_map(function ($q) use ($duel) {
            if ($duel->mode === 'tebak') {
                return ['prompt' => $q['prompt'], 'options' => $q['options']];
            }
            return [
                'clue' => $q['clue'],
                'scrambled' => $q['scrambled'],
                'word_lengths' => $q['word_lengths'],
                'hint_arab' => $q['hint_arab'] ?? null,
            ];
        }, $duel->questions ?? []);

        return view('siswa.game.duel', [
            'duel' => $duel,
            'questions' => $clientQuestions,
            'isP1' => $duel->p1_siswa_id === $this->siswa()->id,
        ]);
    }

    /**
     * Submit jawaban satu ronde (dipanggil per ronde oleh frontend).
     * Untuk AI, giliran AI dihitung di server saat ronde disubmit.
     */
    public function answerDuel(Request $request, GameDuel $duel)
    {
        $this->authorizeDuel($duel);

        if ($duel->isFinished()) {
            return response()->json(['success' => false, 'message' => 'Duel sudah selesai.'], 422);
        }

        $round = (int) $request->input('round');
        $answer = trim((string) $request->input('answer', ''));
        $ms = max(0, (int) $request->input('ms', 0));

        $questions = $duel->questions ?? [];
        if (! isset($questions[$round])) {
            return response()->json(['success' => false, 'message' => 'Ronde tidak valid.'], 422);
        }

        $q = $questions[$round];
        $isP1 = $duel->p1_siswa_id === $this->siswa()->id;
        $correct = $duel->mode === 'tebak'
            ? $this->gameService->checkTebak($q['answer'], $answer)
            : $this->gameService->checkRangkai($q['answer'], $answer);

        $key = $isP1 ? 'p1_answers' : 'p2_answers';
        $list = $duel->{$key} ?? [];
        // Cegah double submit ronde sama.
        foreach ($list as $a) {
            if (($a['round'] ?? -1) === $round) {
                return response()->json(['success' => false, 'message' => 'Ronde sudah dijawab.'], 422);
            }
        }
        $list[] = ['round' => $round, 'correct' => $correct, 'ms' => $ms];
        $duel->{$key} = $list;

        // AI menjawab ronde yang sama (hanya saat P1 submit di duel AI).
        if ($duel->opponent_type === 'ai' && $isP1) {
            [$aiCorrect, $aiMs] = $this->gameService->aiTurn($duel->ai_difficulty ?? 'medium');
            $aiList = $duel->p2_answers ?? [];
            $aiList[] = ['round' => $round, 'correct' => $aiCorrect, 'ms' => $aiMs];
            $duel->p2_answers = $aiList;
        }

        // Hitung skor: menang ronde = benar & lebih cepat.
        $this->recomputeScores($duel);
        $duel->touchActivity();

        // Selesai bila semua ronde terjawab kedua pihak (AI otomatis mengikuti).
        $done = $this->duelIsComplete($duel);
        $result = null;
        if ($done && ! $duel->isFinished()) {
            $result = $this->finishDuel($duel);
        }
        $duel->save();

        return response()->json([
            'success' => true,
            'round_correct' => $correct,
            'answer_key' => $q['answer'],
            'p1_score' => $duel->p1_score,
            'p2_score' => $duel->p2_score,
            'finished' => $duel->isFinished(),
            'result' => $result,
        ]);
    }

    /**
     * Polling ringan status duel (untuk PvP).
     */
    public function duelState(GameDuel $duel)
    {
        $this->authorizeDuel($duel);

        return response()->json([
            'status' => $duel->status,
            'p1_score' => $duel->p1_score,
            'p2_score' => $duel->p2_score,
            'p1_rounds' => count($duel->p1_answers ?? []),
            'p2_rounds' => count($duel->p2_answers ?? []),
            'winner' => $duel->winner,
        ]);
    }

    private function recomputeScores(GameDuel $duel): void
    {
        $p1 = collect($duel->p1_answers ?? [])->keyBy('round');
        $p2 = collect($duel->p2_answers ?? [])->keyBy('round');
        $p1Score = 0;
        $p2Score = 0;

        foreach (array_keys($duel->questions ?? []) as $r) {
            $a = $p1->get($r);
            $b = $p2->get($r);
            if (! $a && ! $b) {
                continue;
            }
            $aOk = (bool) ($a['correct'] ?? false);
            $bOk = (bool) ($b['correct'] ?? false);
            if ($aOk && ! $bOk) { $p1Score++; continue; }
            if ($bOk && ! $aOk) { $p2Score++; continue; }
            if ($aOk && $bOk) {
                // dua-duanya benar: yang lebih cepat menang ronde
                $am = $a['ms'] ?? PHP_INT_MAX;
                $bm = $b['ms'] ?? PHP_INT_MAX;
                if ($am < $bm) $p1Score++;
                elseif ($bm < $am) $p2Score++;
            }
        }

        $duel->p1_score = $p1Score;
        $duel->p2_score = $p2Score;
    }

    private function duelIsComplete(GameDuel $duel): bool
    {
        $need = count($duel->questions ?? []);
        $p1Done = count($duel->p1_answers ?? []) >= $need;
        $p2Done = count($duel->p2_answers ?? []) >= $need;
        return $need > 0 && $p1Done && $p2Done;
    }

    /**
     * Tentukan pemenang & beri poin (menang +10, seri +1, kalah +3).
     */
    private function finishDuel(GameDuel $duel): array
    {
        $duel->status = 'finished';

        if ($duel->p1_score > $duel->p2_score) {
            $duel->winner = 'p1';
        } elseif ($duel->p2_score > $duel->p1_score) {
            $duel->winner = 'p2';
        } else {
            $duel->winner = 'draw';
        }

        $modeLabel = $duel->mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata';

        // Poin P1 (selalu siswa)
        $p1Points = $this->duelPoints($duel->winner === 'p1', $duel->winner === 'draw');
        $this->gamification->awardGamePoints(
            $duel->p1,
            $p1Points,
            "Duel {$modeLabel}: ".$this->outcomeLabel($duel->winner === 'p1', $duel->winner === 'draw')." (vs ".($duel->opponent_type === 'ai' ? 'AI' : 'lawan').")",
            $duel
        );

        // Poin P2 (hanya jika PvP, bukan AI)
        if ($duel->opponent_type === 'pvp' && $duel->p2) {
            $p2Points = $this->duelPoints($duel->winner === 'p2', $duel->winner === 'draw');
            $this->gamification->awardGamePoints(
                $duel->p2,
                $p2Points,
                "Duel {$modeLabel}: ".$this->outcomeLabel($duel->winner === 'p2', $duel->winner === 'draw')." (PvP)",
                $duel
            );
        }

        return [
            'winner' => $duel->winner,
            'p1_score' => $duel->p1_score,
            'p2_score' => $duel->p2_score,
            'p1_points' => $p1Points,
        ];
    }

    private function duelPoints(bool $won, bool $draw): int
    {
        if ($won) return 10;
        if ($draw) return 1;
        return 3;
    }

    private function outcomeLabel(bool $won, bool $draw): string
    {
        return $won ? 'Menang' : ($draw ? 'Seri' : 'Kalah');
    }

    private function authorizeDuel(GameDuel $duel): void
    {
        $id = $this->siswa()->id;
        abort_unless($duel->p1_siswa_id === $id || $duel->p2_siswa_id === $id, 403);
    }
}
