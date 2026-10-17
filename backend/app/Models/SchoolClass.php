<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'kode',
        'nama',
        'tingkat',
        'paralel',
        'wali_kelas_id',
        'room_id',
        'kapasitas',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'kapasitas' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'wali_kelas_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }

    public function activeStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class, 'class_id');
    }
}
