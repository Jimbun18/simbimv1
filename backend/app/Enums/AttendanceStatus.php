<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case HADIR = 'hadir';
    case IZIN = 'izin';
    case SAKIT = 'sakit';
    case ALPA = 'alpa';
    case DINAS = 'dinas'; // khusus guru

    public function label(): string
    {
        return match ($this) {
            self::HADIR => 'Hadir',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::ALPA => 'Alpa',
            self::DINAS => 'Dinas Luar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HADIR => 'green',
            self::IZIN => 'blue',
            self::SAKIT => 'yellow',
            self::ALPA => 'red',
            self::DINAS => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::HADIR => '✓',
            self::IZIN => 'ℹ',
            self::SAKIT => '✚',
            self::ALPA => '✗',
            self::DINAS => '→',
        };
    }

    /**
     * Status yang berlaku untuk absensi murid (tanpa dinas).
     */
    public static function studentStatuses(): array
    {
        return [self::HADIR, self::IZIN, self::SAKIT, self::ALPA];
    }

    /**
     * Semua status termasuk dinas (untuk guru).
     */
    public static function teacherStatuses(): array
    {
        return self::cases();
    }
}
