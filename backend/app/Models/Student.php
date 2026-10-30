<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nis',
        'nisn',
        'nik',
        'no_kk',
        'no_akta_lahir',
        'nama_lengkap',
        'nama_panggilan',
        'jenis_kelamin',
        'tempat_lahir',
        'tgl_lahir',
        'agama',
        'kewarganegaraan',
        'anak_ke',
        'jumlah_saudara',
        'bahasa_sehari_hari',
        'alamat_jalan',
        'rt',
        'rw',
        'desa_kelurahan',
        'kecamatan',
        'kab_kota',
        'provinsi',
        'kode_pos',
        'latitude',
        'longitude',
        'tinggal_bersama',
        'transportasi',
        'jarak_ke_sekolah_km',
        'waktu_tempuh_menit',
        'no_hp_siswa',
        'email',
        'tinggi_cm',
        'berat_kg',
        'golongan_darah',
        'riwayat_penyakit',
        'alergi',
        'kebutuhan_khusus',
        'asal_sekolah_tk',
        'jenis_pendaftaran',
        'tgl_masuk',
        'tahun_masuk',
        'kelas_saat_masuk',
        'class_id',
        'status',
        'foto_path',
        'dusun',
        'telepon',
        'skhun',
        'penerima_kps',
        'no_kps',
        'no_peserta_un',
        'no_seri_ijazah',
        'penerima_kip',
        'nomor_kip',
        'nama_di_kip',
        'nomor_kks',
        'bank',
        'nomor_rekening_bank',
        'rekening_atas_nama',
        'layak_pip',
        'alasan_layak_pip',
        'lingkar_kepala',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['foto_url', 'alamat', 'class_name'];

    public function getClassNameAttribute(): ?string
    {
        return $this->schoolClass?->nama ?? $this->currentClass?->nama ?? null;
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? asset('storage/' . $this->foto_path) : null;
    }

    public function getAlamatAttribute(): string
    {
        $parts = [];
        if (!empty($this->alamat_jalan)) {
            $parts[] = trim($this->alamat_jalan);
        }
        if (!empty($this->rt) || !empty($this->rw)) {
            $rtRw = [];
            if (!empty($this->rt)) $rtRw[] = "RT {$this->rt}";
            if (!empty($this->rw)) $rtRw[] = "RW {$this->rw}";
            $parts[] = implode(' ', $rtRw);
        }
        if (!empty($this->dusun)) {
            $parts[] = trim($this->dusun);
        } elseif (!empty($this->desa_kelurahan)) {
            $parts[] = trim($this->desa_kelurahan);
        }
        if (!empty($this->kecamatan)) {
            $parts[] = trim($this->kecamatan);
        }
        if (!empty($this->kab_kota)) {
            $parts[] = trim($this->kab_kota);
        }
        return !empty($parts) ? implode(', ', $parts) : ($this->alamat_jalan ?? '');
    }

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date:Y-m-d',
            'tgl_masuk' => 'date:Y-m-d',
            'tinggi_cm' => 'float',
            'berat_kg' => 'float',
            'jarak_ke_sekolah_km' => 'float',
            'anak_ke' => 'integer',
            'jumlah_saudara' => 'integer',
            'kelas_saat_masuk' => 'integer',
        ];
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class, 'agama');
    }

    public function transport(): BelongsTo
    {
        return $this->belongsTo(Transport::class, 'transportasi');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')
            ->withPivot(['hubungan', 'is_kontak_utama', 'boleh_lihat_keuangan', 'boleh_jemput'])
            ->withTimestamps();
    }

    public function primaryGuardian()
    {
        return $this->guardians()->wherePivot('is_kontak_utama', true)->first();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }
}
