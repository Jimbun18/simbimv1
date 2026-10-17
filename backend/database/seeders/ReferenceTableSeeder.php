<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\IncomeRange;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\Transport;
use Illuminate\Database\Seeder;

class ReferenceTableSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Agama
        $religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];
        foreach ($religions as $idx => $item) {
            Religion::firstOrCreate(['nama' => $item], ['urutan' => $idx + 1]);
        }

        // 2. Pendidikan
        $educations = ['Tidak sekolah', 'SD', 'SMP', 'SMA/SMK', 'D1-D3', 'S1', 'S2', 'S3'];
        foreach ($educations as $idx => $item) {
            Education::firstOrCreate(['nama' => $item], ['urutan' => $idx + 1]);
        }

        // 3. Pekerjaan
        $occupations = [
            'PNS', 'TNI/Polri', 'Guru/Dosen', 'Karyawan Swasta', 'Wiraswasta',
            'Petani', 'Buruh', 'Pedagang', 'Ibu Rumah Tangga', 'Lainnya'
        ];
        foreach ($occupations as $idx => $item) {
            Occupation::firstOrCreate(['nama' => $item], ['urutan' => $idx + 1]);
        }

        // 4. Rentang Penghasilan Bulanan
        $incomeRanges = ['< 1 jt', '1-2 jt', '2-5 jt', '5-10 jt', '> 10 jt'];
        foreach ($incomeRanges as $idx => $item) {
            IncomeRange::firstOrCreate(['nama' => $item], ['urutan' => $idx + 1]);
        }

        // 5. Transportasi
        $transports = [
            'Jalan kaki', 'Sepeda', 'Motor', 'Antar-jemput sekolah',
            'Angkutan umum', 'Mobil pribadi'
        ];
        foreach ($transports as $idx => $item) {
            Transport::firstOrCreate(['nama' => $item], ['urutan' => $idx + 1]);
        }
    }
}
