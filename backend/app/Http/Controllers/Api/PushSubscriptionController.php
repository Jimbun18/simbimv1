<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = WebPushService::getPublicKey();

        return response()->json([
            'success' => true,
            'data' => [
                'public_key' => $publicKey,
                'configured' => WebPushService::isConfigured(),
            ],
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'max:50'],
        ]);

        $sub = PushSubscription::upsertForUser(
            userId: $request->user()->id,
            endpoint: $validated['endpoint'],
            publicKey: $validated['keys']['p256dh'],
            authToken: $validated['keys']['auth'],
            contentEncoding: $validated['content_encoding'] ?? 'aesgcm',
            userAgent: $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil didaftarkan untuk notifikasi push.',
            'data' => [
                'id' => $sub->id,
            ],
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Langganan notifikasi push pada perangkat ini telah dihapus.',
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        if (!WebPushService::isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'VAPID key belum dikonfigurasi di server (.env). Jalankan php artisan webpush:vapid terlebih dahulu.',
            ], 422);
        }

        $sent = WebPushService::sendToUser(
            userId: $request->user()->id,
            title: 'Uji Notifikasi SIM-BIM',
            body: 'Notifikasi push aktif! Pengingat presensi rutin akan muncul di perangkat ini.',
            url: '/presensi'
        );

        return response()->json([
            'success' => $sent > 0,
            'message' => $sent > 0
                ? "Notifikasi uji coba berhasil dikirim ke {$sent} perangkat."
                : 'Belum ada browser/perangkat yang terdaftar untuk akun ini. Aktifkan izin notifikasi terlebih dahulu.',
            'data' => ['sent_count' => $sent],
        ]);
    }
}
