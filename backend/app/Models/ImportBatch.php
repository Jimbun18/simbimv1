<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'jenis_data',
        'nama_file',
        'total',
        'berhasil',
        'gagal',
        'status',
        'column_mapping',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'berhasil' => 'integer',
            'gagal' => 'integer',
            'column_mapping' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class, 'batch_id');
    }
}
