<?php

namespace App\Enums;

enum StudentStatus: string
{
    case AKTIF = 'aktif';
    case PINDAH = 'pindah';
    case LULUS = 'lulus';
    case KELUAR = 'keluar';
    case NONAKTIF = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::PINDAH => 'Pindah',
            self::LULUS => 'Lulus',
            self::KELUAR => 'Keluar',
            self::NONAKTIF => 'Nonaktif',
        };
    }
}
