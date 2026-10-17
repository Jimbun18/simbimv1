<?php

namespace App\Enums;

enum UserStatus: string
{
    case AKTIF = 'aktif';
    case NONAKTIF = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::NONAKTIF => 'Nonaktif',
        };
    }
}
