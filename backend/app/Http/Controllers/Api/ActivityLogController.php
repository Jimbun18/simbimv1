<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,nama,username');

        if ($request->filled('modul')) {
            $query->where('modul', $request->query('modul'));
        }

        if ($request->filled('aksi')) {
            $query->where('aksi', $request->query('aksi'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('nama', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tgl_mulai')) {
            $query->whereDate('created_at', '>=', $request->query('tgl_mulai'));
        }

        if ($request->filled('tgl_selesai')) {
            $query->whereDate('created_at', '<=', $request->query('tgl_selesai'));
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $logs = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Log aktivitas berhasil diambil.',
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
