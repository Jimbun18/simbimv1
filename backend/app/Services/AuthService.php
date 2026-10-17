<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $login, string $password): array
    {
        $user = $this->findUserByLogin($login);

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if ($user->isLocked()) {
            $minutesLeft = now()->diffInMinutes($user->locked_until) + 1;
            throw ValidationException::withMessages([
                'login' => ["Akun terkunci karena terlalu banyak percobaan gagal. Silakan coba lagi dalam {$minutesLeft} menit."],
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            $this->handleFailedLogin($user);
            throw ValidationException::withMessages([
                'login' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if ($user->status !== 'aktif') {
            throw ValidationException::withMessages([
                'login' => ['Akun Anda berstatus nonaktif. Silakan hubungi administrator.'],
            ]);
        }

        // Successful login
        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        ActivityLogService::log(
            aksi: 'login',
            modul: 'auth',
            targetType: User::class,
            targetId: $user->id,
            keterangan: "User {$user->username} berhasil login.",
            userId: $user->id
        );

        $token = $user->createToken('simbim-token')->plainTextToken;

        return [
            'user' => $user->load('role'),
            'token' => $token,
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();

        ActivityLogService::log(
            aksi: 'logout',
            modul: 'auth',
            targetType: User::class,
            targetId: $user->id,
            keterangan: "User {$user->username} logout.",
            userId: $user->id
        );
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        ActivityLogService::log(
            aksi: 'change_password',
            modul: 'auth',
            targetType: User::class,
            targetId: $user->id,
            keterangan: "User {$user->username} mengubah password.",
            userId: $user->id
        );
    }

    private function findUserByLogin(string $login): ?User
    {
        $normalizedHp = $this->normalizePhone($login);

        return User::where('username', $login)
            ->orWhere('email', $login)
            ->orWhere('no_hp', $login)
            ->orWhere('no_hp', $normalizedHp)
            ->first();
    }

    private function handleFailedLogin(User $user): void
    {
        $attempts = $user->login_attempts + 1;
        $lockedUntil = null;

        if ($attempts >= 5) {
            $lockedUntil = now()->addMinutes(15);
            $attempts = 0;

            ActivityLogService::log(
                aksi: 'account_locked',
                modul: 'auth',
                targetType: User::class,
                targetId: $user->id,
                keterangan: "Akun {$user->username} terkunci selama 15 menit akibat 5 kali gagal login berturut-turut.",
                userId: $user->id
            );
        } else {
            ActivityLogService::log(
                aksi: 'login_failed',
                modul: 'auth',
                targetType: User::class,
                targetId: $user->id,
                keterangan: "Percobaan login gagal ke-{$attempts} untuk akun {$user->username}.",
                userId: $user->id
            );
        }

        $user->update([
            'login_attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            return '+628' . substr($phone, 2);
        }
        return $phone;
    }
}
