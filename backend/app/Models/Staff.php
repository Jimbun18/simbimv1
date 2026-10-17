<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tgl_lahir',
        'agama',
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
        'jabatan',
        'status_kepegawaian',
        'tgl_mulai_tugas',
        'status',
        'pendidikan_terakhir',
        'foto_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date:Y-m-d',
            'tgl_mulai_tugas' => 'date:Y-m-d',
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
}
