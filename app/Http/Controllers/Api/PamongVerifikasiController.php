<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AwardsChecklistPoints;
use App\Http\Controllers\Controller;
use App\Models\PamongActivityLog;
use App\Models\SiswaKarakterChecklist;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API v1 — verifikasi tugas PKG oleh pamong.
 *
 * Padanan `SiswaKarakterController::verificationIndex/verify/unverify` di web,
 * dengan aturan akses yang sama:
 *   - pamong (role teacher) hanya boleh menyentuh siswa yang di-assign
 *     kepadanya (`PamongSiswa` aktif);
 *   - admin / pkg_manager / staff boleh semua siswa.
 *
 * Poin diberikan lewat trait AwardsChecklistPoints supaya identik dengan web.
 */
class PamongVerifikasiController extends Controller
{
    use AwardsChecklistPoints;

    /**
     * Daftar checklist untuk diverifikasi.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->staffFromToken($request);
        if (! $user instanceof User) {
            return $this->forbiddenNonStaff();
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $status = (string) $request->get('status', 'unverified');

        $query = SiswaKarakterChecklist::query()
            ->with(['siswa:id,nama,nis,kelas_id', 'siswa.kelas:id,nama', 'karakter', 'verifier:id,name'])
            ->orderByDesc('checked_at');

        if ($ids = $this->restrictedSiswaIds($user)) {
            $query->whereIn('siswa_id', $ids);
        }

        if ($status === 'verified') {
            $query->verified();
        } elseif ($status === 'unverified') {
            $query->unverified();
        }

        if ($siswaId = $request->get('siswa_id')) {
            $query->where('siswa_id', (int) $siswaId);
        }

        if ($karakterId = $request->get('karakter_id')) {
            $query->where('karakter_id', (int) $karakterId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('checked_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('checked_at', '<=', $dateTo);
        }

        $page = $query->paginate($perPage);

        $statsQuery = SiswaKarakterChecklist::query();
        if ($ids = $this->restrictedSiswaIds($user)) {
            $statsQuery->whereIn('siswa_id', $ids);
        }
        $stats = $statsQuery
            ->selectRaw('SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified')
            ->selectRaw('SUM(CASE WHEN verified_at IS NULL THEN 1 ELSE 0 END) as unverified')
            ->first();

        return response()->json([
            'success' => true,
            'data' => collect($page->items())
                ->map(fn (SiswaKarakterChecklist $c) => $this->payload($c))
                ->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'status' => $status,
                'terverifikasi' => (int) ($stats->verified ?? 0),
                'menunggu' => (int) ($stats->unverified ?? 0),
                'scope' => $user->isTeacher() ? 'assigned' : 'all',
            ],
        ]);
    }

    /**
     * Verifikasi satu checklist + pemberian poin.
     */
    public function verify(Request $request, SiswaKarakterChecklist $checklist): JsonResponse
    {
        $user = $this->staffFromToken($request);
        if (! $user instanceof User) {
            return $this->forbiddenNonStaff();
        }

        if (! $this->canTouch($user, $checklist)) {
            return $this->forbiddenNotAssigned();
        }

        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($checklist->verified_at !== null) {
            return response()->json([
                'success' => false,
                'error' => 'Conflict',
                'message' => 'Tugas ini sudah diverifikasi',
                'code' => 'ALREADY_VERIFIED',
                'data' => $this->payload($checklist->fresh(['siswa', 'karakter', 'verifier'])),
            ], 409);
        }

        $poin = DB::transaction(function () use ($checklist, $user, $data) {
            $checklist->update([
                'verified_by' => $user->id,
                'verified_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            return $this->awardVerificationPoints($checklist->fresh(['siswa', 'karakter']));
        });

        $this->logActivity(
            $user,
            $request,
            'Memverifikasi tugas PKG (mobile): '.($checklist->karakter->nama ?? 'karakter')
                .' untuk siswa '.($checklist->siswa->nama ?? ''),
            ['checklist_id' => $checklist->id, 'siswa_id' => $checklist->siswa_id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil diverifikasi. +'.$poin.' poin diberikan.',
            'data' => $this->payload($checklist->fresh(['siswa', 'karakter', 'verifier'])),
            'meta' => ['poin_diberikan' => $poin],
        ]);
    }

    /**
     * Batalkan verifikasi (poin ditarik kembali).
     *
     * Catatan penting: berbeda dari web, endpoint ini TIDAK menghapus berkas
     * bukti (`clearStoredEvidenceFiles`). Penghapusan berkas tidak bisa
     * dibatalkan, jadi tindakan destruktif itu tetap dilakukan lewat web
     * di mana pamong melihat buktinya lebih dulu.
     */
    public function unverify(Request $request, SiswaKarakterChecklist $checklist): JsonResponse
    {
        $user = $this->staffFromToken($request);
        if (! $user instanceof User) {
            return $this->forbiddenNonStaff();
        }

        if (! $this->canTouch($user, $checklist)) {
            return $this->forbiddenNotAssigned();
        }

        $data = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        if ($checklist->verified_at === null) {
            return response()->json([
                'success' => false,
                'error' => 'Conflict',
                'message' => 'Tugas ini belum diverifikasi',
                'code' => 'NOT_VERIFIED',
            ], 409);
        }

        $poin = DB::transaction(function () use ($checklist, $data, $user) {
            $ditarik = $this->reverseVerificationPoints(
                $checklist->fresh(['siswa', 'karakter']),
                $data['reason'],
                $user->username ?? $user->name ?? 'Pamong'
            );

            $checklist->update([
                'verified_by' => null,
                'verified_at' => null,
                'notes' => null,
            ]);

            return $ditarik;
        });

        $this->logActivity(
            $user,
            $request,
            'Membatalkan verifikasi tugas PKG (mobile): '
                .($checklist->karakter->nama ?? 'karakter').'. Alasan: '.$data['reason'],
            ['checklist_id' => $checklist->id, 'reason' => $data['reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi dibatalkan. '.$poin.' poin ditarik kembali. '
                .'Berkas bukti tetap disimpan — hapus lewat web bila perlu.',
            'data' => $this->payload($checklist->fresh(['siswa', 'karakter'])),
            'meta' => ['poin_ditarik' => $poin],
        ]);
    }

    /**
     * Verifikasi beberapa checklist sekaligus.
     *
     * Fail-closed: id yang di luar wewenang atau sudah terverifikasi
     * dilaporkan per item, bukan membatalkan seluruh permintaan.
     */
    public function bulkVerify(Request $request): JsonResponse
    {
        $user = $this->staffFromToken($request);
        if (! $user instanceof User) {
            return $this->forbiddenNonStaff();
        }

        $data = $request->validate([
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $berhasil = [];
        $gagal = [];
        $totalPoin = 0;

        foreach ($data['ids'] as $id) {
            $checklist = SiswaKarakterChecklist::with(['siswa', 'karakter'])->find($id);

            if (! $checklist) {
                $gagal[] = ['id' => $id, 'alasan' => 'Data tidak ditemukan'];

                continue;
            }

            if (! $this->canTouch($user, $checklist)) {
                $gagal[] = ['id' => $id, 'alasan' => 'Siswa bukan binaan Anda'];

                continue;
            }

            if ($checklist->verified_at !== null) {
                $gagal[] = ['id' => $id, 'alasan' => 'Sudah diverifikasi'];

                continue;
            }

            $poin = DB::transaction(function () use ($checklist, $user, $data) {
                $checklist->update([
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]);

                return $this->awardVerificationPoints($checklist->fresh(['siswa', 'karakter']));
            });

            $totalPoin += $poin;
            $berhasil[] = ['id' => $id, 'poin' => $poin];
        }

        if ($berhasil !== []) {
            $this->logActivity(
                $user,
                $request,
                'Verifikasi massal tugas PKG (mobile): '.count($berhasil).' checklist',
                ['ids' => array_column($berhasil, 'id')]
            );
        }

        return response()->json([
            'success' => true,
            'message' => count($berhasil).' tugas diverifikasi, '.count($gagal).' dilewati.',
            'data' => ['berhasil' => $berhasil, 'gagal' => $gagal],
            'meta' => ['total_poin' => $totalPoin],
        ]);
    }

    /**
     * Token staff (model User) atau null bila token milik siswa/ortu.
     */
    private function staffFromToken(Request $request): ?User
    {
        $user = $request->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Batasan siswa untuk pamong; null berarti tanpa batas.
     *
     * @return array<int, int>|null
     */
    private function restrictedSiswaIds(User $user): ?array
    {
        return $user->isTeacher() ? $user->getAssignedSiswaIds() : null;
    }

    private function canTouch(User $user, SiswaKarakterChecklist $checklist): bool
    {
        $ids = $this->restrictedSiswaIds($user);

        return $ids === null || in_array($checklist->siswa_id, $ids, true);
    }

    private function logActivity(User $user, Request $request, string $description, array $metadata): void
    {
        if (! $user->usesPamongPermissionSystem()) {
            return;
        }

        PamongActivityLog::log(
            userId: $user->id,
            action: 'verify',
            description: $description,
            module: 'tracer_karakter',
            metadata: $metadata,
            ipAddress: $request->ip()
        );
    }

    private function forbiddenNonStaff(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Endpoint ini hanya untuk akun pamong/admin',
            'code' => 'NOT_STAFF_TOKEN',
        ], 403);
    }

    private function forbiddenNotAssigned(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Siswa ini bukan binaan Anda',
            'code' => 'SISWA_NOT_ASSIGNED',
        ], 403);
    }

    private function payload(SiswaKarakterChecklist $c): array
    {
        return [
            'id' => $c->id,
            'siswa_id' => $c->siswa_id,
            'siswa_nama' => $c->siswa?->nama,
            'siswa_nis' => $c->siswa?->nis,
            'kelas' => $c->siswa?->kelas?->nama,
            'karakter_id' => $c->karakter_id,
            'karakter_nama' => $c->karakter?->nama,
            'kategori' => $c->karakter?->kategori,
            'poin' => $c->karakter?->poin,
            'checked_at' => $c->checked_at?->toIso8601String(),
            'hasil_teks' => $c->hasil_teks,
            'student_note' => $c->student_note,
            'is_verified' => $c->verified_at !== null,
            'verified_at' => $c->verified_at?->toIso8601String(),
            'verified_by' => $c->verifier?->name,
            'notes' => $c->notes,
            'has_proof_photo' => filled($c->proof_path),
            'has_proof_voice' => filled($c->voice_note_path),
            'proof_requirement' => $c->karakter?->proof_requirement ?? 'optional',
        ];
    }
}
