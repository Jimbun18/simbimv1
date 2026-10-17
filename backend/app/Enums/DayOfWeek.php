<?php

namespace App\Enums;

enum DayOfWeek: string
{
    case SENIN = 'senin';
    case SELASA = 'selasa';
    case RABU = 'rabu';
    case KAMIS = 'kamis';
    case JUMAT = 'jumat';
    case SABTU = 'sabtu';

    public function label(): string
    {
        return match ($this) {
            self::SENIN => 'Senin',
            self::SELASA => 'Selasa',
            self::RABU => 'Rabu',
            self::KAMIS => 'Kamis',
            self::JUMAT => 'Jumat',
            self::SABTU => 'Sabtu',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::SENIN => 1,
            self::SELASA => 2,
            self::RABU => 3,
            self::KAMIS => 4,
            self::JUMAT => 5,
            self::SABTU => 6,
        };
    }
}
