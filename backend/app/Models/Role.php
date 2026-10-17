<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const KEPALA_SEKOLAH = 'kepala_sekolah';
    public const STAF_ADMIN = 'staf_admin';
    public const GURU = 'guru';
    public const WALI = 'wali';

    protected $fillable = [
        'nama',
        'guard_name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
