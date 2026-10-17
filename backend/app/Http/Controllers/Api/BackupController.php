<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Backup::with('creator:id,nama,username');

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->query('tipe'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = min((int) $request->query('per_page', 15), 50);
        $backups = $query->latest('created_at')->paginate($perPage);

        $totalSize = Backup::sum('ukuran');
        $lastBackup = Backup::latest('created_at')->first();

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat backup berhasil diambil.',
            'summary' => [
                'total_backup' => Backup::count(),
                'total_ukuran_bytes' => (int) $totalSize,
                'total_ukuran_formatted' => $this->formatBytes($totalSize),
                'terakhir_backup' => $lastBackup?->created_at?->format('d/m/Y H:i:s'),
            ],
            'data' => $backups->items(),
            'meta' => [
                'current_page' => $backups->currentPage(),
                'last_page' => $backups->lastPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $backup = $this->backupService->createBackup(
                tipe: 'manual',
                userId: $request->user()->id,
                catatan: $validated['catatan'] ?? 'Backup manual dari web admin'
            );

            return response()->json([
                'success' => true,
                'message' => "Backup manual '{$backup->nama_file}' berhasil dibuat.",
                'data' => $backup->load('creator:id,nama'),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat backup: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function download(int $id): BinaryFileResponse|JsonResponse
    {
        $backup = Backup::findOrFail($id);

        try {
            $path = $this->backupService->getBackupFilePath($backup);
            return response()->download($path, $backup->nama_file, [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Password konfirmasi administrator wajib diisi demi keamanan.',
        ]);

        try {
            $backup = $this->backupService->restoreBackup(
                backupId: $id,
                adminPassword: $validated['password'],
                admin: $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => "Database berhasil dipulihkan dari arsip '{$backup->nama_file}'.",
                'data' => $backup,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memulihkan database: ' . $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $backup = Backup::findOrFail($id);

        $this->backupService->deleteBackup($backup);

        return response()->json([
            'success' => true,
            'message' => "File backup '{$backup->nama_file}' berhasil dihapus.",
            'data' => null,
        ]);
    }

    private function formatBytes(float|int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
