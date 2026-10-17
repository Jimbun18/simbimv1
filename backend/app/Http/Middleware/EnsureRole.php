<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.',
                'data' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
