<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BkCounselingCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'wali_kelas_id',
        'guru_bk_id',
        'tanggal_masuk',
        'sumber_awal',
        'fokus_pemantauan',
        'status_saat_ini',
        'review_terakhir',
        'review_berikutnya',
        'catatan_umum',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'review_terakhir' => 'date',
        'review_berikutnya' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'wali_kelas_id');
    }

    public function bkTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'guru_bk_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BkCaseLog::class, 'bk_counseling_case_id')->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
    }
}

