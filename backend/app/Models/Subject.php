<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kode',
        'nama',
        'is_tematik',
        'tema_nomor',
        'muatan_pelajaran',
        'deskripsi',
        'kelompok',
        'tingkat_berlaku',
        'jam_per_minggu',
        'kkm',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'is_tematik' => 'boolean',
            'tema_nomor' => 'integer',
            'muatan_pelajaran' => 'array',
            'jam_per_minggu' => 'integer',
            'kkm' => 'integer',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->is_tematik && !empty($this->muatan_pelajaran)) {
            $muatan = is_array($this->muatan_pelajaran) ? implode(', ', $this->muatan_pelajaran) : $this->muatan_pelajaran;
            return "{$this->nama} ({$muatan})";
        }
        return (string) ($this->nama ?? '');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'subject_id');
    }
}
