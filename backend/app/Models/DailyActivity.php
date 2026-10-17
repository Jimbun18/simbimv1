<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'daily_activities';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'tanggal',
        'judul',
        'deskripsi',
        'kategori',
        'photo_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
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
