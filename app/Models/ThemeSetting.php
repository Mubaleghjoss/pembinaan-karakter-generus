<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ThemeSetting extends Model
{
    public const CACHE_KEY = 'theme_settings.current';

    protected $fillable = [
        'primary_color',
        'secondary_color',
        'accent_color',
        'success_color',
        'warning_color',
        'danger_color',
        'dark_color',
        'light_color',
        'sidebar_color',
        'topbar_color',
        'logo_path',
        'favicon_path',
        'app_name',
        'app_description',
        'footer_text',
        'footer_organization',
        'footer_address',
        'footer_phone',
        'footer_email',
        'footer_social_links',
    ];

    protected $casts = [
        'footer_social_links' => 'array',
    ];

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()->first() ?? self::create(self::defaults());
        });
    }

    public static function defaults(): array
    {
        return [
            'primary_color' => '#0f766e',
            'secondary_color' => '#0369a1',
            'accent_color' => '#F59E0B',
            'success_color' => '#10B981',
            'warning_color' => '#F59E0B',
            'danger_color' => '#EF4444',
            'dark_color' => '#020617',
            'light_color' => '#F8FAFC',
            'sidebar_color' => '#ffffff',
            'topbar_color' => '#ffffff',
            'app_name' => 'PKG Presensi',
            'footer_text' => 'Pembinaan Karakter Generus',
            'footer_organization' => 'SMA AFBS',
        ];
    }

    public function getFooterCopyrightAttribute(): string
    {
        $year = date('Y');
        $appName = $this->app_name ?? 'PKG Presensi';

        return "© {$year} {$appName}. Hak cipta dilindungi.";
    }

    public function getCssVariables(): array
    {
        return [
            '--color-primary' => $this->primary_color,
            '--color-secondary' => $this->secondary_color,
            '--color-accent' => $this->accent_color,
            '--color-success' => $this->success_color,
            '--color-warning' => $this->warning_color,
            '--color-danger' => $this->danger_color,
            '--color-dark' => $this->dark_color,
            '--color-light' => $this->light_color,
            '--color-sidebar' => $this->sidebar_color,
            '--color-topbar' => $this->topbar_color,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function () {
            Cache::forget(self::CACHE_KEY);
        });
    }
}
