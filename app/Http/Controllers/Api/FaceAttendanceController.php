<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\FaceAttendanceController as WebFaceAttendanceController;
use App\Models\FaceProfile;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Native mobile facade; matching and attendance rules remain in the web controller/service. */
class FaceAttendanceController extends WebFaceAttendanceController
{
    public function profile(Request $request): JsonResponse
    {
        $subject = $request->user();

        abort_unless($subject instanceof Siswa || $subject instanceof User, 403, 'Akun tidak didukung.');

        if ($subject instanceof User) {
            abort_unless($subject->hasAnyRole(User::attendanceRoleNames()), 403, 'Akun tidak memiliki akses presensi wajah.');
        }

        $activeProfile = $this->faceAttendanceService->activeProfileFor($subject);
        $profile = $this->faceAttendanceService->mobileProfileFor($subject);

        return response()->json([
            'success' => true,
            'data' => [
                // Only the authenticated account's active MobileFaceNet profile
                // unlocks native Android attendance. A web/legacy profile must
                // explicitly require Android enrollment instead of looking ready.
                'configured' => $profile !== null,
                'subject_type' => FaceProfile::subjectTypeFor($subject),
                'profile_id' => $profile?->id,
                'status' => $profile?->status ?? ($activeProfile ? 'needs_mobile_enrollment' : 'not_enrolled'),
                'legacy_profile' => $activeProfile !== null && $profile === null,
                'enrolled_at' => $profile?->created_at?->toIso8601String(),
                'last_used_at' => $profile?->last_used_at?->toIso8601String(),
                // Safe read-only values for mobile pre-check UX. The server
                // remains the final authority in FaceAttendanceController::scan.
                'location' => [
                    'center_lat' => (float) $this->faceAttendanceService->config()['center_lat'],
                    'center_lng' => (float) $this->faceAttendanceService->config()['center_lng'],
                    'radius_meters' => (float) $this->faceAttendanceService->config()['radius_meters'],
                    'max_accuracy_meters' => (float) $this->faceAttendanceService->config()['max_accuracy_meters'],
                ],
            ],
        ]);
    }
}
