<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\InAppNotification;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $query = Announcement::with('creator:id,nama');

        if (!$isAdmin) {
            $userRole = $user->role?->nama;
            $query->where('is_aktif', true)
                ->where(function ($q) use ($userRole) {
                    $q->where('target_role', 'semua')
                      ->orWhere('target_role', $userRole);
                })
                ->whereDate('tgl_terbit', '<=', now()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('tgl_berakhir')
                      ->orWhereDate('tgl_berakhir', '>=', now()->toDateString());
                });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        $perPage = min((int) $request->query('per_page', 15), 50);
        $announcements = $query->orderBy('is_pinned', 'desc')
            ->orderBy('tgl_terbit', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengumuman berhasil diambil.',
            'data' => $announcements->items(),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'target_role' => ['required', 'in:semua,guru,wali,staf_admin,kepala_sekolah'],
            'tgl_terbit' => ['sometimes', 'required', 'date'],
            'tgl_berakhir' => ['nullable', 'date', 'after_or_equal:tgl_terbit'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_aktif' => ['sometimes', 'boolean'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('announcements', 'public');
        }

        $user = $request->user();
        $announcement = Announcement::create([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'kategori' => $validated['kategori'] ?? 'umum',
            'target_role' => $validated['target_role'],
            'lampiran_path' => $lampiranPath,
            'tgl_terbit' => $validated['tgl_terbit'] ?? now()->toDateString(),
            'tgl_berakhir' => $validated['tgl_berakhir'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'is_aktif' => $validated['is_aktif'] ?? true,
            'created_by' => $user->id,
        ]);

        ActivityLogService::log(
            aksi: 'create_announcement',
            modul: 'announcements',
            targetType: Announcement::class,
            targetId: $announcement->id,
            dataSesudah: $announcement->toArray(),
            keterangan: "Menerbitkan pengumuman baru: {$announcement->judul} (Target: {$announcement->target_role})."
        );

        // Broadcast in-app notification to target users
        $targetRoleName = $validated['target_role'];
        $usersQuery = User::where('status', 'aktif')->where('id', '!=', $user->id);
        if ($targetRoleName !== 'semua') {
            $role = Role::where('nama', $targetRoleName)->first();
            if ($role) {
                $usersQuery->where('role_id', $role->id);
            }
        }

        $targetUsers = $usersQuery->select('id')->get();
        foreach ($targetUsers as $targetUser) {
            InAppNotification::send(
                userId: $targetUser->id,
                judul: "Pengumuman: {$announcement->judul}",
                isi: substr(strip_tags($announcement->isi), 0, 100) . '...',
                tipe: 'pengumuman',
                targetUrl: "/announcements/{$announcement->id}"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil diterbitkan.',
            'data' => $announcement->load('creator'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $announcement = Announcement::with('creator:id,nama')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengumuman berhasil diambil.',
            'data' => $announcement,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:200'],
            'isi' => ['sometimes', 'required', 'string'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'target_role' => ['sometimes', 'required', 'in:semua,guru,wali,staf_admin,kepala_sekolah'],
            'tgl_terbit' => ['sometimes', 'required', 'date'],
            'tgl_berakhir' => ['nullable', 'date'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_aktif' => ['sometimes', 'boolean'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('lampiran')) {
            if ($announcement->lampiran_path && Storage::disk('public')->exists($announcement->lampiran_path)) {
                Storage::disk('public')->delete($announcement->lampiran_path);
            }
            $validated['lampiran_path'] = $request->file('lampiran')->store('announcements', 'public');
        }

        $oldData = $announcement->toArray();
        $validated['updated_by'] = $request->user()->id;
        $announcement->update($validated);

        ActivityLogService::log(
            aksi: 'update_announcement',
            modul: 'announcements',
            targetType: Announcement::class,
            targetId: $announcement->id,
            dataSebelum: $oldData,
            dataSesudah: $announcement->fresh()->toArray(),
            keterangan: "Memperbarui pengumuman ID #{$id}: {$announcement->judul}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil diperbarui.',
            'data' => $announcement->fresh()->load('creator'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        $oldData = $announcement->toArray();
        $announcement->delete();

        ActivityLogService::log(
            aksi: 'delete_announcement',
            modul: 'announcements',
            targetType: Announcement::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Menghapus pengumuman ID #{$id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dihapus.',
            'data' => null,
        ]);
    }
}
