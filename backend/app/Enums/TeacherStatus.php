<?php

namespace App\Enums;

enum TeacherStatus: string
{
    case AKTIF = 'aktif';
    case CUTI = 'cuti';
    case NONAKTIF = 'nonaktif';
    case PENSIUN = 'pensiun';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::CUTI => 'Cuti',
            self::NONAKTIF => 'Nonaktif',
            self::PENSIUN => 'Pensiun',
        };
    }
}
