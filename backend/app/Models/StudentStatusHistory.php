<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'student_status_history';

    protected $fillable = [
        'student_id',
        'jenis',
        'tanggal',
        'sekolah_asal_tujuan',
        'alasan',
        'no_surat',
        'dokumen_path',
        'dicatat_oleh',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
