<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

/**
 * Validate sync API key for data export/import endpoints.
 * The key is stored in the settings table (configurable via admin UI).
 */
class ValidateSyncKey
{
    public function handle(Request $request, Closure $next)
    {
        $syncKey = Setting::get('sync_export_key');

        if (!$syncKey) {
            return response()->json([
                'success' => false,
                'message' => 'Sync API key belum dikonfigurasi di server. Silakan set di menu Pengaturan > Tarik Data.',
            ], 500);
        }

        $providedKey = $request->header('X-Sync-Key');

        if (!$providedKey || $providedKey !== $syncKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
