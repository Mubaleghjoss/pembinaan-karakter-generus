<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\Karakter;
use App\Models\OrtuComment;
use App\Models\Siswa;
use App\Models\SiswaKarakterChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API v1 — Tugas PKG (tabel `karakter` + `siswa_karakter_checklist`).
 *
 * Cakupan yang disepakati untuk mobile:
 *   - siswa: lihat tugas hari terpilih, tandai selesai (teks/klik), riwayat
 *   - ortu : lihat riwayat anak + tambah komentar
 *
 * Yang SENGAJA tidak dibawa ke API ini: unggah bukti foto & voice note.
 * Alurnya di web memakai TaskProofImageService + kompresi dan penyimpanan
 * berkas; memindahkannya butuh penanganan multipart tersendiri. Karena itu
 * tugas dengan `proof_requirement = required_any` ditolak lewat API dan
 * diarahkan ke web — supaya tidak ada data setengah jadi.
 */
class TugasPkgController extends Controller
{
    use ResolvesSiswaToken;

    /**
     * Daftar tugas aktif untuk tanggal tertentu + status pengerjaan siswa.
     */
    public function index(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        // Sama seperti web: tanggal dibatasi 7 hari ke belakang, tidak boleh masa depan.
        $maxDate = now()->toDateString();
        $minDate = now()->subDays(6)->toDateString();
        $date = (string) $request->get('date', $maxDate);
        $date = max(min($date, $maxDate), $minDate);

        Karakter::deactivateExpiredTasks();

        $tasks = Karakter::active()->availableOn($date)->orderBy('nama')->get();

        $checked = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->whereDate('checked_at', $date)
            ->get()
            ->keyBy('karakter_id');

        // Komentar ortu dimuat sekali untuk seluruh checklist hari itu (bukan
        // per baris) supaya layar harian bisa menampilkannya seperti riwayat.
        $comments = OrtuComment::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('siswa_karakter_checklist_id', $checked->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('siswa_karakter_checklist_id');

        $items = $tasks->map(function (Karakter $k) use ($checked, $comments) {
            $entry = $checked->get($k->id);

            return [
                'id' => $k->id,
                'nama' => $k->nama,
                'deskripsi' => $k->deskripsi,
                'kategori' => $k->kategori,
                'kategori_label' => $k->kategori_label,
                'poin' => $k->poin,
                'jenis_penyelesaian' => $k->jenis_penyelesaian,
                'target_teks' => $k->target_teks,
                'target_klik' => $k->target_klik,
                'proof_requirement' => $k->proof_requirement ?? 'optional',
                'allows_photo_proof' => (bool) $k->allows_photo_proof,
                'allows_voice_note_proof' => (bool) $k->allows_voice_note_proof,
                // Bukti berkas hanya bisa dikirim dari web; app memberi tahu user.
                'proof_via_web_only' => ($k->proof_requirement ?? 'optional') === 'required_any',
                'sudah_dikerjakan' => $entry !== null,
                'checklist' => $entry
                    ? array_merge($this->checklistPayload($entry), [
                        'komentar_ortu' => $comments->get($entry->id, collect())
                            ->map(fn (OrtuComment $kom) => [
                                'id' => $kom->id,
                                'comment' => $kom->comment,
                                'created_at' => $kom->created_at?->toIso8601String(),
                            ])->values(),
                    ])
                    : null,
            ];
        })->values();

        $done = $items->where('sudah_dikerjakan', true)->count();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'date' => $date,
                'min_date' => $minDate,
                'max_date' => $maxDate,
                'total' => $items->count(),
                'selesai' => $done,
                'sisa' => max($items->count() - $done, 0),
                'poin_terkumpul' => $tasks
                    ->filter(fn (Karakter $k) => $checked->has($k->id))
                    ->sum('poin'),
                'read_only' => $siswa->isGraduated() && ! $siswa->canSubmitAsAlumni(),
            ],
        ]);
    }

    /**
     * Tandai satu tugas selesai untuk hari ini.
     */
    public function submit(Request $request, Karakter $karakter): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if ($this->tokenHasAbility($request, 'ortu')) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Akun orang tua tidak dapat mengerjakan tugas',
                'code' => 'ORTU_READ_ONLY',
            ], 403);
        }

        if (! $siswa->canSubmitAsAlumni()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Akun alumni bersifat baca-saja',
                'code' => 'ALUMNI_READ_ONLY',
            ], 403);
        }

        if (! $karakter->is_active || ! $karakter->isAvailable()) {
            return response()->json([
                'success' => false,
                'error' => 'Unavailable',
                'message' => 'Tugas tidak tersedia hari ini',
                'code' => 'TASK_UNAVAILABLE',
            ], 422);
        }

        if (($karakter->proof_requirement ?? 'optional') === 'required_any') {
            return response()->json([
                'success' => false,
                'error' => 'Proof required',
                'message' => 'Tugas ini wajib melampirkan bukti foto/voice note. Kerjakan lewat web PKG.',
                'code' => 'PROOF_VIA_WEB_ONLY',
            ], 422);
        }

        $data = $request->validate([
            'hasil_teks' => 'nullable|string|max:5000',
            'student_note' => 'nullable|string|max:1000',
            'click_count' => 'nullable|integer|min:0|max:10000',
        ]);

        // Validasi sesuai jenis penyelesaian, mengikuti aturan web.
        if ($karakter->jenis_penyelesaian === 'teks' && blank($data['hasil_teks'] ?? null)) {
            return response()->json([
                'success' => false,
                'error' => 'Validation',
                'message' => 'Tugas ini butuh jawaban teks',
                'code' => 'TEKS_REQUIRED',
            ], 422);
        }

        if ($karakter->jenis_penyelesaian === 'klik' && $karakter->target_klik) {
            $clicks = (int) ($data['click_count'] ?? 0);
            if ($clicks < $karakter->target_klik) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation',
                    'message' => 'Jumlah klik belum memenuhi target ('.$clicks.'/'.$karakter->target_klik.')',
                    'code' => 'KLIK_BELUM_CUKUP',
                ], 422);
            }
        }

        $existing = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->where('karakter_id', $karakter->id)
            ->whereDate('checked_at', now()->toDateString())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'Duplicate',
                'message' => 'Tugas ini sudah dikerjakan hari ini',
                'code' => 'ALREADY_SUBMITTED',
                'data' => $this->checklistPayload($existing),
            ], 409);
        }

        $checklist = DB::transaction(fn () => SiswaKarakterChecklist::create([
            'siswa_id' => $siswa->id,
            'karakter_id' => $karakter->id,
            'checked_at' => now(),
            'hasil_teks' => $data['hasil_teks'] ?? null,
            'student_note' => $data['student_note'] ?? null,
            'click_history' => isset($data['click_count'])
                ? ['source' => 'mobile', 'count' => (int) $data['click_count']]
                : null,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil ditandai selesai. Menunggu verifikasi pamong.',
            'data' => $this->checklistPayload($checklist->fresh('karakter')),
        ], 201);
    }

    /**
     * Riwayat pengerjaan (siswa: miliknya; ortu: anaknya).
     */
    public function history(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);

        $query = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->with(['karakter', 'verifier'])
            ->orderByDesc('checked_at');

        if ($request->boolean('only_unverified')) {
            $query->whereNull('verified_at');
        }

        $page = $query->paginate($perPage);

        $comments = OrtuComment::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('siswa_karakter_checklist_id', collect($page->items())->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('siswa_karakter_checklist_id');

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(function (SiswaKarakterChecklist $c) use ($comments) {
                return array_merge($this->checklistPayload($c), [
                    'komentar_ortu' => $comments->get($c->id, collect())->map(fn (OrtuComment $k) => [
                        'id' => $k->id,
                        'comment' => $k->comment,
                        'created_at' => $k->created_at?->toIso8601String(),
                    ])->values(),
                ]);
            })->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Komentar orang tua pada satu checklist anaknya.
     */
    public function comment(Request $request, int $checklistId): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if (! $this->tokenHasAbility($request, 'ortu')) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Hanya akun orang tua yang dapat menambah komentar',
                'code' => 'ORTU_ONLY',
            ], 403);
        }

        if ($siswa->isGraduated()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Portal orang tua alumni bersifat baca-saja',
                'code' => 'ALUMNI_READ_ONLY',
            ], 403);
        }

        $data = $request->validate([
            'comment' => 'required|string|min:3|max:1000',
        ]);

        // Pastikan checklist memang milik anak dari akun ortu ini.
        $checklist = SiswaKarakterChecklist::query()
            ->where('id', $checklistId)
            ->where('siswa_id', $siswa->id)
            ->first();

        if (! $checklist) {
            return response()->json([
                'success' => false,
                'error' => 'Not found',
                'message' => 'Data tugas tidak ditemukan',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $comment = OrtuComment::create([
            'siswa_karakter_checklist_id' => $checklist->id,
            'siswa_id' => $siswa->id,
            'comment' => $data['comment'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Komentar tersimpan',
            'data' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Ringkasan progres untuk kartu dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $today = now()->toDateString();
        $totalToday = Karakter::active()->availableOn($today)->count();

        $doneToday = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->whereDate('checked_at', $today)
            ->distinct('karakter_id')
            ->count('karakter_id');

        $pending = SiswaKarakterChecklist::query()
            ->where('siswa_id', $siswa->id)
            ->whereNull('verified_at')
            ->count();

        $totalPoin = SiswaKarakterChecklist::query()
            ->join('karakter', 'siswa_karakter_checklist.karakter_id', '=', 'karakter.id')
            ->where('siswa_karakter_checklist.siswa_id', $siswa->id)
            ->whereNotNull('siswa_karakter_checklist.verified_at')
            ->sum('karakter.poin');

        return response()->json([
            'success' => true,
            'data' => [
                'tugas_hari_ini' => $totalToday,
                'selesai_hari_ini' => $doneToday,
                'menunggu_verifikasi' => $pending,
                'poin_terverifikasi' => (int) $totalPoin,
                'persentase_hari_ini' => $totalToday > 0
                    ? round($doneToday / $totalToday * 100, 1)
                    : 0.0,
            ],
        ]);
    }

    /**
     * Bentuk payload checklist yang konsisten di semua endpoint.
     */
    private function checklistPayload(SiswaKarakterChecklist $c): array
    {
        return [
            'id' => $c->id,
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
        ];
    }
}
