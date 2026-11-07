<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user has any of the specified roles.
     * Usage: middleware('role:admin') or middleware('role:admin,customer')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user() ?? TokenService::resolveUser($request);

        if ($user) {
            Auth::setUser($user);
        }

        if (!$user) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login.post')->withErrors(['email' => 'Please log in to continue.']);
        }

        $userRole = is_object($user->role) ? strtolower($user->role->name ?? '') : strtolower((string) ($user->role ?? ''));
        if (empty($userRole)) {
            $userRole = $user->role_id === 1 ? 'admin' : 'customer';
        }
        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array($userRole, $allowedRoles, true)) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have permission for this resource.',
                    'required_roles' => $roles,
                ], 403);
            }

            abort(403, 'Unauthorized. Required role: ' . implode(', ', $roles));
        }

        return $next($request);
    }
}

