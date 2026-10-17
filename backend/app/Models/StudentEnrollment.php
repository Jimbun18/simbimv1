<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'tgl_masuk_kelas',
        'tgl_keluar_kelas',
        'status_kelas',
        'nomor_absen',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_masuk_kelas' => 'date',
            'tgl_keluar_kelas' => 'date',
            'nomor_absen' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
