<?php

namespace App\Http\Controllers;

use App\DTOs\CreateSiswaDTO;
use App\DTOs\UpdateSiswaDTO;
use App\Exports\SiswaTemplateExport;
use App\Http\Requests\Siswa\StoreSiswaRequest;
use App\Http\Requests\Siswa\UpdateSiswaRequest;
use App\Http\Resources\SiswaResource;
use App\Imports\SiswaImport;
use App\Models\Level;
use App\Models\Siswa;
use App\Models\SiswaPoint;
use App\Models\PamongSiswa;
use App\Models\User;
use App\Models\WebAuthnCredential;
use App\Services\Contracts\SiswaServiceInterface;
use App\Support\TargetGrade;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Validation\ValidationException;

/**
 * Controller untuk mengelola Siswa (Web)
 *
 * Controller ini menangani request terkait siswa untuk web interface.
 * Business logic didelegasikan ke SiswaService.
 */
class SiswaController extends Controller
{
    public function __construct(
        protected SiswaServiceInterface $siswaService
    ) {
        $this->middleware('auth');
        
        // Apply pamong permission middleware for CRUD operations
        $this->middleware('pamong.permission:siswa,view')->only(['index', 'getList', 'printCards']);
        $this->middleware('pamong.permission:siswa,create')->only(['create', 'store']);
        $this->middleware('pamong.permission:siswa,edit')->only(['edit', 'update']);
        $this->middleware('pamong.permission:siswa,delete')->only(['destroy']);
        $this->middleware('pamong.permission:siswa,import')->only(['import', 'downloadTemplate']);
    }

    /**
     * Display a listing of siswa.
     */
    public function index(Request $request)
    {
        $filters = [];

        if ($request->search) {
            $filters['search'] = $request->search;
        }

        if ($request->school_grade) $filters['school_grade'] = $request->school_grade;
        if ($request->pamong_id) $filters['pamong_id'] = $request->pamong_id;

        $siswa = $this->siswaService->paginate($filters, 20);
        $kelas = collect();
        $schoolGradeOptions = TargetGrade::schoolClassOptions();
        $pamongOptions = User::query()->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
            ->orderByRaw('COALESCE(name, username)')->get(['id', 'name', 'username']);
        $biodataStats = $this->siswaService->getBiodataStatistics();
        $kelompokOptions = Siswa::kelompokOptions();
        $adminReviewers = User::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_ADMIN))
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        $dashboardStats = Cache::remember('siswa:index:stats', now()->addSeconds(90), function () {
            $supportsCredentialPublicKey = WebAuthnCredential::supportsCredentialPublicKey();

            return [
                'totalSiswaAktif' => Siswa::active()->count(),
                'totalBiometrik' => $supportsCredentialPublicKey
                    ? WebAuthnCredential::where('user_type', 'siswa')
                        ->whereNotNull('credential_public_key')
                        ->distinct('user_id')
                        ->count('user_id')
                    : 0,
                'totalBiometrikLegacy' => $supportsCredentialPublicKey
                    ? WebAuthnCredential::where('user_type', 'siswa')
                        ->whereNull('credential_public_key')
                        ->distinct('user_id')
                        ->count('user_id')
                    : WebAuthnCredential::where('user_type', 'siswa')
                        ->distinct('user_id')
                        ->count('user_id'),
            ];
        });

        $totalSiswaAktif = $dashboardStats['totalSiswaAktif'];
        $totalBiometrik = $dashboardStats['totalBiometrik'];
        $totalBiometrikLegacy = $dashboardStats['totalBiometrikLegacy'];

        // Level distribution
        $levelDistribution = Level::active()->orderBy('level')
            ->withCount(['siswaPoints as siswa_count' => function ($q) {
                $q->whereHas('siswa', fn($s) => $s->active());
            }])
            ->get();

        return view('siswa.index', compact(
            'siswa', 'kelas', 'biodataStats',
            'totalSiswaAktif', 'totalBiometrik', 'totalBiometrikLegacy', 'levelDistribution',
            'kelompokOptions', 'schoolGradeOptions', 'pamongOptions',
            'adminReviewers'
        ));
    }

    /**
     * Display all siswa accounts for printing.
     */
    public function accounts(Request $request)
    {
        $query = Siswa::query()->active();

        if ($request->filled('school_grade')) {
            $query->where('school_grade', $request->school_grade);
        }

        $siswaList = $query->orderBy('school_grade')->orderBy('nama')->get();
        $schoolGradeOptions = TargetGrade::schoolClassOptions();

        return view('siswa.accounts', compact('siswaList', 'schoolGradeOptions'));

        return view('siswa.index', compact('siswa', 'kelas'));
    }

    /**
     * Show the form for creating a new siswa.
     */
    public function create()
    {
        $kelompokOptions = Siswa::kelompokOptions();
        $targetGradeOptions = TargetGrade::schoolClassOptions();

        return view('siswa.create', compact('kelompokOptions', 'targetGradeOptions'));
    }

    /**
     * Store a newly created siswa.
     */
    public function store(StoreSiswaRequest $request)
    {
        $dto = CreateSiswaDTO::fromRequest($request);
        $siswa = $this->siswaService->create($dto);

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Siswa berhasil ditambahkan.',
                'data' => $siswa,
            ]);
        }

        return redirect()->route('siswa.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified siswa.
     */
    public function edit(Siswa $siswa)
    {
        $kelompokOptions = Siswa::kelompokOptions();
        $targetGradeOptions = TargetGrade::schoolClassOptions();

        return view('siswa.edit', compact('siswa', 'kelompokOptions', 'targetGradeOptions'));
    }

    /**
     * Update the specified siswa.
     */
    public function update(UpdateSiswaRequest $request, Siswa $siswa)
    {
        $nextStatus = $request->validated('status');
        if ($nextStatus && $nextStatus !== $siswa->status
            && in_array('graduated', [$nextStatus, $siswa->status], true)) {
            throw ValidationException::withMessages([
                'status' => 'Gunakan tombol pengaturan Alumni agar penugasan Pamong dan akses portal diperbarui dengan aman.',
            ]);
        }

        $dto = UpdateSiswaDTO::fromRequest($request);
        $this->siswaService->update($siswa->id, $dto);

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data siswa berhasil diperbarui.',
            ]);
        }

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified siswa.
     */
    public function destroy(Request $request, Siswa $siswa)
    {
        $this->siswaService->delete($siswa->id);

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Siswa berhasil dihapus.',
            ]);
        }

        return redirect()->route('siswa.index')->with('success', 'Siswa berhasil dihapus.');
    }

    /**
     * Show QR generation page.
     */
    public function qrGenerate(Request $request)
    {
        $studentsByGrade = Siswa::active()->orderBy('nama')->get()->groupBy('school_grade');
        $kelas = collect(TargetGrade::schoolClassOptions())->map(function ($label, $grade) use ($studentsByGrade) {
            $students = $studentsByGrade->get($grade, collect());

            return (object) ['id' => $grade, 'nama' => $label, 'siswa_count' => $students->count(), 'siswa' => $students];
        })->values();
        $totalSiswa = Siswa::active()->count();

        return view('qr.generate', compact('kelas', 'totalSiswa'));
    }

    /**
     * Process QR generation request.
     */
    public function qrGeneratePost(Request $request)
    {
        $request->validate([
            'type' => 'required|in:single,bulk,class',
            'student_id' => 'required_if:type,single|exists:siswa,id',
            'class_id' => ['required_if:type,class', 'nullable', \Illuminate\Validation\Rule::in(TargetGrade::values())],
        ]);

        $students = collect();
        $className = null;

        if ($request->type === 'single') {
            $students = Siswa::active()->where('id', $request->student_id)->get();
        } elseif ($request->type === 'class') {
            $className = TargetGrade::schoolClassLabel($request->class_id);
            $students = Siswa::active()->where('school_grade', $request->class_id)->get();
        } elseif ($request->type === 'bulk') {
            $students = Siswa::active()->get();
        }

        // Generate tokens via service
        foreach ($students as $student) {
            if (! $student->qr_token || $student->qr_token_expires_at?->isPast()) {
                $this->siswaService->generateQrCode($student->id);
            }
        }

        // Refresh students to get updated QR tokens
        $students = $students->fresh();

        return view('qr.print', compact('students', 'className'));
    }

    /**
     * Show QR scan page.
     */
    public function qrScan()
    {
        return view('qr.scan');
    }

    /**
     * Get QR code data for student.
     */
    public function getQrCode(Siswa $siswa): JsonResponse
    {
        if (! $siswa->isActive()) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot generate QR code for inactive student',
            ], 400);
        }

        $qrData = $this->siswaService->generateQrCode($siswa->id);

        return response()->json([
            'success' => true,
            'qr_data' => $qrData,
        ]);
    }

    /**
     * Print student card.
     */
    public function printCard(Siswa $siswa)
    {
        if ($siswa->isActive() && ! $siswa->qr_token) {
            $this->siswaService->generateQrCode($siswa->id);
            $siswa->refresh();
        }

        return view('siswa.card', compact('siswa'));
    }

    /**
     * Print-only view for student card (standalone HTML without layout).
     */
    public function printCardOnly(Siswa $siswa)
    {
        if ($siswa->isActive() && ! $siswa->qr_token) {
            $this->siswaService->generateQrCode($siswa->id);
            $siswa->refresh();
        }

        $qrCode = $siswa->isActive() ? $this->buildQrCodeDataUri($siswa) : null;

        return view('siswa.card-print', compact('siswa', 'qrCode'));
    }

    /**
     * Print all current student ID cards without regenerating existing QR tokens.
     */
    public function printCards(Request $request)
    {
        $query = Siswa::query()->active();

        if ($request->filled('school_grade')) {
            $query->where('school_grade', $request->input('school_grade'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->orderBy('school_grade')
            ->orderBy('nama')
            ->get();

        $className = $request->filled('school_grade')
            ? TargetGrade::schoolClassLabel($request->input('school_grade'))
            : null;

        $cards = $students->map(fn (Siswa $siswa) => [
            'siswa' => $siswa,
            'qrCode' => $this->buildQrCodeDataUri($siswa),
        ]);

        return view('siswa.cards-print', compact('cards', 'students', 'className'));
    }

    /**
     * Download student card as PDF.
     */
    public function downloadCard(Siswa $siswa)
    {
        if ($siswa->isActive() && ! $siswa->qr_token) {
            $this->siswaService->generateQrCode($siswa->id);
            $siswa->refresh();
        }

        return view('siswa.card-pdf', compact('siswa'));
    }

    /**
     * Get list of all active students (JSON).
     */
    public function getList(Request $request): JsonResponse
    {
        $requestedPerPage = $request->input('per_page', 20);
        $perPage = in_array($requestedPerPage, ['all', 'semua'], true)
            ? max(1, Siswa::query()->count())
            : max(1, (int) $requestedPerPage);
        
        $filters = [];
        
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        if ($request->filled('school_grade')) $filters['school_grade'] = $request->school_grade;
        if ($request->filled('pamong_id')) $filters['pamong_id'] = $request->integer('pamong_id');
        
        if ($request->filled('status')) {
            if (in_array((string) $request->status, ['1', '0'], true)) {
                $filters['is_active'] = $request->status == '1';
            } else {
                $filters['status'] = (string) $request->status;
            }
        }
        
        if ($request->filled('biodata_status')) {
            $filters['biodata_status'] = $request->biodata_status;
        }

        $siswa = $this->siswaService->paginate($filters, $perPage);
        $meta = [
            'current_page' => $siswa->currentPage(),
            'last_page' => $siswa->lastPage(),
            'per_page' => $siswa->perPage(),
            'total' => $siswa->total(),
            'from' => $siswa->firstItem(),
            'to' => $siswa->lastItem(),
        ];

        return response()->json([
            'success' => true,
            'data' => SiswaResource::collection($siswa->getCollection())->resolve($request),
            'meta' => $meta,
        ] + $meta);
    }

    public function updateAlumniLifecycle(Request $request, Siswa $siswa): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'action' => ['required', 'in:graduate,update,reactivate'],
            'alumni_can_submit' => ['nullable', 'boolean'],
            'alumni_reviewer_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $reviewerId = isset($data['alumni_reviewer_id']) ? (int) $data['alumni_reviewer_id'] : null;
        if ($reviewerId) {
            $reviewer = User::query()->with('role')->find($reviewerId);
            if (! $reviewer || ! $reviewer->isAdmin() || ! $reviewer->isActive()) {
                throw ValidationException::withMessages([
                    'alumni_reviewer_id' => 'Penanggung jawab harus berupa akun Admin yang aktif.',
                ]);
            }
        }

        $message = DB::transaction(function () use ($data, $reviewerId, $request, $siswa) {
            if ($data['action'] === 'reactivate') {
                abort_unless($siswa->isGraduated(), 409, 'Hanya Alumni yang dapat diaktifkan kembali.');
                $siswa->update([
                    'status' => 'active',
                    'is_active' => true,
                    'graduated_at' => null,
                    'alumni_reviewer_id' => null,
                    'alumni_can_submit' => true,
                ]);

                return 'Alumni diaktifkan kembali sebagai siswa. Tetapkan Pamong baru secara manual.';
            }

            if ($data['action'] === 'update') {
                abort_unless($siswa->isGraduated(), 409, 'Setelan Alumni hanya tersedia untuk siswa berstatus Alumni.');
            }

            $siswa->update([
                'status' => 'graduated',
                'is_active' => true,
                'graduated_at' => $siswa->graduated_at ?: now(),
                'alumni_can_submit' => (bool) ($data['alumni_can_submit'] ?? true),
                'alumni_reviewer_id' => $reviewerId,
            ]);

            if ($data['action'] === 'graduate') {
                PamongSiswa::query()
                    ->where('siswa_id', $siswa->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now(), 'ended_by' => $request->user()->id]);
            }

            return $data['action'] === 'graduate'
                ? 'Siswa menjadi Alumni dan penugasan Pamong aktif telah diakhiri.'
                : 'Setelan Alumni berhasil diperbarui.';
        });

        Cache::forget('siswa:index:stats');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => (new SiswaResource($siswa->fresh(['kelas', 'alumniReviewer'])))->resolve($request),
        ]);
    }

    /**
     * Get siswa statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->siswaService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Download import template Excel.
     */
    public function downloadTemplate()
    {
        $export = new SiswaTemplateExport();
        $spreadsheet = $export->create();
        $writer = new Xlsx($spreadsheet);

        $filename = 'template_import_siswa.xlsx';
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import siswa from Excel file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'photos' => 'nullable|file|mimes:zip|max:51200',
        ]);

        try {
            // Ensure temp directory exists
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Store uploaded files temporarily with unique names
            $excelFile = $request->file('file');
            $excelFileName = 'import_' . time() . '_' . uniqid() . '.' . $excelFile->getClientOriginalExtension();
            $excelFile->move($tempDir, $excelFileName);
            $fullExcelPath = $tempDir . '/' . $excelFileName;

            // Verify file exists
            if (!file_exists($fullExcelPath)) {
                throw new \Exception('File Excel gagal disimpan');
            }

            $photoZipPath = null;
            if ($request->hasFile('photos')) {
                $photoFile = $request->file('photos');
                $photoFileName = 'photos_' . time() . '_' . uniqid() . '.zip';
                $photoFile->move($tempDir, $photoFileName);
                $photoZipPath = $tempDir . '/' . $photoFileName;
                
                if (!file_exists($photoZipPath)) {
                    throw new \Exception('File ZIP foto gagal disimpan');
                }
            }

            // Process import
            $importer = new SiswaImport();
            $result = $importer->import($fullExcelPath, $photoZipPath);

            // Cleanup temp files
            if (file_exists($fullExcelPath)) {
                @unlink($fullExcelPath);
            }
            if ($photoZipPath && file_exists($photoZipPath)) {
                @unlink($photoZipPath);
            }

            return response()->json([
                'success' => true,
                'message' => "Import selesai. Berhasil: {$result['success']}, Gagal: {$result['failed']}",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password siswa ke NIS (default).
     */
    public function resetPassword(Siswa $siswa): JsonResponse
    {
        $siswa->password_plain = $siswa->nis;
        $siswa->password = Hash::make($siswa->nis);
        $siswa->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset ke NIS.',
            'new_password' => $siswa->nis,
        ]);
    }

    /**
     * Generate random password untuk siswa.
     */
    public function generatePassword(Siswa $siswa): JsonResponse
    {
        $newPassword = Str::random(8);
        $siswa->password_plain = $newPassword;
        $siswa->password = Hash::make($newPassword);
        $siswa->save();

        return response()->json([
            'success' => true,
            'message' => 'Password baru berhasil digenerate.',
            'password' => $newPassword,
        ]);
    }

    /**
     * Set custom password untuk siswa.
     */
    public function setPassword(Request $request, Siswa $siswa): JsonResponse
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $siswa->password_plain = $request->password;
        $siswa->password = Hash::make($request->password);
        $siswa->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }

    /**
     * Export all siswa account data to Excel (.xlsx).
     */
    public function exportAccounts(Request $request)
    {
        $columns = [
            'id',
            'nis',
            'nama',
            'jenis_kelamin',
            'tanggal_lahir',
            'alamat',
            'phone',
            'school_grade',
            'target_grade_override',
            'nama_wali',
            'phone_wali',
            'email_wali',
            'password_plain',
            'is_active',
        ];

        if (Siswa::hasKelompokColumn()) {
            $columns[] = 'kelompok';
        }

        $query = Siswa::select($columns)
            ->orderBy('school_grade')
            ->orderBy('nama');

        if ($request->school_grade) {
            $query->where('school_grade', $request->school_grade);
        }

        $siswaList = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Akun Siswa');

        // Header columns
        $headers = ['No', 'NIS (Username)', 'Nama Lengkap', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelompok', 'No. Telepon', 'Kelas Sekolah', 'Nama Wali', 'No. Telepon Wali', 'Email Wali', 'Password', 'Status'];
        $lastCol = chr(64 + count($headers)); // 'M'

        foreach ($headers as $colIndex => $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue("{$col}1", $header);
        }

        // Header style: bold, white text, blue background
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Data rows
        foreach ($siswaList as $index => $siswa) {
            $row = $index + 2;
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValueExplicit("B{$row}", $siswa->nis, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $siswa->nama);
            $sheet->setCellValue("D{$row}", $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : ($siswa->jenis_kelamin ?? '-')));
            $sheet->setCellValue("E{$row}", $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('d/m/Y') : '-');
            $sheet->setCellValue("F{$row}", $siswa->kelompok_label ?? '-');
            $sheet->setCellValueExplicit("G{$row}", $siswa->phone ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("H{$row}", $siswa->school_grade_label ?? '-');
            $sheet->setCellValue("I{$row}", $siswa->nama_wali ?? '-');
            $sheet->setCellValueExplicit("J{$row}", $siswa->phone_wali ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("K{$row}", $siswa->email_wali ?? '-');
            $sheet->setCellValueExplicit("L{$row}", $siswa->password_plain ?? $siswa->nis, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("M{$row}", $siswa->is_active ? 'Aktif' : 'Tidak Aktif');
        }

        // Borders for all data (header + rows)
        $lastRow = count($siswaList) + 1;
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray($borderStyle);

        // Zebra striping for data rows
        for ($r = 2; $r <= $lastRow; $r++) {
            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9E2F3');
            }
        }

        // Auto-width columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Center 'No' column
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $filename = 'data_akun_siswa_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Bulk reset password untuk semua siswa di kelas tertentu.
     */
    public function bulkResetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'school_grade' => ['nullable', 'string', \Illuminate\Validation\Rule::in(TargetGrade::values())],
            'ids' => 'nullable|array',
            'ids.*' => 'exists:siswa,id',
        ]);

        // Increase time limit for bulk operation
        set_time_limit(300);

        $query = Siswa::query()->select(['id', 'nis']);
        
        if ($request->has('ids') && !empty($request->ids)) {
            $query->whereIn('id', $request->ids);
        } elseif ($request->filled('school_grade')) {
            $query->where('school_grade', $request->school_grade);
        }

        $count = 0;
        
        // Process in smaller chunks with direct DB update
        $query->chunkById(50, function ($students) use (&$count) {
            foreach ($students as $student) {
                Siswa::where('id', $student->id)
                    ->update([
                        'password' => Hash::make($student->nis),
                    ]);
                $count++;
            }
        }, 'id');

        return response()->json([
            'success' => true,
            'message' => "Password {$count} siswa berhasil direset ke NIS masing-masing.",
            'count' => $count,
        ]);
    }

    /**
     * Reset ortu password to siswa's NIS (default).
     */
    public function resetOrtuPassword(Siswa $siswa)
    {
        $siswa->ortu_username = $siswa->nis;
        $siswa->ortu_password = \Hash::make($siswa->nis);
        $siswa->ortu_password_plain = $siswa->nis;
        $siswa->save();

        return back()->with('success', "Akun ortu {$siswa->nama} berhasil direset ke default (NIS).");
    }

    /**
     * Update ortu account (username + password) by admin.
     */
    public function updateOrtuAccount(Request $request, Siswa $siswa)
    {
        $request->validate([
            'ortu_username' => 'required|string|min:3|max:50|unique:siswa,ortu_username,' . $siswa->id,
            'ortu_password' => 'nullable|string|min:4',
        ]);

        $siswa->ortu_username = $request->ortu_username;

        if ($request->filled('ortu_password')) {
            $siswa->ortu_password_plain = $request->ortu_password;
            $siswa->ortu_password = \Hash::make($request->ortu_password);
        }

        $siswa->save();

        return back()->with('success', "Akun ortu {$siswa->nama} berhasil diperbarui.");
    }

    protected function buildQrCodeDataUri(Siswa $siswa): ?string
    {
        if (! $siswa->qr_token) {
            return null;
        }

        $qrData = json_encode([
            'student_id' => $siswa->id,
            'nis' => $siswa->nis,
            'token' => $siswa->qr_token,
        ]);

        return Builder::create()
            ->writer(new SvgWriter())
            ->data($qrData)
            ->size(200)
            ->margin(10)
            ->build()
            ->getDataUri();
    }
}
