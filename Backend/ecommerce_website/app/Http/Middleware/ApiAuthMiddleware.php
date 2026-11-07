<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming API request.
     * Authenticates the user via session, Bearer token, or X-User-Id header.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        // Authenticate via Bearer token / TokenService
        $user = TokenService::resolveUser($request);
        if ($user) {
            Auth::setUser($user);
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Invalid or missing token.',
        ], 401);
    }
}

