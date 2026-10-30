<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nama',
        'username',
        'email',
        'no_hp',
        'password',
        'role_id',
        'status',
        'must_change_password',
        'login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'foto_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if (!$this->role) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($this->role->nama, $roles, true);
        }

        return $this->role->nama === $roles;
    }

    public function isHeadmaster(): bool
    {
        return $this->hasRole(Role::KEPALA_SEKOLAH);
    }

    public function isStaffAdmin(): bool
    {
        return $this->hasRole(Role::STAF_ADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole([Role::KEPALA_SEKOLAH, Role::STAF_ADMIN]);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(Role::GURU);
    }

    public function isGuruBk(): bool
    {
        if (!$this->isTeacher()) {
            return false;
        }
        $teacher = $this->teacher;
        if (!$teacher) {
            return false;
        }
        $jabatan = strtolower($teacher->jabatan ?? '');
        $tugas = strtolower($teacher->tugas_tambahan ?? '');

        return str_contains($jabatan, 'bk') ||
               str_contains($jabatan, 'bimbingan') ||
               str_contains($tugas, 'bk') ||
               str_contains($tugas, 'bimbingan');
    }

    public function isGuardian(): bool
    {
        return $this->hasRole(Role::WALI);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
