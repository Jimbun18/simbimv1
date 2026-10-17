<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nama',
        'jenis',
        'jam_mulai',
        'jam_selesai',
        'urutan',
        'hari',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
