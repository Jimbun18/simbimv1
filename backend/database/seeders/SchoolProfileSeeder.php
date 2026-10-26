<?php

namespace Database\Seeders;

use App\Models\SchoolProfile;
use Illuminate\Database\Seeder;

class SchoolProfileSeeder extends Seeder
{
    public function run(): void
    {
        SchoolProfile::updateOrCreate(
            ['id' => 1],
            [
                'nama_sekolah' => 'SDI Bina Insan Mandiri Purwokerto',
                'npsn' => '70036695',
                'nss' => '102030405060',
                'jenjang' => 'SD',
                'status_sekolah' => 'Swasta',
                'yayasan' => 'Yayasan Bina Insan Mandiri',
                'alamat_jalan' => 'Jl. Wadas Kelir',
                'rt' => '04',
                'rw' => '05',
                'desa_kelurahan' => 'Karangklesem',
                'kecamatan' => 'Purwokerto Selatan',
                'kab_kota' => 'Banyumas',
                'provinsi' => 'Jawa Tengah',
                'kode_pos' => '53144',
                'telepon' => '0281-635812',
                'email' => 'sdibim.pwt@gmail.com',
                'website' => 'https://sdibim.sch.id',
                'akreditasi' => null,
                'tahun_akreditasi' => null,
                'tahun_berdiri' => 2012,
                'latitude' => -7.4538894,
                'longitude' => 109.2346745,
                'radius_geofence_m' => 100,
                'jam_masuk_default' => '07:00:00',
                'jam_pulang_default' => '14:00:00',
                'toleransi_terlambat_menit' => 15,
            ]
        );
    }
}
