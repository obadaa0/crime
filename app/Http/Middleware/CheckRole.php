<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        if (!$token) {
            return response()->json(['message' => 'سجل الدخول اولا'], 401);
        }
        $user = $token->tokenable;
        if (!$user) {
            return response()->json(['message' => 'سجل الدخول اولا'], 401);
        }
        if (!($user->role === 'police')) {
            return response()->json(['message' => 'سجل الدخول اولا'], 401);
        }
        return $next($request);
    }
}
