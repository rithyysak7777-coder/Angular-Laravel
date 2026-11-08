<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        // 1. Resolve full name from any input format
        $resolvedName = trim((string) (
            $request->input('fullName') ??
            $request->input('full_name') ??
            $request->input('name') ??
            trim(($request->input('first_name', '') . ' ' . $request->input('last_name', '')))
        ));

        if (empty($resolvedName)) {
            $resolvedName = 'Atelier Client';
        }

        $validated = $request->validate([
            'email'        => 'required|email|max:255|unique:users,email',
            'password'     => 'required|string|min:4',
            'phone'        => 'nullable|string|max:32',
            'phone_number' => 'nullable|string|max:32',
        ]);

        $customerRole = Role::firstOrCreate(
            ['name' => 'customer'],
            ['description' => 'Retail Customer']
        );

        $now = now();
        $phone = $request->input('phone_number') ?? $request->input('phone');

        $user = User::create([
            'role_id'       => $customerRole->id,
            'fullName'      => $resolvedName,
            'email'         => strtolower(trim($validated['email'])),
            'password_hash' => Hash::make($validated['password']),
            'phone_number'  => $phone,
            'is_active'     => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $user->load('role');

        // Persist authenticated session to database upon registration
        if ($request->hasSession()) {
            $request->session()->regenerate();
            Auth::login($user);
            $sessionId = $request->session()->getId();
            $request->session()->save();

            try {
                if (Schema::hasTable('sessions')) {
                    \Illuminate\Support\Facades\DB::table('sessions')
                        ->updateOrInsert(
                            ['id' => $sessionId],
                            [
                                'user_id'       => $user->id,
                                'ip_address'    => $request->ip(),
                                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
                                'last_activity' => time(),
                            ]
                        );
                }
            } catch (\Throwable) {}
        }

        $token = TokenService::generateToken($user);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully!',
            'token'   => $token,
            'data'    => $user,
            'user'    => [
                'id'           => $user->id,
                'name'         => $user->fullName,
                'fullName'     => $user->fullName,
                'email'        => $user->email,
                'phone'        => $user->phone_number,
                'role'         => is_object($user->role) ? ($user->role->name ?? 'customer') : (string) ($user->role ?? ($user->role_id === 1 ? 'admin' : 'customer')),
                'is_active'    => $user->is_active,
                'member_since' => '2026',
            ],
        ], 201);
    }

    /**
     * Handle user login with Brute-Force Rate Limiting (5 attempts = 1-min lockout).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($credentials['email']));

        // Phase 3: Construct Unique Throttle Key (email + client IP)
        $throttleKey = Str::transliterate($email . '|' . $request->ip());

        // Phase 3: Check Current Lockout Status (5 failed attempts = 1-minute ban)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Too many failed login attempts. Account access is temporarily locked. Please try again in {$seconds} seconds.";

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'             => false,
                    'message'             => $message,
                    'retry_after_seconds' => $seconds,
                ], 429);
            }

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $user = User::with('role')
            ->where(function ($q) use ($email) {
                $q->where('email', $email);
                if ($email === 'customer@gmail.com') {
                    $q->orWhere('email', 'customer@ecommerce.test');
                } elseif ($email === 'customer@ecommerce.test') {
                    $q->orWhere('email', 'customer@gmail.com');
                } elseif ($email === 'admin@fearlesssoul.com') {
                    $q->orWhere('email', 'admin@ecommerce.test');
                } elseif ($email === 'admin@ecommerce.test') {
                    $q->orWhere('email', 'admin@fearlesssoul.com');
                }
            })
            ->first();

        // Phase 3: Handle Failed Credential Verification
        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            RateLimiter::hit($throttleKey, 60); // 1-minute decay (60 seconds)
            $attempts = RateLimiter::attempts($throttleKey);
            $remaining = max(0, 5 - $attempts);

            $errorMsg = $remaining > 0
                ? "Invalid email or password credentials. You have {$remaining} attempt(s) remaining."
                : "Invalid email or password credentials. Maximum failed attempts reached (5). Access locked for 1 minute.";

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'            => false,
                    'message'            => $errorMsg,
                    'remaining_attempts' => $remaining,
                    'token'              => null,
                ], 401);
            }

            return back()->withErrors(['email' => $errorMsg])->onlyInput('email');
        }

        if (!$user->is_active) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is deactivated. Please contact support.',
                    'token'   => null,
                ], 403);
            }

            return back()->withErrors(['email' => 'Account is deactivated.']);
        }

        // Phase 3: Handle Successful Login -> Clear lockout counter
        RateLimiter::clear($throttleKey);

        // Safeguard against session fixation and persist authenticated session to database
        if ($request->hasSession()) {
            $request->session()->regenerate();
            Auth::login($user);
            $sessionId = $request->session()->getId();
            $request->session()->save();

            try {
                if (Schema::hasTable('sessions')) {
                    \Illuminate\Support\Facades\DB::table('sessions')
                        ->updateOrInsert(
                            ['id' => $sessionId],
                            [
                                'user_id'       => $user->id,
                                'ip_address'    => $request->ip(),
                                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
                                'last_activity' => time(),
                            ]
                        );
                }
            } catch (\Throwable) {}
        }

        // Generate secure access token (1440 minutes / 24 hours validity)
        $token = TokenService::generateToken($user);

        // Support web session redirect if form submission
        if (!$request->is('api/*') && !$request->wantsJson()) {
            return redirect()->intended('/')->with('success', 'Welcome back!');
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'           => $user->id,
                'name'         => $user->fullName,
                'fullName'     => $user->fullName,
                'email'        => $user->email,
                'phone'        => $user->phone_number,
                'role'         => is_object($user->role) ? ($user->role->name ?? 'customer') : (string) ($user->role ?? ($user->role_id === 1 ? 'admin' : 'customer')),
                'is_active'    => $user->is_active,
                'member_since' => '2026',
            ],
        ]);
    }

    /**
     * Handle user logout (Phase 4: Session cleanup, token revocation, guard flush).
     */
    public function logout(Request $request)
    {
        $user = Auth::user() ?? TokenService::resolveUser($request);

        // Phase 4: Revoke API Tokens and delete sessions from database on logout
        if ($user) {
            try {
                if (Schema::hasTable('users') && Schema::hasColumn('users', 'api_token')) {
                    $user->update(['api_token' => null]);
                }
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
                if (Schema::hasTable('sessions')) {
                    \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
                }
            } catch (\Throwable) {
                // Ignore
            }
        }

        // Phase 4: Flush the Web Guard (drop user context from memory)
        Auth::guard('web')->logout();
        if (Auth::check()) {
            Auth::logout();
        }

        // Phase 4: Invalidate Session and Regenerate CSRF Token
        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
            try {
                if (Schema::hasTable('sessions')) {
                    \Illuminate\Support\Facades\DB::table('sessions')->where('id', $sessionId)->delete();
                }
            } catch (\Throwable) {}
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully. Session invalidated and tokens revoked.',
            ]);
        }

        return redirect('/')->with('success', 'Logged out successfully');
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request)
    {
        $user = Auth::user() ?? TokenService::resolveUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data'    => $user->load(['role', 'orders']),
        ]);
    }
}

