<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\QuranReadingCycle;
use App\Models\QuranReadingEntry;
use App\Models\QuranReadingSheet;
use App\Models\QuranSurahProgress;
use App\Models\Siswa;
use App\Models\User;
use App\Services\QuranMobileBarcodeFlowService;
use App\Services\QuranReadingScanService;
use App\Support\QuranCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * API v1 — Tracer bacaan Quran (read + entri manual).
 *
 * Ruang lingkup yang disepakati: aplikasi menampilkan progres dan menerima
 * catatan bacaan manual (halaman / surah–ayat). Alur lembar cetak + scan
 * barcode TETAP di web karena melibatkan pembuatan sheet, token, dan
 * pemindaian gambar — memindahkannya adalah proyek terpisah.
 *
 * Entri dari mobile selalu berstatus 'pending' dan menunggu verifikasi
 * pamong, sama seperti entri manual di web.
 */
class QuranReadingController extends Controller
{
    use ResolvesSiswaToken;

    public function __construct(
        private readonly QuranReadingScanService $scans,
        private readonly QuranMobileBarcodeFlowService $mobileBarcodeFlows,
    ) {}

    /**
     * Daftar catatan bacaan milik siswa (atau anak, bila login ortu).
     */
    public function index(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);

        $query = QuranReadingEntry::query()
            ->where('siswa_id', $siswa->id)
            ->with('verifier')
            ->orderByDesc('reading_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('reading_date', '>=', $request->date('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('reading_date', '<=', $request->date('end_date'));
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())
                ->map(fn (QuranReadingEntry $e) => $this->entryPayload($e))
                ->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * Ringkasan progres: total halaman, status, dan progres siklus khatam.
     */
    public function progress(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        $entries = QuranReadingEntry::query()
            ->where('siswa_id', $siswa->id)
            ->get();

        $verified = $entries->where('status', QuranReadingEntry::STATUS_VERIFIED);

        $cycle = QuranReadingCycle::query()
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('cycle_number')
            ->first();

        $surahDone = 0;
        $surahList = [];

        if ($cycle) {
            $progress = QuranSurahProgress::query()
                ->where('cycle_id', $cycle->id)
                ->orderBy('surah_number')
                ->get();

            $surahDone = $progress->whereNotNull('completed_at')->count();

            $surahList = $progress->map(fn (QuranSurahProgress $p) => [
                'surah_number' => $p->surah_number,
                'surah_nama' => QuranCatalog::name($p->surah_number),
                'ayah_total' => QuranCatalog::ayahCount($p->surah_number),
                'last_ayah' => $p->last_ayah,
                'selesai' => $p->completed_at !== null,
            ])->values()->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_entri' => $entries->count(),
                'entri_terverifikasi' => $verified->count(),
                'entri_pending' => $entries->where('status', QuranReadingEntry::STATUS_PENDING)->count(),
                'entri_ditolak' => $entries->where('status', QuranReadingEntry::STATUS_REJECTED)->count(),
                'total_halaman' => $entries->sum(fn (QuranReadingEntry $e) => $e->page_count),
                'total_halaman_terverifikasi' => $verified->sum(fn (QuranReadingEntry $e) => $e->page_count),
                'bacaan_terakhir' => $entries
                    ->sortByDesc('reading_date')
                    ->first()?->reading_date?->toDateString(),
                'siklus' => $cycle ? [
                    'id' => $cycle->id,
                    'nomor' => $cycle->cycle_number,
                    'status' => $cycle->status,
                    'mulai' => $cycle->started_at?->toDateString(),
                    'selesai' => $cycle->completed_at?->toDateString(),
                    'surah_selesai' => $surahDone,
                    'surah_total' => 114,
                    'persentase' => round($surahDone / 114 * 100, 1),
                ] : null,
                'surah_progress' => $surahList,
            ],
        ]);
    }

    /**
     * Katalog surah (nomor, nama, jumlah ayat) untuk dropdown di aplikasi.
     */
    public function surahs(): JsonResponse
    {
        $items = [];
        for ($i = 1; $i <= 114; $i++) {
            $items[] = [
                'nomor' => $i,
                'nama' => QuranCatalog::name($i),
                'ayah_count' => QuranCatalog::ayahCount($i),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Catat bacaan baru secara manual (status pending).
     */
    public function store(Request $request): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if ($this->tokenHasAbility($request, 'ortu')) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Akun orang tua hanya dapat melihat catatan bacaan',
                'code' => 'ORTU_READ_ONLY',
            ], 403);
        }

        $data = $request->validate([
            'reading_date' => 'required|date|before_or_equal:today',
            'page_start' => 'nullable|integer|min:1|max:604',
            'page_end' => 'nullable|integer|min:1|max:604|gte:page_start',
            // Kolom surah/ayah di tabel bersifat NOT NULL, jadi posisi surah
            // wajib diisi; halaman mushaf tetap opsional.
            'surah_start' => 'required|integer|min:1|max:114',
            'ayah_start' => 'required|integer|min:1|max:286',
            'surah_end' => 'nullable|integer|min:1|max:114|gte:surah_start',
            'ayah_end' => 'nullable|integer|min:1|max:286',
            'mushaf_label' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Bila akhir bacaan tidak diisi, anggap berhenti di posisi awal.
        $data['surah_end'] = $data['surah_end'] ?? $data['surah_start'];
        $data['ayah_end'] = $data['ayah_end'] ?? $data['ayah_start'];

        $entry = QuranReadingEntry::create(array_merge($data, [
            'siswa_id' => $siswa->id,
            'source' => 'mobile',
            'submitted_by_type' => 'siswa',
            'submitted_by_id' => $siswa->id,
            'status' => QuranReadingEntry::STATUS_PENDING,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Catatan bacaan tersimpan. Menunggu verifikasi pamong.',
            'data' => $this->entryPayload($entry),
        ], 201);
    }

    public function identifyBarcode(Request $request): JsonResponse
    {
        [$actorType, $actorId] = $this->barcodeActor($request);
        $data = $request->validate([
            'sheet_payload' => ['required', 'string', 'max:500'],
        ]);
        $sheet = $this->scans->resolveSheet($data['sheet_payload']);
        $sheet->load('siswa');
        $this->authorizeBarcodeStudent($request, $sheet->siswa, $actorType);
        $flow = $this->mobileBarcodeFlows->create($actorType, $actorId, $sheet);

        return response()->json([
            'success' => true,
            'data' => [
                'flow_id' => $flow['id'],
                'expires_at' => $flow['expires_at'],
                'student' => [
                    'name' => $sheet->siswa->nama,
                    'masked_nis' => $this->maskedNis($sheet->siswa->nis),
                    'school_grade' => $sheet->siswa->school_grade_label ?: 'Belum dikonfirmasi',
                    'group' => $sheet->siswa->kelompok_label ?: ($sheet->siswa->kelompok ?: 'Belum diisi'),
                ],
            ],
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function storeBarcode(Request $request): JsonResponse
    {
        [$actorType, $actorId] = $this->barcodeActor($request);
        $data = $request->validate([
            'flow_id' => ['required', 'string', 'size:40', 'alpha_num'],
            'surah_start' => ['required', 'integer', 'between:1,114'],
            'ayah_start' => ['required', 'integer', 'between:1,286'],
            'surah_end' => ['nullable', 'integer', 'between:1,114'],
            'ayah_end' => ['required', 'integer', 'between:1,286'],
            'page_start' => ['nullable', 'required_with:page_end', 'integer', 'between:1,1000'],
            'page_end' => ['nullable', 'required_with:page_start', 'integer', 'between:1,1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['surah_end'] = $data['surah_end'] ?? $data['surah_start'];
        $data['page_start'] = $data['page_start'] ?? null;
        $data['page_end'] = $data['page_end'] ?? null;
        $this->validateBarcodeReadingRange($data);

        return $this->mobileBarcodeFlows->lock($data['flow_id'], function () use ($request, $data, $actorType, $actorId): JsonResponse {
            $flow = $this->mobileBarcodeFlows->get($data['flow_id'], $actorType, $actorId);
            $existing = ! empty($flow['completed_entry_id'])
                ? QuranReadingEntry::query()->find($flow['completed_entry_id'])
                : null;
            if ($existing) {
                return $this->barcodeStoredResponse($existing, false);
            }

            $sheet = QuranReadingSheet::query()->with('siswa')->find($flow['sheet_id']);
            if (! $sheet || $sheet->status !== 'active' || (int) $sheet->siswa_id !== (int) $flow['siswa_id']) {
                throw ValidationException::withMessages(['flow_id' => 'Lembar tidak lagi tersedia. Scan lembar kembali.']);
            }
            $this->authorizeBarcodeStudent($request, $sheet->siswa, $actorType);
            $verified = $actorType === 'user';

            $entry = DB::transaction(fn () => $sheet->siswa->quranReadingEntries()->create([
                'reading_date' => now()->toDateString(),
                'page_start' => $data['page_start'],
                'page_end' => $data['page_end'],
                'surah_start' => $data['surah_start'],
                'ayah_start' => $data['ayah_start'],
                'surah_end' => $data['surah_end'],
                'ayah_end' => $data['ayah_end'],
                'notes' => $data['notes'] ?? null,
                'source' => 'barcode_manual',
                'submitted_by_type' => $actorType,
                'submitted_by_id' => $actorId,
                'status' => $verified ? QuranReadingEntry::STATUS_VERIFIED : QuranReadingEntry::STATUS_PENDING,
                'verified_by' => $verified ? $actorId : null,
                'verified_at' => $verified ? now() : null,
                'sheet_id' => $sheet->id,
            ]));
            $this->mobileBarcodeFlows->markCompleted($data['flow_id'], $flow, $entry);

            return $this->barcodeStoredResponse($entry, true);
        });
    }

    /**
     * Hapus catatan yang belum diverifikasi (milik sendiri).
     */
    public function destroy(Request $request, QuranReadingEntry $entry): JsonResponse
    {
        $siswa = $this->siswaFromToken($request);
        if (! $siswa) {
            return $this->forbiddenNonSiswa();
        }

        if ($entry->siswa_id !== $siswa->id) {
            return response()->json([
                'success' => false,
                'error' => 'Not found',
                'message' => 'Catatan tidak ditemukan',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        if ($entry->status === QuranReadingEntry::STATUS_VERIFIED) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Catatan yang sudah diverifikasi tidak dapat dihapus',
                'code' => 'ALREADY_VERIFIED',
            ], 422);
        }

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catatan dihapus',
        ]);
    }

    private function barcodeActor(Request $request): array
    {
        $actor = $request->user();
        if ($actor instanceof Siswa) {
            if ($this->tokenHasAbility($request, 'ortu')) {
                throw new AccessDeniedHttpException('Akun orang tua hanya dapat melihat catatan bacaan.');
            }
            if (! $this->tokenHasAbility($request, 'siswa')) {
                throw new AccessDeniedHttpException('Token siswa tidak valid.');
            }

            return ['siswa', (int) $actor->id];
        }

        if (! $actor instanceof User || (! $actor->isTeacher() && ! $actor->isAdmin())) {
            throw new AccessDeniedHttpException('Aktor tidak memiliki akses tracer Quran.');
        }

        return ['user', (int) $actor->id];
    }

    private function authorizeBarcodeStudent(Request $request, Siswa $student, string $actorType): void
    {
        if ($actorType === 'siswa') {
            if ((int) $request->user()->id !== (int) $student->id) {
                throw new AccessDeniedHttpException('Barcode bukan milik akun ini.');
            }

            return;
        }

        $user = $request->user();
        if ($user->isTeacher() && ! in_array($student->id, $user->getAssignedSiswaIds(), true)) {
            throw new AccessDeniedHttpException('Generus berada di luar binaan akun ini.');
        }
    }

    private function validateBarcodeReadingRange(array $data): void
    {
        $errors = [];
        if ($data['page_start'] !== null && $data['page_end'] !== null && (int) $data['page_end'] < (int) $data['page_start']) {
            $errors['page_end'] = 'Halaman akhir tidak boleh lebih kecil dari halaman awal.';
        }
        if ((int) $data['surah_end'] < (int) $data['surah_start']) {
            $errors['surah_end'] = 'Surat akhir tidak boleh berada sebelum surat awal.';
        }
        foreach (['start', 'end'] as $side) {
            $surah = (int) $data['surah_'.$side];
            $ayah = (int) $data['ayah_'.$side];
            if ($ayah > QuranCatalog::ayahCount($surah)) {
                $errors['ayah_'.$side] = 'Ayat melebihi jumlah ayat '.QuranCatalog::name($surah).'.';
            }
        }
        if ((int) $data['surah_start'] === (int) $data['surah_end'] && (int) $data['ayah_end'] < (int) $data['ayah_start']) {
            $errors['ayah_end'] = 'Ayat akhir tidak boleh lebih kecil dari ayat awal pada surat yang sama.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function barcodeStoredResponse(QuranReadingEntry $entry, bool $created): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $entry->status === QuranReadingEntry::STATUS_VERIFIED
                ? 'Catatan bacaan tersimpan dan langsung terverifikasi.'
                : 'Catatan bacaan berhasil dikirim untuk verifikasi.',
            'data' => [
                'entry_id' => $entry->id,
                'status' => $entry->status,
                'entry' => $this->entryPayload($entry),
            ],
        ], $created ? 201 : 200)->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function maskedNis(?string $nis): string
    {
        $value = trim((string) $nis);
        if ($value === '') {
            return 'Belum tersedia';
        }

        return str_repeat('•', max(2, mb_strlen($value) - 3)).mb_substr($value, -3);
    }

    private function entryPayload(QuranReadingEntry $e): array
    {
        return [
            'id' => $e->id,
            'reading_date' => $e->reading_date?->toDateString(),
            'page_start' => $e->page_start,
            'page_end' => $e->page_end,
            'page_count' => $e->page_count,
            'page_range_label' => $e->page_range_label,
            'surah_start' => $e->surah_start,
            'surah_start_nama' => $e->surah_start ? QuranCatalog::name((int) $e->surah_start) : null,
            'ayah_start' => $e->ayah_start,
            'surah_end' => $e->surah_end,
            'surah_end_nama' => $e->surah_end ? QuranCatalog::name((int) $e->surah_end) : null,
            'ayah_end' => $e->ayah_end,
            'mushaf_label' => $e->mushaf_label,
            'notes' => $e->notes,
            'source' => $e->source,
            'status' => $e->status,
            'is_verified' => $e->status === QuranReadingEntry::STATUS_VERIFIED,
            'verified_at' => $e->verified_at?->toIso8601String(),
            'verified_by' => $e->verifier?->name,
            'verification_notes' => $e->verification_notes,
        ];
    }
}
