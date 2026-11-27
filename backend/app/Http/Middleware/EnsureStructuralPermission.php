<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStructuralPermission
{
    /**
     * Handle an incoming request based on delegated structural roles.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Kepala Sekolah & Staf Admin memiliki akses penuh (Super Admin)
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Pengawas memiliki akses baca (Read-Only) untuk modul keuangan & sarpras/TU
        if ($user->isSupervisor() && $request->isMethodSafe() && in_array($module, ['finance', 'keuangan', 'tu', 'sarpras'], true)) {
            return $next($request);
        }

        $hasAccess = match ($module) {
            'finance', 'keuangan' => $user->canAccessFinance(),
            'kurikulum' => $user->canAccessKurikulum(),
            'kesiswaan' => $user->canAccessKesiswaan(),
            'tu', 'sarpras' => $user->canAccessTu(),
            'bk' => $user->isGuruBk(),
            'organization' => $user->isAdmin(),
            default => false,
        };

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses struktural untuk modul ini.',
                'data' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
