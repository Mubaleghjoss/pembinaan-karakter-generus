<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobileDeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:4096'],
            'platform' => ['required', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $owner = $request->user();
        $hash = hash('sha256', $data['token']);
        $device = MobileDeviceToken::query()->updateOrCreate(
            ['token_hash' => $hash],
            [
                'owner_type' => $owner::class,
                'owner_id' => $owner->getKey(),
                'token' => $data['token'],
                'platform' => $data['platform'],
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'registered' => true,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:4096'],
        ]);

        MobileDeviceToken::query()
            ->where('owner_type', $request->user()::class)
            ->where('owner_id', $request->user()->getKey())
            ->where('token_hash', hash('sha256', $data['token']))
            ->update(['revoked_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil dicabut.',
        ]);
    }
}
