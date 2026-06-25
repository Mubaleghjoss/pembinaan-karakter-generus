<?php

namespace App\Providers;

use App\Repositories\Contracts\KelasRepositoryInterface;
use App\Repositories\Contracts\PresensiRepositoryInterface;
use App\Repositories\Contracts\SiswaRepositoryInterface;
use App\Repositories\EloquentKelasRepository;
use App\Repositories\EloquentPresensiRepository;
use App\Repositories\EloquentSiswaRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider untuk Repository Pattern
 *
 * Provider ini mendaftarkan binding antara repository interfaces
 * dengan implementasi Eloquent-nya. Hal ini memungkinkan dependency
 * injection dan memudahkan testing dengan mock repositories.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Mendaftarkan binding interface ke implementasi konkret.
     * Menggunakan singleton untuk efisiensi memory.
     */
    public function register(): void
    {
        // Bind PresensiRepositoryInterface ke EloquentPresensiRepository
        $this->app->bind(
            PresensiRepositoryInterface::class,
            EloquentPresensiRepository::class
        );

        // Bind SiswaRepositoryInterface ke EloquentSiswaRepository
        $this->app->bind(
            SiswaRepositoryInterface::class,
            EloquentSiswaRepository::class
        );

        // Bind KelasRepositoryInterface ke EloquentKelasRepository
        $this->app->bind(
            KelasRepositoryInterface::class,
            EloquentKelasRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
