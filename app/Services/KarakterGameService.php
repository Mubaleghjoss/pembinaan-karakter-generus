<?php

namespace App\Services;

use App\Models\KarakterLuhur;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Membuat soal game dari Bank 29 Karakter.
 * - Rangkai Kata: petunjuk (definisi/ringkas) -> jawaban = nama karakter, plus huruf teracak.
 * - Tebak Karakter: studi kasus -> pilih karakter benar dari 4 opsi.
 *
 * Semua ringan: satu query ambil karakter aktif lalu diproses di memori.
 */
class KarakterGameService
{
    /**
     * Ambil karakter aktif (cache-friendly; controller boleh cache hasilnya).
     */
    public function activeCharacters(): Collection
    {
        return KarakterLuhur::query()
            ->where('is_active', true)
            ->orderBy('nomor')
            ->get(['id', 'nomor', 'nama', 'nama_arab', 'ringkas', 'deskripsi', 'definisi', 'studi_kasus']);
    }

    /**
     * Bangun N soal untuk sebuah mode.
     * Return array of soal siap dikirim ke frontend (jawaban disertakan hanya utk server).
     */
    public function buildQuestions(string $mode, int $count = 5): array
    {
        $chars = $this->activeCharacters();
        if ($chars->count() < 4) {
            return [];
        }

        $questions = [];
        $pool = $chars->shuffle()->take($count);

        foreach ($pool as $c) {
            $questions[] = $mode === 'tebak'
                ? $this->buildTebak($c, $chars)
                : $this->buildRangkai($c);
        }

        return array_values(array_filter($questions));
    }

    /**
     * Soal Rangkai Kata: petunjuk + huruf teracak.
     */
    private function buildRangkai(KarakterLuhur $c): array
    {
        $answer = $this->normalizeAnswer($c->nama);
        // Petunjuk: pakai definisi kalau ada, jika tidak ringkas/deskripsi.
        $clue = $c->definisi ?: ($c->deskripsi ?: $c->ringkas ?: 'Salah satu dari 29 karakter luhur.');

        // Huruf teracak (tanpa spasi untuk memudahkan; tampilkan hint jumlah kata).
        $letters = preg_split('//u', str_replace(' ', '', $answer), -1, PREG_SPLIT_NO_EMPTY);
        shuffle($letters);

        return [
            'karakter_id' => $c->id,
            'clue' => $clue,
            'answer' => $answer,
            'scrambled' => implode('', $letters),
            'word_lengths' => array_map(fn ($w) => mb_strlen($w), explode(' ', $answer)),
            'hint_arab' => $c->nama_arab,
        ];
    }

    /**
     * Soal Tebak Karakter: studi kasus + 4 opsi nama karakter.
     */
    private function buildTebak(KarakterLuhur $c, Collection $all): ?array
    {
        $cases = array_values(array_filter((array) ($c->studi_kasus ?? []), fn ($s) => filled($s)));
        if (empty($cases)) {
            // Fallback ke definisi bila belum ada studi kasus.
            $prompt = $c->definisi ?: $c->deskripsi;
            if (blank($prompt)) {
                return null;
            }
        } else {
            $prompt = $cases[array_rand($cases)];
        }

        // 3 pengecoh acak (karakter lain).
        $distractors = $all->where('id', '!=', $c->id)->shuffle()->take(3)->pluck('nama')->all();
        $options = array_merge([$c->nama], $distractors);
        shuffle($options);

        return [
            'karakter_id' => $c->id,
            'prompt' => $prompt,
            'answer' => $c->nama,
            'options' => array_values($options),
        ];
    }

    /**
     * Cek jawaban rangkai kata (abaikan besar/kecil & spasi ganda).
     */
    public function checkRangkai(string $answer, string $userInput): bool
    {
        return $this->canonical($answer) === $this->canonical($userInput);
    }

    public function checkTebak(string $answer, string $userChoice): bool
    {
        return $this->canonical($answer) === $this->canonical($userChoice);
    }

    private function normalizeAnswer(string $nama): string
    {
        // Buang catatan dalam kurung agar jawaban tidak terlalu panjang, mis. "Berilmu (Alim Faqih)" -> "Berilmu".
        $clean = trim(preg_replace('/\s*\([^)]*\)/u', '', $nama));
        return $clean !== '' ? $clean : trim($nama);
    }

    private function canonical(string $s): string
    {
        $s = Str::lower(trim($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }

    /**
     * Simulasi jawaban AI untuk satu ronde.
     * Return [correct(bool), ms(int)] — makin sulit makin cepat & akurat.
     */
    public function aiTurn(string $difficulty): array
    {
        [$acc, $minMs, $maxMs] = match ($difficulty) {
            'hard' => [0.85, 1500, 4000],
            'medium' => [0.65, 2500, 6000],
            default => [0.45, 3500, 8000], // easy
        };

        $correct = (mt_rand(1, 100) / 100) <= $acc;
        $ms = mt_rand($minMs, $maxMs);

        return [$correct, $ms];
    }
}
