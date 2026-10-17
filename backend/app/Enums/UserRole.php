<?php

namespace App\Enums;

enum UserRole: string
{
    case KEPALA_SEKOLAH = 'kepala_sekolah';
    case STAF_ADMIN = 'staf_admin';
    case GURU = 'guru';
    case WALI = 'wali';

    public function label(): string
    {
        return match ($this) {
            self::KEPALA_SEKOLAH => 'Kepala Sekolah',
            self::STAF_ADMIN => 'Staf Administrasi',
            self::GURU => 'Guru',
            self::WALI => 'Wali Murid',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::KEPALA_SEKOLAH, self::STAF_ADMIN]);
    }
}
