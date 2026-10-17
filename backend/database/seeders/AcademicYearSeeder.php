<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $ay = AcademicYear::firstOrCreate(
            ['nama' => '2026/2027'],
            [
                'tgl_mulai' => '2026-07-15',
                'tgl_selesai' => '2027-06-25',
                'status' => 'aktif',
            ]
        );

        Semester::firstOrCreate(
            [
                'academic_year_id' => $ay->id,
                'semester' => 'gasal',
            ],
            [
                'tgl_mulai' => '2026-07-15',
                'tgl_selesai' => '2026-12-20',
                'status' => 'aktif',
            ]
        );

        Semester::firstOrCreate(
            [
                'academic_year_id' => $ay->id,
                'semester' => 'genap',
            ],
            [
                'tgl_mulai' => '2027-01-05',
                'tgl_selesai' => '2027-06-25',
                'status' => 'rencana',
            ]
        );
    }
}
