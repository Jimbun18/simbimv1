<?php

namespace App\Enums;

enum GuardianRelation: string
{
    case AYAH = 'ayah';
    case IBU = 'ibu';
    case WALI = 'wali';

    public function label(): string
    {
        return match ($this) {
            self::AYAH => 'Ayah',
            self::IBU => 'Ibu',
            self::WALI => 'Wali',
        };
    }
}
