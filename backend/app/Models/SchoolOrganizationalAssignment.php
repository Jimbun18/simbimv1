<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolOrganizationalAssignment extends Model
{
    use HasFactory;

    protected $table = 'school_organizational_assignments';

    protected $fillable = [
        'posisi_kode',
        'posisi_nama',
        'posisi_kategori',
        'parent_kode',
        'user_id',
        'teacher_id',
        'staff_id',
        'academic_year_id',
        'no_sk',
        'keterangan',
        'menu_permissions',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'menu_permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Resolve nama personel yang ditugaskan (guru, staf, atau user akun)
     */
    public function getPersonelNameAttribute(): ?string
    {
        if ($this->teacher) {
            return $this->teacher->nama_lengkap;
        }
        if ($this->staff) {
            return $this->staff->nama_lengkap;
        }
        if ($this->user) {
            return $this->user->nama;
        }
        return null;
    }

    /**
     * Resolve foto personel yang ditugaskan
     */
    public function getPersonelFotoAttribute(): ?string
    {
        if ($this->teacher && $this->teacher->foto_path) {
            return $this->teacher->foto_path;
        }
        if ($this->staff && $this->staff->foto_path) {
            return $this->staff->foto_path;
        }
        if ($this->user && $this->user->foto_path) {
            return $this->user->foto_path;
        }
        return null;
    }
}
