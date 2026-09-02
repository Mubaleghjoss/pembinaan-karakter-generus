<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\GameArcadeScore;
use App\Services\GamificationService;
use App\Services\KarakterGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * API v1 — game karakter untuk aplikasi mobile.
 *
 * Padanan `KarakterGameController` (solo tebak/rangkai) dan
 * `ArcadeGameController` (skor arcade) di web, tapi TANPA session cookie:
 * kunci jawaban disimpan di cache server dengan token acak selama 30 menit,
 * jadi klien mobile tidak pernah menerima jawaban sebelum submit.
 *
 * Endpoint:
 *   GET  /game/info                    : ketersediaan bank soal + skor terbaik
 *   POST /game/solo/mulai              : ambil soal (tanpa jawaban) + token
 *   POST /game/solo/submit             : nilai jawaban, beri poin bila lulus
 *   GET  /game/arcade/kata             : daftar kata untuk arcade
 *   POST /game/arcade/skor             : simpan skor terbaik arcade
 *   GET  /game/arcade/leaderboard      : papan skor arcade
 *
 * Aturan poin mengikuti web: solo lulus (>= 60% benar) = 5 poin,
 * dicatat sebagai source 'game' lewat GamificationService::awardGamePoints.
 * Orang tua (token ability 'ortu') tidak boleh bermain — hanya memantau.
 */
class GameController extends Controller
{
    use ResolvesSiswaToken;

    /** Batas jumlah soal per sesi solo. */
    private const SOAL_MIN = 3;

    private const SOAL_MAX = 10;

    /** Masa berlaku kunci jawaban di cache (menit). */
    private const TTL_MENIT = 30;

    /** Poin untuk sesi solo yang lulus — sama dengan web. */
    private const POIN_SOLO = 5;

    public function __construct(
        private readonly KarakterGameService $gameService,
        private readonly GamificationService $gamification,
    ) {}

    /**
     * Info ketersediaan game + skor arcade terbaik siswa ini.
     */
    public function info(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $jumlahKarakter = $this->gameService->activeCharacters()->count();
        $skorTerbaik = GameArcadeScore::where('game', 'pecah-karakter')
            ->where('player_type', 'siswa')
            ->where('player_id', $siswa->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'jumlah_karakter' => $jumlahKarakter,
                'siap' => $jumlahKarakter >= 4,
                'mode' => [
                    ['kode' => 'tebak', 'nama' => 'Tebak Karakter', 'deskripsi' => 'Pilih karakter yang sesuai studi kasus.'],
                    ['kode' => 'rangkai', 'nama' => 'Rangkai Kata', 'deskripsi' => 'Susun huruf teracak jadi nama karakter.'],
                ],
                'poin_per_kemenangan' => self::POIN_SOLO,
                'ambang_lulus_persen' => 60,
                'hanya_memantau' => $this->tokenHasAbility($request, 'ortu'),
                'arcade' => [
                    'skor_terbaik' => (int) ($skorTerbaik->score ?? 0),
                    'combo_terbaik' => (int) ($skorTerbaik->best_combo ?? 0),
                ],
            ],
        ]);
    }

    /**
     * Mulai sesi solo: kirim soal tanpa jawaban, simpan kunci di cache.
     */
    public function mulaiSolo(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if ($this->tokenHasAbility($request, 'ortu')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun orang tua hanya memantau, tidak bisa bermain.',
                'code' => 'ORTU_READ_ONLY',
            ], 403);
        }

        $mode = $request->input('mode') === 'tebak' ? 'tebak' : 'rangkai';
        $jumlah = (int) $request->input('jumlah', 5);
        $jumlah = max(self::SOAL_MIN, min(self::SOAL_MAX, $jumlah));

        $questions = $this->gameService->buildQuestions($mode, $jumlah);
        if (empty($questions)) {
            return response()->json([
                'success' => false,
                'message' => 'Bank karakter belum cukup (minimal 4 karakter aktif).',
                'code' => 'BANK_SOAL_KURANG',
            ], 422);
        }

        $token = Str::random(32);
        Cache::put($this->cacheKey($siswa->id, $token), [
            'mode' => $mode,
            'questions' => $questions,
            'started_at' => now()->timestamp,
        ], now()->addMinutes(self::TTL_MENIT));

        // Versi klien: tanpa 'answer'.
        $soal = array_map(function (array $q) use ($mode) {
            if ($mode === 'tebak') {
                return [
                    'prompt' => $q['prompt'],
                    'options' => $q['options'],
                ];
            }

            return [
                'clue' => $q['clue'],
                'scrambled' => $q['scrambled'],
                'word_lengths' => $q['word_lengths'],
                'hint_arab' => $q['hint_arab'] ?? null,
            ];
        }, $questions);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'mode' => $mode,
                'soal' => $soal,
                'kedaluwarsa_menit' => self::TTL_MENIT,
            ],
        ]);
    }

    /**
     * Nilai jawaban solo. Token sekali pakai (dihapus setelah dinilai).
     */
    public function submitSolo(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if ($this->tokenHasAbility($request, 'ortu')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun orang tua hanya memantau, tidak bisa bermain.',
                'code' => 'ORTU_READ_ONLY',
            ], 403);
        }

        $token = (string) $request->input('token');
        $jawaban = (array) $request->input('jawaban', []);
        $key = $this->cacheKey($siswa->id, $token);
        $sesi = Cache::pull($key);

        if (! $sesi) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi game kedaluwarsa atau sudah dinilai, ulangi.',
                'code' => 'SESI_KEDALUWARSA',
            ], 422);
        }

        $questions = $sesi['questions'];
        $mode = $sesi['mode'];
        $benar = 0;
        $rincian = [];

        foreach ($questions as $i => $q) {
            $ua = trim((string) ($jawaban[$i] ?? ''));
            $ok = $mode === 'tebak'
                ? $this->gameService->checkTebak($q['answer'], $ua)
                : $this->gameService->checkRangkai($q['answer'], $ua);

            if ($ok) {
                $benar++;
            }

            $rincian[] = [
                'nomor' => $i + 1,
                'jawaban_saya' => $ua,
                'kunci' => $q['answer'],
                'benar' => $ok,
            ];
        }

        $total = count($questions);
        $lulus = $total > 0 && ($benar / $total) >= 0.6;

        $poin = 0;
        if ($lulus) {
            $poin = self::POIN_SOLO;
            $this->gamification->awardGamePoints(
                $siswa,
                $poin,
                'Latihan game '.($mode === 'tebak' ? 'Tebak Karakter' : 'Rangkai Kata')." ({$benar}/{$total} benar) — aplikasi mobile"
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mode' => $mode,
                'benar' => $benar,
                'total' => $total,
                'lulus' => $lulus,
                'poin_didapat' => $poin,
                'rincian' => $rincian,
                'total_poin_sekarang' => (int) ($siswa->fresh()->siswaPoint?->total_points ?? 0),
            ],
        ]);
    }

    /**
     * Daftar kata untuk arcade (dipakai klien untuk menghasilkan permainan).
     */
    public function arcadeKata(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $kata = $this->gameService->arcadeWords(40);

        return response()->json([
            'success' => true,
            'data' => [
                'kata' => $kata,
                'jumlah' => count($kata),
                'siap' => count($kata) >= 4,
            ],
        ]);
    }

    /**
     * Simpan skor arcade (hanya skor terbaik, sama seperti web).
     */
    public function simpanSkorArcade(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if ($this->tokenHasAbility($request, 'ortu')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun orang tua hanya memantau, tidak bisa bermain.',
                'code' => 'ORTU_READ_ONLY',
            ], 403);
        }

        $skor = max(0, min(1_000_000, (int) $request->input('skor', 0)));
        $combo = max(0, min(10_000, (int) $request->input('combo', 0)));

        $existing = GameArcadeScore::where('game', 'pecah-karakter')
            ->where('player_type', 'siswa')
            ->where('player_id', $siswa->id)
            ->first();

        $rekorBaru = false;
        if (! $existing) {
            GameArcadeScore::create([
                'game' => 'pecah-karakter',
                'player_type' => 'siswa',
                'player_id' => $siswa->id,
                'player_name' => $siswa->nama,
                'score' => $skor,
                'best_combo' => $combo,
            ]);
            $rekorBaru = true;
        } elseif ($skor > $existing->score) {
            $existing->update([
                'score' => $skor,
                'best_combo' => max($combo, (int) $existing->best_combo),
                'player_name' => $siswa->nama,
            ]);
            $rekorBaru = true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tersimpan' => true,
                'rekor_baru' => $rekorBaru,
                'skor_terbaik' => (int) (GameArcadeScore::where('game', 'pecah-karakter')
                    ->where('player_type', 'siswa')
                    ->where('player_id', $siswa->id)
                    ->value('score') ?? 0),
            ],
        ]);
    }

    /**
     * Papan skor arcade siswa.
     */
    public function arcadeLeaderboard(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $limit = min(max((int) $request->query('limit', 20), 5), 50);

        $rows = GameArcadeScore::where('game', 'pecah-karakter')
            ->where('player_type', 'siswa')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        $entries = [];
        foreach ($rows as $i => $row) {
            $entries[] = [
                'peringkat' => $i + 1,
                'nama' => $row->player_name,
                'skor' => (int) $row->score,
                'combo' => (int) $row->best_combo,
                'is_saya' => (int) $row->player_id === $siswa->id,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $entries,
            'meta' => ['total' => count($entries)],
        ]);
    }

    private function cacheKey(int $siswaId, string $token): string
    {
        return "api_game_solo:{$siswaId}:{$token}";
    }
}
