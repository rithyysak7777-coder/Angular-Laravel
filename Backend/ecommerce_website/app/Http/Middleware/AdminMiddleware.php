<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Ensure the user is authenticated and has the 'admin' role.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user() ?? TokenService::resolveUser($request);

        if (!$user) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Administrator session or token required. Please log in first.',
                ], 401);
            }

            return redirect()->route('login.post')->withErrors(['email' => 'Please log in to continue.']);
        }

        Auth::setUser($user);

        // Verify user account is active
        if (!$user->is_active) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is suspended.',
                ], 403);
            }

            return redirect()->route('login.post')->withErrors(['email' => 'Account is suspended.']);
        }

        // Check if user has admin role
        $roleName = is_object($user->role) ? strtolower($user->role->name ?? '') : strtolower((string) ($user->role ?? ''));
        if (empty($roleName)) {
            $roleName = $user->role_id === 1 ? 'admin' : 'customer';
        }
        if ($roleName !== 'admin') {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Administrator privileges required.',
                ], 403);
            }

            abort(403, 'Unauthorized access. Admin privileges required.');
        }

        return $next($request);
    }
}

