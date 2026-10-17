<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Semester extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'semester',
        'tgl_mulai',
        'tgl_selesai',
        'status',
        'tutup_at',
        'tutup_by',
        'reopen_at',
        'reopen_until',
        'reopen_approved_by',
        'reopen_reason',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'semester_label',
        'nama_lengkap',
        'is_locked',
        'is_reopened',
    ];

    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
            'tutup_at' => 'datetime',
            'reopen_at' => 'datetime',
            'reopen_until' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'semester_id');
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'semester_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class, 'semester_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutup_by');
    }

    public function reopenApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopen_approved_by');
    }

    public function getSemesterLabelAttribute(): string
    {
        return strtolower($this->semester) === 'genap' ? 'Genap' : 'Gasal';
    }

    public function getNamaLengkapAttribute(): string
    {
        $yearName = $this->academicYear?->nama ?? '';
        return trim("TA {$yearName} - Semester {$this->semester_label}");
    }

    public function getIsLockedAttribute(): bool
    {
        if ($this->status !== 'terkunci') {
            return false;
        }

        // Jika ada masa reopen yang masih berlaku, maka status tidak terkunci
        if ($this->reopen_until && now()->lte($this->reopen_until)) {
            return false;
        }

        return true;
    }

    public function getIsReopenedAttribute(): bool
    {
        return $this->status === 'terkunci'
            && $this->reopen_until !== null
            && now()->lte($this->reopen_until);
    }

    /**
     * Mengambil semester yang sedang berstatus 'aktif' (maksimal 1)
     */
    public static function getActive(): ?self
    {
        return static::with('academicYear')
            ->where('status', 'aktif')
            ->whereHas('academicYear', function ($q) {
                $q->where('status', 'aktif');
            })
            ->first();
    }

    /**
     * Mencari semester berdasarkan tanggal tertentu
     */
    public static function findByDate(string|\DateTimeInterface $date): ?self
    {
        $d = is_string($date) ? substr($date, 0, 10) : $date->format('Y-m-d');
        return static::where('tgl_mulai', '<=', $d)
            ->where('tgl_selesai', '>=', $d)
            ->first();
    }
}
