<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama',
        'tgl_mulai',
        'tgl_selesai',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['is_aktif'];

    public function getIsAktifAttribute(): bool
    {
        return $this->status === 'aktif';
    }

    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
        ];
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class, 'academic_year_id');
    }

    public function activeSemester(): HasOne
    {
        return $this->hasOne(Semester::class, 'academic_year_id')->where('status', 'aktif');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'academic_year_id');
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'academic_year_id');
    }

    public static function getActive(): ?self
    {
        return static::where('status', 'aktif')->first();
    }
}
