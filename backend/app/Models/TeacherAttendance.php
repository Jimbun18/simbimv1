<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    use HasFactory;

    protected $table = 'teacher_attendance';

    protected $fillable = [
        'teacher_id',
        'staff_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'lat',
        'lng',
        'lat_keluar',
        'lng_keluar',
        'jarak_masuk_m',
        'jarak_keluar_m',
        'foto_masuk',
        'foto_pulang',
        'is_terlambat',
        'menit_keterlambatan',
        'surat_izin_path',
        'status_persetujuan',
        'disetujui_oleh',
        'alasan_koreksi',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'lat' => 'float',
            'lng' => 'float',
            'lat_keluar' => 'float',
            'lng_keluar' => 'float',
            'is_terlambat' => 'boolean',
            'jarak_masuk_m' => 'integer',
            'jarak_keluar_m' => 'integer',
            'menit_keterlambatan' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
