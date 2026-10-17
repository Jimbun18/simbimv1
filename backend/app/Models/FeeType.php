<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fee_types';

    protected $fillable = [
        'kode',
        'nama',
        'nominal',
        'periode',
        'is_aktif',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'float',
            'is_aktif' => 'boolean',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
