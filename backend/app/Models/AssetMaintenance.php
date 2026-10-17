<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    use HasFactory;

    protected $table = 'asset_maintenance';

    protected $fillable = [
        'asset_id',
        'tanggal',
        'deskripsi',
        'biaya',
        'pelaksana',
        'kondisi_sesudah',
        'bukti_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'biaya' => 'float',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
