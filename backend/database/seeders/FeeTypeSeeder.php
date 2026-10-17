<?php

namespace Database\Seeders;

use App\Models\FeeType;
use Illuminate\Database\Seeder;

class FeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'kode' => 'SPP',
                'nama' => 'SPP Bulanan',
                'nominal' => 350000,
                'periode' => 'bulanan',
                'keterangan' => 'Sumbangan Pembinaan Pendidikan rutin setiap bulan',
            ],
            [
                'kode' => 'PANGKAL',
                'nama' => 'Uang Pangkal / Pembangunan',
                'nominal' => 3500000,
                'periode' => 'sekali',
                'keterangan' => 'Biaya gedung dan sarana prasarana awal masuk sekolah',
            ],
            [
                'kode' => 'SERAGAM',
                'nama' => 'Seragam & Atribut Sekolah',
                'nominal' => 750000,
                'periode' => 'sekali',
                'keterangan' => 'Paket seragam (merah putih, batik, pramuka, olahraga)',
            ],
            [
                'kode' => 'KEGIATAN',
                'nama' => 'Kegiatan & Outing Class Tahunan',
                'nominal' => 500000,
                'periode' => 'tahunan',
                'keterangan' => 'Biaya field trip, manasik haji anak, dan perkemahan',
            ],
            [
                'kode' => 'RE-REG',
                'nama' => 'Daftar Ulang Semester',
                'nominal' => 250000,
                'periode' => 'semester',
                'keterangan' => 'Daftar ulang awal semester genap/ganjil',
            ],
        ];

        foreach ($types as $type) {
            FeeType::updateOrCreate(['kode' => $type['kode']], $type);
        }
    }
}
