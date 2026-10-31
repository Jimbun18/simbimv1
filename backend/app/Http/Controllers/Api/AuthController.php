<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        if (!$request->has('login') && $request->has('username')) {
            $request->merge(['login' => $request->input('username')]);
        }

        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Username, email, atau no. HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $result = $this->authService->login($validated['login'], $validated['password']);

        $user = $result['user']->load(['role', 'teacher.homeroomClasses:id,wali_kelas_id,kode,nama,tingkat', 'staff', 'guardian']);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'username' => $user->username,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                    'role' => $user->role?->nama,
                    'status' => $user->status,
                    'must_change_password' => $result['must_change_password'],
                    'foto_path' => $user->foto_path,
                    'profile_detail' => $user->teacher ?? $user->staff ?? $user->guardian,
                ],
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'teacher.homeroomClasses:id,wali_kelas_id,kode,nama,tingkat', 'staff', 'guardian']);

        $fotoPath = $user->foto_path ?? $user->teacher?->foto_path ?? $user->staff?->foto_path ?? $user->guardian?->foto_path;

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'email' => $user->email,
                'no_hp' => $user->no_hp,
                'role' => $user->role?->nama,
                'status' => $user->status,
                'must_change_password' => (bool) $user->must_change_password,
                'foto_path' => $fotoPath,
                'profile_detail' => $user->teacher ?? $user->staff ?? $user->guardian,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'no_hp' => ['nullable', 'string', 'max:16', Rule::unique('users', 'no_hp')->ignore($user->id)],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldData = $user->only(['nama', 'email', 'no_hp', 'foto_path']);

        if ($request->hasFile('foto')) {
            if ($user->foto_path && Storage::disk('public')->exists($user->foto_path)) {
                Storage::disk('public')->delete($user->foto_path);
            }

            $validated['foto_path'] = app(PhotoCompressionService::class)->compressAndStore(
                $request->file('foto'),
                'profiles'
            );

            // Sinkronkan ke model terkait jika ada
            if ($user->teacher) {
                $user->teacher->update(['foto_path' => $validated['foto_path']]);
            }
            if ($user->staff) {
                $user->staff->update(['foto_path' => $validated['foto_path']]);
            }
            if ($user->guardian) {
                $user->guardian->update(['foto_path' => $validated['foto_path']]);
            }
        }
        unset($validated['foto']);

        $user->update($validated);

        // Sinkronisasi otomatis nama, email, no_hp ke Guru atau Wali jika terkait
        if ($user->teacher) {
            $teacherSync = [];
            if (isset($validated['nama'])) $teacherSync['nama_lengkap'] = $validated['nama'];
            if (array_key_exists('email', $validated)) $teacherSync['email'] = $validated['email'] ?: '';
            if (isset($validated['no_hp'])) $teacherSync['no_hp'] = $validated['no_hp'] ?: '';
            if (!empty($teacherSync)) $user->teacher->update($teacherSync);
        }
        if ($user->guardian) {
            $guardianSync = [];
            if (isset($validated['nama'])) $guardianSync['nama_lengkap'] = $validated['nama'];
            if (array_key_exists('email', $validated)) $guardianSync['email'] = $validated['email'] ?: null;
            if (isset($validated['no_hp'])) {
                $guardianSync['no_hp'] = $validated['no_hp'] ?: '';
                $guardianSync['no_whatsapp'] = $validated['no_hp'] ?: null;
            }
            if (!empty($guardianSync)) $user->guardian->update($guardianSync);
        }

        if ($user->staff) {
            $staffSync = [];
            if (isset($validated['nama'])) $staffSync['nama_lengkap'] = $validated['nama'];
            if (array_key_exists('email', $validated)) $staffSync['email'] = $validated['email'] ?: null;
            if (isset($validated['no_hp'])) $staffSync['no_hp'] = $validated['no_hp'] ?: null;
            if (!empty($staffSync)) $user->staff->update($staffSync);
        }

        ActivityLogService::log(
            aksi: 'update_profile',
            modul: 'auth',
            targetType: User::class,
            targetId: $user->id,
            dataSebelum: $oldData,
            dataSesudah: $user->only(['nama', 'email', 'no_hp', 'foto_path']),
            keterangan: "User {$user->username} memperbarui profil."
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'email' => $user->email,
                'no_hp' => $user->no_hp,
                'role' => $user->role?->nama,
                'status' => $user->status,
                'foto_path' => $user->foto_path,
                'profile_detail' => $user->teacher ?? $user->staff ?? $user->guardian,
            ],
        ]);
    }

    public function deletePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->foto_path && Storage::disk('public')->exists($user->foto_path)) {
            Storage::disk('public')->delete($user->foto_path);
        }

        $user->update(['foto_path' => null]);

        if ($user->teacher) {
            $user->teacher->update(['foto_path' => null]);
        }
        if ($user->staff) {
            $user->staff->update(['foto_path' => null]);
        }
        if ($user->guardian) {
            $user->guardian->update(['foto_path' => null]);
        }

        ActivityLogService::log(
            aksi: 'delete_photo',
            modul: 'auth',
            targetType: User::class,
            targetId: $user->id,
            dataSebelum: ['foto_path' => $user->foto_path],
            dataSesudah: ['foto_path' => null],
            keterangan: "User {$user->username} menghapus foto profil."
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
            'data' => [
                'foto_path' => null,
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'different:current_password'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.different' => 'Password baru harus berbeda dari password saat ini.',
            'new_password_confirmation.same' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $this->authService->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['new_password']
        );

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
            'data' => null,
        ]);
    }
}
