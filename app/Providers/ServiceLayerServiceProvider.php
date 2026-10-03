<?php

namespace App\Providers;

use App\Models\Presensi;
use App\Models\PointTransaction;
use App\Models\QuranReadingEntry;
use App\Observers\PointTransactionCreatedObserver;
use App\Observers\PresensiCreatedObserver;
use App\Observers\QuranReadingEntryCreatedObserver;
use App\Services\Contracts\PamongPresensiServiceInterface;
use App\Services\Contracts\PamongQrServiceInterface;
use App\Services\Contracts\PresensiServiceInterface;
use App\Services\Contracts\QrTokenServiceInterface;
use App\Services\Contracts\SiswaServiceInterface;
use App\Services\PamongPresensiService;
use App\Services\PamongQrService;
use App\Services\PresensiService;
use App\Services\QrTokenService;
use App\Services\SiswaService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider untuk Service Layer
 *
 * Provider ini mendaftarkan binding antara service interfaces
 * dengan implementasinya untuk dependency injection.
 */
class ServiceLayerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind QrTokenServiceInterface ke QrTokenService
        $this->app->bind(
            QrTokenServiceInterface::class,
            QrTokenService::class
        );

        // Bind PresensiServiceInterface ke PresensiService
        $this->app->bind(
            PresensiServiceInterface::class,
            PresensiService::class
        );

        // Bind SiswaServiceInterface ke SiswaService
        $this->app->bind(
            SiswaServiceInterface::class,
            SiswaService::class
        );

        // Bind PamongQrServiceInterface ke PamongQrService
        $this->app->bind(
            PamongQrServiceInterface::class,
            PamongQrService::class
        );

        // Bind PamongPresensiServiceInterface ke PamongPresensiService
        $this->app->bind(
            PamongPresensiServiceInterface::class,
            PamongPresensiService::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Presensi::observe(PresensiCreatedObserver::class);
        QuranReadingEntry::observe(QuranReadingEntryCreatedObserver::class);
        PointTransaction::observe(PointTransactionCreatedObserver::class);
    }
}
