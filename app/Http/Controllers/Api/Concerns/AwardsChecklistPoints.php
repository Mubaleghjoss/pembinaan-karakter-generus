<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Karakter;
use App\Models\PointTransaction;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use App\Services\GamificationService;
use Illuminate\Support\Facades\Log;

/**
 * Perhitungan & pemberian poin verifikasi tugas PKG.
 *
 * Logika di sini SENGAJA menyalin SiswaKarakterController (web) supaya poin
 * yang diberikan lewat API identik dengan yang diberikan lewat web:
 * poin dasar dari karakter + bonus bukti foto/voice, ditambah bonus 50 poin
 * bila seluruh tugas satu kategori sudah terverifikasi.
 *
 * Kalau nanti aturan poin di web berubah, dua tempat ini harus disamakan.
 */
trait AwardsChecklistPoints
{
    /**
     * Rincian poin satu checklist.
     *
     * @return array{base_points:int,photo_bonus:int,voice_bonus:int,proof_bonus:int,total:int}
     */
    protected function checklistPointBreakdown(SiswaKarakterChecklist $checklist): array
    {
        $basePoints = (int) ($checklist->karakter?->poin ?? 10);
        $photoBonus = (int) ($checklist->photo_proof_bonus_points ?? 0);
        $voiceBonus = (int) ($checklist->voice_note_bonus_points ?? 0);

        return [
            'base_points' => $basePoints,
            'photo_bonus' => $photoBonus,
            'voice_bonus' => $voiceBonus,
            'proof_bonus' => $photoBonus + $voiceBonus,
            'total' => $basePoints + $photoBonus + $voiceBonus,
        ];
    }

    /**
     * Metadata transaksi poin, mengikuti bentuk yang dipakai web.
     */
    protected function checklistPointMetadata(SiswaKarakterChecklist $checklist): array
    {
        $breakdown = $this->checklistPointBreakdown($checklist);

        return [
            'checklist_id' => $checklist->id,
            'base_points' => $breakdown['base_points'],
            'photo_proof_bonus_points' => $breakdown['photo_bonus'],
            'voice_note_bonus_points' => $breakdown['voice_bonus'],
            'proof_bonus_points' => $breakdown['proof_bonus'],
            'proof_uploaded' => (bool) $checklist->has_proof,
            'source' => 'api_mobile',
        ];
    }

    /**
     * Berikan poin verifikasi. Mengembalikan jumlah poin yang benar-benar
     * ditambahkan (0 bila gamifikasi gagal — verifikasi tetap sah).
     */
    protected function awardVerificationPoints(SiswaKarakterChecklist $checklist): int
    {
        $siswa = $checklist->siswa;
        $karakter = $checklist->karakter;

        if (! $siswa || ! $karakter) {
            return 0;
        }

        $breakdown = $this->checklistPointBreakdown($checklist);

        try {
            $gamification = app(GamificationService::class);
            $siswaPoint = $gamification->getOrCreateSiswaPoint($siswa);

            $keterangan = 'Verifikasi tugas PKG: '.$karakter->nama
                .' (+'.$breakdown['total'].' poin'
                .($breakdown['proof_bonus'] > 0 ? ', termasuk bonus bukti +'.$breakdown['proof_bonus'] : '')
                .')';

            $siswaPoint->addPoints(
                $breakdown['total'],
                'character',
                $keterangan,
                $checklist,
                $this->checklistPointMetadata($checklist)
            );

            $this->awardCategoryBonus($siswa, $karakter, $gamification);
            $gamification->checkBadgeEligibility($siswa);

            return $breakdown['total'];
        } catch (\Throwable $e) {
            // Sama seperti web: kegagalan gamifikasi tidak membatalkan verifikasi.
            Log::warning('Gamification error on API verify: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Tarik kembali poin saat verifikasi dibatalkan.
     */
    protected function reverseVerificationPoints(
        SiswaKarakterChecklist $checklist,
        string $reason,
        string $actor
    ): int {
        $siswa = $checklist->siswa;
        $karakter = $checklist->karakter;

        if (! $siswa || ! $karakter) {
            return 0;
        }

        $total = $this->checklistPointBreakdown($checklist)['total'];

        try {
            $gamification = app(GamificationService::class);
            $siswaPoint = $gamification->getOrCreateSiswaPoint($siswa);
            $siswaPoint->addPoints(
                -$total,
                'character',
                'Batal verifikasi: '.$karakter->nama.' (-'.$total.' poin). Alasan: '
                    .$reason.' (oleh '.$actor.')',
                $checklist,
                $this->checklistPointMetadata($checklist)
            );

            return $total;
        } catch (\Throwable $e) {
            Log::warning('Gamification error on API unverify: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Bonus 50 poin bila semua tugas dalam satu kategori sudah terverifikasi,
     * maksimal satu kali per kategori per hari.
     */
    private function awardCategoryBonus(
        Siswa $siswa,
        Karakter $karakter,
        GamificationService $gamification
    ): void {
        $categoryTasks = Karakter::active()
            ->where('kategori', $karakter->kategori)
            ->pluck('id');

        if ($categoryTasks->isEmpty()) {
            return;
        }

        $verified = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('karakter_id', $categoryTasks)
            ->verified()
            ->distinct('karakter_id')
            ->count('karakter_id');

        if ($verified < $categoryTasks->count()) {
            return;
        }

        $label = $karakter->kategori_label;

        $already = PointTransaction::query()
            ->where('siswa_id', $siswa->id)
            ->where('description', 'like', '%Bonus kategori '.$label.'%')
            ->whereDate('created_at', today())
            ->exists();

        if ($already) {
            return;
        }

        // CATATAN BUG BACKEND: web (SiswaKarakterController::awardCategoryBonus)
        // memanggil addPoints dengan source 'bonus', padahal kolom
        // point_transactions.source adalah enum
        // ('attendance','character','badge','manual','streak','perfect_month','game').
        // Dengan MySQL strict mode, insert itu gagal ("Data truncated for column
        // 'source'") sehingga bonus 50 poin TIDAK pernah tersimpan lewat web.
        // Di sini dipakai 'manual' — nilai enum yang sah dan tetap masuk ke
        // kolom akumulasi bonus_points (SiswaPoint::addPoints default branch),
        // jadi niat aslinya terpenuhi. Bug web-nya belum diperbaiki (di luar
        // ruang lingkup: menyentuh alur poin yang sudah berjalan di produksi).
        $gamification->getOrCreateSiswaPoint($siswa)->addPoints(
            50,
            'manual',
            '🎉 Bonus kategori '.$label.': Semua tugas selesai & terverifikasi (+50 poin)'
        );
    }
}
