<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'nomor_baris',
        'kolom',
        'pesan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'nomor_baris' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }
}
