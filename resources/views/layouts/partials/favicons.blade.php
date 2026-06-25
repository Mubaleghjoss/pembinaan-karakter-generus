@php
    $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
    $configuredFavicon = $currentTheme->favicon_path ?? null;
    $configuredLogo = $currentTheme->logo_path ?? ($siteSettings['site_logo'] ?? null);
    $faviconStoragePath = collect([$configuredFavicon, $configuredLogo])
        ->filter(fn ($path) => is_string($path) && trim($path) !== '')
        ->map(fn ($path) => ltrim($path, '/'))
        ->first(fn ($path) => $publicDisk->exists($path));

    if ($faviconStoragePath) {
        $faviconUrl = asset('storage/' . $faviconStoragePath) . '?v=' . $publicDisk->lastModified($faviconStoragePath);
        $faviconPath = $faviconStoragePath;
    } else {
        $faviconUrl = asset('images/icons/pkg-pwa-2026-192.png');
        $faviconPath = 'pkg-pwa-2026-192.png';
    }

    $faviconType = match (strtolower(pathinfo($faviconPath, PATHINFO_EXTENSION))) {
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        default => 'image/png',
    };
@endphp

<link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
