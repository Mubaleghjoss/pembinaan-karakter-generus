<?php

namespace App\Providers;

use App\Support\BiometricStatus;
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
            ]);
        });
    }
}
