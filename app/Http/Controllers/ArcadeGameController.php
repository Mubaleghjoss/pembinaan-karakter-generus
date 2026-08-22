<?php

namespace App\Http\Controllers;

use App\Models\GameArcadeMatch;
use App\Models\GameArcadeScore;
use App\Models\Siswa;
use App\Models\ThemeSetting;
use App\Services\KarakterGameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Game arcade "Pecah Karakter" — brick-breaker sederhana.
 * Balok berisi teks nama karakter; pecahkan untuk skor.
 *
 * Desain hemat hosting:
 * - Game jalan penuh di browser (canvas). Skor disubmit 1x.
 * - Bisa dimainkan siswa (login siswa) DAN staff (admin/pamong login web).
 * - Leaderboard TERPISAH: papan siswa vs papan staff (player_type).
 * - PvP via kode (lintas peran): kedua pemain pakai seed sama, submit skor,
 *   pemenang = skor tertinggi. Polling ringan (SELECT 1 row).
 * - PvP TIDAK memberi poin gamifikasi siswa (murni tanding).
 */
class ArcadeGameController extends Controller
{
    public function __construct(private readonly KarakterGameService $gameService) {}

    /**
     * Identitas pemain aktif: siswa (guard siswa) atau staff (guard web).
     * Return [type, id, name] atau null bila guest.
     */
    private function currentPlayer(): ?array
    {
        if ($siswa = Auth::guard('siswa')->user()) {
            return ['siswa', $siswa->id, $siswa->nama];
        }
        if ($user = Auth::guard('web')->user()) {
            return ['staff', $user->id, $user->display_name ?? $user->name ?? $user->username];
        }
        return null;
    }

    /**
     * Lobi arcade: aturan, tombol main solo, buat/gabung PvP, leaderboard terpisah.
     */
    public function index()
    {
        $player = $this->currentPlayer();
        $words = $this->gameService->arcadeWords(40);

        return view('game.arcade.index', [
            'player' => $player,
            'wordCount' => count($words),
            'topSiswa' => $this->leaderboard('siswa'),
            'topStaff' => $this->leaderboard('staff'),
            'theme' => $this->theme(),
        ]);
    }

    /**
     * Halaman main solo (canvas). Kata dikirim ke browser, skor disubmit 1x.
     */
    public function play(Request $request)
    {
        $player = $this->currentPlayer();
        $words = $this->gameService->arcadeWords(40);
        if (count($words) < 4) {
            return redirect()->route('arcade.index')->with('error', 'Bank karakter belum cukup.');
        }

        return view('game.arcade.play', [
            'player' => $player,
            'words' => $words,
            'seed' => (string) $request->query('seed', Str::random(12)),
            'matchCode' => $request->query('match'),
            'theme' => $this->theme(),
        ]);
    }

    /**
     * Submit skor solo → tercatat di leaderboard sesuai peran (siswa/staff).
     * Guest: tidak tersimpan.
     */
    public function submitScore(Request $request)
    {
        $player = $this->currentPlayer();
        $score = max(0, min(1_000_000, (int) $request->input('score', 0)));
        $combo = max(0, min(10_000, (int) $request->input('best_combo', 0)));

        if (! $player) {
            return response()->json(['success' => true, 'saved' => false, 'message' => 'Login untuk menyimpan skor.']);
        }

        [$type, $id, $name] = $player;

        // Simpan hanya skor terbaik pemain (leaderboard bersih).
        $existing = GameArcadeScore::where('game', 'pecah-karakter')
            ->where('player_type', $type)
            ->where('player_id', $id)
            ->first();

        if (! $existing) {
            GameArcadeScore::create([
                'game' => 'pecah-karakter',
                'player_type' => $type,
                'player_id' => $id,
                'player_name' => $name,
                'score' => $score,
                'best_combo' => $combo,
            ]);
        } elseif ($score > $existing->score) {
            $existing->update(['score' => $score, 'best_combo' => max($combo, $existing->best_combo), 'player_name' => $name]);
        }

        return response()->json([
            'success' => true,
            'saved' => true,
            'leaderboard' => $this->leaderboard($type),
        ]);
    }

    /**
     * Buat match PvP (lintas peran) — kode 6 digit + seed kata identik.
     */
    public function createMatch(Request $request)
    {
        $player = $this->currentPlayer();
        abort_unless($player, 403);
        [$type, $id, $name] = $player;

        $code = $this->uniqueCode();
        $match = GameArcadeMatch::create([
            'code' => $code,
            'seed' => Str::random(12),
            'status' => 'waiting',
            'p1_type' => $type,
            'p1_id' => $id,
            'p1_name' => $name,
            'last_activity_at' => now(),
        ]);

        return response()->json(['success' => true, 'code' => $match->code, 'seed' => $match->seed]);
    }

    /**
     * Gabung match via kode.
     */
    public function joinMatch(Request $request)
    {
        $player = $this->currentPlayer();
        abort_unless($player, 403);
        [$type, $id, $name] = $player;

        $code = strtoupper(trim((string) $request->input('code')));
        $match = GameArcadeMatch::where('code', $code)->first();

        if (! $match) {
            return response()->json(['success' => false, 'message' => 'Kode tidak ditemukan.'], 404);
        }
        if ($match->status !== 'waiting' || $match->p2_id !== null) {
            return response()->json(['success' => false, 'message' => 'Match sudah penuh atau berjalan.'], 422);
        }
        // Tidak boleh gabung match sendiri.
        if ($match->p1_type === $type && (int) $match->p1_id === (int) $id) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa gabung match sendiri.'], 422);
        }

        $match->update([
            'p2_type' => $type,
            'p2_id' => $id,
            'p2_name' => $name,
            'status' => 'playing',
            'last_activity_at' => now(),
        ]);

        return response()->json(['success' => true, 'code' => $match->code, 'seed' => $match->seed]);
    }

    /**
     * Submit skor pada match PvP. Pemenang = skor tertinggi (tidak beri poin siswa).
     */
    public function submitMatch(Request $request, GameArcadeMatch $match)
    {
        $player = $this->currentPlayer();
        abort_unless($player, 403);
        [$type, $id] = $player;

        $score = max(0, min(1_000_000, (int) $request->input('score', 0)));

        $isP1 = $match->p1_type === $type && (int) $match->p1_id === (int) $id;
        $isP2 = $match->p2_type === $type && (int) $match->p2_id === (int) $id;
        abort_unless($isP1 || $isP2, 403);

        if ($isP1) {
            $match->p1_score = $score;
        } else {
            $match->p2_score = $score;
        }
        $match->last_activity_at = now();

        // Selesai bila kedua skor terkumpul.
        if ($match->p1_score !== null && $match->p2_score !== null) {
            if ($match->p1_score > $match->p2_score) {
                $match->winner = 'p1';
            } elseif ($match->p2_score > $match->p1_score) {
                $match->winner = 'p2';
            } else {
                $match->winner = 'draw';
            }
            $match->status = 'finished';

            // Skor solo pemain tetap dicatat di leaderboard masing-masing (best score).
            $this->recordFromMatch($match);
        }

        $match->save();

        return response()->json(['success' => true, 'state' => $this->matchState($match)]);
    }

    /**
     * Polling ringan status match.
     */
    public function matchStatus(GameArcadeMatch $match)
    {
        return response()->json($this->matchState($match));
    }

    private function matchState(GameArcadeMatch $match): array
    {
        return [
            'status' => $match->status,
            'p1_name' => $match->p1_name,
            'p2_name' => $match->p2_name,
            'p1_score' => $match->p1_score,
            'p2_score' => $match->p2_score,
            'winner' => $match->winner,
            'seed' => $match->seed,
        ];
    }

    private function recordFromMatch(GameArcadeMatch $match): void
    {
        foreach ([[$match->p1_type, $match->p1_id, $match->p1_name, $match->p1_score],
                  [$match->p2_type, $match->p2_id, $match->p2_name, $match->p2_score]] as [$t, $pid, $pname, $ps]) {
            if (! $t || $pid === null) {
                continue;
            }
            $existing = GameArcadeScore::where('game', 'pecah-karakter')
                ->where('player_type', $t)->where('player_id', $pid)->first();
            if (! $existing) {
                GameArcadeScore::create([
                    'game' => 'pecah-karakter', 'player_type' => $t, 'player_id' => $pid,
                    'player_name' => $pname, 'score' => (int) $ps, 'best_combo' => 0,
                ]);
            } elseif ((int) $ps > $existing->score) {
                $existing->update(['score' => (int) $ps, 'player_name' => $pname]);
            }
        }
    }

    private function leaderboard(string $type): array
    {
        return GameArcadeScore::where('game', 'pecah-karakter')
            ->where('player_type', $type)
            ->orderByDesc('score')
            ->limit(10)
            ->get(['player_name', 'score', 'best_combo'])
            ->map(fn ($r) => ['name' => $r->player_name, 'score' => $r->score, 'combo' => $r->best_combo])
            ->all();
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (GameArcadeMatch::where('code', $code)->exists());
        return $code;
    }

    private function theme(): ?ThemeSetting
    {
        try {
            return ThemeSetting::current();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
