<?php

namespace App\Http\Controllers;

use App\Services\KarakterGameService;
use App\Models\ThemeSetting;

/**
 * Mode coba (guest) game 29 Karakter — tanpa login, tanpa poin.
 * Ringan untuk hosting: soal + jawaban dikirim ke klien, dinilai di sisi klien
 * (tidak ada sesi/DB). Poin hanya tercatat bila siswa login & main di portal.
 */
class PublicGameController extends Controller
{
    public function __construct(private readonly KarakterGameService $gameService) {}

    /**
     * Main coba satu mode (guest). Jawaban ikut dikirim untuk penilaian di klien.
     */
    public function play(string $mode)
    {
        $mode = $mode === 'tebak' ? 'tebak' : 'rangkai';
        $questions = $this->gameService->buildQuestions($mode, 5);

        if (empty($questions)) {
            return redirect()->route('public.rpg.index')
                ->with('error', 'Bank karakter belum tersedia. Coba lagi nanti.');
        }

        // Untuk guest, sertakan jawaban (dinilai di klien) — poin memang tidak dihitung.
        $clientQuestions = array_map(function ($q) use ($mode) {
            if ($mode === 'tebak') {
                return [
                    'prompt' => $q['prompt'],
                    'options' => $q['options'],
                    'answer' => $q['answer'],
                ];
            }
            return [
                'clue' => $q['clue'],
                'scrambled' => $q['scrambled'],
                'word_lengths' => $q['word_lengths'],
                'hint_arab' => $q['hint_arab'] ?? null,
                'answer' => $q['answer'],
            ];
        }, $questions);

        return view('public.game.play', [
            'mode' => $mode,
            'questions' => $clientQuestions,
            'theme' => ThemeSetting::current(),
        ]);
    }
}
