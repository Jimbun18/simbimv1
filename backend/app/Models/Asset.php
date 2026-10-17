<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'lokasi',
        'room_id',
        'tahun_perolehan',
        'nilai',
        'kondisi',
        'jumlah',
        'satuan',
        'foto_path',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'nilai' => 'float',
            'jumlah' => 'integer',
            'tahun_perolehan' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(AssetLoan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
