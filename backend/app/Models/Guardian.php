<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'hubungan',
        'nama_lengkap',
        'nik',
        'tempat_lahir',
        'tgl_lahir',
        'status_hidup',
        'agama',
        'pendidikan_terakhir',
        'pekerjaan',
        'penghasilan_bulanan',
        'no_hp',
        'no_whatsapp',
        'email',
        'alamat_jalan',
        'rt',
        'rw',
        'desa_kelurahan',
        'kecamatan',
        'kab_kota',
        'provinsi',
        'kode_pos',
        'foto_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian')
            ->withPivot(['hubungan', 'is_kontak_utama', 'boleh_lihat_keuangan', 'boleh_jemput'])
            ->withTimestamps();
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class, 'agama');
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class, 'pendidikan_terakhir');
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class, 'pekerjaan');
    }

    public function incomeRange(): BelongsTo
    {
        return $this->belongsTo(IncomeRange::class, 'penghasilan_bulanan');
    }
}
