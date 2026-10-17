<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = 'student_attendance';

    protected $fillable = [
        'student_id',
        'schedule_id',
        'class_id',
        'tanggal',
        'status',
        'keterangan',
        'dicatat_oleh',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
