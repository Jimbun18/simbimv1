<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nip',
        'nuptk',
        'nik',
        'nama_lengkap',
        'gelar_depan',
        'gelar_belakang',
        'jenis_kelamin',
        'tempat_lahir',
        'tgl_lahir',
        'agama',
        'status_pernikahan',
        'alamat_jalan',
        'rt',
        'rw',
        'desa_kelurahan',
        'kecamatan',
        'kab_kota',
        'provinsi',
        'kode_pos',
        'no_hp',
        'email',
        'kontak_darurat_nama',
        'kontak_darurat_hp',
        'kontak_darurat_hubungan',
        'jenis_ptk',
        'status_kepegawaian',
        'tgl_mulai_tugas',
        'tgl_akhir_tugas',
        'jabatan',
        'tugas_tambahan',
        'jam_mengajar_per_minggu',
        'status',
        'pendidikan_terakhir',
        'jurusan_prodi',
        'institusi',
        'tahun_lulus',
        'status_sertifikasi',
        'no_sertifikat_pendidik',
        'tahun_sertifikasi',
        'npwp',
        'nama_bank',
        'no_rekening',
        'atas_nama',
        'foto_path',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? asset('storage/' . $this->foto_path) : null;
    }

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date:Y-m-d',
            'tgl_mulai_tugas' => 'date:Y-m-d',
            'tgl_akhir_tugas' => 'date:Y-m-d',
            'jam_mengajar_per_minggu' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class, 'agama');
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class, 'pendidikan_terakhir');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TeacherDocument::class);
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'wali_kelas_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    public function getNamaGelarAttribute(): string
    {
        $depan = $this->gelar_depan ? trim($this->gelar_depan) . ' ' : '';
        $nama = trim($this->nama_lengkap);
        $belakang = $this->gelar_belakang ? trim($this->gelar_belakang) : '';

        if ($belakang && !str_ends_with(strtolower($nama), strtolower($belakang))) {
            return $depan . $nama . ', ' . $belakang;
        }

        return $depan . $nama;
    }
}
