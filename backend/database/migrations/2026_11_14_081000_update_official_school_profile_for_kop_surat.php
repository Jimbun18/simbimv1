<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('school_profile')
            ->where('id', 1)
            ->update([
                'nama_sekolah' => 'SEKOLAH DASAR ISLAM BINA INSAN MANDIRI PURWOKERTO',
                'yayasan' => 'YAYASAN BINA INSAN MANDIRI BANYUMAS',
                'alamat_jalan' => 'Jalan Wadas Kelir',
                'rt' => '04',
                'rw' => '05',
                'desa_kelurahan' => 'Karangklesem',
                'kecamatan' => 'Purwokerto Selatan',
                'kab_kota' => 'Banyumas',
                'kode_pos' => '53144',
                'telepon' => '(0281) 7782469',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('school_profile')
            ->where('id', 1)
            ->update([
                'nama_sekolah' => 'SDI Bina Insan Mandiri Purwokerto',
                'yayasan' => 'Yayasan Bina Insan Mandiri',
                'alamat_jalan' => 'Jl. Wadas Kelir',
                'rt' => '04',
                'rw' => '05',
                'desa_kelurahan' => 'Karangklesem',
                'kecamatan' => 'Purwokerto Selatan',
                'kab_kota' => 'Banyumas',
                'kode_pos' => '53144',
                'telepon' => '0281-635812',
                'updated_at' => now(),
            ]);
    }
};

