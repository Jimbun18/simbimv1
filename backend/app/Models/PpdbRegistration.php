<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PpdbRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ppdb_registrations';

    protected $fillable = [
        'nomor_pendaftaran',
        'tahun_ajaran',
        'gelombang',
        'jenjang',
        'nama_lengkap',
        'nama_panggilan',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'usia_daftar',
        'jumlah_saudara',
        'anak_ke',
        'alamat_lengkap',
        'tinggal_dengan',
        'asal_sekolah',
        'alamat_asal_sekolah',
        'pindah_ke_kelas',
        'alasan_pindah',
        'nama_ayah',
        'ttl_ayah',
        'pendidikan_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',
        'alamat_ayah',
        'no_hp_ayah',
        'nama_ibu',
        'ttl_ibu',
        'pendidikan_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',
        'alamat_ibu',
        'no_hp_ibu',
        'pas_foto_path',
        'akta_kelahiran_path',
        'kartu_keluarga_path',
        'status',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
        'verified_at' => 'datetime',
        'jumlah_saudara' => 'integer',
        'anak_ke' => 'integer',
    ];

    protected $appends = [
        'pas_foto_url',
        'akta_kelahiran_url',
        'kartu_keluarga_url',
        'status_label',
        'jenjang_badge',
    ];

    public function getPasFotoUrlAttribute(): ?string
    {
        return $this->pas_foto_path ? asset('storage/' . $this->pas_foto_path) : null;
    }

    public function getAktaKelahiranUrlAttribute(): ?string
    {
        return $this->akta_kelahiran_path ? asset('storage/' . $this->akta_kelahiran_path) : null;
    }

    public function getKartuKeluargaUrlAttribute(): ?string
    {
        return $this->kartu_keluarga_path ? asset('storage/' . $this->kartu_keluarga_path) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'diterima' => 'Diterima',
            'cadangan' => 'Cadangan',
            'ditolak' => 'Tidak Diterima',
            default => 'Menunggu Verifikasi',
        };
    }

    public function getJenjangBadgeAttribute(): string
    {
        return match ($this->jenjang) {
            'KB' => 'KB (3-4 Thn)',
            'TK A' => 'TK A (4-5 Thn)',
            'TK B' => 'TK B (5-6 Thn)',
            'SD' => 'SD (Min. 6 Thn)',
            default => $this->jenjang,
        };
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

