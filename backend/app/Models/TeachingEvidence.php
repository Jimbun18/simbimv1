<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingEvidence extends Model
{
    use HasFactory;

    protected $table = 'teaching_evidence';

    protected $fillable = [
        'schedule_id',
        'teacher_id',
        'tanggal',
        'photo_path',
        'materi_ajar',
        'catatan',
        'lat',
        'lng',
        'captured_at',
        'created_by',
    ];

    protected $appends = [
        'foto_url',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'lat' => 'float',
            'lng' => 'float',
            'captured_at' => 'datetime',
        ];
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }
        return str_starts_with($this->photo_path, 'http')
            ? $this->photo_path
            : asset('storage/' . $this->photo_path);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
