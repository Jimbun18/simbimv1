<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $role = $request->query('role');
            $query->whereHas('role', function ($q) use ($role) {
                $q->where('nama', $role);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'no_hp' => ['nullable', 'string', 'max:16', 'unique:users,no_hp'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
            'must_change_password' => ['sometimes', 'boolean'],
        ]);

        $passwordPlain = $validated['password'];
        $validated['password'] = Hash::make($passwordPlain);
        $validated['status'] = $validated['status'] ?? 'aktif';
        $validated['must_change_password'] = $validated['must_change_password'] ?? true;

        $user = User::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'users',
            targetType: User::class,
            targetId: $user->id,
            dataSesudah: $user->only(['nama', 'username', 'email', 'no_hp', 'role_id', 'status']),
            keterangan: "Admin membuat pengguna baru: {$user->username}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dibuat.',
            'data' => $user->load('role'),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => $user->load(['role', 'teacher', 'staff', 'guardian']),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'username' => ['sometimes', 'required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'no_hp' => ['nullable', 'string', 'max:16', Rule::unique('users', 'no_hp')->ignore($user->id)],
            'role_id' => ['sometimes', 'required', 'exists:roles,id'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
            'must_change_password' => ['sometimes', 'boolean'],
        ]);

        $oldData = $user->only(['nama', 'username', 'email', 'no_hp', 'role_id', 'status']);
        $user->update($validated);

        // Sinkronisasi otomatis ke profil Master Guru jika terkait
        if ($user->teacher) {
            $teacherSync = [];
            if (isset($validated['nama'])) {
                $teacherSync['nama_lengkap'] = $validated['nama'];
            }
            if (array_key_exists('email', $validated)) {
                $teacherSync['email'] = $validated['email'] ?: '';
            }
            if (isset($validated['no_hp'])) {
                $teacherSync['no_hp'] = $validated['no_hp'] ?: '';
            }
            if (!empty($teacherSync)) {
                $user->teacher->update($teacherSync);
            }
        }

        // Sinkronisasi otomatis ke profil Master Wali Murid jika terkait
        if ($user->guardian) {
            $guardianSync = [];
            if (isset($validated['nama'])) {
                $guardianSync['nama_lengkap'] = $validated['nama'];
            }
            if (array_key_exists('email', $validated)) {
                $guardianSync['email'] = $validated['email'] ?: null;
            }
            if (isset($validated['no_hp'])) {
                $guardianSync['no_hp'] = $validated['no_hp'] ?: '';
                $guardianSync['no_whatsapp'] = $validated['no_hp'] ?: null;
            }
            if (!empty($guardianSync)) {
                $user->guardian->update($guardianSync);
            }
        }

        ActivityLogService::log(
            aksi: 'update',
            modul: 'users',
            targetType: User::class,
            targetId: $user->id,
            dataSebelum: $oldData,
            dataSesudah: $user->only(['nama', 'username', 'email', 'no_hp', 'role_id', 'status']),
            keterangan: "Admin memperbarui data pengguna: {$user->username}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => $user->load('role'),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menonaktifkan akun sendiri.',
                'data' => null,
            ], 422);
        }

        $oldData = $user->only(['status']);
        $user->update(['status' => 'nonaktif']);
        $user->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'users',
            targetType: User::class,
            targetId: $user->id,
            dataSebelum: $oldData,
            dataSesudah: ['status' => 'nonaktif', 'deleted_at' => now()],
            keterangan: "Admin menonaktifkan pengguna: {$user->username}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dinonaktifkan.',
            'data' => null,
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $newPassword = $validated['password'] ?? 'Password123!';

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        ActivityLogService::log(
            aksi: 'reset_password',
            modul: 'users',
            targetType: User::class,
            targetId: $user->id,
            keterangan: "Admin mereset password pengguna: {$user->username}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Password pengguna berhasil direset.',
            'data' => [
                'temporary_password' => $newPassword,
            ],
        ]);
    }
}
