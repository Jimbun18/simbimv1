<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSupervisorWrite
{
    /**
     * Memastikan role 'pengawas' bersifat ketat Read-Only pada seluruh endpoint bisnis.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSupervisor() && !$request->isMethodSafe()) {
            $path = ltrim($request->path(), '/');

            $allowedPersonalRoutes = [
                'api/v1/auth/logout',
                'api/v1/me',
                'api/v1/me/photo',
                'api/v1/me/password',
                'api/v1/notifications/read-all',
                'api/v1/announcements/mark-all-read',
                'api/v1/push/subscribe',
                'api/v1/push/unsubscribe',
                'api/v1/push/test',
            ];

            $isAllowed = in_array($path, $allowedPersonalRoutes, true)
                || preg_match('#^api/v1/notifications/\d+/read$#', $path) === 1
                || preg_match('#^api/v1/announcements/\d+/read$#', $path) === 1;

            if (!$isAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mode Pengawas bersifat hanya-baca (Read-Only). Anda tidak memiliki izin untuk mengubah data.',
                    'data' => null,
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
