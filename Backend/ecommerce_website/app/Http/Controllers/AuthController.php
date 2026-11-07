<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
     * Handle user login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($credentials['email']));
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

        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password credentials',
                    'token'   => null,
                ], 401);
            }

            return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
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

        // Generate secure access token
        $token = TokenService::generateToken($user);

        // Support session login if session web driver is used
        if (!$request->is('api/*') && !$request->wantsJson()) {
            Auth::login($user);
            $request->session()->regenerate();
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
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user() ?? TokenService::resolveUser($request);
        if ($user) {
            try {
                if (Schema::hasTable('users') && Schema::hasColumn('users', 'api_token')) {
                    $user->update(['api_token' => null]);
                }
            } catch (\Throwable) {
                // Ignore
            }
        }

        if (Auth::check()) {
            Auth::logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
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

