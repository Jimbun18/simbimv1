<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'gedung_lantai',
        'kapasitas',
        'kondisi',
        'asset_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'room_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'room_id');
    }
}
