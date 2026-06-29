<?php

namespace App\Providers;

use App\Models\Siswa;
use App\Models\User;
use App\Support\BiometricStatus;
use App\Support\PopupManager;
use App\Support\TargetGrade;
use App\Services\FaceAttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Check if pamong has menu access
        Blade::if('pamongMenu', function (string $menu) {
            $user = auth()->user();
            return $user && $user->hasPamongMenuAccess($menu);
        });

        // Check if pamong has CRUD permission
        Blade::if('pamongCrud', function (string $module, string $operation) {
            $user = auth()->user();
            return $user && $user->hasPamongCrudPermission($module, $operation);
        });

        // Check if pamong is excluded (full access)
        Blade::if('pamongExcluded', function () {
            $user = auth()->user();
            return $user && $user->isPamongExcluded();
        });

        View::composer('components.profile-assignment-prompt', function ($view) {
            $siswa = Auth::guard('siswa')->user();
            $pamong = Auth::guard('web')->user();
            $profileUser = null;
            $profileType = null;
            $updateUrl = null;
            $needsConfirmation = false;

            if ($siswa instanceof Siswa) {
                $profileUser = $siswa;
                $profileType = 'siswa';
                $updateUrl = route('siswa.profile-assignment.update');
                $needsConfirmation = $siswa->needsProfileAssignmentConfirmation();
            } elseif ($pamong instanceof User && $pamong->usesPamongPermissionSystem()) {
                $profileUser = $pamong;
                $profileType = 'pamong';
                $updateUrl = route('profile-assignment.update');
                $needsConfirmation = $pamong->needsProfileAssignmentConfirmation();
            }

            $view->with([
                'profileAssignmentConfig' => PopupManager::config('profile_assignment_prompt'),
                'profileAssignmentUser' => $profileUser,
                'profileAssignmentType' => $profileType,
                'profileAssignmentUpdateUrl' => $updateUrl,
                'profileAssignmentNeedsConfirmation' => $needsConfirmation,
                'profileAssignmentGroups' => Siswa::kelompokOptions(),
                'profileAssignmentGrades' => TargetGrade::schoolClassOptions(),
            ]);
        });

        View::composer('components.biometric-prompt', function ($view) {
            $data = $view->getData();

            $biometricUser = null;
            $biometricUserType = null;
            $biometricRegisterUrl = null;
            $biometricDismissUrl = null;
            $biometricSettingsUrl = null;
            $biometricSettingsRouteName = null;
            $biometricHasCredential = false;
            $biometricStatus = BiometricStatus::INACTIVE;
            $biometricLegacyCredentialCount = 0;
            $profileAssignmentPending = false;

            if (Auth::guard('siswa')->check()) {
                $biometricUser = Auth::guard('siswa')->user();
                $biometricUserType = 'siswa';
                $biometricRegisterUrl = '/siswa/webauthn/register-options';
                $biometricDismissUrl = '/siswa/webauthn/dismiss-prompt';
                $biometricSettingsUrl = route('siswa.biometrik');
                $biometricSettingsRouteName = 'siswa.biometrik';
                $biometricHasCredential = (bool) ($data['hasBiometricSiswa'] ?? false);
                $biometricStatus = (string) ($data['biometricStatus']['status'] ?? BiometricStatus::INACTIVE);
                $biometricLegacyCredentialCount = (int) ($data['biometricStatus']['legacy_count'] ?? 0);
                $profileAssignmentPending = $biometricUser->needsProfileAssignmentConfirmation();
            } elseif (Auth::guard('web')->check()) {
                $biometricUser = Auth::guard('web')->user();
                $biometricUserType = 'admin';
                $biometricRegisterUrl = '/webauthn/register-options';
                $biometricDismissUrl = '/webauthn/dismiss-prompt';
                $biometricSettingsUrl = route('biometrik');
                $biometricSettingsRouteName = 'biometrik';
                $biometricHasCredential = (bool) ($data['hasBiometricAdmin'] ?? false);
                $biometricStatus = (string) ($data['biometricStatusAdmin'] ?? BiometricStatus::INACTIVE);
                $biometricLegacyCredentialCount = (int) ($data['legacyBiometricAdminCount'] ?? 0);
                $profileAssignmentPending = $biometricUser->needsProfileAssignmentConfirmation();
            } elseif (Auth::guard('ortu')->check()) {
                $biometricUser = Auth::guard('ortu')->user();
                $biometricUserType = 'ortu';
                $biometricRegisterUrl = '/ortu/webauthn/register-options';
                $biometricDismissUrl = '/ortu/webauthn/dismiss-prompt';
                $biometricSettingsUrl = route('ortu.biometrik');
                $biometricSettingsRouteName = 'ortu.biometrik';
                $biometricHasCredential = (bool) ($data['hasBiometricOrtu'] ?? false);
                $biometricStatus = (string) ($data['biometricStatusOrtu'] ?? BiometricStatus::INACTIVE);
                $biometricLegacyCredentialCount = (int) ($data['legacyBiometricOrtuCount'] ?? 0);
            }

            if ($biometricUser && $biometricUserType) {
                try {
                    $resolvedStatus = BiometricStatus::resolve((int) $biometricUser->id, $biometricUserType);
                    $biometricStatus = $resolvedStatus['status'];
                    $biometricHasCredential = (bool) $resolvedStatus['has_valid_credential'];
                    $biometricLegacyCredentialCount = (int) $resolvedStatus['legacy_count'];
                } catch (\Throwable $exception) {
                    \Log::warning('Failed to resolve biometric prompt status.', [
                        'user_id' => $biometricUser->id,
                        'user_type' => $biometricUserType,
                        'error' => $exception->getMessage(),
                    ]);
                }

                $biometricHasCredential = $biometricHasCredential || $biometricStatus === BiometricStatus::ACTIVE;
            }

            $view->with([
                'biometricUser' => $biometricUser,
                'biometricUserType' => $biometricUserType,
                'biometricRegisterUrl' => $biometricRegisterUrl,
                'biometricDismissUrl' => $biometricDismissUrl,
                'biometricSettingsUrl' => $biometricSettingsUrl,
                'biometricSettingsRouteName' => $biometricSettingsRouteName,
                'biometricHasCredential' => $biometricHasCredential,
                'biometricStatus' => $biometricStatus,
                'biometricLegacyCredentialCount' => $biometricLegacyCredentialCount,
                'profileAssignmentPending' => $profileAssignmentPending,
            ]);
        });

        View::composer('components.face-enrollment-prompt', function ($view) {
            $faceUser = null;
            $faceUserType = null;
            $faceProfileExists = false;
            $faceProfileUrl = null;
            $faceEnrollmentEnabledForUser = false;
            $profileAssignmentPending = false;

            try {
                $service = app(FaceAttendanceService::class);

                if (Auth::guard('siswa')->check()) {
                    $faceUser = Auth::guard('siswa')->user();
                    $faceUserType = 'siswa';
                    $faceProfileUrl = route('siswa.face-profile.show');
                    $faceEnrollmentEnabledForUser = $service->enrollmentEnabledFor($faceUser);
                    $profileAssignmentPending = $faceUser->needsProfileAssignmentConfirmation();
                } elseif (Auth::guard('web')->check()) {
                    $user = Auth::guard('web')->user();

                    if ($user instanceof User && $user->hasAnyRole(User::attendanceRoleNames())) {
                        $faceUser = $user;
                        $faceUserType = 'pamong';
                        $faceProfileUrl = route('face-profile.show');
                        $faceEnrollmentEnabledForUser = $service->enrollmentEnabledFor($faceUser);
                        $profileAssignmentPending = method_exists($faceUser, 'needsProfileAssignmentConfirmation')
                            && $faceUser->needsProfileAssignmentConfirmation();
                    }
                }

                if ($faceUser && $faceEnrollmentEnabledForUser) {
                    $faceProfileExists = (bool) $service->activeProfileFor($faceUser);
                }
            } catch (\Throwable $exception) {
                \Log::warning('Failed to resolve face enrollment prompt status.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            $view->with([
                'faceEnrollmentConfig' => PopupManager::config('face_enrollment_prompt'),
                'faceEnrollmentUser' => $faceUser,
                'faceEnrollmentUserType' => $faceUserType,
                'faceEnrollmentProfileExists' => $faceProfileExists,
                'faceEnrollmentUrl' => $faceProfileUrl,
                'faceEnrollmentEnabledForUser' => $faceEnrollmentEnabledForUser,
                'profileAssignmentPending' => $profileAssignmentPending,
            ]);
        });
    }
}
