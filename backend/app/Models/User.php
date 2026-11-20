<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
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

    public function organizationalAssignments()
    {
        return $this->hasMany(SchoolOrganizationalAssignment::class);
    }

    public function getActiveStructuralPositions(): array
    {
        $positions = [];

        try {
            $teacherId = $this->teacher?->id;
            $staffId = $this->staff?->id;

            if (class_exists(SchoolOrganizationalAssignment::class) && Schema::hasTable('school_organizational_assignments')) {
                $assignments = SchoolOrganizationalAssignment::where('is_active', true)
                    ->where(function ($query) use ($teacherId, $staffId) {
                        $query->where('user_id', $this->id);
                        if ($teacherId) {
                            $query->orWhere('teacher_id', $teacherId);
                        }
                        if ($staffId) {
                            $query->orWhere('staff_id', $staffId);
                        }
                    })
                    ->pluck('posisi_kode')
                    ->toArray();

                $positions = array_merge($positions, $assignments);
            }
        } catch (\Throwable $e) {
            // Fallback aman jika tabel belum dibuat di environment database
        }

        if ($this->teacher) {
            $jabatan = strtolower($this->teacher->jabatan ?? '');
            $tugas = strtolower($this->teacher->tugas_tambahan ?? '');
            $combined = $jabatan . ' ' . $tugas;

            if (str_contains($combined, 'bendahara') || str_contains($combined, 'keuangan')) {
                $positions[] = 'bendahara';
            }
            if (str_contains($combined, 'bk') || str_contains($combined, 'bimbingan')) {
                $positions[] = 'bk';
            }
            if (str_contains($combined, 'kurikulum')) {
                $positions[] = 'waka_kurikulum';
            }
            if (str_contains($combined, 'kesiswaan')) {
                $positions[] = 'waka_kesiswaan';
            }
        }

        if ($this->staff) {
            $jabatanStaff = strtolower($this->staff->jabatan ?? '');
            if (str_contains($jabatanStaff, 'bendahara') || str_contains($jabatanStaff, 'keuangan')) {
                $positions[] = 'bendahara';
            }
            if (str_contains($jabatanStaff, 'tu') || str_contains($jabatanStaff, 'tata usaha')) {
                $positions[] = 'kepala_tu';
            }
        }

        return array_values(array_unique($positions));
    }

    public function hasStructuralPosition(string $posisiKode): bool
    {
        try {
            return in_array($posisiKode, $this->getActiveStructuralPositions(), true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isBendahara(): bool
    {
        return $this->hasStructuralPosition('bendahara');
    }

    public function isWakaKurikulum(): bool
    {
        return $this->hasStructuralPosition('waka_kurikulum');
    }

    public function isWakaKesiswaan(): bool
    {
        return $this->hasStructuralPosition('waka_kesiswaan');
    }

    public function isKepalaTu(): bool
    {
        return $this->hasStructuralPosition('kepala_tu');
    }

    public function isBinaKesiswaan(): bool
    {
        return $this->hasStructuralPosition('bina_kesiswaan');
    }

    public function isBinaPotensi(): bool
    {
        return $this->hasStructuralPosition('bina_potensi');
    }

    public function isBinaPrestasi(): bool
    {
        return $this->hasStructuralPosition('bina_prestasi');
    }

    public function canAccessFinance(): bool
    {
        try {
            return $this->isAdmin() || $this->isBendahara();
        } catch (\Throwable $e) {
            return $this->isAdmin();
        }
    }

    public function canAccessKurikulum(): bool
    {
        try {
            return $this->isAdmin() || $this->isWakaKurikulum();
        } catch (\Throwable $e) {
            return $this->isAdmin();
        }
    }

    public function canAccessKesiswaan(): bool
    {
        try {
            return $this->isAdmin() || $this->isWakaKesiswaan() || $this->isBinaKesiswaan();
        } catch (\Throwable $e) {
            return $this->isAdmin();
        }
    }

    public function canAccessTu(): bool
    {
        try {
            return $this->isAdmin() || $this->isKepalaTu();
        } catch (\Throwable $e) {
            return $this->isAdmin();
        }
    }

    public function isGuruBk(): bool
    {
        try {
            if ($this->hasStructuralPosition('bk')) {
                return true;
            }
        } catch (\Throwable $e) {
            // fallback
        }

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
