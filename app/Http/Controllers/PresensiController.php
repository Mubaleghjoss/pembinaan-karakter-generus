<?php

namespace App\Http\Controllers;

use App\DTOs\RecordAttendanceDTO;
use App\DTOs\ScanQrDTO;
use App\Exceptions\DuplicateAttendanceException;
use App\Exceptions\QrTokenExpiredException;
use App\Exports\PresensiExport;
use App\Exports\PresensiTemplateExport;
use App\Http\Requests\Presensi\ScanQrRequest;
use App\Http\Requests\Presensi\StorePresensiRequest;
use App\Http\Requests\Presensi\UpdatePresensiRequest;
use App\Http\Resources\PresensiResource;
use App\Imports\PresensiImport;
use App\Models\AttendanceSchedule;
use App\Models\Kelas;
use App\Models\OrganizationalTeam;
use App\Models\PamongPresensi;
use App\Models\PointPeriod;
use App\Models\PointTransaction;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AttendanceOverviewService;
use App\Services\Contracts\PamongPresensiServiceInterface;
use App\Services\Contracts\PamongQrServiceInterface;
use App\Services\Contracts\PresensiServiceInterface;
use App\Services\GamificationService;
use App\Support\TargetGrade;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Controller untuk mengelola Presensi (Web)
 *
 * Controller ini menangani request terkait presensi untuk web interface.
 * Business logic didelegasikan ke PresensiService.
 */
class PresensiController extends Controller
{
    public function __construct(
        protected PresensiServiceInterface $presensiService,
        protected PamongQrServiceInterface $pamongQrService,
        protected PamongPresensiServiceInterface $pamongPresensiService,
        protected AttendanceOverviewService $attendanceOverview,
    ) {
        $this->middleware('auth')->except(['scan']);
        $this->middleware('pamong.permission:presensi,view')->only(['index', 'getData', 'getStats', 'recap', 'periodPanel', 'shareSummary']);
        $this->middleware('pamong.permission:manual_attendance,view')->only(['students']);
        $this->middleware('pamong.permission:manual_attendance,create')->only(['store', 'bulkStore', 'quickStatus', 'import', 'downloadTemplate']);
        $this->middleware('pamong.permission:presensi,edit')->only(['update']);
    }

    /**
     * Display a listing of presensi.
     */
    public function index(Request $request)
    {
        $tanggal = $this->resolveAttendanceDate($request);
        $autoAlphaCount = $this->backfillClosedSiswaAlpha($tanggal, $tanggal);

        $presensi = $this->buildListingQuery($request, $tanggal)
            ->latest('jam_masuk')
            ->paginate(20);

        $pointPeriods = PointPeriod::query()->orderByDesc('start_date')->get();
        $schedule = AttendanceSchedule::getActiveSchedule(AttendanceSchedule::TARGET_SISWA);
        $isOpen = $schedule ? $schedule->isOpen() : false;
        $canCreateManualAttendance = $request->user()->canCreateManualAttendance();
        $canAccessAllManualAttendanceStudents = $request->user()->canAccessAllManualAttendanceStudents();
        $canEditPresensi = $request->user()->hasPamongCrudPermission('presensi', 'edit');
        $schoolGradeOptions = TargetGrade::schoolClassOptions();
        $kelompokOptions = $this->attendanceOverview->groupOptions();
        $pamongOptions = User::query()->where('status', 'active')->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))->orderByRaw('COALESCE(name, username)')->get(['id', 'name', 'username']);

        return view('presensi.index', compact(
            'presensi',
            'pointPeriods',
            'schedule',
            'isOpen',
            'autoAlphaCount',
            'canCreateManualAttendance',
            'canAccessAllManualAttendanceStudents',
            'canEditPresensi',
            'schoolGradeOptions',
            'kelompokOptions',
            'pamongOptions'
        ));
    }

    /**
     * Keep the legacy create URL on the single, compact manual-input flow.
     */
    public function create()
    {
        return redirect(route('presensi.index', ['tab' => 'input']).'#input');
    }

    public function students(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->canCreateManualAttendance()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin input presensi manual.',
                'data' => [],
            ], 403);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = trim($validated['search'] ?? '');
        $canAccessAllStudents = $user->canAccessAllManualAttendanceStudents();
        $limit = (int) ($validated['per_page'] ?? ($canAccessAllStudents ? 50 : 10));

        $students = Siswa::query()
            ->select(['id', 'nis', 'nama', 'school_grade', 'foto_path', 'status', 'is_active'])
            ->active()
            ->forManualAttendance($user)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nama', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('nama')
            ->limit($limit)
            ->get()
            ->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'school_grade' => $siswa->school_grade,
                'school_grade_label' => $siswa->school_grade_label,
                'foto_url' => $siswa->foto_url,
            ]);

        return response()->json([
            'success' => true,
            'data' => $students,
            'meta' => [
                'can_access_all_students' => $canAccessAllStudents,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Store a newly created presensi.
     */
    public function store(StorePresensiRequest $request)
    {
        $siswa = Siswa::query()->findOrFail($request->validated('siswa_id'));

        if (! $siswa->isActive()) {
            return back()->withErrors([
                'siswa_id' => 'Presensi baru hanya dapat dicatat untuk siswa berstatus Aktif.',
            ])->withInput();
        }

        if (! $request->user()->canRecordManualAttendanceFor($siswa)) {
            $message = $request->user()->canCreateManualAttendance()
                ? 'Anda hanya dapat mengisi presensi siswa binaan.'
                : 'Anda tidak memiliki izin input presensi manual.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->withErrors(['siswa_id' => $message])->withInput();
        }

        try {
            $dto = RecordAttendanceDTO::fromRequest($request);
            $presensi = $this->presensiService->recordAttendance($dto);
        } catch (DuplicateAttendanceException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['siswa_id' => $e->getMessage()])->withInput();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi berhasil disimpan.',
                'data' => new PresensiResource($presensi->load('siswa')),
            ]);
        }

        return back()->with('success', 'Presensi berhasil disimpan.');
    }

    /**
     * Update the specified presensi.
     */
    public function update(UpdatePresensiRequest $request, Presensi $presensi)
    {
        $presensi->update($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi berhasil diperbarui.',
                'data' => new PresensiResource($presensi->fresh()->load('siswa')),
            ]);
        }

        return back()->with('success', 'Presensi berhasil diperbarui.');
    }

    public function quickStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', 'string', 'in:hadir,sakit,izin,alpha'],
        ]);
        $siswa = Siswa::query()->active()->findOrFail($validated['siswa_id']);

        if (! $request->user()->canRecordManualAttendanceFor($siswa)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin mengubah presensi Generus ini.',
            ], 403);
        }

        $presensi = DB::transaction(function () use ($validated, $request, $siswa) {
            $existing = Presensi::query()
                ->where('siswa_id', $siswa->id)
                ->whereDate('tanggal', $validated['tanggal'])
                ->lockForUpdate()
                ->first();

            if ($existing && ($existing->qr_code_used || data_get($existing->metadata, 'face.method') === 'face')) {
                return null;
            }

            $status = $validated['status'];
            $isToday = $validated['tanggal'] === now()->toDateString();
            $metadata = array_merge($existing?->metadata ?? [], [
                'manual_input' => [
                    'source' => 'group_summary_quick_status',
                    'updated_by' => $request->user()->id,
                    'updated_at' => now()->toIso8601String(),
                ],
            ]);
            $attributes = [
                'siswa_id' => $siswa->id,
                'tanggal' => $validated['tanggal'],
                'status' => $status,
                'jam_masuk' => $status === 'hadir' && $isToday ? now()->format('H:i:s') : null,
                'jam_keluar' => null,
                'keterangan' => $status === 'alpha' ? 'Tanpa keterangan' : null,
                'is_verified' => true,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'metadata' => $metadata,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing->fresh('siswa');
            }

            return Presensi::query()->create($attributes)->load('siswa');
        });

        if (! $presensi) {
            return response()->json([
                'success' => false,
                'message' => 'Presensi hasil scan tidak dapat ditimpa dari input cepat. Gunakan Koreksi Presensi.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Presensi '.$siswa->nama.' berhasil diperbarui.',
            'data' => new PresensiResource($presensi),
        ]);
    }

    public function shareSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'school_grade' => ['nullable', 'string', Rule::in(TargetGrade::values())],
            'pamong_id' => ['nullable', 'integer', 'exists:users,id'],
            'kelompok' => ['nullable', 'string', 'max:80'],
            'group' => ['nullable', 'string', 'max:80'],
        ]);
        $tanggal = $validated['tanggal'] ?? now()->toDateString();
        $this->backfillClosedSiswaAlpha($tanggal, $tanggal);
        $filters = [
            'school_grade' => $validated['school_grade'] ?? null,
            'pamong_id' => isset($validated['pamong_id']) ? (int) $validated['pamong_id'] : null,
            'kelompok' => $this->attendanceOverview->normalizeGroupFilter($validated['kelompok'] ?? null),
        ];
        $onlyGroup = $this->attendanceOverview->normalizeGroupFilter($validated['group'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $this->attendanceOverview->shareText($request->user(), $tanggal, $filters, $onlyGroup),
        ]);
    }

    /**
     * Remove the specified presensi.
     * Also reverses any attendance points that were awarded for this presensi.
     */
    public function destroy(Presensi $presensi)
    {
        // Reverse attendance points if they were awarded for this presensi
        $siswa = $presensi->siswa;
        if ($siswa) {
            try {
                $transactions = PointTransaction::where('siswa_id', $siswa->id)
                    ->where('reference_type', Presensi::class)
                    ->where('reference_id', $presensi->id)
                    ->get();

                if ($transactions->isNotEmpty()) {
                    $gamificationService = app(GamificationService::class);
                    $siswaPoint = $gamificationService->getOrCreateSiswaPoint($siswa);

                    foreach ($transactions as $transaction) {
                        // Reverse the points
                        $siswaPoint->addPoints(
                            -$transaction->points,
                            'attendance',
                            'Hapus presensi: '.$presensi->tanggal.' (-'.$transaction->points.' poin)',
                            $presensi
                        );
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Gagal reverse poin kehadiran: '.$e->getMessage());
            }
        }

        $presensi->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data presensi berhasil dihapus dan poin dikurangi.',
            ]);
        }

        return back()->with('success', 'Data presensi berhasil dihapus dan poin dikurangi.');
    }

    /**
     * Verify the specified presensi.
     */
    public function verify(Presensi $presensi)
    {
        $this->presensiService->verifyAttendance($presensi->id, auth()->id());

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi berhasil diverifikasi.',
            ]);
        }

        return back()->with('success', 'Presensi berhasil diverifikasi.');
    }

    /**
     * Process QR code scan for attendance.
     * Supports both siswa and pamong QR codes.
     */
    public function scan(ScanQrRequest $request): JsonResponse
    {
        try {
            // Get Active Schedule
            $schedule = AttendanceSchedule::getActiveSchedule();

            if (! $schedule) {
                $hasActiveSchedule = AttendanceSchedule::where('is_active', true)->exists();

                return response()->json([
                    'success' => false,
                    'message' => $hasActiveSchedule
                        ? 'Tidak ada jadwal presensi yang berlaku untuk tanggal hari ini.'
                        : 'Jadwal presensi belum dikonfigurasi. Hubungi admin.',
                ], 500);
            }

            // Check if attendance is open
            if (! $schedule->isOpen()) {
                return $this->attendanceScheduleUnavailableResponse($schedule);
            }

            $rawToken = $request->validated('token');

            // Try to parse as pamong or siswa QR
            $parsedPayload = $this->pamongQrService->parsePayload($rawToken);

            if ($parsedPayload && $parsedPayload['type'] === 'pamong') {
                if (! $schedule->targetsPamong()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Barcode tidak sesuai.',
                        'detail' => 'Barcode tidak sesuai. Jadwal presensi aktif saat ini hanya untuk siswa.',
                        'code' => 'BARCODE_TIDAK_SESUAI',
                    ], 400);
                }

                // Handle pamong QR scan
                return $this->handlePamongScan($parsedPayload, $request);
            }

            if (! $schedule->targetsSiswa()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode tidak sesuai.',
                    'detail' => 'Barcode tidak sesuai. Jadwal presensi aktif saat ini hanya untuk pamong.',
                    'code' => 'BARCODE_TIDAK_SESUAI',
                ], 400);
            }

            // Handle siswa QR scan (existing logic)
            return $this->handleSiswaScan($rawToken, $request);

        } catch (QrTokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode tidak sesuai.',
                'detail' => 'Barcode tidak sesuai atau sudah kedaluwarsa. Silakan generate ulang QR Code.',
                'code' => 'BARCODE_TIDAK_SESUAI',
            ], 400);
        } catch (DuplicateAttendanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Scan QR Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi admin.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function attendanceScheduleUnavailableResponse(AttendanceSchedule $schedule): JsonResponse
    {
        $now = Carbon::now();
        $openTime = Carbon::parse($schedule->open_time)->format('H:i');
        $closeTime = Carbon::parse($schedule->close_time)->format('H:i');
        $openAt = Carbon::parse($schedule->open_time)->setDate($now->year, $now->month, $now->day);
        $closeAt = Carbon::parse($schedule->close_time)->setDate($now->year, $now->month, $now->day);
        $dayName = strtolower($now->format('l'));
        $isTodayInSchedule = $schedule->isDateActive($now)
            && (empty($schedule->days) || in_array($dayName, $schedule->days ?? [], true));

        if ($isTodayInSchedule && $now->gt($closeAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Presensi sudah ditutup.',
                'detail' => "Presensi sudah ditutup.\nJam operasional: {$openTime} - {$closeTime}",
                'code' => 'PRESENSI_SUDAH_TUTUP',
            ], 400);
        }

        $detail = $isTodayInSchedule && $now->lt($openAt)
            ? "Belum waktunya presensi.\nJam operasional: {$openTime} - {$closeTime}"
            : "Belum waktunya presensi untuk jadwal hari ini.\nJam operasional: {$openTime} - {$closeTime}";

        return response()->json([
            'success' => false,
            'message' => 'Belum waktunya presensi.',
            'detail' => $detail,
            'code' => 'PRESENSI_BELUM_WAKTUNYA',
        ], 400);
    }

    /**
     * Handle pamong QR scan
     */
    protected function handlePamongScan(array $parsedPayload, ScanQrRequest $request): JsonResponse
    {
        $pamong = User::findOrFail($parsedPayload['id']);

        // Verify this is actually a pamong
        if (! $this->pamongQrService->isPamong($pamong)) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode tidak sesuai.',
                'detail' => 'Barcode tidak sesuai. QR Code ini bukan untuk presensi pamong.',
                'code' => 'BARCODE_TIDAK_SESUAI',
            ], 400);
        }

        $result = $this->pamongPresensiService->recordAttendance(
            $pamong,
            $parsedPayload['token'],
            [
                'device_info' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]
        );

        // Handle different scan results
        if ($result['status'] === 'already_present') {
            $presensi = $result['presensi'];
            $jamMasuk = Carbon::parse($presensi->jam_masuk)->format('H:i');

            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan presensi hari ini pada jam {$jamMasuk}.\nStatus: {$presensi->status}",
                'pamong' => [
                    'nama' => $pamong->name,
                    'username' => $pamong->username,
                    'status' => $presensi->status,
                    'jam_masuk' => $jamMasuk,
                ],
            ], 400);
        }

        if ($result['status'] === 'checkout') {
            $presensi = $result['presensi'];
            $jamKeluar = Carbon::parse($presensi->jam_keluar)->format('H:i');

            return response()->json([
                'success' => true,
                'message' => "{$pamong->name}, berhasil mencatat jam keluar!\nJam: {$jamKeluar}",
                'pamong' => [
                    'nama' => $pamong->name,
                    'username' => $pamong->username,
                    'foto' => $pamong->avatar_url,
                    'jam' => $jamKeluar,
                    'status' => 'checkout',
                ],
            ]);
        }

        // Success checkin response
        $presensi = $result['presensi'];
        $statusText = $presensi->status === 'hadir' ? 'HADIR' : 'TERLAMBAT';
        $message = "{$pamong->name}, {$statusText}!\nPRESENSI PAMONG BERHASIL.\nJam: ".now()->format('H:i:s');

        return response()->json([
            'success' => true,
            'message' => $message,
            'pamong' => [
                'nama' => $pamong->name,
                'username' => $pamong->username,
                'foto' => $pamong->avatar_url,
                'jam' => now()->format('H:i'),
                'status' => $presensi->status,
            ],
        ]);
    }

    /**
     * Handle siswa QR scan (existing logic)
     */
    protected function handleSiswaScan(string $rawToken, ScanQrRequest $request): JsonResponse
    {
        $studentId = null;
        $token = null;

        // Try PKG format first (PKG|1|STUDENT_ID|TOKEN|HASH)
        $delimiter = config('qrcode.payload.delimiter', '|');
        $prefix = config('qrcode.payload.prefix', 'PKG');

        if (str_starts_with($rawToken, $prefix.$delimiter)) {
            $parts = explode($delimiter, $rawToken);
            if (count($parts) >= 4) {
                $studentId = (int) $parts[2];
                $token = $parts[3];
            }
        } else {
            // Try JSON format
            $qrData = json_decode($rawToken, true);
            if ($qrData && isset($qrData['student_id']) && isset($qrData['token'])) {
                $studentId = $qrData['student_id'];
                $token = $qrData['token'];
            }
        }

        if (! $studentId || ! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode tidak sesuai.',
                'detail' => 'Barcode tidak sesuai. Gunakan QR Code presensi PKG yang valid.',
                'code' => 'BARCODE_TIDAK_SESUAI',
            ], 400);
        }

        // Create DTO for scan
        $dto = new ScanQrDTO(
            studentId: $studentId,
            token: $token,
            location: null,
            deviceInfo: $request->userAgent(),
            ipAddress: $request->ip()
        );

        // Process scan via service
        $result = $this->presensiService->scanQrCode($dto);

        // Handle different scan results
        if ($result['status'] === 'already_present') {
            $presensi = $result['presensi'];
            $jamMasuk = Carbon::parse($presensi->jam_masuk)->format('H:i');

            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan presensi hari ini pada jam {$jamMasuk}.\nStatus: {$presensi->status}",
                'student' => [
                    'nama' => $presensi->siswa->nama,
                    'nis' => $presensi->siswa->nis,
                    'status' => $presensi->status,
                    'jam_masuk' => $jamMasuk,
                ],
            ], 400);
        }

        // Success response
        $presensi = $result['presensi'];
        $siswa = $presensi->siswa;
        $statusText = $presensi->status === 'hadir' ? 'HADIR' : 'TERLAMBAT';
        $message = "{$siswa->nama}, {$statusText}!`nKAMU TELAH TERDAFTAR PADA HARI INI.`nJam: ".now()->format('H:i:s').'`nLANCAR BAROKAH!';

        return response()->json([
            'success' => true,
            'message' => $message,
            'student' => [
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'foto' => $siswa->foto_url,
                'jam' => now()->format('H:i'),
                'status' => $presensi->status,
            ],
        ]);
    }

    /**
     * Display attendance recap.
     */
    public function recap(Request $request)
    {
        $query = array_merge($request->query(), [
            'tab' => 'rekap',
            'panel' => 'laporan-periode',
        ]);

        return redirect(route('presensi.index', $query).'#laporan-periode');
    }

    public function periodPanel(Request $request)
    {
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        $audience = in_array($request->input('audience'), ['siswa', 'pamong'], true)
            ? $request->input('audience')
            : 'all';
        $schoolGrade = TargetGrade::normalizeSchoolClassInput($request->input('school_grade'));
        $binaanPamongId = $request->filled('pamong_id') ? $request->integer('pamong_id') : null;
        $kelompok = $this->attendanceOverview->normalizeGroupFilter($request->input('kelompok'));
        $pamongRole = in_array($request->input('pamong_role'), User::attendanceRoleNames(), true)
            ? $request->input('pamong_role')
            : null;
        $teamId = $request->filled('team_id') ? $request->integer('team_id') : null;
        $status = $request->input('status');

        if ($audience !== 'pamong') {
            $this->backfillClosedSiswaAlpha($startDate, $endDate);
        }

        if ($audience !== 'siswa') {
            $this->pamongPresensiService->backfillClosedAlpha(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );
        }

        $rows = collect();
        $statsRows = collect();

        if ($audience !== 'pamong') {
            $siswaRows = $this->buildSiswaRecapRows($status, $startDate, $endDate, $schoolGrade, $binaanPamongId, $kelompok);
            $rows = $rows->merge($siswaRows);
            $statsRows = $statsRows->merge(
                $status ? $this->buildSiswaRecapRows(null, $startDate, $endDate, $schoolGrade, $binaanPamongId, $kelompok) : $siswaRows
            );
        }

        if ($audience !== 'siswa') {
            $pamongRows = $this->buildPamongRecapRows($status, $startDate, $endDate, $pamongRole, $teamId);
            $rows = $rows->merge($pamongRows);
            $statsRows = $statsRows->merge(
                $status ? $this->buildPamongRecapRows(null, $startDate, $endDate, $pamongRole, $teamId) : $pamongRows
            );
        }

        $rows = $rows
            ->sort(function (array $a, array $b) {
                return [$b['date_sort'], $b['time_sort'], $a['name']]
                    <=> [$a['date_sort'], $a['time_sort'], $b['name']];
            })
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $records = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => route('presensi.index'),
                'query' => array_merge($request->query(), [
                    'tab' => 'rekap',
                    'panel' => 'laporan-periode',
                ]),
            ]
        );

        $recap = $this->buildRecapStats($statsRows);
        $typeRecap = [
            'siswa' => $this->buildRecapStats($statsRows->where('type', 'siswa')->values()),
            'pamong' => $this->buildRecapStats($statsRows->where('type', 'pamong')->values()),
        ];

        $schoolGradeOptions = TargetGrade::schoolClassOptions();
        $binaanPamongOptions = User::query()->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
            ->orderBy('name')->get(['id', 'name', 'username']);
        $kelompokOptions = $this->attendanceOverview->groupOptions();
        $teamOptions = OrganizationalTeam::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);
        $pamongRoleOptions = [
            User::ROLE_TEACHER => 'Pamong',
            User::ROLE_PKG_MANAGER => 'Pengurus PKG',
            User::ROLE_ADMIN => 'Admin',
        ];

        return view('presensi.partials.period-report', compact(
            'records',
            'recap',
            'typeRecap',
            'schoolGradeOptions',
            'binaanPamongOptions',
            'kelompokOptions',
            'teamOptions',
            'pamongRoleOptions',
            'startDate',
            'endDate',
            'audience',
            'kelompok',
            'pamongRole',
            'teamId'
        ));
    }

    /**
     * Get presensi data for web interface (JSON).
     */
    public function getData(Request $request): JsonResponse
    {
        $tanggal = $this->resolveAttendanceDate($request);
        $allDates = $request->boolean('all_dates');

        // Mode antrean dari dashboard harus menampilkan lintas tanggal karena
        // angka dashboard juga menghitung seluruh tanggal. Jangan membuat
        // alpha otomatis lintas periode di sini; backfill hanya untuk harian.
        if (! $allDates) {
            $this->backfillClosedSiswaAlpha($tanggal, $tanggal);
        }

        $dateScope = $allDates
            ? [Presensi::query()->min('tanggal') ?: $tanggal, $tanggal]
            : $tanggal;
        $presensiQuery = $this->buildListingQuery($request, $dateScope);
        $this->applyStatusFilter($presensiQuery, $request->input('status'));

        $presensi = $presensiQuery
            ->when($request->verified !== null && $request->verified !== '', fn ($query) => $query->where('is_verified', $request->boolean('verified')))
            ->latest('jam_masuk')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => PresensiResource::collection($presensi),
            'meta' => [
                'current_page' => $presensi->currentPage(),
                'from' => $presensi->firstItem(),
                'last_page' => $presensi->lastPage(),
                'per_page' => $presensi->perPage(),
                'to' => $presensi->lastItem(),
                'total' => $presensi->total(),
            ],
            // Ringkasan kelompok bersifat harian; sembunyikan pada mode
            // antrean lintas tanggal agar tidak disalahartikan.
            'group_summary' => $allDates ? [] : $this->buildKelompokSummary($request, $tanggal),
        ]);
    }

    /**
     * Get statistics for web interface.
     */
    public function getStats(Request $request): JsonResponse
    {
        $tanggal = $this->resolveAttendanceDate($request);
        $this->backfillClosedSiswaAlpha($tanggal, $tanggal);
        $query = $this->buildListingQuery($request, $tanggal);
        $stats = [
            'total' => (clone $query)->count(),
            'hadir' => (clone $query)->where('status', 'hadir')->count(),
            'terlambat' => (clone $query)->where('status', 'terlambat')->count(),
            'tidak_hadir' => (clone $query)->whereIn('status', ['alpha', 'tidak_hadir', 'izin', 'sakit'])->count(),
            'verified' => (clone $query)->where('is_verified', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function bulkVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['nullable', 'date', 'date_format:Y-m-d'],
            'school_grade' => ['nullable', 'string', Rule::in(TargetGrade::values())],
            'pamong_id' => ['nullable', 'integer', 'exists:users,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'kelompok' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'in:hadir,terlambat,izin,sakit,alpha,tidak_hadir'],
            'verified' => ['nullable', 'in:0,1'],
            'all_dates' => ['nullable', 'boolean'],
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();
        $allDates = (bool) ($validated['all_dates'] ?? false);

        if (! $allDates) {
            $this->backfillClosedSiswaAlpha($tanggal, $tanggal);
        }

        $dateScope = $allDates
            ? [Presensi::query()->min('tanggal') ?: $tanggal, $tanggal]
            : $tanggal;
        $query = $this->buildListingQuery($request, $dateScope);

        $this->applyStatusFilter($query, $validated['status'] ?? null);

        if (array_key_exists('verified', $validated) && $validated['verified'] !== null && $validated['verified'] !== '') {
            $query->where('is_verified', (bool) $validated['verified']);
        }

        $updated = $query
            ->where('is_verified', false)
            ->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => $updated > 0
                ? "{$updated} data presensi berhasil diverifikasi."
                : 'Tidak ada data presensi yang perlu diverifikasi.',
            'updated' => $updated,
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        if (! $request->user()->canAccessAllManualAttendanceStudents()) {
            return response()->json([
                'success' => false,
                'message' => 'Input presensi massal hanya untuk akun dengan izin Semua Siswa.',
            ], 403);
        }

        $validated = $request->validate([
            'school_grade' => ['nullable', 'required_without:pamong_id', 'string', Rule::in(TargetGrade::values())],
            'pamong_id' => ['nullable', 'required_without:school_grade', 'integer', 'exists:users,id'],
            'tanggal' => ['required', 'date', 'date_format:Y-m-d'],
            'status' => ['required', 'string', 'in:hadir,terlambat,izin,sakit,alpha,tidak_hadir'],
        ]);

        $status = $this->normalizeStatusFilter($validated['status']) ?: 'alpha';
        $siswaIds = Siswa::query()
            ->active()
            ->when($validated['school_grade'] ?? null, fn ($query, $grade) => $query->where('school_grade', $grade))
            ->when($validated['pamong_id'] ?? null, fn ($query, $pamongId) => $query->whereHas('pamongAssignments', fn ($assignment) => $assignment->where('pamong_id', $pamongId)))
            ->orderBy('id')
            ->pluck('id');

        if ($siswaIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada siswa aktif di kelas ini.',
            ], 422);
        }

        $existingSiswaIds = Presensi::query()
            ->whereDate('tanggal', $validated['tanggal'])
            ->whereIn('siswa_id', $siswaIds)
            ->pluck('siswa_id');

        $missingSiswaIds = $siswaIds->diff($existingSiswaIds)->values();

        if ($missingSiswaIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua siswa di kelas ini sudah memiliki data presensi pada tanggal tersebut.',
                'created' => 0,
            ]);
        }

        $timestamp = now();
        $jamMasuk = in_array($status, ['hadir', 'terlambat'], true) ? $timestamp->format('H:i:s') : null;
        $rows = $missingSiswaIds
            ->map(fn (int $siswaId) => [
                'siswa_id' => $siswaId,
                'tanggal' => $validated['tanggal'],
                'jam_masuk' => $jamMasuk,
                'jam_keluar' => null,
                'status' => $status,
                'qr_code_used' => null,
                'scan_location' => null,
                'scan_device_info' => null,
                'scan_ip_address' => null,
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => $timestamp,
                'keterangan' => 'Input presensi massal.',
                'metadata' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        $created = 0;
        foreach (array_chunk($rows, 100) as $chunk) {
            $created += Presensi::query()->insertOrIgnore($chunk);
        }

        return response()->json([
            'success' => true,
            'message' => "{$created} data presensi massal berhasil disimpan.",
            'created' => $created,
        ]);
    }

    public function export(Request $request)
    {
        $tanggal = $this->resolveAttendanceDate($request);
        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;
        $this->backfillClosedSiswaAlpha($tanggal, $tanggal, $kelasId);

        $query = $this->buildListingQuery($request, $tanggal);
        $this->applyStatusFilter($query, $request->input('status'));

        $records = $query
            ->when($request->verified !== null && $request->verified !== '', fn ($query) => $query->where('is_verified', $request->boolean('verified')))
            ->orderBy('tanggal')
            ->orderBy('siswa_id')
            ->get();

        $kelasName = $kelasId ? Kelas::query()->whereKey($kelasId)->value('nama') : 'Semua Kelas';
        $status = $this->statusLabel($this->normalizeStatusFilter($request->input('status'))) ?: 'Semua Status';
        $verified = match ((string) $request->input('verified', '')) {
            '1' => 'Terverifikasi',
            '0' => 'Belum Verifikasi',
            default => 'Semua',
        };

        return (new PresensiExport($records, [
            'tanggal' => $tanggal,
            'kelas' => $kelasName,
            'status' => $status,
            'verified' => $verified,
        ]))->download('data-presensi-'.$tanggal.'.xlsx');
    }

    /**
     * Download template Excel untuk import presensi.
     */
    public function downloadTemplate(Request $request)
    {
        if (! $request->user()->canAccessAllManualAttendanceStudents()) {
            return back()->with('error', 'Unduh template impor hanya untuk akun dengan izin Semua Siswa.');
        }

        $export = new PresensiTemplateExport(true);

        return $export->download('template-presensi.xlsx');
    }

    /**
     * Import data presensi dari file Excel.
     */
    public function import(Request $request)
    {
        if (! $request->user()->canAccessAllManualAttendanceStudents()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impor presensi hanya untuk akun dengan izin Semua Siswa.',
                ], 403);
            }

            return back()->with('error', 'Impor presensi hanya untuk akun dengan izin Semua Siswa.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
            'period_id' => 'nullable|exists:point_periods,id',
            'source_label' => 'nullable|string|max:120',
            'award_points' => 'nullable|boolean',
            'mark_verified' => 'nullable|boolean',
        ], [
            'file.required' => 'File harus diupload.',
            'file.mimes' => 'File harus berformat Excel (xlsx, xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new PresensiImport([
                'period_id' => $request->integer('period_id') ?: null,
                'source_label' => $request->input('source_label'),
                'award_points' => $request->boolean('award_points'),
                'mark_verified' => $request->boolean('mark_verified', true),
                'imported_by' => auth()->id(),
            ]);
            $results = $import->import($request->file('file'));

            $message = "Impor selesai. Berhasil: {$results['success']}, Gagal: {$results['failed']}";
            $message .= " (presensi siswa baru: {$results['siswa_created']}, presensi siswa diperbarui: {$results['siswa_updated']}, presensi pamong baru: {$results['pamong_created']}, presensi pamong diperbarui: {$results['pamong_updated']})";
            if (($results['points_awarded'] ?? 0) > 0) {
                $message .= ", Poin ditambahkan: {$results['points_awarded']}";
            }

            $recapQuery = $this->importRecapQuery($results);
            $recapUrl = ! empty($recapQuery) ? route('presensi.recap', $recapQuery) : null;
            if (! empty($recapQuery['start_date']) && ! empty($recapQuery['end_date'])) {
                $startLabel = Carbon::parse($recapQuery['start_date'])->translatedFormat('d M Y');
                $endLabel = Carbon::parse($recapQuery['end_date'])->translatedFormat('d M Y');
                $message .= ". Rentang data: {$startLabel} - {$endLabel}";
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $results,
                    'recap_url' => $recapUrl,
                ]);
            }

            if ($results['failed'] > 0) {
                return back()
                    ->with('warning', $message)
                    ->with('import_errors', $results['errors'])
                    ->with('import_recap_url', $recapUrl);
            }

            if ($recapUrl) {
                return redirect($recapUrl)->with('success', $message);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Import Presensi Error: '.$e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal import data: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal import data: '.$e->getMessage());
        }
    }

    protected function importRecapQuery(array $results): array
    {
        if (empty($results['date_min']) || empty($results['date_max'])) {
            return [];
        }

        return [
            'start_date' => $results['date_min'],
            'end_date' => $results['date_max'],
            'audience' => 'all',
        ];
    }

    protected function buildListingQuery(Request $request, string|array $tanggal)
    {
        $siswaColumns = ['id', 'nis', 'nama', 'school_grade', 'alamat', 'foto_path'];

        if (Siswa::hasKelompokColumn()) {
            $siswaColumns[] = 'kelompok';
        }

        return Presensi::query()
            ->select([
                'id',
                'siswa_id',
                'tanggal',
                'jam_masuk',
                'jam_keluar',
                'status',
                'is_verified',
                'verified_by',
                'verified_at',
                'keterangan',
                'metadata',
            ])
            ->with([
                'siswa' => fn ($query) => $query->select($siswaColumns),
                'verifier:id,name',
            ])
            ->when(
                is_array($tanggal),
                fn ($query) => $query->whereBetween('tanggal', $tanggal),
                fn ($query) => $query->whereDate('tanggal', $tanggal)
            )
            ->whereHas('siswa', function ($student) use ($request) {
                $user = $request->user();
                $student->active()
                    ->when($request->filled('school_grade'), fn ($query) => $query->where('school_grade', $request->string('school_grade')))
                    ->when($request->filled('pamong_id'), fn ($query) => $query->whereHas('pamongAssignments', fn ($assignment) => $assignment->where('pamong_id', $request->integer('pamong_id'))));

                if (! ($user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded())) {
                    $student->assignedTo($user->id);
                }

                $this->applyStudentGroupFilter($student, $request->input('kelompok'));
            });
    }

    protected function buildSiswaRecapRows(
        ?string $status,
        Carbon $startDate,
        Carbon $endDate,
        ?string $schoolGrade,
        ?int $pamongId,
        ?string $kelompok
    ): Collection {
        $siswaColumns = ['id', 'nis', 'nama', 'school_grade', 'alamat', 'foto_path'];

        if (Siswa::hasKelompokColumn()) {
            $siswaColumns[] = 'kelompok';
        }

        $query = Presensi::query()
            ->select([
                'id',
                'siswa_id',
                'tanggal',
                'jam_masuk',
                'jam_keluar',
                'status',
                'is_verified',
                'verified_by',
                'verified_at',
                'keterangan',
                'metadata',
            ])
            ->with([
                'siswa' => fn ($query) => $query->select($siswaColumns),
                'verifier:id,name',
            ])
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereHas('siswa', function ($query) use ($schoolGrade, $pamongId, $kelompok) {
                $user = request()->user();
                $query->active()
                    ->when($schoolGrade, fn ($query, $grade) => $query->where('school_grade', $grade))
                    ->when($pamongId, fn ($query, $id) => $query->byPamong($id))
                    ->when($kelompok, fn ($query) => $this->applyStudentGroupFilter($query, $kelompok));

                if (! ($user->isAdmin() || $user->isPengurusPkg() || $user->isPamongExcluded())) {
                    $query->assignedTo($user->id);
                }
            });

        $this->applyStatusFilter($query, $status);

        return $query
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->get()
            ->map(function (Presensi $item) {
                $date = $item->tanggal instanceof Carbon ? $item->tanggal : Carbon::parse($item->tanggal);
                $siswa = $item->siswa;
                $status = $this->normalizeStatusFilter($item->status) ?: $item->status;

                return [
                    'key' => 'siswa-'.$item->id,
                    'type' => 'siswa',
                    'type_label' => 'Siswa',
                    'date' => $date->format('d M Y'),
                    'date_sort' => $date->format('Y-m-d'),
                    'name' => $siswa?->nama ?: '-',
                    'identifier' => $siswa?->nis ?: '-',
                    'unit' => $siswa?->school_grade_label ?: 'Kelas belum dikonfirmasi',
                    'group' => $siswa?->kelompok_label ?: '-',
                    'role' => 'Siswa',
                    'team' => '-',
                    'jam_masuk' => $this->formatRecapTime($item->jam_masuk) ?: '-',
                    'jam_keluar' => $this->formatRecapTime($item->jam_keluar) ?: '-',
                    'time_sort' => $this->formatRecapTime($item->jam_masuk, 'H:i:s') ?: '00:00:00',
                    'status' => $status,
                    'status_label' => $this->statusLabel($status) ?: ucfirst((string) $status),
                    'status_class' => $this->statusBadgeClass($status),
                    'keterangan' => $item->keterangan ?: '-',
                    'verified_by' => $item->verifier?->name,
                    'verified_at' => $this->formatRecapTime($item->verified_at),
                    'face_proof' => $this->buildFaceProof($item->metadata),
                ];
            });
    }

    protected function buildPamongRecapRows(
        ?string $status,
        Carbon $startDate,
        Carbon $endDate,
        ?string $pamongRole,
        ?int $teamId
    ): Collection {
        $query = PamongPresensi::query()
            ->select([
                'id',
                'user_id',
                'tanggal',
                'jam_masuk',
                'jam_keluar',
                'status',
                'keterangan',
                'is_verified',
                'verified_by',
                'verified_at',
                'metadata',
            ])
            ->with([
                'user:id,name,username,role_id,organizational_team_id,organizational_title,status',
                'user.role:id,name,display_name',
                'user.organizationalTeam:id,name,short_name',
                'verifier:id,name',
            ])
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereHas('user', function ($query) use ($pamongRole, $teamId) {
                $query->where('status', 'active')
                    ->whereHas('role', function ($query) use ($pamongRole) {
                        $query->whereIn('name', $pamongRole ? [$pamongRole] : User::attendanceRoleNames());
                    })
                    ->when($teamId, fn ($query, $teamId) => $query->where('organizational_team_id', $teamId));
            });

        $this->applyStatusFilter($query, $status);

        return $query
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->get()
            ->map(function (PamongPresensi $item) {
                $date = $item->tanggal instanceof Carbon ? $item->tanggal : Carbon::parse($item->tanggal);
                $user = $item->user;
                $status = $this->normalizeStatusFilter($item->status) ?: $item->status;
                $roleLabel = $user?->operationalRoleLabel() ?: 'Pamong';
                $teamLabel = $user?->organizationalTeam?->short_name
                    ?: $user?->organizationalTeam?->name
                    ?: 'Tanpa bidang';

                return [
                    'key' => 'pamong-'.$item->id,
                    'type' => 'pamong',
                    'type_label' => 'Pamong/Pengurus',
                    'date' => $date->format('d M Y'),
                    'date_sort' => $date->format('Y-m-d'),
                    'name' => $user?->name ?: '-',
                    'identifier' => $roleLabel,
                    'unit' => $teamLabel,
                    'group' => $user?->organizational_title ?: $roleLabel,
                    'role' => $roleLabel,
                    'team' => $teamLabel,
                    'jam_masuk' => $this->formatRecapTime($item->jam_masuk) ?: '-',
                    'jam_keluar' => $this->formatRecapTime($item->jam_keluar) ?: '-',
                    'time_sort' => $this->formatRecapTime($item->jam_masuk, 'H:i:s') ?: '00:00:00',
                    'status' => $status,
                    'status_label' => $this->statusLabel($status) ?: ucfirst((string) $status),
                    'status_class' => $this->statusBadgeClass($status),
                    'keterangan' => $item->keterangan ?: '-',
                    'verified_by' => $item->verifier?->name,
                    'verified_at' => $this->formatRecapTime($item->verified_at),
                    'face_proof' => $this->buildFaceProof($item->metadata),
                ];
            });
    }

    protected function buildFaceProof(?array $metadata): ?array
    {
        $face = data_get($metadata, 'face');

        if (! is_array($face) || data_get($face, 'method') !== 'face') {
            return null;
        }

        $proofPath = data_get($face, 'proof_path');

        return [
            'proof_path' => $proofPath,
            'proof_url' => $proofPath ? Storage::disk('public')->url($proofPath) : data_get($face, 'proof_url'),
            'similarity_percent' => data_get($face, 'similarity_percent'),
            'match_distance' => data_get($face, 'match_distance'),
            'distance_meters' => data_get($face, 'location.distance_meters'),
            'radius_meters' => data_get($face, 'location.radius_meters'),
            'accuracy_meters' => data_get($face, 'location.accuracy_meters'),
        ];
    }

    protected function buildRecapStats(Collection $rows): array
    {
        $total = $rows->count();
        $hadir = $rows->where('status', 'hadir')->count();
        $terlambat = $rows->where('status', 'terlambat')->count();
        $present = $hadir + $terlambat;
        $percent = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'izin' => $rows->where('status', 'izin')->count(),
            'sakit' => $rows->where('status', 'sakit')->count(),
            'alpha' => $rows->where('status', 'alpha')->count(),
            'persentase_numeric' => $percent,
            'persentase' => rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.').'%',
        ];
    }

    protected function formatRecapTime(mixed $value, string $format = 'H:i'): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return ($value instanceof Carbon ? $value : Carbon::parse($value))->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            'hadir' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'terlambat' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'alpha', 'tidak_hadir' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    protected function backfillClosedSiswaAlpha(Carbon|string $startDate, Carbon|string $endDate, ?int $kelasId = null): int
    {
        $start = $startDate instanceof Carbon ? $startDate->format('Y-m-d') : Carbon::parse($startDate)->format('Y-m-d');
        $end = $endDate instanceof Carbon ? $endDate->format('Y-m-d') : Carbon::parse($endDate)->format('Y-m-d');

        return $this->presensiService->backfillClosedAlpha($start, $end, $kelasId);
    }

    protected function resolveAttendanceDate(Request $request): string
    {
        return Carbon::parse($request->input('tanggal', $request->input('date', now()->toDateString())))->format('Y-m-d');
    }

    protected function normalizeStatusFilter(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return $status === 'tidak_hadir' ? 'alpha' : $status;
    }

    protected function applyStatusFilter($query, ?string $status): void
    {
        $status = $this->normalizeStatusFilter($status);

        if (! $status) {
            return;
        }

        if ($status === 'alpha') {
            $query->whereIn('status', ['alpha', 'tidak_hadir']);

            return;
        }

        if ($status === 'izin_sakit') {
            $query->whereIn('status', ['izin', 'sakit']);

            return;
        }

        $query->where('status', $status);
    }

    protected function statusLabel(?string $status): ?string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha', 'tidak_hadir' => 'Alpa (Tanpa Keterangan)',
            default => null,
        };
    }

    protected function buildKelompokSummary(Request $request, string $tanggal): array
    {
        return $this->attendanceOverview->groupSummary($request->user(), $tanggal, [
            'school_grade' => $request->input('school_grade'),
            'pamong_id' => $request->filled('pamong_id') ? $request->integer('pamong_id') : null,
            'kelompok' => $this->attendanceOverview->normalizeGroupFilter($request->input('kelompok')),
        ]);
    }

    protected function applyStudentGroupFilter($query, ?string $value): void
    {
        $group = $this->attendanceOverview->normalizeGroupFilter($value);
        if (! $group) {
            return;
        }

        $validGroups = array_keys(Siswa::kelompokOptions());
        if (! Siswa::hasKelompokColumn()) {
            $group === AttendanceOverviewService::UNASSIGNED_GROUP
                ? $query->where(fn ($nested) => $nested->whereNull('alamat')->orWhere('alamat', '')->orWhereNotIn('alamat', $validGroups))
                : $query->where('alamat', $group);

            return;
        }

        if ($group === AttendanceOverviewService::UNASSIGNED_GROUP) {
            $query->where(function ($nested) use ($validGroups) {
                $nested->whereNotIn('kelompok', $validGroups)
                    ->orWhere(function ($fallback) use ($validGroups) {
                        $fallback->where(fn ($missing) => $missing->whereNull('kelompok')->orWhere('kelompok', ''))
                            ->where(fn ($legacy) => $legacy->whereNull('alamat')->orWhere('alamat', '')->orWhereNotIn('alamat', $validGroups));
                    });
            });

            return;
        }

        $query->where(function ($nested) use ($group) {
            $nested->where('kelompok', $group)
                ->orWhere(function ($fallback) use ($group) {
                    $fallback->where(fn ($missing) => $missing->whereNull('kelompok')->orWhere('kelompok', ''))
                        ->where('alamat', $group);
                });
        });
    }
}
