<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'teacher_id',
        'tanggal',
        'waktu',
        'tempat',
        'situasi_sebelum',
        'perilaku_diamati',
        'respons_lingkungan',
        'rekomendasi_bk',
        'rekomendasi_bk_by',
        'rekomendasi_bk_at',
        'saran_untuk_guru',
        'catatan_wali_kelas',
        'status_pemantauan',
        'is_escalated_to_bk',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'rekomendasi_bk_at' => 'datetime',
        'is_escalated_to_bk' => 'boolean',
    ];

    protected $appends = ['class_name'];

    public function getClassNameAttribute(): ?string
    {
        return $this->schoolClass?->nama ?? $this->student?->schoolClass?->nama ?? null;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function bkTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'rekomendasi_bk_by');
    }
}

