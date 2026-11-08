<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate a secure access token for the given user.
     * Encodes user payload and cryptographically signs with the APP_KEY.
     */
    public static function generateToken(User $user): string
    {
        $roleName = 'customer';
        try {
            if ($user->relationLoaded('role') && is_object($user->getRelation('role'))) {
                $roleName = $user->getRelation('role')?->name ?? 'customer';
            } elseif (is_object($user->role)) {
                $roleName = $user->role?->name ?? 'customer';
            } elseif (is_string($user->role)) {
                $roleName = $user->role;
            } else {
                $roleName = $user->role_id === 1 ? 'admin' : 'customer';
            }
        } catch (\Throwable) {
            $roleName = $user->role_id === 1 ? 'admin' : 'customer';
        }

        $payload = base64_encode(json_encode([
            'uid'   => $user->id,
            'email' => $user->email,
            'role'  => $roleName,
            'rnd'   => Str::random(16),
            'iat'   => time(),
            'exp'   => time() + (60 * (int) config('sanctum.expiration', 1440)), // 1440 minutes (24 hours / 1 day)
        ]));

        $key = config('app.key') ?: 'streetwear_atelier_secret_key_2026';
        $signature = hash_hmac('sha256', $payload, $key);
        $token = $payload . '.' . $signature;

        // Persist to database if api_token column exists on users table
        try {
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'api_token')) {
                $user->update(['api_token' => $token]);
            }
        } catch (\Throwable) {
            // Ignore if schema check fails during unmigrated state
        }

        // Ensure an active session row exists in PostgreSQL sessions table for this user
        try {
            if (Schema::hasTable('sessions')) {
                $hasSession = DB::table('sessions')->where('user_id', $user->id)->exists();
                if (!$hasSession) {
                    $newSessionId = Str::random(40);
                    DB::table('sessions')->insert([
                        'id'            => $newSessionId,
                        'user_id'       => $user->id,
                        'ip_address'    => request()->ip() ?? '127.0.0.1',
                        'user_agent'    => substr((string) request()->userAgent(), 0, 500) ?: 'Mozilla/5.0',
                        'payload'       => serialize(['user' => $user->id]),
                        'last_activity' => time(),
                    ]);
                }
            }
        } catch (\Throwable) {}

        return $token;
    }

    /**
     * Resolve the authenticated user from the request's Bearer token or headers.
     */
    public static function resolveUser(Request $request): ?User
    {
        // 0. If user is already authenticated via session
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->is_active) {
                if (Schema::hasTable('sessions') && $request->hasSession()) {
                    $sessionId = $request->session()->getId();
                    $sessionValid = \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('id', $sessionId)
                        ->where('user_id', $user->id)
                        ->exists();
                    if (!$sessionValid) {
                        Auth::logout();
                        return null;
                    }
                }
                return $user;
            }
        }

        // 1. Extract token from Authorization Bearer header, custom header, or input
        $token = $request->bearerToken() 
            ?? $request->header('token') 
            ?? $request->header('X-Auth-Token') 
            ?? $request->input('token');

        if ($token && str_contains($token, '.')) {
            [$payloadB64, $signature] = explode('.', $token, 2);
            $key = config('app.key') ?: 'streetwear_atelier_secret_key_2026';
            $expectedSignature = hash_hmac('sha256', $payloadB64, $key);

            if (hash_equals($expectedSignature, $signature)) {
                $data = json_decode(base64_decode($payloadB64), true);
                if (is_array($data) && isset($data['uid'])) {
                    // Check if token has expired (1440 minutes / 24 hours validity)
                    if (!isset($data['exp']) || $data['exp'] > time()) {
                        $user = User::with('role')->find($data['uid']);
                        if ($user && $user->is_active) {
                            // Verify token matches active database api_token (if column exists)
                            if (Schema::hasTable('users') && Schema::hasColumn('users', 'api_token')) {
                                if (empty($user->api_token) || !hash_equals($user->api_token, $token)) {
                                    return null;
                                }
                            }

                            // Verify that an active session row actually exists in PostgreSQL sessions table
                            // If session was deleted in database or cookie was cleared in browser, invalidate
                            if (Schema::hasTable('sessions')) {
                                $hasActiveSession = DB::table('sessions')
                                    ->where('user_id', $user->id)
                                    ->exists();

                                if (!$hasActiveSession) {
                                    return null; // Session row was deleted in database!
                                }

                                // If request came from browser and session cookie was deleted, enforce re-login
                                if ($request->hasSession() && $request->headers->has('origin')) {
                                    $currentSessionId = $request->session()->getId();
                                    $sessionCookieName = config('session.cookie');
                                    $hasCookie = $request->hasCookie($sessionCookieName);
                                    $matchesSession = DB::table('sessions')
                                        ->where('id', $currentSessionId)
                                        ->where('user_id', $user->id)
                                        ->exists();

                                    if (!$matchesSession && !$hasCookie) {
                                        return null; // Session cookie was deleted in DevTools!
                                    }
                                }
                            }

                            return $user;
                        }
                    }
                }
            }
        }

        // 2. Check if token matches api_token stored in database
        try {
            if ($token && Schema::hasTable('users') && Schema::hasColumn('users', 'api_token')) {
                $user = User::with('role')->where('api_token', $token)->first();
                if ($user && $user->is_active) {
                    return $user;
                }
            }
        } catch (\Throwable) {
            // Fall through
        }

        return null;
    }
}
