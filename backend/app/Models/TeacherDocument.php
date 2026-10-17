<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'jenis',
        'nama_dokumen',
        'file_path',
        'tgl_terbit',
        'tgl_kedaluwarsa',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_terbit' => 'date',
            'tgl_kedaluwarsa' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
