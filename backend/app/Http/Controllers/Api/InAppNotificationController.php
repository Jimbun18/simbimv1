<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InAppNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $unreadCount = InAppNotification::where('user_id', $userId)
            ->where('dibaca', false)
            ->count();

        $notifications = InAppNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar notifikasi berhasil diambil.',
            'data' => $notifications->items(),
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = InAppNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->update([
            'dibaca' => true,
            'dibaca_pada' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai telah dibaca.',
            'data' => $notification,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = InAppNotification::where('user_id', $request->user()->id)
            ->where('dibaca', false)
            ->update([
                'dibaca' => true,
                'dibaca_pada' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} notifikasi berhasil ditandai telah dibaca.",
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }
}
