<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $key = 'login.'.$request->ip();

        // Rate limiting
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too many login attempts. Please try again in '.$seconds.' seconds.',
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = User::where('username', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 300); // 5 minutes lockout

            // Record failed attempt
            if ($user) {
                $user->recordFailedLogin($request->ip());
            }

            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        // Check if account is locked
        if ($user->isLocked()) {
            return response()->json([
                'error' => 'Account is temporarily locked due to multiple failed login attempts',
            ], 423);
        }

        // Check if account is active
        if (! $user->isActive()) {
            return response()->json([
                'error' => 'Account is inactive',
            ], 403);
        }

        // Clear rate limiting on successful login
        RateLimiter::clear($key);

        // Record successful login
        $user->recordLogin($request->ip());

        // Create token
        $token = $user->createToken('auth-token', ['*'], Carbon::now()->addDays(7))->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->name ?? null,
                'permissions' => $user->getAllPermissions(),
            ],
            'token' => $token,
            'expires_at' => Carbon::now()->addDays(7)->toISOString(),
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->name ?? null,
                'permissions' => $user->getAllPermissions(),
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth-token', ['*'], Carbon::now()->addDays(7))->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => Carbon::now()->addDays(7)->toISOString(),
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Revoke all tokens except current
        $currentToken = $request->user()->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }
}
