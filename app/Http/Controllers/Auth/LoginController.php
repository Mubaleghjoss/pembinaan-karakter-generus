<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly LoginThrottle $loginThrottle)
    {
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        $identity = (string) $request->input('login');
        $this->loginThrottle->ensureNotLimited($request, 'operasional', $identity, 'login');

        // Attempt to log the user in
        if ($this->attemptLogin($request)) {
            $request->session()->regenerate();
            $this->loginThrottle->clearIdentity($request, 'operasional', $identity);

            // Record login activity
            $user = Auth::user();
            $user->recordLogin($request->ip(), $request->userAgent());

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form with an error message
        $this->loginThrottle->recordFailure($request, 'operasional', $identity);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Username, No HP, atau email harus diisi.',
            'password.required' => 'Password harus diisi.',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(Request $request)
    {
        $login = $request->input('login');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Determine if login is email, phone, or username
        $user = null;
        
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            // Login with email
            $user = User::where('email', $login)->first();
        } elseif (preg_match('/^[0-9]{10,15}$/', $login)) {
            // Login with phone number (10-15 digits)
            $user = User::where('phone', $login)->first();
        }
        
        // If not found by email/phone, try username
        if (!$user) {
            $user = User::where('username', $login)->first();
        }

        if (! $user) {
            return false;
        }

        // Check if user is active
        if (! $user->isActive()) {
            return false;
        }

        // Check if account is locked
        if ($user->isLocked()) {
            return false;
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            // Increment failed login attempts
            $user->increment('failed_login_attempts');

            // Lock account if too many failed attempts
            if ($user->failed_login_attempts >= 5) {
                $user->lockAccount();
            }

            return false;
        }

        // Reset failed login attempts on successful login
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Log the user in
        Auth::login($user, $remember);

        return true;
    }

    /**
     * Send the response after the user was authenticated.
     */
    protected function sendLoginResponse(Request $request)
    {
        $user = Auth::user();
        $redirectRoute = $user->isGuru()
            ? ($user->must_change_password ? 'guru.password.initial' : 'guru.dashboard')
            : 'dashboard';

        if ($request->expectsJson()) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'user' => $user->load('role'),
                'token' => $token,
                'redirect_url' => route($redirectRoute),
            ]);
        }

        if ($user->isGuru()) {
            return redirect()->route($redirectRoute);
        }

        return redirect()->intended(route($redirectRoute));
    }

    /**
     * Send the response after a failed login attempt.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $field = 'login';
        $message = 'Username, nomor HP, email, atau password salah.';

        if (!$request->expectsJson()) {
            return redirect()->route('login')
                ->withInput($request->only('login', 'remember'))
                ->withErrors([
                    $field => [$message],
                ]);
        }

        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginUrl = route('login', ['fresh' => Str::random(12)]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logout berhasil',
                'redirect' => $loginUrl,
            ]);
        }

        return redirect()
            ->to($loginUrl)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

}
