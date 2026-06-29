<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\FaceProfile;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Contracts\PamongPresensiServiceInterface;
use App\Services\Contracts\PresensiServiceInterface;
use App\Services\FaceAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class FaceAttendanceController extends Controller
{
    public function __construct(
        protected FaceAttendanceService $faceAttendanceService,
        protected PresensiServiceInterface $presensiService,
        protected PamongPresensiServiceInterface $pamongPresensiService
    ) {}

    public function profile(Request $request)
    {
        $subject = $this->authenticatedSubject($request);

        abort_if(! $subject, 403);

        $faceProfile = $this->faceAttendanceService->activeProfileFor($subject);
        $settings = $this->faceAttendanceService->config();
        $enrollUrl = $subject instanceof Siswa
            ? route('siswa.face-profile.enroll')
            : route('face-profile.enroll');
        $backUrl = $subject instanceof Siswa
            ? route('siswa.dashboard')
            : route('dashboard');
        $subjectType = $subject instanceof Siswa ? 'siswa' : 'pamong';
        $subjectLabel = $subject instanceof Siswa
            ? $subject->nama
            : ($subject->name ?: $subject->username);

        return view('face-profile.show', compact(
            'subject',
            'faceProfile',
            'settings',
            'enrollUrl',
            'backUrl',
            'subjectType',
            'subjectLabel'
        ));
    }

    public function enroll(Request $request): JsonResponse
    {
        $subject = $this->authenticatedSubject($request);

        if (! $subject) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak dikenali. Silakan login ulang.',
            ], 403);
        }

        if (! $this->faceAttendanceService->enrollmentEnabledFor($subject)) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran wajah belum diaktifkan untuk akun ini.',
            ], 403);
        }

        $validated = $request->validate([
            'descriptor' => ['required', 'array', 'min:32', 'max:4096'],
            'descriptor.*' => ['required', 'numeric'],
            'reference_image' => ['required', 'string'],
            'client_captured_at' => ['nullable', 'date'],
        ]);

        try {
            $enrolledByUserId = $subject instanceof User ? $subject->id : null;

            $profile = $this->faceAttendanceService->enroll(
                $subject,
                $validated['descriptor'],
                $validated['reference_image'],
                $enrolledByUserId,
                [
                    'client_captured_at' => $validated['client_captured_at'] ?? null,
                    'device_info' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                ]
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data wajah berhasil disimpan. Scan wajah presensi sudah bisa digunakan.',
            'data' => [
                'profile_id' => $profile->id,
                'photo_url' => $profile->photo_path ? Storage::disk('public')->url($profile->photo_path) : null,
            ],
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descriptor' => ['required', 'array', 'min:32', 'max:4096'],
            'descriptor.*' => ['required', 'numeric'],
            'proof_image' => ['required', 'string'],
            'location' => ['required', 'array'],
            'location.lat' => ['required', 'numeric', 'between:-90,90'],
            'location.lng' => ['required', 'numeric', 'between:-180,180'],
            'location.accuracy_meters' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'client_captured_at' => ['nullable', 'date'],
        ]);

        try {
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

            if (! $schedule->isOpen()) {
                return $this->scheduleUnavailableResponse($schedule);
            }

            $eligibleTypes = $this->faceAttendanceService->eligibleSubjectTypesForSchedule($schedule);

            if ($eligibleTypes === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scan wajah belum diaktifkan untuk target jadwal presensi saat ini.',
                ], 403);
            }

            $location = $this->faceAttendanceService->validateLocation($validated['location']);
            $match = $this->faceAttendanceService->findBestProfileMatch($validated['descriptor'], $eligibleTypes);

            if (! $match || ! $match['accepted']) {
                return $this->faceNotMatchedResponse($eligibleTypes, $match);
            }

            $profile = $match['profile'];
            $subject = $match['subject'];
            $proofPath = $this->faceAttendanceService->storeProofImage($validated['proof_image']);
            $metadata = $this->buildFaceMetadata($profile, $match, $location, $proofPath, $request, $validated['client_captured_at'] ?? null);

            if ($subject instanceof Siswa) {
                $result = $this->presensiService->recordFaceAttendance($subject, $metadata);
                $profile->forceFill(['last_used_at' => now()])->save();

                return $this->faceSiswaResponse($subject, $result, $match, $location);
            }

            if ($subject instanceof User) {
                $result = $this->pamongPresensiService->recordFaceAttendance($subject, $metadata);
                $profile->forceFill(['last_used_at' => now()])->save();

                return $this->facePamongResponse($subject, $result, $match, $location);
            }

            return response()->json([
                'success' => false,
                'message' => 'Akun hasil scan tidak didukung untuk presensi wajah.',
            ], 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses scan wajah. Silakan coba lagi atau hubungi admin.',
                'error' => app()->environment('local') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    private function authenticatedSubject(Request $request): Siswa|User|null
    {
        $siswa = Auth::guard('siswa')->user();

        if ($siswa instanceof Siswa) {
            return $siswa;
        }

        $user = $request->user();

        if ($user instanceof User && $user->hasAnyRole(User::attendanceRoleNames())) {
            return $user;
        }

        return null;
    }

    private function buildFaceMetadata(
        FaceProfile $profile,
        array $match,
        array $location,
        string $proofPath,
        Request $request,
        ?string $clientCapturedAt
    ): array {
        $face = [
            'method' => 'face',
            'profile_id' => $profile->id,
            'proof_path' => $proofPath,
            'proof_url' => Storage::disk('public')->url($proofPath),
            'match_distance' => round((float) $match['distance'], 6),
            'similarity_percent' => $match['similarity_percent'],
            'location' => [
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'accuracy_meters' => round($location['accuracy_meters'], 2),
                'distance_meters' => round($location['distance_meters'], 2),
                'radius_meters' => round($location['radius_meters'], 2),
                'center_lat' => $location['center_lat'],
                'center_lng' => $location['center_lng'],
            ],
            'client_captured_at' => $clientCapturedAt,
            'server_captured_at' => now()->toIso8601String(),
        ];

        return [
            'face' => $face,
            'scan_location' => $location['lat'].','.$location['lng'],
            'scan_device_info' => [
                'user_agent' => $request->userAgent(),
                'method' => 'face',
            ],
            'scan_ip_address' => $request->ip(),
        ];
    }

    private function faceSiswaResponse(Siswa $siswa, array $result, array $match, array $location): JsonResponse
    {
        $presensi = $result['presensi'];

        if ($result['status'] === 'already_present') {
            $jamMasuk = Carbon::parse($presensi->jam_masuk)->format('H:i');

            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan presensi hari ini pada jam {$jamMasuk}.\nStatus: {$presensi->status}",
                'student' => [
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis,
                    'status' => $presensi->status,
                    'jam_masuk' => $jamMasuk,
                ],
            ], 400);
        }

        if ($result['status'] === 'checkout') {
            $jamKeluar = Carbon::parse($presensi->jam_keluar)->format('H:i');

            return response()->json([
                'success' => true,
                'message' => "{$siswa->nama}, jam keluar berhasil dicatat dengan scan wajah.\nJam: {$jamKeluar}",
                'student' => [
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis,
                    'foto' => $siswa->foto_url,
                    'jam' => $jamKeluar,
                    'status' => 'checkout',
                ],
            ]);
        }

        $statusText = $presensi->status === 'hadir' ? 'HADIR' : 'TERLAMBAT';

        return response()->json([
            'success' => true,
            'message' => "{$siswa->nama}, {$statusText}!\nPresensi scan wajah berhasil.\nJarak lokasi: ".round($location['distance_meters'])." meter.\nKemiripan: {$match['similarity_percent']}%.",
            'student' => [
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'foto' => $siswa->foto_url,
                'jam' => now()->format('H:i'),
                'status' => $presensi->status,
                'method' => 'face',
            ],
        ]);
    }

    private function facePamongResponse(User $pamong, array $result, array $match, array $location): JsonResponse
    {
        $presensi = $result['presensi'];

        if ($result['status'] === 'already_present') {
            $jamMasuk = Carbon::parse($presensi->jam_masuk)->format('H:i');

            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan presensi hari ini pada jam {$jamMasuk}.\nStatus: {$presensi->status}",
                'pamong' => [
                    'nama' => $pamong->name ?: $pamong->username,
                    'username' => $pamong->username,
                    'status' => $presensi->status,
                    'jam_masuk' => $jamMasuk,
                ],
            ], 400);
        }

        if ($result['status'] === 'checkout') {
            $jamKeluar = Carbon::parse($presensi->jam_keluar)->format('H:i');

            return response()->json([
                'success' => true,
                'message' => ($pamong->name ?: $pamong->username).", jam keluar berhasil dicatat dengan scan wajah.\nJam: {$jamKeluar}",
                'pamong' => [
                    'nama' => $pamong->name ?: $pamong->username,
                    'username' => $pamong->username,
                    'foto' => $pamong->avatar_url,
                    'jam' => $jamKeluar,
                    'status' => 'checkout',
                ],
            ]);
        }

        $statusText = $presensi->status === 'hadir' ? 'HADIR' : 'TERLAMBAT';

        return response()->json([
            'success' => true,
            'message' => ($pamong->name ?: $pamong->username).", {$statusText}!\nPresensi pamong dengan scan wajah berhasil.\nJarak lokasi: ".round($location['distance_meters'])." meter.\nKemiripan: {$match['similarity_percent']}%.",
            'pamong' => [
                'nama' => $pamong->name ?: $pamong->username,
                'username' => $pamong->username,
                'foto' => $pamong->avatar_url,
                'jam' => now()->format('H:i'),
                'status' => $presensi->status,
                'method' => 'face',
            ],
        ]);
    }

    private function faceNotMatchedResponse(array $eligibleTypes, ?array $bestMatch = null): JsonResponse
    {
        $targetMessage = match ($eligibleTypes) {
            [FaceProfile::SUBJECT_SISWA] => 'Jadwal aktif saat ini hanya memeriksa data wajah siswa. Jika yang scan admin atau pamong, ubah target jadwal presensi ke Pamong saja atau Siswa dan Pamong.',
            [FaceProfile::SUBJECT_USER] => 'Jadwal aktif saat ini hanya memeriksa data wajah pamong/admin. Jika yang scan siswa, ubah target jadwal presensi ke Siswa saja atau Siswa dan Pamong.',
            default => 'Pastikan akun sudah daftar wajah awal, status akun aktif, dan batas minimal kemiripan wajah di pengaturan tidak terlalu tinggi.',
        };
        $matchMessage = $bestMatch
            ? ' Profil terdekat memiliki kemiripan '.round((float) ($bestMatch['similarity_percent'] ?? 0), 1).'%, batas minimal '.round((float) $bestMatch['threshold'], 1).'%. Skor beda mentah '.round((float) $bestMatch['distance'], 3).'. Jika wajah benar, turunkan batas minimal kemiripan atau daftar ulang wajah dengan pencahayaan yang sama.'
            : ' Belum ada profil wajah aktif yang bisa dibandingkan untuk target jadwal ini.';

        return response()->json([
            'success' => false,
            'message' => 'Wajah belum cocok dengan data presensi yang terdaftar. '.$targetMessage.$matchMessage,
            'code' => 'FACE_NOT_MATCHED',
        ], 422);
    }

    private function scheduleUnavailableResponse(AttendanceSchedule $schedule): JsonResponse
    {
        $now = Carbon::now();
        $openTime = Carbon::parse($schedule->open_time)->format('H:i');
        $closeTime = Carbon::parse($schedule->close_time)->format('H:i');
        $openAt = Carbon::parse($schedule->open_time)->setDate($now->year, $now->month, $now->day);
        $dayName = strtolower($now->format('l'));
        $isTodayInSchedule = $schedule->isDateActive($now)
            && (empty($schedule->days) || in_array($dayName, $schedule->days ?? [], true));

        $detail = $isTodayInSchedule && $now->lt($openAt)
            ? "Belum waktunya presensi.\nJam operasional: {$openTime} - {$closeTime}"
            : "Belum waktunya presensi untuk jadwal hari ini.\nJam operasional: {$openTime} - {$closeTime}";

        return response()->json([
            'success' => false,
            'message' => $now->gt(Carbon::parse($schedule->close_time)->setDate($now->year, $now->month, $now->day))
                ? 'Presensi sudah ditutup.'
                : 'Belum waktunya presensi.',
            'detail' => $detail,
            'code' => 'PRESENSI_BELUM_WAKTUNYA',
        ], 400);
    }
}
