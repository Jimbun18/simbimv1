<?php

namespace App\Enums;

enum Gender: string
{
    case LAKI_LAKI = 'L';
    case PEREMPUAN = 'P';

    public function label(): string
    {
        return match ($this) {
            self::LAKI_LAKI => 'Laki-laki',
            self::PEREMPUAN => 'Perempuan',
        };
    }
}
