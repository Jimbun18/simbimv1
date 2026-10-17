<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolProfile extends Model
{
    use HasFactory;

    protected $table = 'school_profile';

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'nss',
        'jenjang',
        'status_sekolah',
        'yayasan',
        'alamat_jalan',
        'rt',
        'rw',
        'desa_kelurahan',
        'kecamatan',
        'kab_kota',
        'provinsi',
        'kode_pos',
        'telepon',
        'email',
        'website',
        'kepala_sekolah_id',
        'akreditasi',
        'tahun_akreditasi',
        'tahun_berdiri',
        'logo_path',
        'latitude',
        'longitude',
        'radius_geofence_m',
        'jam_masuk_default',
        'jam_pulang_default',
        'toleransi_terlambat_menit',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_geofence_m' => 'integer',
            'toleransi_terlambat_menit' => 'integer',
        ];
    }

    public function headmaster(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'kepala_sekolah_id');
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = [];
        if ($this->alamat_jalan) $parts[] = $this->alamat_jalan;
        if ($this->rt || $this->rw) $parts[] = 'RT.' . ($this->rt ?? '04') . ' RW.' . ($this->rw ?? '05');
        if ($this->desa_kelurahan) $parts[] = 'Kel. ' . $this->desa_kelurahan;
        if ($this->kecamatan) $parts[] = 'Kec. ' . $this->kecamatan;
        if ($this->kab_kota) $parts[] = 'Kab. ' . $this->kab_kota;
        if ($this->kode_pos) $parts[] = 'Kode Pos ' . $this->kode_pos;
        return !empty($parts) ? implode(' ', $parts) : 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem Kec. Purwokerto Selatan Kab. Banyumas Kode pos 53144';
    }
}
