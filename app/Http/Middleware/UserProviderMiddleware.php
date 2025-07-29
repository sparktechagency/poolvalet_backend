<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserProviderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!in_array(Auth::user()->role, ['USER', 'PROVIDER'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return $next($request);

        } catch (AuthenticationException $exception) {
            return response()->json([
                'message' => 'Unauthorized: ' . $exception->getMessage()
            ], 401);
        }
    }
}
