<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalTeam;
use App\Services\Contracts\PamongQrServiceInterface;
use App\Services\FaceAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        $teams = collect();

        if ($user->usesPamongPermissionSystem()) {
            $teams = OrganizationalTeam::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'short_name']);
        }

        return view('profile.show', [
            'user' => $user->load(['role', 'organizationalTeam']),
            'teams' => $teams,
            'pageTitle' => 'Profil Saya',
        ]);
    }

    /**
     * Show the current user's operational ID card.
     */
    public function idCard(PamongQrServiceInterface $pamongQrService, FaceAttendanceService $faceAttendanceService)
    {
        $user = Auth::user()->load(['role', 'organizationalTeam']);

        if (! $pamongQrService->isPamong($user)) {
            abort(403, 'Kartu ID hanya tersedia untuk admin, pamong, dan pengurus PKG.');
        }

        $qrData = $pamongQrService->getQrData($user);
        $faceProfile = $faceAttendanceService->activeProfileFor($user);
        $faceAttendanceSettings = $faceAttendanceService->config();
        $faceEnrollmentEnabled = $faceAttendanceService->enrollmentEnabledFor($user);

        return view('profile.id-card', [
            'user' => $user,
            'qrData' => $qrData,
            'faceProfile' => $faceProfile,
            'faceAttendanceSettings' => $faceAttendanceSettings,
            'faceEnrollmentEnabled' => $faceEnrollmentEnabled,
            'pageTitle' => 'ID Card Saya',
        ]);
    }

    /**
     * Show a print-only ID card for the current user.
     */
    public function idCardPrint(PamongQrServiceInterface $pamongQrService)
    {
        $user = Auth::user()->load(['role', 'organizationalTeam']);

        if (! $pamongQrService->isPamong($user)) {
            abort(403, 'Kartu ID hanya tersedia untuk admin, pamong, dan pengurus PKG.');
        }

        $qrData = $pamongQrService->getQrData($user);

        return view('profile.id-card-print', compact('user', 'qrData'));
    }

    /**
     * Refresh the current user's ID card QR token.
     */
    public function refreshIdCardQr(PamongQrServiceInterface $pamongQrService): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (! $pamongQrService->isPamong($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu ID hanya tersedia untuk admin, pamong, dan pengurus PKG.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'QR ID Card berhasil diperbarui.',
            'data' => $pamongQrService->refreshToken($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$user->id,
            'username' => 'nullable|string|max:255|unique:users,username,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'organizational_team_id' => ['nullable', Rule::exists('organizational_teams', 'id')],
            'organizational_title' => 'nullable|string|max:120',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'username.unique' => 'Username sudah digunakan.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau webp.',
            'avatar.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        // Only include fields that are present in the request
        $data = [];
        if ($request->filled('name')) {
            $data['name'] = $request->name;
        }
        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }
        if ($request->filled('username')) {
            $data['username'] = $request->username;
        }
        if ($request->has('phone')) {
            $data['phone'] = $request->phone;
        }
        if ($user->usesPamongPermissionSystem()) {
            if ($request->has('organizational_team_id')) {
                $data['organizational_team_id'] = $request->input('organizational_team_id') ?: null;
            }
            if ($request->has('organizational_title')) {
                $data['organizational_title'] = $request->input('organizational_title') ?: null;
            }
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_path'] = $avatarPath;
        }

        $user->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profil berhasil diperbarui',
                'user' => $user->fresh()->load(['role', 'organizationalTeam']),
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'password.required' => 'Password baru harus diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Verify current password
        if (! Hash::check($request->current_password, $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Password saat ini salah',
                    'errors' => [
                        'current_password' => ['Password saat ini salah'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'current_password' => 'Password saat ini salah',
            ])->with('error', 'Password gagal diperbarui. Password saat ini salah.');
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password berhasil diperbarui',
            ]);
        }

        return back()->with('success', 'Password berhasil diperbarui');
    }
}
